<?php

namespace leinonen\Yii2Algolia\ActiveRecord;

use leinonen\Yii2Algolia\AlgoliaManager;
use leinonen\Yii2Algolia\SearchableInterface;
use Yii;

/**
 * Simple trait that implements methods needed for the SearchableInterface + couple helpers for dealing with ActiveRecords.
 */
trait Searchable
{
    /**
     * @see \yii\base\ArrayableTrait::toArray()
     *
     * @param array $fields
     * @param array $expand
     * @param bool $recursive
     */
    abstract public function toArray(array $fields = [], array $expand = [], $recursive = true);

    /**
     * @see \yii\db\BaseActiveRecord::getPrimaryKey()
     *
     * @param bool $asArray
     */
    abstract public function getPrimaryKey($asArray = false);

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

        return $manager->pushToIndices($this);
    }

    /**
     * Removes the model from Algolia.
     *
     * @throws \Exception
     */
    public function removeFromIndices()
    {
        $manager = $this->getAlgoliaManager();

        $manager->removeFromIndices($this);
    }

    /**
     * Updates the model in Algolia.
     */
    public function updateInIndices()
    {
        $manager = $this->getAlgoliaManager();

        $manager->updateInIndices($this);
    }

    /**
     * Re-indexes the indices safely for this ActiveRecord class.
     */
    public static function reindex()
    {
        $manager = static::getAlgoliaManager();
        
        $manager->reindex(__CLASS__);
    }

    /**
     * Clears the indices for this ActiveRecord class.
     */
    public static function clearIndices()
    {
        $manager = static::getAlgoliaManager();

        $manager->clearIndices(__CLASS__);
    }

    /**
     * Returns the AlgoliaManager Instance.
     *
     * @return AlgoliaManager
     * @throws \yii\base\InvalidConfigException
     */
    private static function getAlgoliaManager()
    {
        return Yii::$container->get(AlgoliaManager::class);
    }
}
