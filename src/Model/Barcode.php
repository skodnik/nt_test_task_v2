<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Класс модели баркод.
 */
class Barcode
{
    /**
     * Создание баркода.
     *
     * @param int $userId
     * @return string
     */
    public static function getNew(int $userId): string
    {
        /**
         * Формирование баркода.
         */
        return $userId . time() . rand(100, 999);
    }
}