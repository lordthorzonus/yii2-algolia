<?php

namespace leinonen\Yii2Algolia\Tests\Integration;

use leinonen\Yii2Algolia\AlgoliaComponent;
use leinonen\Yii2Algolia\AlgoliaManager;
use leinonen\Yii2Algolia\SearchableInterface;
use leinonen\Yii2Algolia\Tests\Helpers\DummyActiveRecordModel;
use leinonen\Yii2Algolia\Tests\Helpers\DummyModel;
use Yii;
use Mockery as m;
use yiiunit\TestCase;

class AlgoliaManagerTest extends TestCase
{
    /**
     * @var AlgoliaManager
     */
    private $algoliaManager;

    public function setUp()
    {
        parent::setUp();

        $this->mockWebApplication([
            'bootstrap' => ['algolia'],
            'components' => [
                'algolia' => [
                    'class' => AlgoliaComponent::class,
                    'applicationId' => getenv('ALGOLIA_ID'),
                    'apiKey' => getenv('ALGOLIA_KEY'),
                ],
            ],
        ]);

        $this->algoliaManager = Yii::$container->get(AlgoliaManager::class);
    }

    /** @test */
    public function it_can_index_an_searchable_object()
    {
        $index = $this->addDummyObjectToIndex();
        $searchResult = $index->search('otherProperty');

        $this->deleteIndex($index);
        $this->assertCount(1, $searchResult['hits']);
    }

    /** @test */
    public function it_can_index_multiple_objects_in_a_batch()
    {
        $dummyModel1 = $this->makeDummyModel(1);
        $dummyModel2 = $this->makeDummyModel(2);
        $indexName = $dummyModel1->getIndices()[0];

        $pushResponse = $this->algoliaManager->pushMultipleToIndices([$dummyModel1, $dummyModel2]);

        $index = $this->algoliaManager->initIndex($indexName);
        $index->waitTask($pushResponse[$indexName]['taskID']);

        $searchResult = $index->search('otherProperty');

        $this->deleteIndex($index);
        $this->assertCount(2, $searchResult['hits']);
    }

    /** @test */
    public function it_can_update_an_existing_searchable_object()
    {
        $index = $this->addDummyObjectToIndex();
        $dummyObject = $this->makeDummyActiveRecord();
        $dummyObject->otherProperty = 'A new text for property';

        $updateResponse = $this->algoliaManager->updateInIndices($dummyObject);
        $index->waitTask($updateResponse[$index->indexName]['taskID']);

        $searchResult = $index->search('A new text for property');

        $this->deleteIndex($index);
        $this->assertCount(1, $searchResult['hits']);
    }

    /** @test */
    public function it_uses_right_index_according_to_given_env()
    {
        // Clean up.
        $this->destroyApplication();
        $this->mockWebApplication([
            'bootstrap' => ['algolia'],
            'components' => [
                'algolia' => [
                    'class' => AlgoliaComponent::class,
                    'applicationId' => getenv('ALGOLIA_ID'),
                    'apiKey' => getenv('ALGOLIA_KEY'),
                    'env' => 'test',
                ],
            ],
        ]);

        $algoliaManager = Yii::$container->get(AlgoliaManager::class);

        $searchableObject = $this->makeDummyActiveRecord();
        $searchableObject->shouldReceive('getIndices')->andReturn(['index']);

        $pushResponse = $algoliaManager->pushToIndices($searchableObject);


        $this->assertArrayHasKey('test_index', $pushResponse);
        $this->assertEquals('test', $algoliaManager->getEnv());
        $this->deleteIndex($algoliaManager->initIndex('test_index'));
    }

    /**
     * Creates one dummy object to a new index and asserts that it is successful.
     *
     * @return \AlgoliaSearch\Index
     */
    private function addDummyObjectToIndex()
    {
        $searchableObject = $this->makeDummyActiveRecord();
        $indexName = $searchableObject->getIndices()[0];

        $pushResponse = $this->algoliaManager->pushToIndices($searchableObject);

        $index = $this->algoliaManager->initIndex($indexName);
        $index->waitTask($pushResponse[$indexName]['taskID']);

        return $index;
    }

    /**
     * Deletes an Algolia index and asserts that it is successful.
     *
     * @param $index
     */
    private function deleteIndex($index)
    {
        $deleteResult = $this->algoliaManager->deleteIndex($index->indexName);
        $this->assertArrayHasKey('deletedAt', $deleteResult);
        $this->assertArrayHasKey('taskID', $deleteResult);
    }

    /**
     * Returns a dummy Yii's Base Mdoel object.
     *
     * @param int $objectId
     *
     * @return SearchableInterface
     */
    private function makeDummyModel($objectId = 1)
    {
        return new DummyModel([
            'id' => $objectId,
            'test' => 'test',
            'otherProperty' => 'otherProperty',
        ]);
    }

    /**
     * Returns a dummy ActiveRecord object.
     *
     * @param int $objectId
     *
     * @return SearchableInterface
     */
    private function makeDummyActiveRecord($objectId = 1)
    {
        // ActiveRecord needs to mocked because of the database.
        $searchableObject = m::mock(DummyActiveRecordModel::class);
        $searchableObject->shouldReceive('attributes')->andReturn([
            'test',
            'otherProperty',
        ]);

        $searchableObject->shouldReceive('getObjectID')->andReturn($objectId);
        $searchableObject->makePartial();
        $searchableObject->test = 'test';
        $searchableObject->otherProperty = 'otherProperty';

        return $searchableObject;
    }
}
