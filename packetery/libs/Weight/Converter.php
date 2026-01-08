<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Weight;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Converter
{
    /** @var array */
    public static $mapping = [
        'kg' => 1,
        'g' => 0.001,
        'lb' => 0.45359237,
        'oz' => 0.0283495231,
    ];

    /**
     * @param float $value
     *
     * @return float|null
     */
    public static function getKilograms($value)
    {
        $unit = strtolower(\Configuration::get('PS_WEIGHT_UNIT'));

        if (!isset(self::$mapping[$unit])) {
            return null;
        }

        return $value * self::$mapping[$unit];
    }

    /**
     * @return bool
     */
    public static function isKgConversionSupported()
    {
        $unit = strtolower(\Configuration::get('PS_WEIGHT_UNIT'));

        return isset(self::$mapping[$unit]);
    }
}
