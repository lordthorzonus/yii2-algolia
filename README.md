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
* [Backend search](#backend-search)
* [Using multiple environments](#using-multiple-environments) 
* [Contributing](#contributing)

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
The preferred way of using the package is through dependency injection. Just inject the `leinonen\Yii2Algolia\AlgoliaManager`. 

It has all the same methods available as the official Algolia Client (`AlgoliaSearch\Client`). The documentation can be found [here](https://github.com/algolia/algoliasearch-client-php). The manager class delegates all the methods to the original Client and provides some addittional helpers on top.

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

It's also possible to index multiple models of the same class in a batch with the service's `pushMultipleToIndices()`.

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
$contact = Contact::findOne(['name' => 'test']);
$manager->removeFromIndices($contact);
```

It's also possible to delete multiple models of the same class in a batch with the service's `removeMultipleFromIndices()`

```php
$contacts = Contact::find()->where(['type' => Contact::TYPE_AWESOME])->all();
$manager->removeMultipleFromIndices($contacts);
```

####Manual Updating
Update is triggered using the `updateInIndices()` instance method.

```php
$contact = Contact::findOne(['name' => 'test']);
$contact->updateInIndices();
```

Or with the service:
```php
$contact = Contact::findOne(['name' => 'test']);
$manager->updateInIndices($contact);
```

It's also possible to update multiple models of the same class in a batch with the service's `updateMultipleInIndices()`.

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
 
 In background reindexing is done by chunking through all of the ActiveRecord models of the given class, 500 objects at time. 
 This means you can safely use reindexing even over really large datasets without consuming too much memory. Just mind your Algolia quota.
 
#####Reindexing By ActiveQuery
 If you need to index a lot of related relationships to Algolia you can use the powerful `reindexByActiveQuery()` method found in `leinonen\Yii2Algolia\AlgoliaManager` class:
 ```php
 $contactsQuery = Contact::find()->joinWith('company')->where(['company_name' => 'Algolia']);
 $manager->reindexByActiveQuery($contactsQuery);
 ```   
 
 The `reindexByActiveQuery()` method also uses chunking in background, so it's safe to do query's over really big datasets. The indices to be reindexed will be resolved from the result of the queries models. 
 
 To get the relations indexed into Algolia you of course need to also modify the `getAlgoliaRecord()` or the `fields()` method from the ActiveRecord model.
 Yii provides a handy `isRelationPopulated()` method for customizing this:
 
 ```php
 class Contact extends ActiveRecord implements SearchableInterface
 {
    use Searchable;
    
    /**
     * {@inheritdoc}
     */
    public function getAlgoliaRecord()
    {
        $record = $this->toArray();
        
        if($this->isRelationPopulated('company')) {
            $record['company'] = $this->company->toArray();
        }
        
        return $record;
    }
 }
``` 
 
#####Reindexing With a set of explicit SearchableModels
 It's also possible to explicitly define which objects should be reindexed. This can be done by using `reindexOnly()` method found in `leinonen\Yii2Algolia\AlgoliaManager` class:
 ```php
 $contacts = Contact::find()->where(['type' => Contact::TYPE_AWESOME])->all();
 $manager->reindexOnly($contacts);
 ```
In the background the method figures out the indices of that need to be reindexed and therefore the array must consist only models of a same class. Be wary of the memory consumption if you are fetching a lot of ActiveRecords this way.
 
 
 
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

### Backend Search
Like [Algolia](https://github.com/algolia/algoliasearch-client-php#search-in-an-index---search) I would strongly recommend using Algolia's [JavaScript client](https://github.com/algolia/algoliasearch-client-js)
for the best search experience. You can however use some helpers for doing search on php side:

With the service: 

```php
$manager->search(Contact::class, 'John Doe');
```

The method also accepts [optional search parameters](https://github.com/algolia/algoliasearch-client-php#search-parameters):

```php
$manager->search(Contact::class, 'John Doe', ['hitsPerPage' => 2, 'attributesToRetrieve' => 'name,address']);
```

Searching is also available from a ActiveRecord class that uses the `leinonen\Yii2Algolia\ActiveRecord\Searchable` trait:

```php
Contact::search('John Doe');
Contact::search('John Doe', ['hitsPerPage' => 2, 'attributesToRetrieve' => 'name,address']);
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

## Contributing
Pull requests are welcome! Have a look at the [CONTRIBUTING.md](https://github.com/lordthorzonus/yii2-algolia/blob/master/CONTRIBUTING.md) document for some instructions.
