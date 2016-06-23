<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Client;
use InvalidArgumentException;

class AlgoliaFactory
{
    /**
     * Makes a new Algolia Client.
     *
     * @param array $config
     *
     * @return Client
     */
    public function make(array $config)
    {
        list($applicationId, $apiKey, $hostsArray, $options) = $this->getConfig($config);

        return $this->createClient($applicationId, $apiKey, $hostsArray, $options);
    }

    /**
     * Returns a new instance for given class which must implement the SearchableInterface.
     *
     * @param string $className
     *
     * @return SearchableInterface
     */
    public function makeSearchableObject($className)
    {
        if (! (new \ReflectionClass($className))->implementsInterface(SearchableInterface::class)) {
            throw new \InvalidArgumentException("Cannot initiate a class ({$className}) which doesn't implement leinonen\\Yii2Algolia\\SearchableInterface");
        }

        return new $className();
    }

    /**
     * Creates an new Algolia Client.
     *
     * @param string $applicationId The application ID you have in your admin interface
     * @param string $apiKey A valid API key for the service
     * @param null|array $hostsArray The list of hosts that you have received for the service
     * @param array $options
     *
     * @return Client
     */
    protected function createClient($applicationId, $apiKey, $hostsArray = null, $options = [])
    {
        return new Client($applicationId, $apiKey, $hostsArray, $options);
    }

    /**
     * Returns the configurations for the Algolia Client in correct format.
     * @param array $config
     *
     * @return array
     */
    protected function getConfig(array $config)
    {
        if (! array_key_exists('applicationId', $config) || ! array_key_exists('apiKey', $config)) {
            throw new InvalidArgumentException('Configuration keys applicationId and apiKey are required');
        }

        $applicationId = $config['applicationId'];
        $apiKey = $config['apiKey'];
        $hostsArray = null;
        $options = [];

        if (array_key_exists('hostsArray', $config)) {
            $hostsArray = $config['hostsArray'];
        }

        if (array_key_exists('options', $config)) {
            $options = $config['options'];
        }

        return [$applicationId, $apiKey, $hostsArray, $options];
    }
}
