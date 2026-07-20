<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

class Currency
{
    /** @var array<int, array<string, mixed>> */
    private static array $fixtures = [];

    /** @var int */
    public $id = 0;
    /** @var string */
    public $iso_code = '';

    public function __construct($idCurrency = null)
    {
        $key = (int) $idCurrency;
        $data = self::$fixtures[$key] ?? [];

        $this->id = isset(self::$fixtures[$key]) ? $key : 0;
        $this->iso_code = $data['iso_code'] ?? '';
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function loadFixture(int $idCurrency, array $data): void
    {
        self::$fixtures[$idCurrency] = $data;
    }

    public static function reset(): void
    {
        self::$fixtures = [];
    }
}
