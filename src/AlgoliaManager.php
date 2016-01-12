<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Client;
use AlgoliaSearch\Index;
use leinonen\Yii2Algolia\ActiveRecord\SearchableInterface;

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
 * @method initIndex($indexName)
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
     * Initiates a new AlgoliaManager.
     *
     * @param AlgoliaFactory $factory
     * @param array $config Configurations for the Algolia Client.
     */
    public function __construct(AlgoliaFactory $factory, array $config = [])
    {
        $this->factory = $factory;
        $this->config = $config;
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
     * Re-indexes the indices safely for the given ActiveRecord Class.
     * @param SearchableInterface $activeRecord
     */
    public function reIndex(SearchableInterface $activeRecord)
    {
        /** @var SearchableInterface[] $models */
        $models = $activeRecord::find()->all();
        $indices = $activeRecord->getIndices();
        $records = [];

        foreach ($models as $model) {
            $records[] = $model->getAlgoliaRecord();
        }

        foreach ($indices as $index) {
            $temporaryIndexName = 'tmp_' . $index->indexName;

            /** @var Index $temporaryIndex */
            $temporaryIndex = $this->initIndex($temporaryIndexName);
            $temporaryIndex->addObjects($records);

            $this->moveIndex($temporaryIndexName, $index->indexName);
        }
    }

    /**
     * Clears the indices for the given ActiveRecord Class.
     * @param SearchableInterface $activeRecord
     */
    public function clearIndices(SearchableInterface $activeRecord)
    {
        $indices = $activeRecord->getIndices();

        foreach ($indices as $index) {
            $index->clearIndex();
        }
    }

    /**
     * Dynamically pass methods to the Algolia Client.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->getClient(), $method], $parameters);
    }
}
