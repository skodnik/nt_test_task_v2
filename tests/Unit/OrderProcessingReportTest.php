<?php

declare(strict_types=1);

use App\Model\OrderProcessingReport;
use PHPUnit\Framework\TestCase;

class OrderProcessingReportTest extends TestCase
{
    /**
     * Создание объекта отчета.
     *
     * @return OrderProcessingReport
     */
    public function test_createReport(): OrderProcessingReport
    {
        $report = new OrderProcessingReport();

        $this->assertInstanceOf(OrderProcessingReport::class, $report);

        return $report;
    }

    /**
     * Проверка исключения при попытке получения пустого отчета.
     *
     * @depends test_createReport
     */
    public function test_getException(OrderProcessingReport $report)
    {
        $this->expectException(\RuntimeException::class);

        $report->getArray();
    }

    /**
     * Внесение записи в отчет.
     *
     * @depends test_createReport
     */
    public function test_push(OrderProcessingReport $report): OrderProcessingReport
    {
        $report->push(1,1,'test title', 'test message');

        $this->assertInstanceOf(OrderProcessingReport::class, $report);

        return $report;
    }

    /**
     * Получение массива содержащего отчет.
     *
     * @depends test_push
     */
    public function test_getArray(OrderProcessingReport $report): OrderProcessingReport
    {
        $expected = [
            1 => [
                1 => ['test title' => 'test message']
            ]
        ];

        $this->assertEquals($expected, $report->getArray());

        return $report;
    }

    /**
     * Получение json строки содержащей отчет.
     *
     * @depends test_getArray
     */
    public function test_getJson(OrderProcessingReport $report)
    {
        $expected = '{"1":{"1":{"test title":"test message"}}}';
        $actual = $report->getJson();

        $this->assertJson($actual);
        $this->assertEquals($expected, $actual);
    }
}