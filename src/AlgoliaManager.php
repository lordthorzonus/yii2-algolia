<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Client;
use AlgoliaSearch\Index;
use leinonen\Yii2Algolia\ActiveRecord\ActiveRecordFactory;

/**
 * @method setConnectTimeout(int $connectTimeout, int $timeout = 30, int $searchTimeout = 5)
 * @method enableRateLimitForward(string $adminAPIKey, string $endUserIP, string $rateLimitAPIKey)
 * @method setForwarderFor(string $ip)
 * @method setAlgoliaUserToken(string $token)
 * @method disableRateLimitForward()
 * @method isAlive()
 * @method setExtraHeader(string $key, string $value)
 * @method mixed multipleQueries(array $queries, string $indexNameKey = "indexName", string $strategy = "none")
 * @method mixed listIndexes()
 * @method deleteIndex(string $indexName)
 * @method mixed moveIndex(string $srcIndexName, string $dstIndexName)
 * @method mixed copyIndex(string $srcIndexName, string $dstIndexName)
 * @method mixed getLogs(int $offset = 0, int $length = 10, string $type = "all")
 * @method Index initIndex(string $indexName)
 * @method mixed listUserKeys()
 * @method mixed getUserKeyACL(string $key)
 * @method mixed deleteUserKey(string $key)
 * @method mixed addUserKey(array $obj, int $validity = 0, int $maxQueriesPerIPPerHour = 0, int $maxHitsPerQuery = 0, array $indexes = null)
 * @method mixed updateUserKey(string $key, array $obj, int $validity = 0, int $maxQueriesPerIPPerHour = 0, int $maxHitsPerQuery = 0, array $indexes = null)
 * @method mixed batch(array $requests)
 * @method string generateSecuredApiKey(string $privateApiKey, mixed $query, string $userToken = null)
 * @method string buildQuery(array $args)
 * @method mixed request(\AlgoliaSearch\ClientContext $context, string $method, string $path, array $params, array $data, array $hostsArray, int $connectTimeout, int $readTimeout)
 * @method mixed doRequest(\AlgoliaSearch\ClientContext $context, string $method, string $path, array $params, array $data, array $hostsArray, int $connectTimeout, int $readTimeout)
 * @method \AlgoliaSearch\PlacesIndex initPlaces(string $appId, string $appKey, array $hostsArray = null, array $options = [])
 * @see Client
 */
class AlgoliaManager
{
    /**
     * @var AlgoliaFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var null|Client
     */
    protected $client;

    /**
     * @var ActiveRecordFactory
     */
    protected $activeRecordFactory;

    /**
     * Initiates a new AlgoliaManager.
     *
     * @param AlgoliaFactory $algoliaFactory
     * @param ActiveRecordFactory $activeRecordFactory
     * @param array $config Configurations for the Algolia Client.
     */
    public function __construct(AlgoliaFactory $algoliaFactory, ActiveRecordFactory $activeRecordFactory, array $config = [])
    {
        $this->factory = $algoliaFactory;
        $this->config = $config;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    /**
     * Returns the Algolia Client.
     *
     * @return Client
     */
    public function getClient()
    {
        if (is_null($this->client)) {
            $this->client = $this->factory->make($this->config);
        }

        return $this->client;
    }

    /**
     * Returns the config array.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Indexes a searchable model to all indices.
     *
     * @param SearchableInterface $searchableModel
     *
     * @return array
     */
    public function pushToIndices(SearchableInterface $searchableModel)
    {
        $indices = $searchableModel->getIndices();
        $response = [];

        foreach ($indices as $index) {
            $record = $searchableModel->getAlgoliaRecord();
            $response[$index] = $this->initIndex($index)->addObject($record, $searchableModel->getObjectID());
        }

        return $response;
    }

    /**
     * Updates the models data in all indices.
     *
     * @param SearchableInterface $searchableModel
     *
     * @return array
     */
    public function updateInIndices(SearchableInterface $searchableModel)
    {
        $indices = $searchableModel->getIndices();
        $response = [];

        foreach ($indices as $index) {
            $record = $searchableModel->getAlgoliaRecord();
            $record['objectID'] = $searchableModel->getObjectID();
            $response[$index] = $this->initIndex($index)->saveObject($record);
        }

        return $response;
    }

    /**
     * Removes a searchable model from indices.
     *
     * @param SearchableInterface $searchableModel
     *
     * @return array
     * @throws \Exception
     */
    public function removeFromIndices(SearchableInterface $searchableModel)
    {
        $indices = $searchableModel->getIndices();
        $response = [];

        foreach ($indices as $index) {
            $objectID = $searchableModel->getObjectID();
            $response[$index] = $this->initIndex($index)->deleteObject($objectID);
        }

        return $response;
    }

    /**
     * Re-indexes the indices safely for the given ActiveRecord Class.
     *
     * @param string $className The name of the ActiveRecord to be indexed.
     *
     * @return array
     */
    public function reindex($className)
    {
        $this->checkImplementsSearchableInterface($className);
        $activeRecord = $this->activeRecordFactory->make($className);
        $response = [];

        /** @var SearchableInterface[] $activeRecordEntities */
        $activeRecordEntities = $activeRecord->find()->all();

        /* @var SearchableInterface $activeRecord */
        $indices = $activeRecord->getIndices();
        $records = [];

        foreach ($activeRecordEntities as $activeRecordEntity) {
            $record = $activeRecordEntity->getAlgoliaRecord();
            $record['objectID'] = $activeRecordEntity->getObjectID();
            $records[] = $record;
        }

        foreach ($indices as $indexName) {
            $temporaryIndexName = 'tmp_' . $indexName;

            /** @var Index $temporaryIndex */
            $temporaryIndex = $this->initIndex($temporaryIndexName);
            $temporaryIndex->addObjects($records);

            $originalIndex = $this->initIndex($indexName);
            $settings = $originalIndex->getSettings();

            // Temporary index overrides all the settings on the main one.
            // So let's set the original settings on the temporary one before atomically moving the index.
            $temporaryIndex->setSettings($settings);

            $response[$indexName] = $this->moveIndex($temporaryIndexName, $indexName);
        }

        return $response;
    }

    /**
     * Clears the indices for the given Class that implements SearchableInterface.
     *
     * @param string $className The name of the Class which indices are to be cleared.
     *
     * @return array
     */
    public function clearIndices($className)
    {
        $this->checkImplementsSearchableInterface($className);
        $activeRecord = $this->activeRecordFactory->make($className);
        $response = [];

        /* @var SearchableInterface $activeRecord */
        $indices = $activeRecord->getIndices();

        foreach ($indices as $index) {
            $response[$index] = $this->initIndex($index)->clearIndex();
        }

        return $response;
    }

    /**
     * Dynamically pass methods to the Algolia Client.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->getClient(), $method], $parameters);
    }

    /**
     * Checks if the given class implements SearchableInterface.
     *
     * @param string $class Either name or instance of the class to be checked.
     */
    private function checkImplementsSearchableInterface($class)
    {
        $reflectionClass = new \ReflectionClass($class);

        if (! $reflectionClass->implementsInterface(SearchableInterface::class)) {
            throw new \InvalidArgumentException("The class: {$reflectionClass->name} doesn't implement leinonen\\Yii2Algolia\\SearchableInterface");
        }
    }
}
