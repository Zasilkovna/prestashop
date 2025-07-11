<?php

namespace Packetery\Module;

use Tools;

class Helper
{
    const TRACKING_URL = 'https://tracking.packeta.com/Z%s';

    /**
     * @param string $packetId
     * @return string
     */
    public static function getTrackingUrl($packetId)
    {
        return sprintf(self::TRACKING_URL, rawurlencode($packetId));
    }

    /**
     * @return string
     */
    public static function getBaseUri()
    {
        return __PS_BASE_URI__ === '/' ? '' : Tools::substr(__PS_BASE_URI__, 0, Tools::strlen(__PS_BASE_URI__) - 1);
    }

    /**
     * @param string $data
     * @return mixed|null
     */
    public static function unserialize($data)
    {
        if (PHP_VERSION_ID >= 70000) {
            return unserialize($data, ['allowed_classes' => false]);
        }

        return is_string($data) ? @unserialize($data) : null;
    }
}
