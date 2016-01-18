<?php


namespace leinonen\Yii2Algolia\ActiveRecord;


use yii\db\ActiveRecordInterface;

class ActiveRecordFactory
{
    /**
     * Returns an new ActiveRecordInterface instance.
     *
     * @param string $className
     *
     * @return ActiveRecordInterface
     */
    public function make($className)
    {
        if(! (new \ReflectionClass($className))->implementsInterface(ActiveRecordInterface::class)){
            throw new \InvalidArgumentException("Cannot initiate a class ({$className}) which doesn't implement \\yii\\db\\ActiveRecordInterface");
        }

        return new $className();
    }
}