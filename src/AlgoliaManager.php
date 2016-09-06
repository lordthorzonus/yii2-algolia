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
     * @var AlgoliaConfig
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
     * @var null|string
     */
    protected $env;

    /**
     * Initiates a new AlgoliaManager.
     *
     * @param Client $client
     * @param ActiveRecordFactory $activeRecordFactory
     *
     */
    public function __construct(Client $client, ActiveRecordFactory $activeRecordFactory)
    {
        $this->client = $client;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    /**
     * Returns the Algolia Client.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the environment for the manager.
     *
     * @param string $env
     */
    public function setEnv($env)
    {
        $this->env = $env;
    }

    /**
     * Returns the environment for the manager.
     *
     * @return null|string
     */
    public function getEnv()
    {
        return $this->env;
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
        $indices = $this->initIndices($searchableModel);
        $response = [];

        foreach ($indices as $index) {
            $record = $searchableModel->getAlgoliaRecord();
            $response[$index->indexName] = $index->addObject($record, $searchableModel->getObjectID());
        }

        return $response;
    }

    /**
     * Indexes multiple searchable models in a batch. The given searchable models must be of the same class.
     *
     * @param SearchableInterface[] $searchableModels
     *
     * @return array
     */
    public function pushMultipleToIndices(array $searchableModels)
    {
        list($indices, $algoliaRecords) = $this->getIndicesAndAlgoliaRecordsFromSearchableModelArray($searchableModels);
        $response = [];

        foreach ($indices as $index) {
            /* @var Index $index  */
            $response[$index->indexName] = $index->addObjects($algoliaRecords);
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
        $indices = $this->initIndices($searchableModel);
        $response = [];

        foreach ($indices as $index) {
            $record = $searchableModel->getAlgoliaRecord();
            $record['objectID'] = $searchableModel->getObjectID();
            $response[$index->indexName] = $index->saveObject($record);
        }

        return $response;
    }

    /**
     * Updates multiple models data in all indices.  The given searchable models must be of the same class.
     *
     * @param SearchableInterface[] $searchableModels
     *
     * @return array
     */
    public function updateMultipleInIndices(array $searchableModels)
    {
        list($indices, $algoliaRecords) = $this->getIndicesAndAlgoliaRecordsFromSearchableModelArray($searchableModels);
        $response = [];

        foreach ($indices as $index) {
            /* @var Index $index  */
            $response[$index->indexName] = $index->saveObjects($algoliaRecords);
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
        $indices = $indices = $this->initIndices($searchableModel);
        $response = [];

        foreach ($indices as $index) {
            $objectID = $searchableModel->getObjectID();
            $response[$index->indexName] = $index->deleteObject($objectID);
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
        $indices = $indices = $this->initIndices($activeRecord);
        $records = [];

        foreach ($activeRecordEntities as $activeRecordEntity) {
            $record = $activeRecordEntity->getAlgoliaRecord();
            $record['objectID'] = $activeRecordEntity->getObjectID();
            $records[] = $record;
        }

        foreach ($indices as $index) {
            $temporaryIndexName = 'tmp_' . $index->indexName;

            /** @var Index $temporaryIndex */
            $temporaryIndex = $this->initIndex($temporaryIndexName);
            $temporaryIndex->addObjects($records);

            $settings = $index->getSettings();

            // Temporary index overrides all the settings on the main one.
            // So let's set the original settings on the temporary one before atomically moving the index.
            $temporaryIndex->setSettings($settings);

            $response[$index->indexName] = $this->moveIndex($temporaryIndexName, $index->indexName);
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
        $indices = $indices = $this->initIndices($activeRecord);

        foreach ($indices as $index) {
            $response[$index->indexName] = $index->clearIndex();
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

    /**
     * Returns the name of the class for given object.
     *
     * @param $class
     *
     * @return string
     */
    private function getClassName($class)
    {
        $reflectionClass = new \ReflectionClass($class);

        return $reflectionClass->name;
    }

    /**
     * Initializes indices for the given SearchableModel.
     *
     * @param SearchableInterface $searchableModel
     *
     * @return Index[]
     */
    private function initIndices(SearchableInterface $searchableModel)
    {
        $indexNames = $searchableModel->getIndices();

        $indices = array_map(function ($indexName) {
            if ($this->env !== null) {
                $indexName = $this->env . '_' . $indexName;
            }

            return $this->initIndex($indexName);
        }, $indexNames);

        return $indices;
    }

    /**
     * Maps an array of searchable models into an Algolia friendly array. Returns also indices for the searchable model
     * which the array consists of.
     *
     * @param SearchableInterface[] $searchableModels
     *
     * @return array
     */
    private function getIndicesAndAlgoliaRecordsFromSearchableModelArray(array $searchableModels)
    {
        // Use the first element of the array to define what kind of models we are indexing.
        $arrayType = $this->getClassName($searchableModels[0]);
        $indices = $this->initIndices($searchableModels[0]);

        $algoliaRecords = array_map(function (SearchableInterface $searchableModel) use ($arrayType) {
            if (! $searchableModel instanceof $arrayType) {
                throw new \InvalidArgumentException('The given array should not contain multiple different classes');
            }

            $algoliaRecord = $searchableModel->getAlgoliaRecord();
            $algoliaRecord['objectID'] = $searchableModel->getObjectID();

            return $algoliaRecord;
        }, $searchableModels);

        return [$indices, $algoliaRecords];
    }
}
