<?php

namespace leinonen\Yii2Algolia\Tests\Helpers;

use yii\base\Model;
use leinonen\Yii2Algolia\SearchableInterface;

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
