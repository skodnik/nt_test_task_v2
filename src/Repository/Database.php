<?php

declare(strict_types=1);

namespace App\Repository;

use App\App;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

class Database
{
    private Connection $connection;

    /**
     * Инициализация подключения.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'user' => '',
            'password' => '',
            'path' => __DIR__ . '/../../storage/database/' . App::env('DATABASE_NAME'),
        ];

        $this->connection = DriverManager::getConnection($connectionParams);
    }

    /**
     * Возвращает экземпляр объекта подключения к базе данных.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}