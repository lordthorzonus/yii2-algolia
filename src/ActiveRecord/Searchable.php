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
     * Returns the indices for this model.
     *
     * @return \AlgoliaSearch\Index[]
     */
    public function getIndices()
    {
        $manager = $this->getAlgoliaManager();
        $indices = $this->indices();

        if(empty($indices)){
            $className = (new \ReflectionClass($this))->getShortName();
            return [$manager->initIndex($className)];
        }

        $indexes = [];

        foreach($indices as $index){
            $indexes[] = $manager->initIndex($index);
        }

        return $indexes;
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
        $indices = $this->getIndices();

        foreach($indices as $index){
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
        $indices = $this->getIndices();

        foreach($indices as $index){
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
