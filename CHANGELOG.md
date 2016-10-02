#Changelog

## 1.1.0
* The instance methods on `leinonen\Yii2Algolia\ActiveRecord\Searchable` were fixed to return the response from Algolia as expected instead of void. This includes methods:
    * `removeFromIndices()`
    * `updateInIndices()`
    * `reindex()`
    * `clearIndices()`
* `leinonen\Yii2Agolia\AlgoliaManager` and `leinonen\Yii2Algolia\ActiveRecord\Searchable` have now feature to do backend searches with `search()` method.
* Some of integration tests are now run against a real database