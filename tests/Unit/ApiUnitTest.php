<?php

declare(strict_types=1);

use App\Repository\Api;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    /**
     * Бронирование заказа.
     */
    public function test_bookOrder(): void
    {
        $responseBooking = Api::bookOrder(
            1,
            '2021-04-09 20:21:21',
            800,
            1,
            500,
            0,
            '4551636043593409'
        );

        $this->assertJson($responseBooking);
    }

    /**
     * Подтверждение заказа.
     */
    public function test_approveOrder(): void
    {
        $responseApprove = Api::approveOrder('4551636043593409');

        $this->assertJson($responseApprove);
    }
}