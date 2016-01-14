<?php

namespace leinonen\Yii2Algolia\ActiveRecord;


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
     *
     * @return array
     */
    public function getAlgoliaRecord();
}
