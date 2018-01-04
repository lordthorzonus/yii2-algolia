<?php

namespace leinonen\Yii2Algolia\Tests\Unit;

use leinonen\Yii2Algolia\AlgoliaConfig;
use leinonen\Yii2Algolia\AlgoliaFactory;
use leinonen\Yii2Algolia\AlgoliaManager;
use leinonen\Yii2Algolia\Tests\Helpers\DummyActiveRecordModel;

class AlgoliaFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_create_a_new_AlgoliaManager()
    {
        $factory = new AlgoliaFactory();
        $manager = $factory->make(new AlgoliaConfig('app-id', 'secret'));

        $this->assertInstanceOf(AlgoliaManager::class, $manager);
    }

    /** @test */
    public function it_can_make_new_searchable_objects()
    {
        $factory = new AlgoliaFactory();
        $searchableModel = $factory->makeSearchableObject(DummyActiveRecordModel::class);
        $this->assertInstanceOf(DummyActiveRecordModel::class, $searchableModel);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot initiate a class (leinonen\Yii2Algolia\AlgoliaFactory) which doesn't implement leinonen\Yii2Algolia\SearchableInterface
     */
    public function it_should_throw_an_exception_if_not_a_searchable_class_is_given()
    {
        $factory = new AlgoliaFactory();
        $factory->makeSearchableObject(AlgoliaFactory::class);
    }
}
