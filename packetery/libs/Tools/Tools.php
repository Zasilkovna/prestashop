<?php

namespace Packetery\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\HttpFoundation\Request;
use Tools as PrestaShopTools;

class Tools extends \ToolsCore
{
    /**
     * Get a value from $_POST / $_GET
     * if unavailable, take a default value.
     *
     * @param string $key Value key
     * @param mixed $default_value (optional)
     *
     * @return mixed Value
     */
    public static function getValue($key, $default_value = false)
    {
        // we need to get rid of stripslashes in case of PrestaShop version < 1.7.6
        // otherwise, it's sometimes not possible to decode JSON got in POST
        if (PrestaShopTools::version_compare(_PS_VERSION_, '1.7.6', '<')) {
            // version from PrestaShop 1.7.6
            if (empty($key) || !is_string($key)) {
                return false;
            }

            if (getenv('kernel.environment') === 'test' && self::$request instanceof Request) {
                $value = self::$request->request->get($key, self::$request->query->get($key, $default_value));
            } else {
                $value = (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $default_value));
            }

            if (is_string($value)) {
                return urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($value)));
            }

            return $value;
        }

        return PrestaShopTools::getValue($key, $default_value);
    }

    public static function sanitizeFloatValue(string $value): string
    {
        return str_replace([',', ' '], ['.', ''], $value);
    }
}
