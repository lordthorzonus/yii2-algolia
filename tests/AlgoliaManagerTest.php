<?php

namespace leinonen\Yii2Algolia\Tests;

use AlgoliaSearch\Client;
use AlgoliaSearch\Index;
use leinonen\Yii2Algolia\AlgoliaFactory;
use leinonen\Yii2Algolia\AlgoliaManager;
use leinonen\Yii2Algolia\Tests\helpers\DummyModel;
use Mockery as m;
use yii\db\ActiveQuery;

class AlgoliaManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_return_the_client()
    {
        $config = [
            'applicationId' => 'test',
            'appKey' => 'secret',
        ];
        $mockAlgoliaClient = m::mock(Client::class);

        $manager = $this->getManager($config, $mockAlgoliaClient);

        $client = $manager->getClient();
        $this->assertEquals($mockAlgoliaClient, $client);
    }

    /** @test */
    public function it_delegates_the_methods_to_Algolia_client()
    {
        $config = [
            'applicationId' => 'test',
            'appKey' => 'secret',
        ];

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('test');

        $manager = $this->getManager($config, $mockAlgoliaClient);

        $manager->initIndex('test');
    }

    /** @test */
    public function it_can_reindex_the_indices_for_the_given_active_record_class()
    {
        $testModel = m::mock(DummyModel::class);

        $mockIndex = m::mock(Index::class);
        $mockIndex->indexName = 'test';

        $testModel->shouldReceive('getIndices')->andReturn([$mockIndex]);
        $testModel->shouldReceive('getAlgoliaRecord')->andReturn(['objectID' => 1]);

        $mockTemporaryIndex = m::mock(Index::class);
        $mockTemporaryIndex->indexName = 'tmp_test';
        $mockTemporaryIndex->shouldReceive('addObjects')->with([['objectID' => 1]]);

        $mockActiveQuery = m::mock(ActiveQuery::class);
        $mockActiveQuery->shouldReceive('all')->andReturn([$testModel]);

        $testModel->shouldReceive('find')->andReturn($mockActiveQuery);

        $config = [
            'applicationId' => 'test',
            'appKey' => 'secret',
        ];

        $mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaClient->shouldReceive('initIndex')->with('tmp_test')->andReturn($mockTemporaryIndex);
        $mockAlgoliaClient->shouldReceive('moveIndex')->withArgs(['tmp_test', 'test']);

        $manager = $this->getManager($config, $mockAlgoliaClient);

        $manager->reIndex($testModel);

    }

    /** @test */
    public function it_can_clear_the_indices_for_the_given_active_record_class()
    {
        $testModel = m::mock(DummyModel::class);
        $mockIndex = m::mock(Index::class);
        $mockIndex->shouldReceive('clearIndex');
        $testModel->shouldReceive('getIndices')->andReturn([$mockIndex]);

        $config = [
            'applicationId' => 'test',
            'appKey' => 'secret',
        ];
        $mockAlgoliaClient = m::mock(Client::class);

        $manager = $this->getManager($config, $mockAlgoliaClient);

        $manager->clearIndices($testModel);
    }

    /**
     * Returns an new AlgoliaManager with mocked Factory.
     * @param $config
     * @param $client
     *
     * @return AlgoliaManager
     */
    protected function getManager($config, $client)
    {
        $mockAlgoliaFactory = m::mock(AlgoliaFactory::class);
        $mockAlgoliaFactory->shouldReceive('make')->with($config)->andReturn($client);
        $manager = new AlgoliaManager($mockAlgoliaFactory, $config);

        return $manager;
    }
}
