<?php

namespace leinonen\Yii2Algolia\Tests\Unit\ActiveRecord;

use AlgoliaSearch\Index;
use leinonen\Yii2Algolia\AlgoliaComponent;
use leinonen\Yii2Algolia\AlgoliaManager;
use leinonen\Yii2Algolia\Tests\Helpers\DummyActiveRecordModel;
use Mockery as m;
use Yii;
use yiiunit\TestCase;

class SearchableTest extends TestCase
{
    /**
     * @var \Mockery\MockInterface
     */
    private $dummyAlgoliaManager;

    public function setUp()
    {
        parent::setUp();
        $this->mockWebApplication([
            'bootstrap' => ['algolia'],
            'components' => [
                'algolia' => [
                    'class' => AlgoliaComponent::class,
                    'applicationId' => 'test',
                    'apiKey' => 'secret',
                ],
            ],
        ]);
        $this->dummyAlgoliaManager = m::mock(AlgoliaManager::class);

        // Override the Registered AlgoliaManager with a mock
        Yii::$container->set(AlgoliaManager::class, function () {
            return $this->dummyAlgoliaManager;
        });
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_return_indices_for_the_model()
    {
        $testModel = new DummyActiveRecordModel();
        $this->assertEquals(['DummyActiveRecordModel'], $testModel->getIndices());
    }

    /** @test */
    public function if_the_indices_are_specified_they_take_precedence()
    {
        $testModel = m::mock(DummyActiveRecordModel::class)->makePartial();
        $testModel->shouldReceive('indices')->andReturn(['firstIndice', 'secondIndice']);

        $this->assertEquals(['firstIndice', 'secondIndice'], $testModel->getIndices());
    }

    /** @test */
    public function it_can_be_converted_to_algolia_record()
    {
        $testModel = m::mock(DummyActiveRecordModel::class)->makePartial();
        $testModel->shouldReceive('toArray')->andReturn(['property1' => 'test']);

        $this->assertEquals(['property1' => 'test'], $testModel->getAlgoliaRecord());
    }

    /** @test */
    public function it_can_be_pushed_to_indices()
    {
        $testModel = m::mock(DummyActiveRecordModel::class)->makePartial();
        $this->dummyAlgoliaManager->shouldReceive('pushToIndices')
            ->once()
            ->with($testModel)
            ->andReturn('response');

        $response = $testModel->index();
        $this->assertEquals('response', $response);
    }

    /** @test */
    public function it_can_be_removed_from_indices()
    {
        $testModel = m::mock(DummyActiveRecordModel::class)->makePartial();
        $this->dummyAlgoliaManager->shouldReceive('removeFromIndices')
            ->once()
            ->with($testModel)
            ->andReturn('response');

        $response = $testModel->removeFromIndices();
        $this->assertEquals('response', $response);
    }

    /** @test */
    public function it_can_be_updated_in_indices()
    {
        $testModel = m::mock(DummyActiveRecordModel::class)->makePartial();
        $this->dummyAlgoliaManager->shouldReceive('updateInIndices')
            ->once()
            ->with($testModel)
            ->andReturn('response');

        $response = $testModel->updateInIndices();
        $this->assertEquals('response', $response);
    }

    /** @test */
    public function it_uses_the_primary_key_of_the_active_record_for_the_object_id()
    {
        $testModel = m::mock(DummyActiveRecordModel::class)->makePartial();
        $testModel->shouldReceive('getPrimaryKey')->andReturn(1);

        $this->assertEquals(1, $testModel->getObjectID());
    }

    /** @test */
    public function it_can_be_re_indexed()
    {
        $this->dummyAlgoliaManager->shouldReceive('reindex')
            ->once()
            ->with(DummyActiveRecordModel::class)
            ->andReturn('response');

        $response = DummyActiveRecordModel::reindex();
        $this->assertEquals('response', $response);
    }

    /** @test */
    public function it_can_be_cleared_from_indices()
    {
        $this->dummyAlgoliaManager->shouldReceive('clearIndices')
            ->once()
            ->with(DummyActiveRecordModel::class)
            ->andReturn('response');

        $response = DummyActiveRecordModel::clearIndices();
        $this->assertEquals('response', $response);
    }

    /** @test */
    public function it_can_be_searched()
    {
        $this->dummyAlgoliaManager->shouldReceive('search')
            ->once()
            ->with(DummyActiveRecordModel::class, 'query string', null)
            ->andReturn('response');

        $response = DummyActiveRecordModel::search('query string');
        $this->assertEquals('response', $response);
    }

    /** @test */
    public function it_can_be_searched_with_addittional_search_parameters()
    {
        $searchParameters = ['attributesToRetrieve' => 'firstname,lastname', 'hitsPerPage' => 50];
        $this->dummyAlgoliaManager->shouldReceive('search')
            ->once()
            ->with(DummyActiveRecordModel::class, 'query string', $searchParameters)
            ->andReturn('response');

        $response = DummyActiveRecordModel::search('query string', $searchParameters);
        $this->assertEquals('response', $response);
    }
}
