<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Client;
use AlgoliaSearch\Index;
use leinonen\Yii2Algolia\ActiveRecord\ActiveRecordFactory;

/**
 * @method setConnectTimeout($connectTimeout, $timeout = 30, $searchTimeout = 5)
 * @method enableRateLimitForward($adminAPIKey, $endUserIP, $rateLimitAPIKey)
 * @method disableRateLimitForward()
 * @method isAlive()
 * @method setExtraHeader($key, $value)
 * @method multipleQueries($queries, $indexNameKey = "indexName", $strategy = "none")
 * @method listIndexes()
 * @method deleteIndex($indexName)
 * @method moveIndex($srcIndexName, $dstIndexName)
 * @method copyIndex($srcIndexName, $dstIndexName)
 * @method getLogs($offset = 0, $length = 10, $type = "all")
 * @method Index initIndex($indexName)
 * @method listUserKeys()
 * @method getUserKeyACL($key)
 * @method deleteUserKey($key)
 * @method addUserKey($obj, $validity = 0, $maxQueriesPerIPPerHour = 0, $maxHitsPerQuery = 0, $indexes = null)
 * @method batch($requests)
 * @method generateSecuredApiKey($privateApiKey, $query, $userToken = null)
 * @method request($context, $method, $path, $params = array(), $data = array(), $hostsArray, $connectTimeout, $readTimeout)
 * @method doRequest($context, $method, $host, $path, $params, $data, $connectTimeout, $readTimeout)
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
     */
    public function pushToIndices(SearchableInterface $searchableModel)
    {
        $indices = $searchableModel->getIndices();

        foreach ($indices as $index) {
            $record = $searchableModel->getAlgoliaRecord();
            $this->initIndex($index)->addObject($record, $searchableModel->getObjectID());
        }
    }

    /**
     * Updates the models data in all indices.
     *
     * @param SearchableInterface $searchableModel
     */
    public function updateInIndices(SearchableInterface $searchableModel)
    {
        $indices = $searchableModel->getIndices();

        foreach ($indices as $index) {
            $record = $searchableModel->getAlgoliaRecord();
            $record['objectID'] = $searchableModel->getObjectID();
            $this->initIndex($index)->saveObject($record);
        }
    }

    /**
     * Removes a searchable model from indices.
     *
     * @param SearchableInterface $searchableModel
     *
     * @throws \Exception
     */
    public function removeFromIndices(SearchableInterface $searchableModel)
    {
        $indices = $searchableModel->getIndices();

        foreach ($indices as $index) {
            $objectID = $searchableModel->getObjectID();
            $this->initIndex($index)->deleteObject($objectID);
        }
    }

    /**
     * Re-indexes the indices safely for the given ActiveRecord Class.
     *
     * @param string $className The name of the ActiveRecord to be indexed.
     */
    public function reindex($className)
    {
        $this->checkImplementsSearchableInterface($className);
        $activeRecord = $this->activeRecordFactory->make($className);

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

        foreach ($indices as $index) {
            $temporaryIndexName = 'tmp_' . $index;

            /** @var Index $temporaryIndex */
            $temporaryIndex = $this->initIndex($temporaryIndexName);
            $temporaryIndex->addObjects($records);

            $this->moveIndex($temporaryIndexName, $index);
        }
    }

    /**
     * Clears the indices for the given Class that implements SearchableInterface.
     *
     * @param string $className The name of the Class which indices are to be cleared.
     */
    public function clearIndices($className)
    {
        $this->checkImplementsSearchableInterface($className);
        $activeRecord = $this->activeRecordFactory->make($className);

        /* @var SearchableInterface $activeRecord */
        $indices = $activeRecord->getIndices();

        foreach ($indices as $index) {
            $this->initIndex($index)->clearIndex();
        }
    }

    /**
     * Dynamically pass methods to the Algolia Client.

     * @param string $method
     * @param array  $parameters
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
     * @param mixed $class Either name or instance of the class to be checked.
     */
    private function checkImplementsSearchableInterface($class)
    {
        $reflectionClass = new \ReflectionClass($class);

        if (! $reflectionClass->implementsInterface(SearchableInterface::class)) {
            throw new \InvalidArgumentException("The class: {$reflectionClass->getName()} doesn't implement leinonen\\Yii2Algolia\\SearchableInterface");
        }
    }
}
