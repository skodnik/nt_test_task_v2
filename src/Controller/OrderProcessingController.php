<?php

declare(strict_types=1);

namespace App\Controller;

use App\App;
use App\Factory\DataBaseFactory;
use App\Model\Barcode;
use App\Model\Order;
use App\Model\OrderProcessingReport;
use App\Repository\Api;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use RuntimeException;

/**
 * Класс обработчик заказа.
 */
class OrderProcessingController
{
    /**
     * Объект отчет.
     *
     * @var OrderProcessingReport
     */
    private OrderProcessingReport $report;

    public function __construct()
    {
        $this->report = new OrderProcessingReport();
    }

    /**
     * Метод (функция) сохраняющий заказ в таблицу заказов.
     *
     * @param Order $order
     * @param string $orderTableName
     * @return OrderProcessingReport
     * @throws Exception
     */
    public function __invoke(Order $order, string $orderTableName): OrderProcessingReport
    {
        /**
         * Создание объекта-подключения к базе данных сервиса.
         */
        $db = DataBaseFactory::createDatabase()->getConnection();

        /**
         * Переменная итератор.
         */
        $iteration = 1;

        /**
         * Цикл обработки заказа формирующий уникальный, несуществующий
         * ни в базе данных сервиса, ни во внешнем сервисе вызываемом по API.
         */
        while (true) {
            $this->report->push($iteration, 1, 'Title', 'Generate barcode');
            /**
             * Создание баркода.
             */
            $order->setBarcode(Barcode::getNew($order->getUserId()));
            $this->report->push($iteration, 1, 'Result', 'barcode: ' . $order->getBarcode());

            /**
             * Предварительное сохранение заказа в базе данных сервиса.
             */

            try {
                if (1 === $iteration) {
                    $this->report->push($iteration, 2, 'Title', 'Preserve order in database');
                    /**
                     * Заказ не существует в базе, т.е. выполняется первый проход.
                     */
                    $order->store($db, $orderTableName);
                } else {
                    $this->report->push($iteration, 2, 'Title', 'Update order in database');
                    /**
                     * Заказ ранее сохранен в базу, но требуется обновление баркода.
                     */
                    $order->updateBarcode($db, $orderTableName);
                }
            } catch (UniqueConstraintViolationException $exception) {
                /**
                 * Инкрементальной увеличение маркера итератора.
                 */
                $iteration++;

                /**
                 * Переход на следующую итерацию цикла, т.к. переданный баркод существует в базе данных сервиса.
                 */
                continue;
            }
            $this->report->push($iteration, 2, 'Result', 'orderId: ' . $order->getId());

            $this->report->push($iteration, 3, 'Title', 'Make booking API request');
            /**
             * Запрос брони заказа по API.
             */
            $responseBooking = Api::bookOrder(
                $order->getEventId(),
                $order->getEventDate(),
                $order->getTicketAdultPrice(),
                $order->getTicketAdultQuantity(),
                $order->getTicketKidPrice(),
                $order->getTicketKidQuantity(),
                $order->getBarcode()
            );
            $this->report->push($iteration, 3, 'Result', $responseBooking);

            $responseBookingArray = json_decode($responseBooking, true);

            /**
             * Если в ответе содержится поле 'error' необходим новый проход, в противном случе, выход из цикла.
             */
            if(key_exists('error', $responseBookingArray)) {
                /**
                 * Условие контролирующее количество попыток получения подтверждения заказа.
                 */
                if ($iteration >= (int)App::env('ATTEMPTS_COUNT_TO_CALL_THE_API')) {
                    throw new RuntimeException('Failed barcode validation in external API');
                }

                /**
                 * Инкрементальное увеличение маркера итератора.
                 */
                $iteration++;
            } else {
                break;
            }
        }

        $this->report->push($iteration, 4, 'Title', 'Make approve API request');
        /**
         * Запрос на подтверждение брони.
         */
        $responseApprove = Api::approveOrder($order->getBarcode());
        $this->report->push($iteration, 4, 'Result', $responseApprove);

        $responseApproveArray = json_decode($responseApprove, true);

        /**
         * Условие контролирующее успешность подтверждения брони.
         */
        if (key_exists('error', $responseApproveArray)) {
            $this->report->push($iteration, 5, 'Title', 'Remove order from database');
            /**
             * Удаление заказа в базе данных сервиса как неподтвержденного.
             */
            $order->confirm($db, $orderTableName);
            $this->report->push($iteration, 5, 'Result', 'Removed');
        } else {
            $this->report->push($iteration, 5, 'Title', 'Confirm an order');
            /**
             * Маркировка заказа в базе данных сервиса как успешно подтвержденного.
             */
            $order->delete($db, $orderTableName);
            $this->report->push($iteration, 5, 'Result', 'Order stored successfully with barcode: ' . $order->getBarcode());
        }

        return $this->report;
    }
}