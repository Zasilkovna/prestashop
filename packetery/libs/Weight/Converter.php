<?php

namespace Packetery\Weight;

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
