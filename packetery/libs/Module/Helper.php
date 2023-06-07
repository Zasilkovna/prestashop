<?php

namespace Packetery\Module;

class Helper
{
    const TRACKING_URL = 'https://tracking.packeta.com/?id=%s';

    /**
     * @return string
     */
    public static function getTrackingUrl($packet_id)
    {
        return sprintf(self::TRACKING_URL, rawurlencode($packet_id));
    }

}
