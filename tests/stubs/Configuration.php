<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

/**
 * Returns only the value of the exact scope. ConfigHelper::get() does the fallback to shop group and global.
 * Unlike updateValue(), get() does not take the missing scope from the Shop context.
 */
class Configuration
{
    private const GLOBAL_SCOPE = 'global';

    /** @var array<string, array<string, mixed>> values by key and scope */
    private static array $values = [];

    /** @var array<string, array{int|null, int|null}> shop group id and shop id of the last update by key */
    private static array $updateShopIds = [];

    public static function set(
        string $key,
        mixed $value,
        ?int $idShopGroup = null,
        ?int $idShop = null
    ): void {
        self::$values[$key][self::getScope($idShopGroup, $idShop)] = $value;
    }

    public static function updateValue(
        string $key,
        mixed $value,
        bool $html = false,
        ?int $idShopGroup = null,
        ?int $idShop = null
    ): bool {
        self::$updateShopIds[$key] = [$idShopGroup, $idShop];
        $idShopGroup = $idShopGroup ?? Shop::getContextShopGroupID(true);
        $idShop = $idShop ?? Shop::getContextShopID(true);
        self::set(
            $key,
            $value,
            $idShopGroup,
            $idShop
        );

        return true;
    }

    public static function reset(): void
    {
        self::$values = [];
        self::$updateShopIds = [];
    }

    /**
     * @return array{int|null, int|null}|null
     */
    public static function getUpdateShopIds(string $key): ?array
    {
        return self::$updateShopIds[$key] ?? null;
    }

    public static function get(
        string $key,
        ?int $idLang = null,
        ?int $idShopGroup = null,
        ?int $idShop = null
    ): mixed {
        return self::$values[$key][self::getScope($idShopGroup, $idShop)] ?? false;
    }

    private static function getScope(?int $idShopGroup, ?int $idShop): string
    {
        if ($idShop) {
            return "shop-{$idShop}";
        }
        if ($idShopGroup) {
            return "group-{$idShopGroup}";
        }

        return self::GLOBAL_SCOPE;
    }
}
