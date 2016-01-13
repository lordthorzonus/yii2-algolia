<?php

namespace leinonen\Yii2Algolia\ActiveRecord;

use leinonen\Yii2Algolia\AlgoliaManager;
use Yii;

/**
 * Simple trait that implements methods needed for the SearchableInterface.
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
        $record['objectID'] = $this->getPrimaryKey();

        return $record;
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
            $index->addObject($this->getAlgoliaRecord());
        }
    }

    /**
     * Removes the model from Algolia.
     *
     * @throws \Exception
     */
    public function removeFromIndex()
    {
        $manager = $this->getAlgoliaManager();
        $indices = $this->getIndices();

        foreach ($indices as $index) {
            $index = $manager->initIndex($index);
            $index->deleteObject($this->getPrimaryKey());
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
