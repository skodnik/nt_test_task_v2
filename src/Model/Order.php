<?php

declare(strict_types=1);

namespace App\Model;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;

/**
 * Модель заказа.
 */
class Order
{
    private int $id;
    private int $event_id;
    private string $event_date;
    private int $ticket_adult_price;
    private int $ticket_adult_quantity;
    private int $ticket_kid_price;
    private int $ticket_kid_quantity;
    private string $barcode;
    private int $user_id;
    private int $equal_price;
    private string $created;

    /**
     * Предсохранение заказа в базе данных сервиса.
     *
     * @throws Exception
     */
    public function store(Connection $db, string $tableName): Result
    {
        $sqlQuery = 'INSERT INTO ' . $tableName . ' (
        event_id, 
        event_date, 
        ticket_adult_price, 
        ticket_adult_quantity, 
        ticket_kid_price,
        ticket_kid_quantity,
        barcode,
        user_id,
        equal_price       
        ) VALUES ( 
        ' . $this->getEventId() . ',
        "' . $this->getEventDate() . '",
        ' . $this->getTicketAdultPrice() . ',
        ' . $this->getTicketAdultQuantity() . ',
        ' . $this->getTicketKidPrice() . ',
        ' . $this->getTicketKidQuantity() . ',
        "' . $this->getBarcode() . '",
        ' . $this->getUserId() . ',
        ' . $this->getEqualPrice() . '
        )';

        $result = $db->executeQuery($sqlQuery);

        $this->setId((int)$db->lastInsertId());

        return $result;
    }

    /**
     * Обновление баркода заказа в базе данных сервиса.
     *
     * @param Connection $db
     * @param string $tableName
     * @return Result
     * @throws Exception
     */
    public function updateBarcode(Connection $db, string $tableName): Result
    {
        $sqlQuery = 'UPDATE ' . $tableName . ' SET barcode = "' . $this->getBarcode() . '" WHERE id = ' . $this->getId();

        return $db->executeQuery($sqlQuery);
    }

    /**
     * Установка маркера подтверждения заказа в базе данных сервиса.
     *
     * @param Connection $db
     * @param string $tableName
     * @return Result
     * @throws Exception
     */
    public function confirm(Connection $db, string $tableName): Result
    {
        $this->setCreated();

        $sqlQuery = 'UPDATE ' . $tableName . ' SET created = "' . $this->getCreated() . '" WHERE id = ' . $this->getId();

        return $db->executeQuery($sqlQuery);
    }

    /**
     * Удаление заказа из базы данных сервиса.
     *
     * @param Connection $db
     * @param string $tableName
     * @return Result
     * @throws Exception
     */
    public function delete(Connection $db, string $tableName): Result
    {
        $sqlQuery = 'DELETE FROM ' . $tableName . ' WHERE id = ' . $this->getId();

        return $db->executeQuery($sqlQuery);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Order
     */
    public function setId(int $id): Order
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return int
     */
    public function getEventId(): int
    {
        return $this->event_id;
    }

    /**
     * @param mixed $event_id
     */
    public function setEventId($event_id): Order
    {
        $this->event_id = $event_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventDate(): string
    {
        return $this->event_date;
    }

    /**
     * @param mixed $event_date
     */
    public function setEventDate($event_date): Order
    {
        $this->event_date = $event_date;

        return $this;
    }

    /**
     * @return int
     */
    public function getTicketAdultPrice(): int
    {
        return $this->ticket_adult_price;
    }

    /**
     * @param mixed $ticket_adult_price
     */
    public function setTicketAdultPrice($ticket_adult_price): Order
    {
        $this->ticket_adult_price = $ticket_adult_price;

        return $this;
    }

    /**
     * @return int
     */
    public function getTicketAdultQuantity(): int
    {
        return $this->ticket_adult_quantity;
    }

    /**
     * @param mixed $ticket_adult_quantity
     */
    public function setTicketAdultQuantity($ticket_adult_quantity): Order
    {
        $this->ticket_adult_quantity = $ticket_adult_quantity;

        return $this;
    }

    /**
     * @return int
     */
    public function getTicketKidPrice(): int
    {
        return $this->ticket_kid_price;
    }

    /**
     * @param mixed $ticket_kid_price
     */
    public function setTicketKidPrice($ticket_kid_price): Order
    {
        $this->ticket_kid_price = $ticket_kid_price;

        return $this;
    }

    /**
     * @return int
     */
    public function getTicketKidQuantity(): int
    {
        return $this->ticket_kid_quantity;
    }

    /**
     * @param mixed $ticket_kid_quantity
     */
    public function setTicketKidQuantity($ticket_kid_quantity): Order
    {
        $this->ticket_kid_quantity = $ticket_kid_quantity;

        return $this;
    }

    /**
     * @return string
     */
    public function getBarcode(): string
    {
        return $this->barcode;
    }

    /**
     * @param mixed $barcode
     */
    public function setBarcode($barcode): Order
    {
        $this->barcode = $barcode;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id): Order
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getEqualPrice(): int
    {
        return ($this->getTicketAdultPrice() * $this->getTicketAdultQuantity()) + ($this->getTicketKidPrice() * $this->getTicketKidQuantity());
    }

    /**
     * @return Order
     */
    public function setEqualPrice(): Order
    {
        $this->equal_price = ($this->getTicketAdultPrice() * $this->getTicketAdultQuantity()) + ($this->getTicketKidPrice() * $this->getTicketKidQuantity());

        return $this;
    }

    /**
     * @return string
     */
    public function getCreated(): string
    {
        return $this->created ?? '';
    }

    public function setCreated(): void
    {
        $this->created = date('Y-m-d H:i:s');
    }
}