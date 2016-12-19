<?php

namespace leinonen\Yii2Algolia\Tests\Helpers;

use yii\db\ActiveRecord;
use leinonen\Yii2Algolia\SearchableInterface;
use leinonen\Yii2Algolia\ActiveRecord\Searchable;
use leinonen\Yii2Algolia\ActiveRecord\SynchronousAutoIndexBehavior;

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
