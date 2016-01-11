<?php

namespace leinonen\Yii2Algolia\Tests;

use AlgoliaSearch\Client;
use leinonen\Yii2Algolia\AlgoliaFactory;

class AlgoliaFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_create_a_new_Algolia_Client_instance()
    {
        $factory = new AlgoliaFactory();
        $client = $factory->make([
            'applicationId' => 'app-id',
            'apiKey' => 'secret',
        ]);

        $this->assertInstanceOf(Client::class, $client);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Configuration keys applicationId and apiKey are required
     */
    public function it_should_throw_an_exception_if_applicationId_is_not_defined()
    {
        $factory = new AlgoliaFactory();
        $client = $factory->make([
            'apiKey' => 'secret',
        ]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Configuration keys applicationId and apiKey are required
     */
    public function it_should_throw_an_exception_if_apiKey_is_not_defined()
    {
        $factory = new AlgoliaFactory();
        $client = $factory->make([
            'applicationId' => 'app-id',
        ]);
    }
}
