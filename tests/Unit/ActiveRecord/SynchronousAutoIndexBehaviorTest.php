<?php

namespace leinonen\Yii2Algolia\Tests\Unit\ActiveRecord;

use leinonen\Yii2Algolia\ActiveRecord\SynchronousAutoIndexBehavior;
use leinonen\Yii2Algolia\AlgoliaComponent;
use leinonen\Yii2Algolia\AlgoliaManager;
use leinonen\Yii2Algolia\Tests\Helpers\DummyActiveRecordModel;
use leinonen\Yii2Algolia\Tests\Helpers\SynchronousAutoIndexedDummyModel;
use Mockery as m;
use Yii;
use yii\db\ActiveRecord;
use yiiunit\TestCase;

class SynchronousAutoIndexBehaviorTest extends TestCase
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
    public function it_should_call_push_to_indices_method_from_algolia_manager_when_the_owner_is_saved()
    {
        $dummyModel = new SynchronousAutoIndexedDummyModel();
        $this->dummyAlgoliaManager->shouldReceive('pushToIndices')->once()->with($dummyModel);

        $dummyModel->trigger(ActiveRecord::EVENT_AFTER_INSERT);
    }

    /** @test */
    public function it_should_call_remove_from_indices_method_from_algolia_manager_when_the_owner_deleted()
    {
        $dummyModel = new SynchronousAutoIndexedDummyModel();
        $this->dummyAlgoliaManager->shouldReceive('removeFromIndices')->once()->with($dummyModel);

        $dummyModel->trigger(ActiveRecord::EVENT_AFTER_DELETE);
    }

    /** @test */
    public function it_should_call_update_in_indices_method_from_algolia_manager_when_the_owner_updated()
    {
        $dummyModel = new SynchronousAutoIndexedDummyModel();
        $this->dummyAlgoliaManager->shouldReceive('updateInIndices')->once()->with($dummyModel);

        $dummyModel->trigger(ActiveRecord::EVENT_AFTER_UPDATE);
    }

    /** @test */
    public function it_should_not_call_push_to_indices_if_afterInsert_is_set_to_false()
    {
        $dummyModel = new DummyActiveRecordModel();
        $dummyModel->attachBehavior('test', [
            'class' => SynchronousAutoIndexBehavior::class,
            'afterInsert' => false,
        ]);

        $this->dummyAlgoliaManager->shouldNotReceive('pushToIndices');

        $dummyModel->trigger(ActiveRecord::EVENT_AFTER_INSERT);
    }

    /** @test */
    public function it_should_not_call_remove_from_indices_if_afterDelete_is_set_to_false()
    {
        $dummyModel = new DummyActiveRecordModel();
        $dummyModel->attachBehavior('test', [
            'class' => SynchronousAutoIndexBehavior::class,
            'afterDelete' => false,
        ]);

        $this->dummyAlgoliaManager->shouldNotReceive('removeFromIndices');

        $dummyModel->trigger(ActiveRecord::EVENT_AFTER_DELETE);
    }

    /** @test */
    public function it_should_not_call_remove_from_indices_if_afterUpdate_is_set_to_false()
    {
        $dummyModel = new DummyActiveRecordModel();
        $dummyModel->attachBehavior('test', [
            'class' => SynchronousAutoIndexBehavior::class,
            'afterUpdate' => false,
        ]);

        $this->dummyAlgoliaManager->shouldNotReceive('updateInIndices');

        $dummyModel->trigger(ActiveRecord::EVENT_AFTER_UPDATE);
    }
}
