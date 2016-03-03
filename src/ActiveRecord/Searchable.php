<?php

namespace leinonen\Yii2Algolia\ActiveRecord;

use leinonen\Yii2Algolia\AlgoliaManager;
use Yii;

/**
 * Simple trait that implements methods needed for the SearchableInterface + couple helpers for dealing with ActiveRecords.
 */
trait Searchable
{
    /**
     * An array of indices that this model uses. If none specified
     * The name of the model will be used as the index.
     *
     * @return array
     */
    public function indices()
    {
        return [];
    }

    /**
     * Returns an array of indices for this model.
     *
     * @return array
     */
    public function getIndices()
    {
        $indices = $this->indices();

        if (empty($indices)) {
            $className = (new \ReflectionClass($this))->getShortName();

            return [$className];
        }

        return $indices;
    }

    /**
     * Returns the model in algolia friendly array form.
     *
     * @return array
     */
    public function getAlgoliaRecord()
    {
        $record = $this->toArray();

        return $record;
    }

    /**
     * Returns an unique identifier for the Model.
     *
     * @return int
     */
    public function getObjectID()
    {
        return $this->getPrimaryKey();
    }

    /**
     * Indexes the model to Algolia.
     */
    public function index()
    {
        $manager = $this->getAlgoliaManager();
        $indices = $this->getIndices();

        foreach ($indices as $index) {
            $index = $manager->initIndex($index);
            $index->addObject($this->getAlgoliaRecord(), $this->getObjectID());
        }
    }

    /**
     * Removes the model from Algolia.
     *
     * @throws \Exception
     */
    public function removeFromIndices()
    {
        $manager = $this->getAlgoliaManager();
        $indices = $this->getIndices();

        foreach ($indices as $index) {
            $index = $manager->initIndex($index);
            $index->deleteObject($this->getObjectID());
        }
    }

    /**
     *
     */
    public function updateInIndices()
    {
        $manager = $this->getAlgoliaManager();
        $indices = $this->getIndices();

        foreach ($indices as $index) {
            $index = $manager->initIndex($index);
            $record = $this->getAlgoliaRecord();
            $record['objectID'] = $this->getObjectID();
            $index->saveObject($record);
        }
    }

    /**
     * Returns the AlgoliaManager Instance.
     *
     * @return AlgoliaManager
     * @throws \yii\base\InvalidConfigException
     */
    private function getAlgoliaManager()
    {
        return Yii::$container->get(AlgoliaManager::class);
    }
}
