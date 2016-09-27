<?php


namespace leinonen\Yii2Algolia\Tests\Unit\ActiveRecord;

use leinonen\Yii2Algolia\ActiveRecord\ActiveQueryChunker;
use leinonen\Yii2Algolia\Tests\Helpers\DummyModel;
use Mockery as m;
use yii\db\ActiveQueryInterface;

class ActiveQueryChunkerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
    }

    /** @test */
    public function it_can_chunk_the_results_from_an_active_query()
    {
        $dummyModel1 = m::mock(DummyModel::class)->makePartial();
        $dummyModel1->id = 1;
        $dummyModel2 = m::mock(DummyModel::class)->makePartial();
        $dummyModel2->id = 2;

        $activeQuery = m::mock(ActiveQueryInterface::class);
        $activeQueryForFirstPage = m::mock(ActiveQueryInterface::class);
        $activeQueryForSecondPage = m::mock(ActiveQueryInterface::class);
        $activeQueryForThirdPage = m::mock(ActiveQueryInterface::class);

        // Simulate the pagination for 2 results when there is one result per page.
        $activeQuery->shouldReceive('offset')->with(0)->once()->andReturn($activeQueryForFirstPage);
        $activeQuery->shouldReceive('offset')->with(1)->once()->andReturn($activeQueryForSecondPage);
        $activeQuery->shouldReceive('offset')->with(2)->once()->andReturn($activeQueryForThirdPage);
        $activeQueryForFirstPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForFirstPage);
        $activeQueryForSecondPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForSecondPage);
        $activeQueryForThirdPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForThirdPage);
        $activeQueryForFirstPage->shouldReceive('all')->andReturn([$dummyModel1]);
        $activeQueryForSecondPage->shouldReceive('all')->andReturn([$dummyModel2]);
        $activeQueryForThirdPage->shouldReceive('all')->andReturn([]);

        $callbackAssertor = m::mock('StdClass');
        $callbackAssertor->shouldReceive('doSomething')->with($dummyModel1->id)->once();
        $callbackAssertor->shouldReceive('doSomething')->with($dummyModel2->id)->once();

        $activeQueryChunker = new ActiveQueryChunker();
        $activeQueryChunker->chunk($activeQuery, 1, function ($dummyModelChunk) use ($callbackAssertor) {
            $this->assertCount(1, $dummyModelChunk);

            foreach($dummyModelChunk as $dummyModel) {
                $callbackAssertor->doSomething($dummyModel->id);
            }
        });
    }

    /** @test */
    public function the_chunk_can_be_stopped_by_returning_false_from_the_callable()
    {
        $dummyModel1 = m::mock(DummyModel::class)->makePartial();
        $dummyModel1->id = 1;
        $dummyModel2 = m::mock(DummyModel::class)->makePartial();
        $dummyModel2->id = 2;

        $activeQuery = m::mock(ActiveQueryInterface::class);
        $activeQueryForFirstPage = m::mock(ActiveQueryInterface::class);

        // Simulate the pagination for 1 result when the pagination should be stopped after first result set.
        $activeQuery->shouldReceive('offset')->with(0)->once()->andReturn($activeQueryForFirstPage);
        $activeQuery->shouldReceive('offset')->with(1)->never();
        $activeQueryForFirstPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForFirstPage);
        $activeQueryForFirstPage->shouldReceive('all')->andReturn([$dummyModel1]);

        $callbackAssertor = m::mock('StdClass');
        $callbackAssertor->shouldReceive('doSomething')->with($dummyModel1->id)->once();
        $callbackAssertor->shouldReceive('doSomething')->with($dummyModel2->id)->never();

        $activeQueryChunker = new ActiveQueryChunker();

        $activeQueryChunker->chunk($activeQuery, 1, function ($dummyModelChunk) use ($callbackAssertor) {
            $this->assertCount(1, $dummyModelChunk);

            foreach($dummyModelChunk as $dummyModel) {
                $callbackAssertor->doSomething($dummyModel->id);
            }

            return false;
        });
    }

    /** @test */
    public function the_result_of_the_chunks_is_merged_and_returned_as_array()
    {
        $dummyModel1 = m::mock(DummyModel::class)->makePartial();
        $dummyModel1->id = 1;
        $dummyModel2 = m::mock(DummyModel::class)->makePartial();
        $dummyModel2->id = 2;

        $activeQuery = m::mock(ActiveQueryInterface::class);
        $activeQueryForFirstPage = m::mock(ActiveQueryInterface::class);
        $activeQueryForSecondPage = m::mock(ActiveQueryInterface::class);
        $activeQueryForThirdPage = m::mock(ActiveQueryInterface::class);

        // Simulate the pagination for 2 results when there is one result per page.
        $activeQuery->shouldReceive('offset')->with(0)->once()->andReturn($activeQueryForFirstPage);
        $activeQuery->shouldReceive('offset')->with(1)->once()->andReturn($activeQueryForSecondPage);
        $activeQuery->shouldReceive('offset')->with(2)->once()->andReturn($activeQueryForThirdPage);
        $activeQueryForFirstPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForFirstPage);
        $activeQueryForSecondPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForSecondPage);
        $activeQueryForThirdPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForThirdPage);
        $activeQueryForFirstPage->shouldReceive('all')->andReturn([$dummyModel1]);
        $activeQueryForSecondPage->shouldReceive('all')->andReturn([$dummyModel2]);
        $activeQueryForThirdPage->shouldReceive('all')->andReturn([]);

        $activeQueryChunker = new ActiveQueryChunker();
        $results = $activeQueryChunker->chunk($activeQuery, 1, function ($dummyModelChunk) {
            $this->assertCount(1, $dummyModelChunk);

            foreach($dummyModelChunk as $dummyModel) {
                $dummyModel->id = 'new id';
            }

            return $dummyModelChunk;
        });

        $this->assertCount(2, $results);
        $this->assertEquals('new id', $results[0]->id);
        $this->assertEquals('new id', $results[1]->id);
    }

    /** @test */
    public function only_arrays_returned_from_callable_are_merged_into_the_results()
    {
        $dummyModel1 = m::mock(DummyModel::class)->makePartial();
        $dummyModel1->id = 1;
        $dummyModel2 = m::mock(DummyModel::class)->makePartial();
        $dummyModel2->id = 2;

        $activeQuery = m::mock(ActiveQueryInterface::class);
        $activeQueryForFirstPage = m::mock(ActiveQueryInterface::class);
        $activeQueryForSecondPage = m::mock(ActiveQueryInterface::class);
        $activeQueryForThirdPage = m::mock(ActiveQueryInterface::class);

        // Simulate the pagination for 2 results when there is one result per page.
        $activeQuery->shouldReceive('offset')->with(0)->once()->andReturn($activeQueryForFirstPage);
        $activeQuery->shouldReceive('offset')->with(1)->once()->andReturn($activeQueryForSecondPage);
        $activeQuery->shouldReceive('offset')->with(2)->once()->andReturn($activeQueryForThirdPage);
        $activeQueryForFirstPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForFirstPage);
        $activeQueryForSecondPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForSecondPage);
        $activeQueryForThirdPage->shouldReceive('limit')->with(1)->once()->andReturn($activeQueryForThirdPage);
        $activeQueryForFirstPage->shouldReceive('all')->andReturn([$dummyModel1]);
        $activeQueryForSecondPage->shouldReceive('all')->andReturn([$dummyModel2]);
        $activeQueryForThirdPage->shouldReceive('all')->andReturn([]);

        $activeQueryChunker = new ActiveQueryChunker();
        $results = $activeQueryChunker->chunk($activeQuery, 1, function ($dummyModelChunk) {
            $this->assertCount(1, $dummyModelChunk);

            foreach($dummyModelChunk as $dummyModel) {
                if($dummyModel->id === 1) {
                    return [$dummyModel];
                }
            }
        });

        $this->assertCount(1, $results);
        $this->assertEquals(1, $results[0]->id);

    }
}
