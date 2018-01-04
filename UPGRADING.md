Yii2 Algolia Upgrade Guide
====================

1.3.0 to 2.0.0
--------------
- `leinonen\Yii2Algolia\AlgoliaComponent` constructor arguments have changed. If you are for some reason initializing the class manually you need to update the code.
- `leinonen\Yii2Algolia\AlgoliaFactory::make()` now returns instances of `leinonen\Yii2Algolia\AlgoliaManager` instead of the official Algolia client. If you are using this internal class for creating your own clients you need to create your own factory.  

0.9.6 to 1.0
----------
- `leinonen\Yii2Algolia\AlgoliaManager` requires an additional constructor argument `leinonen\Yii2Algolia\ActiveRecord\ActiveQueryChunker`
- `leinonen\Yii2Algolia\AlgoliaComponent` requires an additional constructor argument `leinonen\Yii2Algolia\ActiveRecord\ActiveQueryChunker`
- `leinonen\Yii2Algolia\AlgoliaManager::reindex()` Internal logic changed greatly. It now processes all the records in chunks. If you have overridden the method please double check the logic.
