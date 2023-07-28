<?php

namespace Packetery\Module;

class Helper
{

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private static function isCastableToInt($value)
    {
        if (is_int($value)) {
            return true;
        }
        if (is_string($value) && strlen((string)(float)$value) !== strlen($value)) {
            return false;
        }
        if (is_float($value) || is_string($value)) {
            if ($value <= PHP_INT_MAX && $value >= -PHP_INT_MAX) {
                return (int)$value == $value;
            }

            return false;
        }

        return false;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public static function typeCastableArrayItemsAsInts(array $array)
    {
        foreach ($array as $key => $value) {
            if (self::isCastableToInt($value)) {
                $array[$key] = (int)$value;
            }
        }

        return $array;
    }

}
