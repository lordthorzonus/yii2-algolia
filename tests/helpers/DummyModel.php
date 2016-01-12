<?php


namespace leinonen\Yii2Algolia\Tests\helpers;


use leinonen\Yii2Algolia\ActiveRecord\Searchable;
use leinonen\Yii2Algolia\ActiveRecord\SearchableInterface;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use Mockery as m;

class DummyModel extends ActiveRecord implements SearchableInterface
{
    use Searchable;
}