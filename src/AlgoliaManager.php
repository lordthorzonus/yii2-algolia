<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Client;
use AlgoliaSearch\Index;
use leinonen\Yii2Algolia\ActiveRecord\ActiveQueryChunker;
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
     * @var Client
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
     * @var ActiveQueryChunker
     */
    private $activeQueryChunker;

    /**
     * Initiates a new AlgoliaManager.
     *
     * @param Client $client
     * @param ActiveRecordFactory $activeRecordFactory
     * @param ActiveQueryChunker $activeQueryChunker
     */
    public function __construct(
        Client $client,
        ActiveRecordFactory $activeRecordFactory,
        ActiveQueryChunker $activeQueryChunker
    ) {
        $this->client = $client;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->activeQueryChunker = $activeQueryChunker;
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
        $algoliaRecords = $this->getAlgoliaRecordsFromSearchableModelArray($searchableModels);
        $indices = $this->initIndices($searchableModels[0]);

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
     * Updates multiple models data in all indices. The given searchable models must be of the same class.
     *
     * @param SearchableInterface[] $searchableModels
     *
     * @return array
     */
    public function updateMultipleInIndices(array $searchableModels)
    {
        $algoliaRecords = $this->getAlgoliaRecordsFromSearchableModelArray($searchableModels);
        $indices = $this->initIndices($searchableModels[0]);

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
     * Removes multiple models from all indices. The given searchable models must be of the same class.
     *
     * @param array $searchableModels
     *
     * @return array
     * @throws \Exception
     */
    public function removeMultipleFromIndices(array $searchableModels)
    {
        $algoliaRecords = $this->getAlgoliaRecordsFromSearchableModelArray($searchableModels);
        $indices = $this->initIndices($searchableModels[0]);
        $objectIds = array_map(function ($algoliaRecord) {
            return $algoliaRecord['objectID'];
        }, $algoliaRecords);

        $response = [];

        foreach ($indices as $index) {
            /* @var Index $index  */
            $response[$index->indexName] = $index->deleteObjects($objectIds);
        }

        return $response;
    }

    /**
     * Re-indexes the indices safely for the given ActiveRecord Class.
     *
     * @param string $className The name of the ActiveRecord to be indexed
     *
     * @return array
     */
    public function reindex($className)
    {
        $this->checkImplementsSearchableInterface($className);
        $activeRecord = $this->activeRecordFactory->make($className);
        $indices = $this->initIndices($activeRecord);

        $records = $this->activeQueryChunker->chunk(
            $activeRecord->find(),
            500,
            function ($activeRecordEntities) {
                return $this->getAlgoliaRecordsFromSearchableModelArray($activeRecordEntities);
            }
        );

        $response = [];

        foreach ($indices as $index) {
            $response[$index->indexName] = $this->reindexAtomically($index, $records);
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
     * Maps an array of searchable models into an Algolia friendly array.
     *
     * @param SearchableInterface[] $searchableModels
     *
     * @return array
     */
    private function getAlgoliaRecordsFromSearchableModelArray(array $searchableModels)
    {
        // Use the first element of the array to define what kind of models we are indexing.
        $arrayType = get_class($searchableModels[0]);

        $algoliaRecords = array_map(function (SearchableInterface $searchableModel) use ($arrayType) {
            if (! $searchableModel instanceof $arrayType) {
                throw new \InvalidArgumentException('The given array should not contain multiple different classes');
            }

            $algoliaRecord = $searchableModel->getAlgoliaRecord();
            $algoliaRecord['objectID'] = $searchableModel->getObjectID();

            return $algoliaRecord;
        }, $searchableModels);

        return $algoliaRecords;
    }

    /**
     * Reindex atomically the given index with the given records.
     *
     * @param Index $index
     * @param array $algoliaRecords
     *
     * @return mixed
     */
    private function reindexAtomically(Index $index, array $algoliaRecords)
    {
        $temporaryIndexName = 'tmp_' . $index->indexName;

        $temporaryIndex = $this->initIndex($temporaryIndexName);
        $temporaryIndex->addObjects($algoliaRecords);

        $settings = $index->getSettings();

        // Temporary index overrides all the settings on the main one.
        // So we need to set the original settings on the temporary one before atomically moving the index.
        $temporaryIndex->setSettings($settings);

        return $this->moveIndex($temporaryIndexName, $index->indexName);
    }
}
