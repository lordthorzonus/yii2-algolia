# Yii2 Algolia
[![Latest Stable Version](https://poser.pugx.org/leinonen/yii2-algolia/version)](https://packagist.org/packages/leinonen/yii2-algolia) 
[![Latest Unstable Version](https://poser.pugx.org/leinonen/yii2-algolia/v/unstable)](//packagist.org/packages/leinonen/yii2-algolia) 
[![Total Downloads](https://poser.pugx.org/leinonen/yii2-algolia/downloads)](https://packagist.org/packages/leinonen/yii2-algolia)
[![License](https://poser.pugx.org/leinonen/yii2-algolia/license)](https://packagist.org/packages/leinonen/yii2-algolia)
[![Build Status](https://travis-ci.org/lordthorzonus/yii2-algolia.svg)](https://travis-ci.org/lordthorzonus/yii2-algolia)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0580d302-f028-45dc-8968-016b8aec786a/mini.png)](https://insight.sensiolabs.com/projects/0580d302-f028-45dc-8968-016b8aec786a)


Yii2 Algolia is an Algolia bridge for Yii2. It uses the [official Algolia Search API package](https://github.com/algolia/algoliasearch-client-php).

## Installation
Require this package, with [Composer](https://getcomposer.org/), in the root directory of your project.

```bash
composer require leinonen/yii2-algolia
```

### Configuration
Add the component to your application config. Also bootstrap the component.

```php
use leinonen\Yii2Algolia\AlgoliaComponent;
...
'bootstrap' => ['algolia'],
'components' => [
    'algolia' => [
        'class' => AlgoliaComponent::class,
        'applicationId' => 'test',
        'apiKey' => 'secret',
    ],
],
```

## Usage
The preferred way of using the package is through dependency injection. Just inject the `leinonen\Yii2Algolia\AlgoliaManager`. It has all the same methods available as the official Algolia Client (`AlgoliaSearch\Client`). The documentation can be found [here](https://github.com/algolia/algoliasearch-client-php).

```php

use leinonen\Yii2Algolia\AlgoliaManager;

class MyController
{
    public function actionExample(AlgoliaManager $manager)
    {
        $index = $manager->initIndex("contacts");
        $results = $index->search("query string");
    }
}

```

You can also access component like a Yii component.

```php
use Yii;

$index = Yii::$app->algolia->initIndex("contacts");
```
