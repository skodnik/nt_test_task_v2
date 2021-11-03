<?php

declare(strict_types=1);

namespace App\Factory;

use App\Repository\Database;

/**
 * Класс фабрики объекта базы данных.
 */
class DataBaseFactory
{
    /**
     * Получение экземпляра объекта базы данных.
     *
     * @return Database
     */
    public static function createDatabase(): Database
    {
        return new Database();
    }
}