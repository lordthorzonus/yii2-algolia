<?php

namespace leinonen\Yii2Algolia\Tests\Unit\ActiveRecord;

use leinonen\Yii2Algolia\AlgoliaFactory;
use leinonen\Yii2Algolia\ActiveRecord\ActiveRecordFactory;
use leinonen\Yii2Algolia\Tests\Helpers\DummyActiveRecordModel;

class ActiveRecordFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_creates_active_record_instances_with_given_class_names()
    {
        $factory = new ActiveRecordFactory();
        $createdActiveRecord = $factory->make(DummyActiveRecordModel::class);

        $this->assertInstanceOf(DummyActiveRecordModel::class, $createdActiveRecord);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot initiate a class (leinonen\Yii2Algolia\AlgoliaFactory) which doesn't implement \yii\db\ActiveRecordInterface
     */
    public function it_throws_an_error_if_the_class_is_not_instance_of_ActiveRecordInterface()
    {
        $factory = new ActiveRecordFactory();
        $createdActiveRecord = $factory->make(AlgoliaFactory::class);
    }
}
