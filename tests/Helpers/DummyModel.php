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
     * {@inheritdoc}
     */
    public function getIndices()
    {
        return ['dummy_model'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAlgoliaRecord()
    {
        return $this->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectID()
    {
        return $this->id;
    }
}
