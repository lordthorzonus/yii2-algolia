<?php

namespace leinonen\Yii2Algolia;

interface SearchableInterface
{
    /**
     * Returns an array of indices for this model.
     *
     * @return array
     */
    public function getIndices();

    /**
     * Returns the model in algolia friendly array form.
     * Must return an key called objectID for identifying with Algolia.
     *
     * @return array
     */
    public function getAlgoliaRecord();

    /**
     * Returns an unique identifier for the Model.
     *
     * @return int
     */
    public function getObjectID();
}
