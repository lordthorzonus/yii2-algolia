<?php

namespace leinonen\Yii2Algolia\Tests\Unit;

use AlgoliaSearch\Client;
use leinonen\Yii2Algolia\AlgoliaComponent;
use leinonen\Yii2Algolia\AlgoliaFactory;
use leinonen\Yii2Algolia\AlgoliaManager;
use Yii;
use yiiunit\TestCase;
use Mockery as m;

class AlgoliaComponentTest extends TestCase
{
    /**
     * @var Client
     */
    private $mockAlgoliaClient;

    public function setUp()
    {
        parent::setUp();

        $mockAlgoliaFactory = m::mock(AlgoliaFactory::class);
        $this->mockAlgoliaClient = m::mock(Client::class);
        $mockAlgoliaFactory->shouldReceive('make')->andReturn($this->mockAlgoliaClient);
        Yii::$container->set(AlgoliaFactory::class, $mockAlgoliaFactory);

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

    public function tearDown()
    {
        m::close();

        // The mockWebApplication is called after a mock has been already set to the container
        // So we need to manually clear that.
        Yii::$container->clear(AlgoliaFactory::class);

        parent::tearDown();
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

        /** @var Client $client */
        $client = $manager->getClient();
        $this->assertEquals($this->mockAlgoliaClient, $client);

    }

    /** @test */
    public function it_delegates_the_methods_to_AlgoliaManager()
    {
        $this->assertEquals($this->mockAlgoliaClient, Yii::$app->algolia->getClient());
    }

    /**
     * @test
     * @expectedException \Exception
     * @expectedExceptionMessage applicationId and apiKey are required
     */
    public function it_throws_an_error_if_applicationId_is_not_specified()
    {
        $this->mockApplication([
            'bootstrap' => ['algolia'],
            'components' => [
                'algolia' => [
                    'class' => AlgoliaComponent::class,
                    'apiKey' => 'secret',
                ],
            ],
        ]);
    }

    /**
     * @test
     * @expectedException \Exception
     * @expectedExceptionMessage applicationId and apiKey are required
     */
    public function it_throws_an_error_if_apiKey_is_not_specified()
    {
        $this->mockApplication([
            'bootstrap' => ['algolia'],
            'components' => [
                'algolia' => [
                    'class' => AlgoliaComponent::class,
                    'applicationId' => 'app-id',
                ],
            ],
        ]);
    }
}
