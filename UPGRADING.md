Yii2 Algolia Upgrade Guide
====================

0.9.6 to 1.0
----------
- `leinonen\Yii2Algolia\AlgoliaManager` requires an additional constructor argument `leinonen\Yii2Algolia\ActiveRecord\ActiveQueryChunker`
- `leinonen\Yii2Algolia\AlgoliaComponent` requires an additional constructor argument `leinonen\Yii2Algolia\ActiveRecord\ActiveQueryChunker`
- `leinonen\Yii2Algolia\AlgoliaManager::reindex()` Internal logic changed greatly. It now processes all the records in chunks. If you have overridden the method please double check the logic.
