<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Helper
{
    public const TRACKING_URL = 'https://tracking.packeta.com/Z%s';

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
     * @return string
     */
    public static function getBaseUri()
    {
        return __PS_BASE_URI__ === '/' ? '' : \Tools::substr(__PS_BASE_URI__, 0, \Tools::strlen(__PS_BASE_URI__) - 1);
    }

    /**
     * @param string $data
     *
     * @return mixed|null
     */
    public static function unserialize(
        $data
    ) {
        return unserialize(
            $data,
            [
                'allowed_classes' => false,
            ]
        );
    }
}
