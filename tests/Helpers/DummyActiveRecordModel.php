<?php

namespace leinonen\Yii2Algolia\Tests\Helpers;

use leinonen\Yii2Algolia\ActiveRecord\Searchable;
use leinonen\Yii2Algolia\SearchableInterface;
use yii\db\ActiveRecord;

class DummyActiveRecordModel extends ActiveRecord implements SearchableInterface
{
    use Searchable;
}
