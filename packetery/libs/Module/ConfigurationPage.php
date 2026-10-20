<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ConfigurationPage
{
    /** views/css/back.css also uses this ID */
    public const CRON_JOBS_ANCHOR = 'packetery-cron-jobs';
    /** views/css/back.css also uses this ID */
    public const ORDER_STATUS_CHANGE_ANCHOR = 'packetery-order-status-change';
    public const TAB_PARAMETER = 'packetery_tab';
    public const PACKET_STATUS_TRACKING_TAB = 'packet_status_tracking';
    /**
     * HelperForm builds the checkbox id from the field name payment_cod and the module name
     * views/css/back.css also uses this prefix
     */
    public const PAYMENT_COD_ANCHOR_PREFIX = 'payment_cod_';

    public static function isPacketStatusTrackingTabRequested(): bool
    {
        return \Tools::getValue(self::TAB_PARAMETER) === self::PACKET_STATUS_TRACKING_TAB;
    }
}
