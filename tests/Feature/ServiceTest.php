<?php

declare(strict_types=1);

use App\Factory\DataBaseFactory;
use App\Model\Barcode;
use App\Model\Order;
use App\Controller\OrderProcessingController;
use App\Model\OrderProcessingReport;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    private Connection $db;
    private const USERS_TABLE_NAME = 'users_test';
    private const ORDERS_TABLE_NAME = 'orders_test';

    /**
     * Подготовка окружения.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function setUpBeforeClass(): void
    {
        $db = DataBaseFactory::createDatabase()->getConnection();

        $sqlQueries = [
            // Инициализация базы данных
            'init_users_table' => 'CREATE TABLE IF NOT EXISTS ' . self::USERS_TABLE_NAME . '(
            id INTEGER PRIMARY KEY, 
            name VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )',
            'init_orders_table' => 'CREATE TABLE IF NOT EXISTS ' . self::ORDERS_TABLE_NAME . '(
            id INTEGER PRIMARY KEY, 
            event_id INTEGER,
            event_date DATETIME,
            ticket_adult_price INTEGER(11),
            ticket_adult_quantity INTEGER(11),
            ticket_kid_price INTEGER(11),
            ticket_kid_quantity INTEGER(11),
            barcode VARCHAR(120) UNIQUE,
            user_id INTEGER(11),
            equal_price INTEGER(11),
            created DATETIME DEFAULT NULL
            )',

            // Посев тестовых значений
            'seed_users' => 'INSERT INTO ' . self::USERS_TABLE_NAME . ' (name) VALUES ("ivan"), ("petr"), ("sergey")',
            'seed_orders' => 'INSERT INTO ' . self::ORDERS_TABLE_NAME .
                ' (event_id, event_date, ticket_adult_price, ticket_adult_quantity, ticket_kid_price, ticket_kid_quantity, barcode, user_id, equal_price, created) ' .
                ' VALUES 
                (3, "2021-08-21 13:00:00", 700, 1, 450, 0, "11111111", 451, 700, "2021-01-11 13:22:09"),
                (6, "2021-07-29 18:00:00", 1000, 0, 800, 2, "22222222", 364, 1600, "2021-01-12 16:62:08"),
                (3, "2021-08-15 17:00:00", 700, 4, 450, 3, "33333333", 15, 4150, "2021-01-13 10:08:45")'
        ];

        foreach ($sqlQueries as $sqlQuery) {
            $db->executeQuery($sqlQuery);
        }
    }

    protected function setUp(): void
    {
        $this->db = DataBaseFactory::createDatabase()->getConnection();
    }

    /**
     * Получение всех записей таблицы users.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function test_getAllUsers(): void
    {
        $users = $this->db->fetchAllAssociative('SELECT * FROM ' . self::USERS_TABLE_NAME);

        $this->assertCount(3, $users);
    }

    /**
     * Получение всех записей таблицы orders.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function test_getAllOrders(): void
    {
        $orders = $this->db->fetchAllAssociative('SELECT * FROM ' . self::ORDERS_TABLE_NAME);

        $this->assertCount(3, $orders);
    }

    /**
     * Вспомогательный метод создания объекта заказа.
     *
     * @param int $userId
     * @param int $eventId
     * @param string $eventDate
     * @param int $adultPrice
     * @param int $adultQuantity
     * @param int $kidPrice
     * @param int $kidQuantity
     * @param string $barcode
     * @return Order
     */
    private function makeOrder(
        int $userId,
        int $eventId,
        string $eventDate,
        int $adultPrice,
        int $adultQuantity,
        int $kidPrice,
        int $kidQuantity,
        string $barcode
    ): Order {
        return (new Order())
            ->setEventId($eventId)
            ->setEventDate($eventDate)
            ->setTicketAdultPrice($adultPrice)
            ->setTicketAdultQuantity($adultQuantity)
            ->setTicketKidPrice($kidPrice)
            ->setTicketKidQuantity($kidQuantity)
            ->setBarcode($barcode)
            ->setUserId($userId)
            ->setEqualPrice();
    }

    /**
     * Создание заказа и проверка его свойств.
     *
     * @return Order
     */
    public function test_makeOrder(): Order
    {
        $userId = 451;
        $eventId = 6;
        $eventDate = '2021-11-03 10:36:36';
        $adultPrice = rand(500, 900);
        $adultQuantity = rand(0, 10);
        $kidPrice = rand(300, 500);
        $kidQuantity = rand(0, 10);
        $barcode = Barcode::getNew($userId);

        $order = $this->makeOrder(
            $userId,
            $eventId,
            $eventDate,
            $adultPrice,
            $adultQuantity,
            $kidPrice,
            $kidQuantity,
            $barcode
        );

        $expectedAdultQuantity = ($adultPrice * $adultQuantity) + ($kidPrice * $kidQuantity);
        $this->assertEquals($expectedAdultQuantity, $order->getEqualPrice());

        $this->assertEquals($userId, $order->getUserId());
        $this->assertEquals($eventId, $order->getEventId());
        $this->assertEquals($eventDate, $order->getEventDate());
        $this->assertEquals($barcode, $order->getBarcode());

        return $order;
    }

    /**
     * Предсохранение заказа в базе данных сервиса.
     *
     * @depends test_makeOrder
     * @throws \Doctrine\DBAL\Exception
     */
    public function test_storeOrder(Order $order): Order
    {
        $order->store($this->db, self::ORDERS_TABLE_NAME);

        $order->setId((int)$this->db->lastInsertId());

        $sql = 'SELECT * FROM ' . self::ORDERS_TABLE_NAME . ' WHERE id = "' . $order->getId() . '"';

        $orderFromDB = $this->db->fetchAllAssociative($sql)[0];

        $this->assertEquals($order->getEqualPrice(), $orderFromDB['equal_price']);

        return $order;
    }

    /**
     * Попытка предсохранения заказа содержащего баркод заведомо существующий в базе данных сервиса.
     *
     * @depends test_makeOrder
     * @throws \Doctrine\DBAL\Exception
     */
    public function test_storeOrderWithNotUniqueBarcodeException(Order $order): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        $order->store($this->db, self::ORDERS_TABLE_NAME);
    }

    /**
     * Подтверждение заказа в базе данных через установку значения поля created.
     *
     * @depends test_makeOrder
     * @throws \Doctrine\DBAL\Exception
     */
    public function test_confirmOrder(Order $order): void
    {
        $order->confirm($this->db, self::ORDERS_TABLE_NAME);

        $sql = 'SELECT * FROM ' . self::ORDERS_TABLE_NAME . ' WHERE id = "' . $order->getId() . '"';

        $this->db->fetchAllAssociative($sql)[0];

        $this->assertNotNull($order->getCreated());
    }

    /**
     * Обращение к контроллеру процессинга заказа.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function test_orderProcessingController(): void
    {
        $userId = 455;
        $eventId = 5;
        $eventDate = '2021-11-03 10:36:35';
        $adultPrice = rand(500, 900);
        $adultQuantity = rand(0, 10);
        $kidPrice = rand(300, 500);
        $kidQuantity = rand(0, 10);
        $barcode = '4551636043593409';

        $order = $this->makeOrder(
            $userId,
            $eventId,
            $eventDate,
            $adultPrice,
            $adultQuantity,
            $kidPrice,
            $kidQuantity,
            $barcode
        );

        $processing = new OrderProcessingController();

        try {
            /**
             * Успешная генерация уникального баркода.
             */
            $report = $processing($order, self::ORDERS_TABLE_NAME);
            $this->assertInstanceOf(OrderProcessingReport::class, $report);
        } catch (RuntimeException $exception) {
            /**
             * Исчерпан лимит попыток перегенерации баркода.
             */
            $this->assertTrue(true);
        }
    }

    /**
     * Возврат состояния окружения к исходному.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function tearDownAfterClass(): void
    {
        $db = DataBaseFactory::createDatabase()->getConnection();

        $tables = [
            self::USERS_TABLE_NAME,
            self::ORDERS_TABLE_NAME,
        ];

        foreach ($tables as $table) {
            $sql = 'DROP TABLE IF EXISTS ' . $table;
            $db->executeQuery($sql);
        }
    }
}