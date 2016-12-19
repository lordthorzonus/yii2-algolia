<?php

namespace leinonen\Yii2Algolia\Tests\Helpers;

use yii\db\ActiveRecord;
use leinonen\Yii2Algolia\SearchableInterface;
use leinonen\Yii2Algolia\ActiveRecord\Searchable;

class DummyActiveRecordModel extends ActiveRecord implements SearchableInterface
{
    use Searchable;
}
