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
        parent::tearDown();

        m::close();
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
        $this->dummyAlgoliaManager->shouldReceive('pushToIndices')->with($testModel);

        $testModel->index();
    }

    /** @test */
    public function it_can_be_removed_from_indices()
    {
        $testModel = m::mock(DummyActiveRecordModel::class)->makePartial();
        $this->dummyAlgoliaManager->shouldReceive('removeFromIndices')->with($testModel);

        $testModel->removeFromIndices();
    }

    /** @test */
    public function it_can_be_updated_in_indices()
    {
        $testModel = m::mock(DummyActiveRecordModel::class)->makePartial();
        $this->dummyAlgoliaManager->shouldReceive('updateInIndices')->with($testModel);

        $testModel->updateInIndices();
    }
}
