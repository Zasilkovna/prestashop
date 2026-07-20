<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

class Country
{
    /** @var array<int, string> */
    private static array $isoById = [];

    public static function loadFixture(int $idCountry, string $isoCode): void
    {
        self::$isoById[$idCountry] = $isoCode;
    }

    public static function reset(): void
    {
        self::$isoById = [];
    }

    public static function getIsoById(int $idCountry): string|false
    {
        return self::$isoById[$idCountry] ?? false;
    }
}
