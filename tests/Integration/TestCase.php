<?php


namespace leinonen\Yii2Algolia\Tests\Integration;


use yii\db\Connection;

class TestCase extends \yiiunit\TestCase
{
    protected $databaseConfig;

    public function setUp()
    {
        $dsn = getenv('DB_DRIVER') . ':host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME');

        $this->databaseConfig = [
            'class' => Connection::class,
            'dsn' => $dsn,
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => 'utf8',
        ];

        parent::setUp();
    }


}
