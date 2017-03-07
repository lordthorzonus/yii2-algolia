#Changelog

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
