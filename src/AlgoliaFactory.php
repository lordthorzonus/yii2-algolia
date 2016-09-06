<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Client;

class AlgoliaFactory
{
    /**
     * Makes a new Algolia Client.
     *
     * @param AlgoliaConfig $config
     *
     * @return Client
     */
    public function make(AlgoliaConfig $config)
    {
        return new Client(
            $config->getApplicationId(),
            $config->getApiKey(),
            $config->getHostsArray(),
            $config->getOptions()
        );
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
}
