<?php

declare(strict_types=1);

namespace App\Model;

class OrderProcessingReport
{
    private array $report;

    /**
     * Добавляет запись в массив отчета.
     *
     * @param int $iteration
     * @param int $step
     * @param string $title
     * @param string $message
     * @return $this
     */
    public function push(int $iteration, int $step, string $title, string $message): OrderProcessingReport
    {
        $this->report[$iteration][$step][$title] = $message;

        return $this;
    }

    /**
     * Возвращает отчет в формате массива.
     *
     * @return array
     */
    public function getArray(): array
    {
        if (!isset($this->report)) {
            throw new \RuntimeException('Empty report');
        }

        return $this->report;
    }

    /**
     * Возвращает отчет в формате json.
     *
     * @param bool $pretty
     * @param bool $unescaped
     * @return string
     */
    public function getJson(bool $pretty = false, bool $unescaped = false): string
    {
        $flags = ($pretty ? JSON_PRETTY_PRINT : 0) | ($unescaped ? JSON_UNESCAPED_UNICODE : 0);

        return json_encode($this->getArray(), $flags);
    }
}