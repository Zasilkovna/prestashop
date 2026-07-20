<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

class Order
{
    /** @var array<int, array<string, mixed>> */
    private static array $fixtures = [];

    /** @var int */
    public $id = 0;
    /** @var int */
    public $id_shop_group = 0;
    /** @var int */
    public $id_shop = 0;
    /** @var int */
    public $id_address_delivery = 0;
    /** @var int */
    public $id_currency = 0;

    /** @var Customer|null */
    private $customer;
    /** @var float */
    private $totalPaid = 0.0;
    /** @var bool */
    private $delivered = false;

    public function __construct($orderId = null)
    {
        $key = (int) $orderId;
        $data = self::$fixtures[$key] ?? [];

        $this->id = isset(self::$fixtures[$key]) ? $key : 0;
        $this->id_shop_group = $data['id_shop_group'] ?? 0;
        $this->id_shop = $data['id_shop'] ?? 0;
        $this->id_address_delivery = $data['id_address_delivery'] ?? 0;
        $this->id_currency = $data['id_currency'] ?? 0;
        $this->customer = $data['customer'] ?? null;
        $this->totalPaid = $data['total_paid'] ?? 0.0;
        $this->delivered = $data['delivered'] ?? false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function loadFixture(int $orderId, array $data): void
    {
        self::$fixtures[$orderId] = $data;
    }

    public static function reset(): void
    {
        self::$fixtures = [];
    }

    public function getCustomer(): Customer
    {
        return $this->customer ?? new Customer();
    }

    public function getTotalPaid(): float
    {
        return (float) $this->totalPaid;
    }

    public function hasBeenDelivered(): bool
    {
        return (bool) $this->delivered;
    }
}
