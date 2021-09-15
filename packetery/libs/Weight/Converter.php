<?php

namespace Packetery\Weight;

class Converter
{
    /** @var string */
    const UNIT_KILOGRAMS = 'kg';

    /** @var string */
    const UNIT_GRAMS = 'g';

    /** @var array[] */
    private static $mapping = [
        self::UNIT_KILOGRAMS => [ // to kilos
            self::UNIT_KILOGRAMS => 1,
            self::UNIT_GRAMS => 0.001, // from grams
        ]
    ];

    /**
     * @param mixed $value
     * @return float|null
     */
    public static function getKilos($value)
    {
        return self::convert($value, \Configuration::get('PS_WEIGHT_UNIT'), self::UNIT_KILOGRAMS);
    }

    /**
     * @param mixed $value
     * @param string $unit
     * @param string $targetUnit
     * @return float|int
     */
    private static function convert($value, $unit, $targetUnit)
    {
        $unit = strtolower($unit);
        $value = (float)$value;

        if (!isset(self::$mapping[$targetUnit][$unit])) {
            return null;
        }

        return $value * self::$mapping[$targetUnit][$unit];
    }

    /**
     * @return bool
     */
    public static function isKgConvertionSupported()
    {
        $value = self::getKilos(1.0);
        return $value !== null;
    }
}
