<?php

declare(strict_types=1);

namespace App\Controller;

use App\App;
use App\Factory\DataBaseFactory;
use App\Model\Barcode;
use App\Model\Order;
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
     * Метод (функция) сохраняющий заказ в таблицу заказов.
     *
     * @param Order $order
     * @param string $orderTableName
     * @return array
     * @throws Exception
     */
    public function __invoke(Order $order, string $orderTableName): array
    {
        /**
         * Переменная контейнер для сохранения результатов процессинга.
         */
        $result = [];

        /**
         * Создание объекта-подключения к базе данных сервиса.
         */
        $db = DataBaseFactory::createDatabase()->getConnection();

        /**
         * Переменная итератор.
         */
        $i = 1;

        /**
         * Цикл обработки заказа формирующий уникальный, несуществующий
         * ни в базе данных сервиса, ни во внешнем сервисе вызываемом по API.
         */
        while (true) {
            $result[$i]['Step 1']['Title'] = 'Generate barcode';
            /**
             * Создание баркода.
             */
            $order->setBarcode(Barcode::getNew($order->getUserId()));
            $result[$i]['Step 1']['Result'] = $order->getBarcode();

            /**
             * Предварительное сохранение заказа в базе данных сервиса.
             */
            $result[$i]['Step 2']['Title'] = 'Preserve order in database';
            try {
                if (1 === $i) {
                    /**
                     * Заказ не существует в базе, т.е. выполняется первый проход.
                     */
                    $order->store($db, $orderTableName);
                } else {
                    /**
                     * Заказ ранее сохранен в базу, но требуется обновление баркода.
                     */
                    $order->updateBarcode($db, $orderTableName);
                }
            } catch (UniqueConstraintViolationException $exception) {
                /**
                 * Инкрементальной увеличение маркера итератора.
                 */
                $i++;

                /**
                 * Переход на следующую итерацию цикла, т.к. переданный баркод существует в базе данных сервиса.
                 */
                continue;
            }
            $result[$i]['Step 2']['Result'] = 'orderId: ' . $order->getId();

            $result[$i]['Step 3']['Title'] = 'Make booking API request';
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
            );
            $result[$i]['Step 3']['Result'] = $responseBooking;

            $responseBookingArray = json_decode($responseBooking, true);

            /**
             * Если в ответе содержится поле 'error' необходим новый проход, в противном случе, выход из цикла.
             */
            if(key_exists('error', $responseBookingArray)) {
                /**
                 * Условие контролирующее количество попыток получения подтверждения заказа.
                 */
                if ($i >= (int)App::env('ATTEMPTS_COUNT_TO_CALL_THE_API')) {
                    throw new RuntimeException('Failed barcode validation in external API');
                }

                /**
                 * Инкрементальное увеличение маркера итератора.
                 */
                $i++;
            } else {
                break;
            }
        }

        $result[$i]['Step 4']['Title'] = 'Make approve API request';
        /**
         * Запрос на подтверждение брони.
         */
        $responseApprove = Api::approveOrder($order->getBarcode());
        $result[$i]['Step 4']['Result'] = $responseApprove;

        $responseApproveArray = json_decode($responseApprove, true);

        /**
         * Условие контролирующее успешность подтверждения брони.
         */
        if (key_exists('error', $responseApproveArray)) {
            $result[$i]['Step 5']['Title'] = 'Remove order from database';
            /**
             * Удаление заказа в базе данных сервиса как неподтвержденного.
             */
            $order->confirm($db, $orderTableName);
            $result[$i]['Step 5']['Result'] = 'Removed';

        } else {
            $result[$i]['Step 5']['Title'] = 'Confirm an order';
            /**
             * Маркировка заказа в базе данных сервиса как успешно подтвержденного.
             */
            $order->delete($db, $orderTableName);
            $result[$i]['Step 5']['Result'] = 'Order stored successfully with barcode: ' . $order->getBarcode();
        }

        return $result;
    }
}