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
     * Returns the model in an Algolia friendly array form.
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
