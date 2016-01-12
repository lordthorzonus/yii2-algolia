<?php


namespace leinonen\Yii2Algolia\ActiveRecord;

use yii\db\ActiveRecordInterface;

interface SearchableInterface extends ActiveRecordInterface
{
    /**
     * An array of indices that this model uses. If none specified
     * The name of the model will be used as the index.
     *
     * @return array
     */
    public function indices();

    /**
     * Returns the indices for this model.
     *
     * @return \AlgoliaSearch\Index[]
     */
    public function getIndices();

    /**
     * Returns the model in algolia friendly array form.
     *
     * @return array
     */
    public function getAlgoliaRecord();

    /**
     * Indexes the model to Algolia.
     */
    public function index();

    /**
     * Removes the model from Algolia.
     *
     * @throws \Exception
     */
    public function removeFromIndex();

}
