<?php

namespace leinonen\Yii2Algolia\Tests\Helpers;

use leinonen\Yii2Algolia\ActiveRecord\Searchable;
use leinonen\Yii2Algolia\ActiveRecord\SynchronousAutoIndexBehavior;
use leinonen\Yii2Algolia\SearchableInterface;
use yii\db\ActiveRecord;

class SynchronousAutoIndexedDummyModel extends ActiveRecord implements SearchableInterface
{
    use Searchable;

    public function behaviors()
    {
        return [
            SynchronousAutoIndexBehavior::class,
        ];
    }
}
    