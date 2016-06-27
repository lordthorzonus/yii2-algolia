<?php

namespace leinonen\Yii2Algolia\Tests\Helpers;

use leinonen\Yii2Algolia\SearchableInterface;
use yii\base\Model;

class DummyModel extends Model implements SearchableInterface
{
    public $test;
    public $otherProperty;
    public $id;

    /**
     * Returns an array of indices for this model.
     *
     * @return array
     */
    public function getIndices()
    {
        return ['dummy_model'];
    }

    /**
     * Returns the model in an Algolia friendly array form.
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
        return $this->id;
    }
}
