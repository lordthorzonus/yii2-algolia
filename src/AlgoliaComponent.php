<?php

namespace leinonen\Yii2Algolia;

use Yii;
use yii\base\Component;
use AlgoliaSearch\Client;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;

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
 * @method scopedCopyIndex(string $srcIndexName, string $dstIndexName, array $scope = [], array $requestHeaders = [])
 * @method mixed getLogs(int $offset = 0, int $length = 10, string $type = "all")
 * @method assignUserID($userID, $clusterName)
 * @method removeUserID($userID)
 * @method listClusters()
 * @method getUserID($userID)
 * @method listUserIDs($page = 0, $hitsPerPage = 20)
 * @method getTopUserID()
 * @method searchUserIDs($query, $clusterName = null, $page = null, $hitsPerPage = null)
 * @method \AlgoliaSearch\Index initIndex(string $indexName)
 * @method mixed listApiKeys()
 * @method mixed getApiKey(string $key)
 * @method mixed deleteApiKey(string $key)
 * @method mixed addApiKey(array $obj, int $validity = 0, int $maxQueriesPerIPPerHour = 0, int $maxHitsPerQuery = 0, array $indexes = null)
 * @method mixed updateApiKey(string $key, array $obj, int $validity = 0, int $maxQueriesPerIPPerHour = 0, int $maxHitsPerQuery = 0, array $indexes = null)
 * @method mixed batch(array $requests)
 * @method string generateSecuredApiKey(string $privateApiKey, mixed $query, string $userToken = null)
 * @method string buildQuery(array $args)
 * @method mixed request(\AlgoliaSearch\ClientContext $context, string $method, string $path, array $params, array $data, array $hostsArray, int $connectTimeout, int $readTimeout)
 * @method mixed doRequest(\AlgoliaSearch\ClientContext $context, string $method, string $path, array $params, array $data, array $hostsArray, int $connectTimeout, int $readTimeout)
 * @method \AlgoliaSearch\PlacesIndex initPlaces(string $appId = null, string $appKey = null, array $hostsArray = null, array $options = [])
 * @method getContext()
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
     * @var AlgoliaFactory
     */
    private $algoliaFactory;

    /**
     * Initiates a new AlgoliaComponent.
     *
     * @param AlgoliaFactory $algoliaFactory
     * @param array $config
     */
    public function __construct(
        AlgoliaFactory $algoliaFactory,
        $config = []
    ) {
        $this->algoliaFactory = $algoliaFactory;

        parent::__construct($config);
    }

    /**
     * Bootstrap method to be called during application bootstrap stage.
     *
     * @param Application $app the application currently running
     *
     * @throws InvalidConfigException
     */
    public function bootstrap($app)
    {
        if (empty($this->applicationId) || empty($this->apiKey)) {
            throw new InvalidConfigException('applicationId and apiKey are required');
        }

        Yii::$container->set(AlgoliaManager::class, function () {
            return $this->createManager();
        });
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
        if ($this->manager === null) {
            $this->manager = $this->createManager();
        }

        return \call_user_func_array([$this->manager, $method], $parameters);
    }

    /**
     * Returns a new AlgoliaManager.
     *
     * @return AlgoliaManager
     */
    private function createManager()
    {
        $config = $this->generateConfig();

        $algoliaManager = $this->algoliaFactory->make($config);
        $algoliaManager->setEnv($this->env);

        return $algoliaManager;
    }
    
    /**
     * Generates config for the Algolia Manager.
     *
     * @return AlgoliaConfig
     */
    private function generateConfig()
    {
        return new AlgoliaConfig(
            $this->applicationId,
            $this->apiKey,
            $this->hostsArray,
            $this->options
        );
    }
}
