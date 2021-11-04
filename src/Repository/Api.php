<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * Класс осуществляющий взаимодействие с API.
 * В текущей реализации - имитатор.
 */
class Api
{
    /**
     * Метод заглушка имитирующий вызов API для бронирования заказа.
     *
     * @param int $event_id
     * @param string $event_date
     * @param int $ticket_adult_price
     * @param int $ticket_adult_quantity
     * @param int $ticket_kid_price
     * @param int $ticket_kid_quantity
     * @param string $barcode
     * @return string
     */
    public static function bookOrder(
        int $event_id,
        string $event_date,
        int $ticket_adult_price,
        int $ticket_adult_quantity,
        int $ticket_kid_price,
        int $ticket_kid_quantity,
        string $barcode
    ): string
    {
        $responses = [
            ['message' => 'order successfully booked'],
            ['error' => 'barcode already exists'],
        ];

        /**
         * Случайным образом возвращает один из возможных ответов.
         */
        return json_encode($responses[rand(0, count($responses) - 1)]);
    }

    /**
     * Метод заглушка имитирующий вызов API для подтверждения брони.
     *
     * @param string $barcode
     * @return string
     */
    public static function approveOrder(string $barcode): string
    {
        $responses = [
            ['message' => 'order successfully approved'],
            ['error' => 'event cancelled'],
            ['error' => 'no tickets'],
            ['error' => 'no seats'],
            ['error' => 'fan removed'],
        ];

        /**
         * Случайным образом возвращает один из возможных ответов.
         */
        return json_encode($responses[rand(0, count($responses) - 1)]);
    }
}