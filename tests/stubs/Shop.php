<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

class Shop
{
    private static ?int $contextShopGroupId = null;

    private static ?int $contextShopId = null;

    public static function setContext(?int $shopGroupId, ?int $shopId): void
    {
        self::$contextShopGroupId = $shopGroupId;
        self::$contextShopId = $shopId;
    }

    public static function reset(): void
    {
        self::$contextShopGroupId = null;
        self::$contextShopId = null;
    }

    public static function getContextShopGroupID(bool $nullIfNoShop = false): ?int
    {
        return self::$contextShopGroupId;
    }

    public static function getContextShopID(bool $nullIfNoShop = false): ?int
    {
        return self::$contextShopId;
    }
}
