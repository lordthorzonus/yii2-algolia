# Changelog

## 2.0.0
This is a breaking release. All the changes have been marked with probability will it affect your code.

Nothing from the `AlgoliaManager`'s public api is changed so if you are not extending this packages internal classes or using them in some really custom way you should be safe to upgrade without changes.

* `init()` removed from the AlgoliaComponent ![probably won't](https://img.shields.io/badge/will%20it%20affect%20me%3F-probably%20won't-green.svg)
* `createManager()` visibility changed to private in AlgoliaComponent ![probably won't](https://img.shields.io/badge/will%20it%20affect%20me%3F-probably%20won't-green.svg)
* `yii\base\InvalidConfigException` is thrown instead of `Exception` in case of invalid configuration for the AlgoliaComponent ![probably won't](https://img.shields.io/badge/will%20it%20affect%20me%3F-probably%20won't-green.svg)
* AlgoliaComponent is now only dependent on AlgoliaFactory. ![maybe won't](https://img.shields.io/badge/will%20it%20affect%20me%3F-maybe%20won't-yellowgreen.svg)
* `leinonen\Yii2Algolia\AlgoliaFactory::make()`  now returns instances of AlgoliaManager instead of `AlgoliaSearch\Client` ![maybe won't](https://img.shields.io/badge/will%20it%20affect%20me%3F-maybe%20won't-yellowgreen.svg)

Additions and other changes:

 * Updated the package to depend on ^1.25.0 versions of the [official Algolia client](https://github.com/algolia/algoliasearch-client-php) and added all the new methods to the @method phpdocs.

## 1.3.0
* Updated the package to depend on ^1.16.0 versions of the [official Algolia client](https://github.com/algolia/algoliasearch-client-php)  
* Removed methods marked as deprecated from the `leinonen\Yii2Algolia\AlgoliaManager` & `leinonen\Yii2Algolia\AlgoliaManager` @method phpdocs. The methods are still found in the `AlgoliaSearch\Client` so no existing code will break, just no IDE autocompletion anymore. Expect these to be removed in 2.0.0.  This includes methods:
   * `listUserKeys()`
   * `getUserKeyACL()`
   * `deleteUserKey()`
   * `addUserKey()`
   * `updateUserKey()`
* Added @method phpdocs for the new corresponding api key methods:
   * `listApiKeys()`
   * `getApiKey()`
   * `deleteApiKey()`
   * `addApiKey()`
   * `updateApiKey()`

## 1.2.0
* Small documentation changes
* Minor refactorings to make the code prettier 

## 1.1.1
* Added FQN to all global functions for micro-optimization. See: https://github.com/Roave/FunctionFQNReplacer for more info.
* Documentation about `getObjectID()`

## 1.1.0
* The instance methods on `leinonen\Yii2Algolia\ActiveRecord\Searchable` were fixed to return the response from Algolia as expected instead of void. This includes methods:
    * `removeFromIndices()`
    * `updateInIndices()`
    * `reindex()`
    * `clearIndices()`
* `leinonen\Yii2Agolia\AlgoliaManager` and `leinonen\Yii2Algolia\ActiveRecord\Searchable` have now feature to do backend searches with `search()` method.
* Some of integration tests are now run against a real database
* All of the base methods are now covered by integration tests. 
