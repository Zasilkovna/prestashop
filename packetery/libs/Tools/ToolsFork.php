<?php

namespace Packetery\Tools;

use Symfony\Component\HttpFoundation\Request;
use ToolsCore;
use Tools;

class ToolsFork extends ToolsCore
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
        if (Tools::version_compare(_PS_VERSION_, '1.7.6', '<')) {
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

        return Tools::getValue($key, $default_value);
    }
}
