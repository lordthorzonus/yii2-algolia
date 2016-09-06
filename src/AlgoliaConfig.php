<?php

namespace leinonen\Yii2Algolia;

class AlgoliaConfig
{
    /**
     * @var string
     */
    private $applicationId;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var array|null
     */
    private $hostsArray;

    /**
     * @var array
     */
    private $options;

    /**
     * Initiates a new AlgoliaConfig.
     *
     * @param string $applicationId The application ID you have in your admin interface
     * @param string $apiKey A valid API key for the service
     * @param null|array $hostsArray The list of hosts that you have received for the service
     * @param array $options
     */
    public function __construct($applicationId, $apiKey, $hostsArray = null, $options = [])
    {
        $this->applicationId = $applicationId;
        $this->apiKey = $apiKey;
        $this->hostsArray = $hostsArray;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getApplicationId()
    {
        return $this->applicationId;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return array|null
     */
    public function getHostsArray()
    {
        return $this->hostsArray;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
