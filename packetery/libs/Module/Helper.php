<?php

namespace Packetery\Module;

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

}
