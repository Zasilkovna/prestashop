<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

class Tools
{
    /** @var array<string, mixed> request values the tested code reads via getValue() */
    public static $values = [];

    /**
     * @return mixed
     */
    public static function getValue(string $key, $defaultValue = false)
    {
        return self::$values[$key] ?? $defaultValue;
    }

    public static function version_compare(string $v1, string $v2, string $operator): bool
    {
        return (bool) version_compare($v1, $v2, $operator);
    }
}
