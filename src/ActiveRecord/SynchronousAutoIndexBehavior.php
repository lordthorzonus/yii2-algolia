<?php

namespace leinonen\Yii2Algolia\ActiveRecord;

use leinonen\Yii2Algolia\AlgoliaManager;
use Yii;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;

class SynchronousAutoIndexBehavior extends Behavior
{
    /**
     * If the model should be removed from index after delete.
     *
     * @var bool
     */
    public $afterDelete = true;

    /**
     * If the model should be added to index after insert.
     *
     * @var bool
     */
    public $afterInsert = true;

    /**
     * If the model should be updated also to index after update.
     *
     * @var bool
     */
    public $afterUpdate = true;

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => function (Event $event) {
                if ($this->afterInsert) {
                    /** @var $manager AlgoliaManager */
                    $manager = Yii::$container->get(AlgoliaManager::class);
                    $manager->pushToIndices($event->sender);
                }
            },
            ActiveRecord::EVENT_AFTER_DELETE => function (Event $event) {
                if ($this->afterDelete) {
                    /** @var $manager AlgoliaManager */
                    $manager = Yii::$container->get(AlgoliaManager::class);
                    $manager->removeFromIndices($event->sender);
                }
            },
            ActiveRecord::EVENT_AFTER_UPDATE => function (Event $event) {
                if ($this->afterUpdate) {
                    /** @var $manager AlgoliaManager */
                    $manager = Yii::$container->get(AlgoliaManager::class);
                    $manager->updateInIndices($event->sender);
                }
            },
        ];
    }
}
