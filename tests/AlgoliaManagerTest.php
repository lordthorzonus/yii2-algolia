<?php

namespace leinonen\Yii2Algolia\Tests;

use AlgoliaSearch\Client;
use AlgoliaSearch\Index;
use leinonen\Yii2Algolia\ActiveRecord\ActiveRecordFactory;
use leinonen\Yii2Algolia\AlgoliaFactory;
use leinonen\Yii2Algolia\AlgoliaManager;
use leinonen\Yii2Algolia\Tests\helpers\DummyActiveRecordModel;
use leinonen\Yii2Algolia\Tests\helpers\NotSearchableDummyModel;
use Mockery as m;
use yii\db\ActiveQuery;

class AlgoliaManagerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    /** @test */
    public function it_can_return_the_client()
    {
        $mockAlgoliaClient = m::mock(Client::class);
        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);
        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory);

        $client = $manager->getClient();
        $this->assertEquals($mockAlgoliaClient, $client);
    }

    /** @test */
    public function it_delegates_the_methods_to_Algolia_client()
    {
        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('test');
        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory);
        $manager->initIndex('test');
    }

    /** @test */
    public function it_can_reindex_the_indices_for_the_given_active_record_class()
    {
        $testModel = m::mock(DummyActiveRecordModel::class);
        $testModel->shouldReceive('getIndices')->andReturn(['test']);
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['objectID' => 1]);

        $mockActiveQuery = m::mock(ActiveQuery::class);
        $mockActiveQuery->shouldReceive('all')->andReturn([$testModel]);

        $testModel->shouldReceive('find')->andReturn($mockActiveQuery);

        $mockTemporaryIndex = m::mock(Index::class);
        $mockTemporaryIndex->indexName = 'tmp_test';
        $mockTemporaryIndex->shouldReceive('addObjects')->with([['objectID' => 1]]);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('tmp_test')->andReturn($mockTemporaryIndex);
        $mockAlgoliaClient->shouldReceive('moveIndex')->withArgs(['tmp_test', 'test']);

        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);
        $mockActiveRecordFactory->shouldReceive('make')->once()->with(DummyActiveRecordModel::class)->andReturn($testModel);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory);
        $manager->reIndex(DummyActiveRecordModel::class);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The class: leinonen\Yii2Algolia\Tests\helpers\NotSearchableDummyModel doesn't implement leinonen\Yii2Algolia\SearchableInterface
     */
    public function it_should_throw_an_error_if_non_searchable_class_was_given_to_reIndex()
    {
        $mockAlgoliaClient = m::mock(Client::class);
        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory);
        $manager->reIndex(NotSearchableDummyModel::class);
    }

    /** @test */
    public function it_can_clear_the_indices_for_the_given_active_record_class()
    {
        $testModel = m::mock(DummyActiveRecordModel::class);
        $testModel->shouldReceive('getIndices')->andReturn(['dummyIndex']);

        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);
        $mockActiveRecordFactory->shouldReceive('make')->once()->with(DummyActiveRecordModel::class)->andReturn($testModel);

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('clearIndex');

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('dummyIndex')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory);
        $manager->clearIndices($testModel);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The class: leinonen\Yii2Algolia\Tests\helpers\NotSearchableDummyModel doesn't implement leinonen\Yii2Algolia\SearchableInterface
     */
    public function it_should_throw_an_error_if_non_searchable_class_was_given_to_clearIndices()
    {
        $mockAlgoliaClient = m::mock(Client::class);
        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory);
        $manager->clearIndices(NotSearchableDummyModel::class);
    }

    /** @test */
    public function it_can_index_an_object_that_implements_searchable_interface()
    {
        $dummyModel = m::mock(DummyActiveRecordModel::class);
        $dummyModel->shouldReceive('getAlgoliaRecord')->andReturn(['objectID' => 1, 'property1' => 'test']);
        $dummyModel->shouldReceive('getIndices')->andReturn(['dummyIndex']);

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('addObject')->once()->with(['objectID' => 1, 'property1' => 'test']);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('dummyIndex')->andReturn($mockIndex);

        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory);
        $manager->pushToIndex($dummyModel);
    }

    /** @test */
    public function it_can_remove_an_object_that_implements_searchable_interface_from_index()
    {
        $dummyModel = m::mock(DummyActiveRecordModel::class);
        $dummyModel->shouldReceive('getObjectID')->andReturn(1);
        $dummyModel->shouldReceive('getIndices')->andReturn(['dummyIndex']);

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('deleteObject')->once()->with(1);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('dummyIndex')->andReturn($mockIndex);

        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory);

        $manager->removeFromIndex($dummyModel);
    }

    /**
     * Returns an new AlgoliaManager with mocked Factories.
     *
     * @param $client
     * @param $activeRecordFactory
     * @param $config
     *
     * @return AlgoliaManager
     */
    protected function getManager($client, $activeRecordFactory, $config = null)
    {
        if (! $config) {
            $config = [
                'applicationId' => 'test',
                'appKey' => 'secret',
            ];
        }

        $mockAlgoliaFactory = m::mock(AlgoliaFactory::class);
        $mockAlgoliaFactory->shouldReceive('make')->with($config)->andReturn($client);
        $manager = new AlgoliaManager($mockAlgoliaFactory, $activeRecordFactory, $config);

        return $manager;
    }
}
