<?php

declare(strict_types=1);

use App\Model\Barcode;
use PHPUnit\Framework\TestCase;

class BarcodeTest extends TestCase
{
    /**
     * Генерация баркода и проверка его структуры.
     */
    public function test_getNewBarcode(): void
    {
        $userId = 451;
        $barcode = Barcode::getNew($userId);

        $this->assertStringStartsWith((string)$userId, $barcode);
    }
}