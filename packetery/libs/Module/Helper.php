<?php

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Helper
{
    const TRACKING_URL = 'https://tracking.packeta.com/Z%s';

    /**
     * @param string $packetId
     *
     * @return string
     */
    public static function getTrackingUrl($packetId)
    {
        return sprintf(self::TRACKING_URL, rawurlencode($packetId));
    }

    /**
     * @param string $data
     *
     * @return mixed|null
     */
    public static function json_to_string($data)
    {
        if (PHP_VERSION_ID >= 70000) {
            return json_decode($data, true);
        }

        return is_string($data) ? @json_decode($data, true) : null;
    }
}
