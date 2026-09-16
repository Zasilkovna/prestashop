<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

class Configuration
{
    /** @var array<string, mixed> */
    private static array $values = [];

    public static function set(string $key, mixed $value): void
    {
        self::$values[$key] = $value;
    }

    public static function reset(): void
    {
        self::$values = [];
    }

    public static function get(string $key): mixed
    {
        return self::$values[$key] ?? null;
    }
}
