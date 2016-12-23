#Changelog

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
