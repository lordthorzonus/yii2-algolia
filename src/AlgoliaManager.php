<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Index;
use AlgoliaSearch\Client;
use leinonen\Yii2Algolia\ActiveRecord\Searchable;
use yii\db\ActiveQueryInterface;
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
 * @method mixed listApiKeys()
 * @method mixed getApiKey(string $key)
 * @method mixed deleteApiKey(string $key)
 * @method mixed addApiKey(array $obj, int $validity = 0, int $maxQueriesPerIPPerHour = 0, int $maxHitsPerQuery = 0, array $indexes = null)
 * @method mixed updateApiKey(string $key, array $obj, int $validity = 0, int $maxQueriesPerIPPerHour = 0, int $maxHitsPerQuery = 0, array $indexes = null)
 * @method mixed batch(array $requests)
 * @method string generateSecuredApiKey(string $privateApiKey, mixed $query, string $userToken = null)
 * @method string buildQuery(array $args)
 * @method mixed request(\AlgoliaSearch\ClientContext $context, string $method, string $path, array $params, array $data, array $hostsArray, int $connectTimeout, int $readTimeout)
 * @method mixed doRequest(\AlgoliaSearch\ClientContext $context, string $method, string $path, array $params, array $data, array $hostsArray, int $connectTimeout, int $readTimeout)
 * @method \AlgoliaSearch\PlacesIndex initPlaces(string $appId, string $appKey, array $hostsArray = null, array $options = [])
 * @method getContext()
 * @see Client
 */
class AlgoliaManager
{
    /**
      * Size for the chunks used in reindexing methods.
      */
     const CHUNK_SIZE = 500;

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
        $record = $searchableModel->getAlgoliaRecord();

        return $this->processIndices($indices, function (Index $index) use ($record, $searchableModel) {
            return $index->addObject($record, $searchableModel->getObjectID());
        });
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

        return $this->processIndices($indices, function (Index $index) use ($algoliaRecords) {
            return $index->addObjects($algoliaRecords);
        });
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
        $record = $searchableModel->getAlgoliaRecord();
        $record['objectID'] = $searchableModel->getObjectID();

        return $this->processIndices($indices, function (Index $index) use ($record) {
            return $index->saveObject($record);
        });
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

