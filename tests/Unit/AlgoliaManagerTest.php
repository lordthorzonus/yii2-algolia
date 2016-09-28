<?php

namespace leinonen\Yii2Algolia\Tests\Unit;

use AlgoliaSearch\Client;
use AlgoliaSearch\Index;
use leinonen\Yii2Algolia\ActiveRecord\ActiveQueryChunker;
use leinonen\Yii2Algolia\ActiveRecord\ActiveRecordFactory;
use leinonen\Yii2Algolia\AlgoliaManager;
use leinonen\Yii2Algolia\SearchableInterface;
use leinonen\Yii2Algolia\Tests\Helpers\DummyActiveRecordModel;
use leinonen\Yii2Algolia\Tests\Helpers\DummyModel;
use leinonen\Yii2Algolia\Tests\Helpers\NotSearchableDummyModel;
use Mockery as m;
use yii\db\ActiveQuery;

class AlgoliaManagerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
        parent::tearDown();
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
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getObjectID')->andReturn(1);
        $expectedTestModelAlgoliaRecord = ['property1' => 'test', 'objectID' => 1];

        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);
        $mockActiveRecordFactory->shouldReceive('make')->once()->with(DummyActiveRecordModel::class)->andReturn($testModel);

        $mockActiveQuery = m::mock(ActiveQuery::class);
        $testModel->shouldReceive('find')->andReturn($mockActiveQuery);

        $mockActiveQueryChunker = $this->mockActiveQueryChunkingForReindex($mockActiveQuery, $testModel, $expectedTestModelAlgoliaRecord);

        $mockIndex = m::mock(Index::class);
        $mockIndex->indexName = 'test';
        $mockTemporaryIndex = m::mock(Index::class);
        $mockTemporaryIndex->indexName = 'tmp_test';

        // Assert that the actual indexing happens
        $mockTemporaryIndex->shouldReceive('addObjects')->with([['property1' => 'test', 'objectID' => 1]])->once();

        // Settings should stay the same during the atomical move
        $mockIndex->shouldReceive('getSettings')->andReturn(['setting1' => 'value1']);
        $mockTemporaryIndex->shouldReceive('setSettings')->with(['setting1' => 'value1']);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('tmp_test')->once()->andReturn($mockTemporaryIndex);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('test')->once()->andReturn($mockIndex);
        $mockAlgoliaClient->shouldReceive('moveIndex')->withArgs(['tmp_test', 'test']);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory, $mockActiveQueryChunker);
        $manager->reindex(DummyActiveRecordModel::class);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The class: leinonen\Yii2Algolia\Tests\Helpers\NotSearchableDummyModel doesn't implement leinonen\Yii2Algolia\SearchableInterface
     */
    public function it_should_throw_an_error_if_non_searchable_class_was_given_to_reIndex()
    {
        $mockAlgoliaClient = m::mock(Client::class);
        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory);
        $manager->reindex(NotSearchableDummyModel::class);
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
     * @expectedExceptionMessage The class: leinonen\Yii2Algolia\Tests\Helpers\NotSearchableDummyModel doesn't implement leinonen\Yii2Algolia\SearchableInterface
     */
    public function it_should_throw_an_error_if_non_searchable_class_was_given_to_clearIndices()
    {
        $mockAlgoliaClient = m::mock(Client::class);

        $manager = $this->getManager($mockAlgoliaClient);
        $manager->clearIndices(NotSearchableDummyModel::class);
    }

    /** @test */
    public function it_can_index_an_object_that_implements_searchable_interface()
    {
        $dummyModel = m::mock(DummyActiveRecordModel::class);
        $dummyModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $dummyModel->shouldReceive('getObjectID')->andReturn(1);
        $dummyModel->shouldReceive('getIndices')->andReturn(['dummyIndex']);

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('addObject')->once()->withArgs([['property1' => 'test'], 1]);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('dummyIndex')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient);
        $manager->pushToIndices($dummyModel);
    }

    /** @test */
    public function it_can_update_an_object_that_implements_searchable_interface_in_all_indices_()
    {
        $dummyModel = m::mock(DummyActiveRecordModel::class);
        $dummyModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $dummyModel->shouldReceive('getObjectID')->andReturn(1);
        $dummyModel->shouldReceive('getIndices')->andReturn(['dummyIndex']);

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('saveObject')->once()->with(['property1' => 'test', 'objectID' => 1]);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('dummyIndex')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient);

        $manager->updateInIndices($dummyModel);
    }

    /** @test */
    public function it_can_remove_an_object_that_implements_searchable_interface_from_indices()
    {
        $dummyModel = m::mock(DummyActiveRecordModel::class);
        $dummyModel->shouldReceive('getObjectID')->andReturn(1);
        $dummyModel->shouldReceive('getIndices')->andReturn(['dummyIndex']);

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('deleteObject')->once()->with(1);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('dummyIndex')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient);

        $manager->removeFromIndices($dummyModel);
    }

    /** @test */
    public function it_can_remove_multiple_objects_that_implement_searchable_interface_from_indices()
    {
        $testModel = m::mock(DummyActiveRecordModel::class);
        $testModel->shouldReceive('getIndices')->andReturn(['test']);
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getObjectID')->andReturn(1);

        $testModel2 = m::mock(DummyActiveRecordModel::class);
        $testModel2->shouldNotReceive('getIndices');
        $testModel2->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel2->shouldReceive('getObjectID')->andReturn(2);

        $arrayOfTestModels = [$testModel, $testModel2];

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('deleteObjects')->once()->with([1, 2]);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('test')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient);

        $manager->removeMultipleFromIndices($arrayOfTestModels);
    }

    /** @test */
    public function it_prefixes_the_indexes_with_the_given_environment_config_for_crud_operations()
    {
        $dummyModel = m::mock(DummyActiveRecordModel::class);
        $dummyModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $dummyModel->shouldReceive('getObjectID')->andReturn(1);
        $dummyModel->shouldReceive('getIndices')->andReturn(['dummyIndex']);

        $mockIndex = m::mock(Index::class);
        $mockIndex->indexName = 'dev_dummyIndex';
        $mockIndex->shouldReceive('saveObject')->once()->with(['property1' => 'test', 'objectID' => 1]);
        $mockIndex->shouldReceive('deleteObject')->once()->with(1);
        $mockIndex->shouldReceive('addObject')->once()->withArgs([['property1' => 'test'], 1]);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('dev_dummyIndex')->times(3)->andReturn($mockIndex);

        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);
        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory, null, 'dev');

        $manager->updateInIndices($dummyModel);
        $manager->removeFromIndices($dummyModel);
        $manager->pushToIndices($dummyModel);
    }

    /** @test */
    public function it_prefixes_the_indexes_with_given_environment_config_for_reindex_operation()
    {
        $testModel = m::mock(DummyActiveRecordModel::class);
        $testModel->shouldReceive('getIndices')->andReturn(['test']);
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getObjectID')->andReturn(1);
        $expectedTestModelAlgoliaRecord = ['property1' => 'test', 'objectID' => 1];

        $mockActiveQuery = m::mock(ActiveQuery::class);
        $mockActiveQuery->shouldReceive('all')->andReturn([$testModel]);

        $mockActiveQuery = m::mock(ActiveQuery::class);
        $testModel->shouldReceive('find')->andReturn($mockActiveQuery);

        $mockActiveQueryChunker = $this->mockActiveQueryChunkingForReindex($mockActiveQuery, $testModel, $expectedTestModelAlgoliaRecord);

        $mockIndex = m::mock(Index::class);
        $mockIndex->indexName = 'dev_test';
        $mockIndex->shouldReceive('getSettings')->andReturn(['setting1' => 'value1']);

        $mockTemporaryIndex = m::mock(Index::class);
        $mockTemporaryIndex->indexName = 'tmp_dev_test';
        $mockTemporaryIndex->shouldReceive('addObjects')->with([['property1' => 'test', 'objectID' => 1]]);
        $mockTemporaryIndex->shouldReceive('setSettings')->with(['setting1' => 'value1']);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('tmp_dev_test')->andReturn($mockTemporaryIndex);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('dev_test')->andReturn($mockIndex);
        $mockAlgoliaClient->shouldReceive('moveIndex')->withArgs(['tmp_dev_test', 'dev_test']);

        $mockActiveRecordFactory = m::mock(ActiveRecordFactory::class);
        $mockActiveRecordFactory->shouldReceive('make')->once()->with(DummyActiveRecordModel::class)->andReturn($testModel);

        $manager = $this->getManager($mockAlgoliaClient, $mockActiveRecordFactory, $mockActiveQueryChunker, 'dev');

        $manager->reindex(DummyActiveRecordModel::class);
    }

    /** @test */
    public function it_can_index_multiple_searchable_objects_in_a_batch()
    {
        $testModel = m::mock(DummyActiveRecordModel::class);
        $testModel->shouldReceive('getIndices')->andReturn(['test']);
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getObjectID')->andReturn(1);

        $testModel2 = m::mock(DummyActiveRecordModel::class);
        $testModel2->shouldNotReceive('getIndices');
        $testModel2->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel2->shouldReceive('getObjectID')->andReturn(2);

        $arrayOfTestModels = [$testModel, $testModel2];

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('addObjects')->once()->with([['property1' => 'test', 'objectID' => 1], ['property1' => 'test', 'objectID' => 2]]);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('test')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient);
        $manager->pushMultipleToIndices($arrayOfTestModels);
    }

    /** @test */
    public function it_can_update_multiple_searchable_objects_in_a_batch()
    {
        $testModel = m::mock(DummyActiveRecordModel::class);
        $testModel->shouldReceive('getIndices')->andReturn(['test']);
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getObjectID')->andReturn(1);

        $testModel2 = m::mock(DummyActiveRecordModel::class);
        $testModel2->shouldReceive('getIndices')->andReturn(['test']);
        $testModel2->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel2->shouldReceive('getObjectID')->andReturn(2);

        $arrayOfTestModels = [$testModel, $testModel2];

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('saveObjects')->once()->with([['property1' => 'test', 'objectID' => 1], ['property1' => 'test', 'objectID' => 2]]);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('test')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient);
        $manager->updateMultipleInIndices($arrayOfTestModels);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The given array should not contain multiple different classes
     */
    public function it_should_throw_an_exception_if_multiple_different_objects_are_used_for_updating_in_batches()
    {
        $testModel = m::mock(DummyActiveRecordModel::class);
        $testModel->shouldReceive('getIndices')->andReturn(['test']);
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getObjectID')->andReturn(1);

        // This model should throw an exception
        $testModel2 = m::mock(DummyModel::class);

        $mockIndex = m::mock(Index::class);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('test')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient);
        $manager->updateMultipleInIndices([$testModel, $testModel2]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The given array should not contain multiple different classes
     */
    public function it_should_throw_an_exception_if_multiple_different_objects_are_used_for_indexing_in_batches()
    {
        $testModel = m::mock(DummyActiveRecordModel::class);
        $testModel->shouldReceive('getIndices')->andReturn(['test']);
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getObjectID')->andReturn(1);

        // This model should throw an exception
        $testModel2 = m::mock(DummyModel::class);

        $mockIndex = m::mock(Index::class);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('test')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient);
        $manager->pushMultipleToIndices([$testModel, $testModel2]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The given array should not contain multiple different classes
     */
    public function it_should_throw_an_exception_if_multiple_different_objects_are_used_for_deleting_in_batches()
    {
        $testModel = m::mock(DummyActiveRecordModel::class);
        $testModel->shouldReceive('getIndices')->andReturn(['test']);
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getObjectID')->andReturn(1);

        // This model should throw an exception
        $testModel2 = m::mock(DummyModel::class);

        $mockIndex = m::mock(Index::class);

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('test')->andReturn($mockIndex);

        $manager = $this->getManager($mockAlgoliaClient);
        $manager->removeMultipleFromIndices([$testModel, $testModel2]);
    }

    /**
     * Returns an new AlgoliaManager with mocked Factories.
     *
     * @param Client $client
     * @param null|ActiveRecordFactory $activeRecordFactory
     * @param null|ActiveQuery $activeQueryChunker
     * @param null|string $env
     *
     * @return AlgoliaManager
     */
    private function getManager($client, $activeRecordFactory = null, $activeQueryChunker = null, $env = null)
    {
        if ($activeRecordFactory === null) {
            $activeRecordFactory = m::mock(ActiveRecordFactory::class);
        }

        if($activeQueryChunker === null) {
            $activeQueryChunker = m::mock(ActiveQueryChunker::class);
        }

        $manager = new AlgoliaManager($client, $activeRecordFactory, $activeQueryChunker);
        $manager->setEnv($env);

        return $manager;
    }

    /**
     * Returns a mock of the ActiveQueryChunker with expectations for the reindex operation.
     *
     * @param m\MockInterface $mockActiveQuery The ActiveQuery
     * @param SearchableInterface $testModel
     * @param array $expectedTestModelAlgoliaRecord
     *
     * @return ActiveQueryChunker
     */
    private function mockActiveQueryChunkingForReindex($mockActiveQuery, $testModel, $expectedTestModelAlgoliaRecord)
    {
        $mockActiveQueryChunker = m::mock(ActiveQueryChunker::class);

        // Mock the chunk and assert that the given closure works as expected
        $mockActiveQueryChunker->shouldReceive('chunk')->withArgs(
            [
                $mockActiveQuery,
                500,
                m::on(function ($closure) use ($testModel, $expectedTestModelAlgoliaRecord) {

                    // The closure receives an array consisting of a single testModel as the result of chunking
                    // and it should convert it into a proper array of Algolia indexable records.
                    // We'll only test the first chunk as the it confirms if the closure works as expected.
                    $closureResult = $closure([$testModel]);
                    $this->assertEquals([$expectedTestModelAlgoliaRecord], $closureResult);

                    return is_callable($closure);
                })
            ]
        )->andReturn([$expectedTestModelAlgoliaRecord]);

        return $mockActiveQueryChunker;
    }
}
