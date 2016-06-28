# Yii2 Algolia
[![Latest Stable Version](https://poser.pugx.org/leinonen/yii2-algolia/version)](https://packagist.org/packages/leinonen/yii2-algolia) 
[![Latest Unstable Version](https://poser.pugx.org/leinonen/yii2-algolia/v/unstable)](//packagist.org/packages/leinonen/yii2-algolia) 
[![Total Downloads](https://poser.pugx.org/leinonen/yii2-algolia/downloads)](https://packagist.org/packages/leinonen/yii2-algolia)
[![License](https://poser.pugx.org/leinonen/yii2-algolia/license)](https://packagist.org/packages/leinonen/yii2-algolia)
[![Build Status](https://travis-ci.org/lordthorzonus/yii2-algolia.svg)](https://travis-ci.org/lordthorzonus/yii2-algolia)
[![Code Coverage](https://scrutinizer-ci.com/g/lordthorzonus/yii2-algolia/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lordthorzonus/yii2-algolia/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lordthorzonus/yii2-algolia/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lordthorzonus/yii2-algolia/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0580d302-f028-45dc-8968-016b8aec786a/mini.png)](https://insight.sensiolabs.com/projects/0580d302-f028-45dc-8968-016b8aec786a)


Yii2 Algolia is an Algolia bridge for Yii2. It uses the [official Algolia Search API package](https://github.com/algolia/algoliasearch-client-php).

Table of contents
=================

* [Installation](#installation)
* [Usage](#usage)
* [ActiveRecordHelpers](#activerecord-helpers)
* [Indexing](#indexing)
    * [Manual Indexing](#manual-indexing)
    * [Manual Removal](#manual-removal)
    * [Manual Updating](#manual-updating)
    * [Reindexing](#reindexing)
    * [Clearing Indices](#clearing-indices)
    * [Auto-indexing](#auto-indexing)
* [Using multiple environments](#using-multiple-environments) 
    

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

    private $algoliaManager;

    public function __construct($id, $module, AlgoliaManager $algoliaManager, $config = [])
    {
        $this->algoliaManager = $algoliaManager;
        parent::__construct($id, $module, $config);
    }

    public function actionExample()
    {
        $index = $this->algoliaManager->initIndex("contacts");
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
This package also provides helpers for dealing with Yii's ActiveRecord Models.

### Configuring an ActiveRecord Class
To use the helpers just implement the `leinonen\Yii2Algolia\SearchableInterface`. The `leinonen\Yii2Algolia\Searchable` trait provides everything that you need. You can control what fields are indexed to Algolia by using the `fields()` and `extraFields()` methods like you normally would. You can also override the `getAlgoliaRecord()` for more custom use cases.

```php
use leinonen\Yii2Algolia\ActiveRecord\Searchable;
use leinonen\Yii2Algolia\SearchableInterface;
use yii\db\ActiveRecord;

class Contact extends ActiveRecord implements SearchableInterface
{
    use Searchable;
}
```

By default the helpers will use the class name as the name of the index. You can also specify the indices you want to sync the class to:
```php
class Contact extends ActiveRecord implements SearchableInterface
{
    use Searchable;
    
    /**
     * {@inheritdoc}
     */
    public function indices()
    {
        return ['first_index', 'second_index'];
    }
}
```

By default the model is converted into an array in background with Yii's `toArray()` method. If you want to customize it you can override the `getAlgoliaRecord() method`.
```php
class Contact extends ActiveRecord implements SearchableInterface
{
    use Searchable;
    
    /**
     * {@inheritdoc}
     */
    public function getAlgoliaRecord()
    {
        return array_merge($this->toArray(), ['someStaticValue' => "It's easy"]);
    }
}
```


You can also also implement the `leinonen\Yii2Algolia\SearchableInterface` for plain old PHP objects and then use the `leinonen\Yii2Algolia\AlgoliaManager` to control them. Note that all helpers are not available for use other than with ActiveRecord classes.

###Indexing

####Manual Indexing
You can trigger indexing using the `index()` instance method on an ActiveRecord model with the help of `leinonen\Yii2Algolia\Searchable` trait.

```php
$contact = new Contact();
$contact->name = 'test';
$contact->index();
```

Or if you fancy a more service like architecture, you can use the methods on `leinonen\Yii2Algolia\AlgoliaManager`:

```php
$contact = new Contact();
$contact->name = 'test';
$manager->pushToIndices($contact);
```

It's also possible to index multiple models of the same type in a batch with the service's `pushMultipleToIndices()`.

```php
$contact1 = new Contact();
$contact1->name = 'test';

$contact2 = new Contact();
$contact2->name = 'anotherTest';

$manager->pushMultipleToIndices([$contact1, $contact2]);
```

####Manual Removal
Removing is triggered using the `removeFromIndices()` instance method.

```php
$contact = Contact::findOne(['name' => 'test');
$contact->removeFromIndices();
```

Or with the service:
```php
$contact = Contact::findOne(['name' => 'test');
$manager->removeFromIndices($contact);
```

####Manual Updating
Update is triggered using the `updateInIndices()` instance method.

```php
$contact = Contact::findOne(['name' => 'test');
$contact->updateInIndices();
```

Or with the service:
```php
$contact = Contact::findOne(['name' => 'test');
$manager->updateInIndices($contact);
```

It's also possible to update multiple models of the same type in a batch with the service's `updateMultipleInIndices()`.

```php
$contacts = Contact::find()->where(['type' => Contact::TYPE_AWESOME])->all();
foreach($contacts as $contact) {
  $contact->type = Contact::TYPE_NOT_SO_AWESOME;
}
$manager->updateMultipleInIndices($contacts);
```

####Reindexing
To safely reindex all your ActiveRecord models(index to a temporary index + move the temporary index to the current one), use the `leinonen\Yii2Algolia\AlgoliaManager::reindex()` method:

```php
$manager->reindex(Contact::class);
```

You can also use the static method on ActiveRecord class if you prefer Yii's style:

```php
Contact::reindex();
```
 
####Clearing Indices
To clear indices where the ActiveRecord is synced to, use the `clearIndices()` method found in `leinonen\Yii2Algolia\AlgoliaManager` class:

```php
$manager->clearIndices(Contact::class);
```

You can also use the static method on ActiveRecord class if you prefer Yii's style:

```php
Contact::clearIndices();
```

###Auto-indexing
Another solution is to attach the `leinonen\Yii2Algolia\ActiveRecord\SynchronousAutoIndexBehavior` behavior to the ActiveRecord model. This behavior will then trigger automatically when the model is created, updated or deleted. The model needs of course to implement the `leinonen\Yii2Algolia\SearchableInterface` via the mentioned trait or your custom methods.

**Beware that the Algolia API will be called every time separately when something happens to the specified ActiveRecord model. This can cause performance issues.** At the moment Yii2 doesn't provide queues out of the box, so asynchronous updating isn't available.

####Configuration
```php
use leinonen\Yii2Algolia\ActiveRecord\Searchable;
use leinonen\Yii2Algolia\SearchableInterface;
use leinonen\Yii2Algolia\ActiveRecord\SynchronousAutoIndexBehavior;
use yii\db\ActiveRecord;

class Contact extends ActiveRecord implements SearchableInterface
{
    use Searchable;
    
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            SynchronousAutoIndexBehavior::class,
        ];
    }
}
```

You can also explicitly turn off events for insert, update or delete with props `afterInsert`, `afterUpdate`, `afterDelete`:

```php
public function behaviors()
{
    return [
        [
            'class' => SynchronousAutoIndexBehavior::class,
            'afterInsert' => false,
            'afterUpdate' => false,
        ],
    ];
}
```

###Using multiple environments
You can automatically prefix all the index names with the current App environment using the following configuration:

```php
use leinonen\Yii2Algolia\AlgoliaComponent;
...
'bootstrap' => ['algolia'],
'components' => [
    'algolia' => [
        'class' => AlgoliaComponent::class,
        'applicationId' => 'test',
        'apiKey' => 'secret',
        'env' => YII_ENV
    ],
],
```
Then when using any of the helpers methods from `leinonen\Yii2Algolia\AlgoliaManager` the environment will be prefixed to the index name. Also using the helper methods found on `leinonen\Yii2Algolia\Searchable` trait will work. **Note if you use methods straight from [The Official Algolia Client](https://github.com/algolia/algoliasearch-client-php) the env config will have no effect.** 
