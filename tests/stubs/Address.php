<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

class Address
{
    /** @var array<int, array<string, mixed>> */
    private static array $fixtures = [];

    /** @var string */
    public $phone_mobile = '';
    /** @var string */
    public $phone = '';

    public function __construct($idAddress = null)
    {
        $data = self::$fixtures[(int) $idAddress] ?? [];

        $this->phone_mobile = $data['phone_mobile'] ?? '';
        $this->phone = $data['phone'] ?? '';
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function loadFixture(int $idAddress, array $data): void
    {
        self::$fixtures[$idAddress] = $data;
    }

    public static function reset(): void
    {
        self::$fixtures = [];
    }
}
