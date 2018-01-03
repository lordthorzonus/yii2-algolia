<?php

namespace leinonen\Yii2Algolia;

use AlgoliaSearch\Client;
use leinonen\Yii2Algolia\ActiveRecord\ActiveQueryChunker;
use leinonen\Yii2Algolia\ActiveRecord\ActiveRecordFactory;

class AlgoliaFactory
{
    /**
     * Makes a new Algolia Client.
     *
     * @param AlgoliaConfig $config
     *
     * @return AlgoliaManager
     *
     * @throws \Exception
     */
    public function make(AlgoliaConfig $config)
    {
        return new AlgoliaManager(
            new Client(
                $config->getApplicationId(),
                $config->getApiKey(),
                $config->getHostsArray(),
                $config->getOptions()
            ),
            new ActiveRecordFactory(),
            new ActiveQueryChunker()
        );
    }

    /**
     * Returns a new instance for given class which must implement the SearchableInterface.
     *
     * @param string $className
     *
     * @return SearchableInterface
     *
     * @throws \InvalidArgumentException
     */
    public function makeSearchableObject($className)
    {
        if (! (new \ReflectionClass($className))->implementsInterface(SearchableInterface::class)) {
            throw new \InvalidArgumentException("Cannot initiate a class ({$className}) which doesn't implement leinonen\\Yii2Algolia\\SearchableInterface");
        }

        return new $className();
    }
}
