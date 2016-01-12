<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Client;
use leinonen\Yii2Algolia\ActiveRecord\SearchableInterface;

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
        $models = SearchableInterface::find()->all();
        $indices = $activeRecord->getIndices();
        $records = [];

        foreach($models as $model) {
            $records[] = $model->getAlgoliaRecord();
        }

        foreach($indices as $index){
            $temporaryIndexName = 'tmp_' . $index->indexName;
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

        foreach($indices as $index){
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