        return $this->processIndices($indices, function (Index $index) use ($algoliaRecords) {
            return $index->saveObjects($algoliaRecords);
        });
    }

    /**
     * Removes a searchable model from indices.
     *
     * @param SearchableInterface $searchableModel
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function removeFromIndices(SearchableInterface $searchableModel)
    {
        $indices = $indices = $this->initIndices($searchableModel);
        $objectID = $searchableModel->getObjectID();

        return $this->processIndices($indices, function (Index $index) use ($objectID) {
            return $index->deleteObject($objectID);
        });
    }

    /**
     * Removes multiple models from all indices. The given searchable models must be of the same class.
     *
     * @param array $searchableModels
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function removeMultipleFromIndices(array $searchableModels)
    {
        $algoliaRecords = $this->getAlgoliaRecordsFromSearchableModelArray($searchableModels);
        $indices = $this->initIndices($searchableModels[0]);
        $objectIds = \array_map(function ($algoliaRecord) {
            return $algoliaRecord['objectID'];
        }, $algoliaRecords);

        return $this->processIndices($indices, function (Index $index) use ($objectIds) {
            return $index->deleteObjects($objectIds);
        });
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

        $records = $this->activeQueryChunker->chunk(
            $activeRecord->find(),
            self::CHUNK_SIZE,
            function ($activeRecordEntities) {
                return $this->getAlgoliaRecordsFromSearchableModelArray($activeRecordEntities);
            }
        );

        /* @var SearchableInterface $activeRecord */
        $indices = $this->initIndices($activeRecord);

        return $this->processIndices($indices, function (Index $index) use ($records) {
            return $this->reindexAtomically($index, $records);
        });
    }

    /**
     * Re-indexes the related indices for the given array only with the objects from the given array.
     * The given array must consist of Searchable objects of same class.
     *
     * @param SearchableInterface[] $searchableModels
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function reindexOnly(array $searchableModels)
    {
        $records = $this->getAlgoliaRecordsFromSearchableModelArray($searchableModels);
        $indices = $this->initIndices($searchableModels[0]);

        return $this->processIndices($indices, function (Index $index) use ($records) {
            return $this->reindexAtomically($index, $records);
        });
    }

    /**
     * Re-indexes the related indices for the given ActiveQueryInterface.
     * The result of the given ActiveQuery must consist from Searchable models of the same class.
     *
     * @param ActiveQueryInterface $activeQuery
     *
     * @return array
     */
    public function reindexByActiveQuery(ActiveQueryInterface $activeQuery)
    {
        $indices = null;
        $records = $this->activeQueryChunker->chunk(
            $activeQuery,
            self::CHUNK_SIZE,
            function ($activeRecordEntities) use (&$indices) {
                $records = $this->getAlgoliaRecordsFromSearchableModelArray($activeRecordEntities);

                // The converting ActiveRecords to Algolia ones already does the type checking
                // so it's safe to init indices here during the first chunk.
                if ($indices === null) {
                    $indices = $this->initIndices($activeRecordEntities[0]);
                }

                return $records;
            }
        );

        return $this->processIndices($indices, function (Index $index) use ($records) {
            return $this->reindexAtomically($index, $records);
        });
    }

    /**
     * Clears the indices for the given Class that implements SearchableInterface.
     *
     * @param string $className The name of the Class which indices are to be cleared.
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function clearIndices($className)
    {
        $this->checkImplementsSearchableInterface($className);
        /** @var SearchableInterface $activeRecord */
        $activeRecord = $this->activeRecordFactory->make($className);
        $indices = $indices = $this->initIndices($activeRecord);

        return $this->processIndices($indices, function (Index $index) {
            return $index->clearIndex();
        });
    }

    /**
     * @param string $className The name of the class which is to be searched.
     * @param string $query
     * @param null|array $searchParameters Optional search parameters given as an associative array.
     *
     * @link https://github.com/algolia/algoliasearch-client-php#search-parameters Allowed search parameters.
     *
     * @return array
     */
    public function search($className, $query, array $searchParameters = null)
    {
        $this->checkImplementsSearchableInterface($className);
        /* @var SearchableInterface $activeRecord */
        $activeRecord = $this->activeRecordFactory->make($className);
        $indices = $indices = $this->initIndices($activeRecord);

        return $this->processIndices($indices, function (Index $index) use ($query, $searchParameters) {
            return $index->search($query, $searchParameters);
        });
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
        return \call_user_func_array([$this->getClient(), $method], $parameters);
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

        return \array_map(function ($indexName) {
            if ($this->env !== null) {
                $indexName = $this->env . '_' . $indexName;
            }

            return $this->initIndex($indexName);
        }, $indexNames);
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
        if (empty($searchableModels)) {
            throw new \InvalidArgumentException('The given array should not be empty');
        }

        // Use the first element of the array to define what kind of models we are indexing.
        $arrayType = \get_class($searchableModels[0]);
        $this->checkImplementsSearchableInterface($arrayType);

        return \array_map(function (SearchableInterface $searchableModel) use ($arrayType) {
            if (! $searchableModel instanceof $arrayType) {
                throw new \InvalidArgumentException('The given array should not contain multiple different classes');
            }

            $algoliaRecord = $searchableModel->getAlgoliaRecord();
            $algoliaRecord['objectID'] = $searchableModel->getObjectID();

            return $algoliaRecord;
        }, $searchableModels);
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

    /**
     * Performs actions for given indices returning an array of responses from those actions.
     *
     * @param Index[] $indices
     * @param callable $callback
     *
     * @return array The response as an array in format of ['indexName' => $responseFromAlgoliaClient]
     */
    private function processIndices($indices, callable $callback)
    {
        $response = [];

        foreach ($indices as $index) {
            $response[$index->indexName] = \call_user_func($callback, $index);
        }

        return $response;
    }
}
