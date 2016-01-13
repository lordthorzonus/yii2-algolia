<?php

namespace leinonen\Yii2Algolia\Tests\ActiveRecord;

use AlgoliaSearch\Index;
use leinonen\Yii2Algolia\AlgoliaComponent;
use leinonen\Yii2Algolia\AlgoliaManager;
use leinonen\Yii2Algolia\Tests\helpers\DummyModel;
use Yii;
use yiiunit\TestCase;
use Mockery as m;

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

        //Override the Registered AlgoliaManager with a mock
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
        $testModel = new DummyModel();
        $this->assertEquals(['DummyModel'], $testModel->getIndices());
    }

    /** @test */
    public function if_the_indices_are_specified_they_take_precedence()
    {
        $testModel = m::mock(DummyModel::class)->makePartial();
        $testModel->shouldReceive('indices')->andReturn(['firstIndice', 'secondIndice']);


        $this->assertEquals(['firstIndice', 'secondIndice'], $testModel->getIndices());
    }

    /** @test */
    public function it_can_be_converted_to_algolia_record()
    {
        $testModel = m::mock(DummyModel::class)->makePartial();
        $testModel->shouldReceive('toArray')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getPrimaryKey')->andReturn(1);

        $this->assertEquals(['objectID' => 1, 'property1' => 'test'], $testModel->getAlgoliaRecord());
    }

    /** @test */
    public function it_can_be_pushed_to_indices()
    {
        $testModel = m::mock(DummyModel::class)->makePartial();
        $testModel->shouldReceive('toArray')->andReturn(['property1' => 'test']);
        $testModel->shouldReceive('getPrimaryKey')->andReturn(1);
        $testModel->shouldReceive('getIndices')->andReturn(['DummyModel']);

        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('addObject')->once()->with(['objectID' => 1, 'property1' => 'test']);

        $this->dummyAlgoliaManager->shouldReceive('initIndex')->with('DummyModel')->andReturn($mockIndex);
        $testModel->index();
    }

    /** @test */
    public function it_can_be_removed_from_indices()
    {
        $testModel = m::mock(DummyModel::class)->makePartial();
        $testModel->shouldReceive('getPrimaryKey')->andReturn(1);
        $testModel->shouldReceive('getIndices')->andReturn(['DummyModel']);


        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('deleteObject')->once()->with(1);

        $this->dummyAlgoliaManager->shouldReceive('initIndex')->with('DummyModel')->andReturn($mockIndex);
        $testModel->removeFromIndex();
    }
}
