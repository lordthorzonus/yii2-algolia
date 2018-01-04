<?php

namespace leinonen\Yii2Algolia\ActiveRecord;

use Yii;
use leinonen\Yii2Algolia\AlgoliaManager;

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
        return $this->toArray();
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
     * @see AlgoliaManager::pushToIndices()
     *
     * @return array
     */
    public function index()
    {
        $manager = static::getAlgoliaManager();

        return $manager->pushToIndices($this);
    }

    /**
     * @see AlgoliaManager::removeFromIndices()
     *
     * @return array
     */
    public function removeFromIndices()
    {
        $manager = static::getAlgoliaManager();

        return $manager->removeFromIndices($this);
    }

    /**
     * @see AlgoliaManager::updateInIndices()
     *
     * @return array
     */
    public function updateInIndices()
    {
        $manager = static::getAlgoliaManager();

        return $manager->updateInIndices($this);
    }

    /**
     * @see AlgoliaManager::reindex()
     *
     * @return array
     */
    public static function reindex()
    {
        $manager = static::getAlgoliaManager();

        return $manager->reindex(__CLASS__);
    }

    /**
     * @see AlgoliaManager::clearIndices()
     *
     * @return array
     */
    public static function clearIndices()
    {
        $manager = static::getAlgoliaManager();

        return $manager->clearIndices(__CLASS__);
    }

    /**
     * @see AlgoliaManager::search()
     *
     * @param string $query
     * @param null|array $searchParameters
     *
     * @return array
     */
    public static function search($query, array $searchParameters = null)
    {
        $manager = static::getAlgoliaManager();

        return $manager->search(__CLASS__, $query, $searchParameters);
    }

    /**
     * Returns the AlgoliaManager Instance.
     *
     * @internal Marked as protected only to counter the problems with mocking.
     *
     * @return AlgoliaManager
     */
    protected static function getAlgoliaManager()
    {
        return Yii::$container->get(AlgoliaManager::class);
    }
}
