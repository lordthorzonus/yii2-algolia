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

You can also access the manager like a Yii component.

```php
use Yii;

$index = Yii::$app->algolia->initIndex("contacts");
```

## ActiveRecord Helpers
**Note that this is still very much work in progress**.
This package also provides helpers for dealing with Yii's ActiveRecord Models.

### Configuring an ActiveRecord Class
To use the helpers just implement the `leinonen\Yii2Algolia\ActiveRecord\SearchableInterface`. The `leinonen\Yii2Algolia\Searchable` trait provides everything that you need. You can control what fields are indexed to Algolia by using the `fields()` and `extraFields()` methods like you normally would. You can also override the `getAlgoliaRecord()` for more custom use cases.

```php
use leinonen\Yii2Algolia\ActiveRecord\Searchable;
use leinonen\Yii2Algolia\ActiveRecord\SearchableInterface;
use yii\db\ActiveRecord;

class Contact extends ActiveRecord implements SearchableInterface
{
    use Searchable;
}
```

By default the helpers will use the class name as the name of the index. You can also specify the indices you want to sync the clas to:
```php
class Contact extends ActiveRecord implements SearchableInterface
{
    use Searchable;
    
    public function indices()
    {
        return ['first_index', 'second_index'];
    }
}
```
###Indexing

####Manual Indexing
You can trigger indexing using the `index()` instance method.

```php
$contact = new Contact();
$contact->name = 'test';
$contact->index();
```

####Manual Removal
And trigger the removing using the `removeFromIndex()` instance method.

```php
$contact = Contact::findOne(['name' => 'test');
$contact->removeFromIndex();
```

####Re-indexing
To safely reindex all your ActiveRecord models(index to a temporary index + move the temporary index to the current one atomically), use the `reIndex()` method found in `leinonen\Yii2Algolia\AlgoliaManager` class:

```php
$manager->reIndex(new Contact());
```
Note that the manager requires and actual instance of the ActiveRecord class
 
####Clearing Indices
To clear indices where the class is synced use the `clearIndices()` method found in `leinonen\Yii2Algolia\AlgoliaManager` class:

```php
$manager->clearIndices(new Contact());
```
