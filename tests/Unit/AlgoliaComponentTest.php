<?php

namespace leinonen\Yii2Algolia\Tests\Unit;

use leinonen\Yii2Algolia\AlgoliaComponent;
use leinonen\Yii2Algolia\AlgoliaManager;
use Yii;
use yiiunit\TestCase;

class AlgoliaComponentTest extends TestCase
{
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
    }

    /** @test */
    public function it_accessible_like_proper_yii2_component()
    {
        $this->assertInstanceOf(AlgoliaComponent::class, Yii::$app->algolia);
    }

    /** @test */
    public function it_registers_AlgoliaManager_to_di_container_properly()
    {
        $manager = Yii::$container->get(AlgoliaManager::class);
        $this->assertInstanceOf(AlgoliaManager::class, $manager);
        $this->assertEquals([
            'applicationId' => 'test',
            'apiKey' => 'secret',
            'hostsArray' => null,
            'options' => [],
        ], $manager->getConfig());
    }

    /** @test */
    public function it_delegates_the_methods_to_AlgoliaManager()
    {
        $this->assertEquals([
            'applicationId' => 'test',
            'apiKey' => 'secret',
            'hostsArray' => null,
            'options' => [],
        ], Yii::$app->algolia->getConfig());
    }
}
