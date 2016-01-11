<?php

namespace leinonen\Yii2Algolia;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;

class AlgoliaComponent extends Component implements BootstrapInterface
{
    /**
     * @var string The application ID you have in your admin interface
     */
    public $applicationId;

    /**
     * @var string A valid API key for the service
     */
    public $apiKey;

    /**
     * @var null|array The list of hosts that you have received for the service
     */
    public $hostsArray = null;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var AlgoliaManager
     */
    protected $manager;

    /**
     * Bootstrap method to be called during application bootstrap stage.
     *
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        Yii::$container->set(AlgoliaManager::class, function () {
             return $this->createManager();
        });
    }

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
        $this->manager = $this->createManager();
    }

    /**
     * Returns a new AlgoliaManager.
     *
     * @return array
     */
    protected function createManager()
    {
        $factory = new AlgoliaFactory();
        $config = [
            'applicationId' => $this->applicationId,
            'apiKey' => $this->apiKey,
            'hostsArray' => $this->hostsArray,
            'options' => $this->options,
        ];

        return new AlgoliaManager($factory, $config);
    }

    /**
     * Dynamically pass methods to the AlgoliaManager.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->manager, $method], $parameters);
    }
}
