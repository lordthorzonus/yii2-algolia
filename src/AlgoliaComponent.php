<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Client;
use leinonen\Yii2Algolia\ActiveRecord\ActiveRecordFactory;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;

/**
 * @method Client getClient()
 * @method array getConfig()
 * @method pushToIndices(SearchableInterface $searchableModel)
 * @method updateInIndices(SearchableInterface $searchableModel)
 * @method removeFromIndices(SearchableInterface $searchableModel)
 * @method reindex(string $className)
 * @method clearIndices(string $className)
 * @method setConnectTimeout(int $connectTimeout, int $timeout = 30, int $searchTimeout = 5)
 * @method enableRateLimitForward(string $adminAPIKey, string $endUserIP, string $rateLimitAPIKey)
 * @method setForwarderFor(string $ip)
 * @method setAlgoliaUserToken(string $token)
 * @method disableRateLimitForward()
 * @method isAlive()
 * @method setExtraHeader(string $key, string $value)
 * @method mixed multipleQueries(array $queries, string $indexNameKey = "indexName", string $strategy = "none")
 * @method mixed listIndexes()
 * @method deleteIndex(string $indexName)
 * @method mixed moveIndex(string $srcIndexName, string $dstIndexName)
 * @method mixed copyIndex(string $srcIndexName, string $dstIndexName)
 * @method mixed getLogs(int $offset = 0, int $length = 10, string $type = "all")
 * @method \AlgoliaSearch\Index initIndex(string $indexName)
 * @method mixed listUserKeys()
 * @method mixed getUserKeyACL(string $key)
 * @method mixed deleteUserKey(string $key)
 * @method mixed addUserKey(array $obj, int $validity = 0, int $maxQueriesPerIPPerHour = 0, int $maxHitsPerQuery = 0, array $indexes = null)
 * @method mixed updateUserKey(string $key, array $obj, int $validity = 0, int $maxQueriesPerIPPerHour = 0, int $maxHitsPerQuery = 0, array $indexes = null)
 * @method mixed batch(array $requests)
 * @method string generateSecuredApiKey(string $privateApiKey, mixed $query, string $userToken = null)
 * @method string buildQuery(array $args)
 * @method mixed request(Client $context, string $method, string $path, array $params, array $data, array $hostsArray, int $connectTimeout, int $readTimeout)
 * @method mixed doRequest(Client $context, string $method, string $path, array $params, array $data, array $hostsArray, int $connectTimeout, int $readTimeout)
 * @method \AlgoliaSearch\PlacesIndex initPlaces(string $appId, string $appKey, array $hostsArray = null, array $options = [])
 * @see Client
 * @see AlgoliaManager
 */
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
     * @var null|string
     */
    public $env = null;

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
     * @return AlgoliaManager
     */
    protected function createManager()
    {
        $factory = new AlgoliaFactory();
        $activeRecordFactory = new ActiveRecordFactory();
        $config = [
            'applicationId' => $this->applicationId,
            'apiKey' => $this->apiKey,
            'hostsArray' => $this->hostsArray,
            'options' => $this->options,
        ];

        $algoliaManager = new AlgoliaManager($factory, $activeRecordFactory, $config);
        $algoliaManager->setEnv($this->env);

        return $algoliaManager;
    }

    /**
     * Dynamically pass methods to the AlgoliaManager.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->manager, $method], $parameters);
    }
}
