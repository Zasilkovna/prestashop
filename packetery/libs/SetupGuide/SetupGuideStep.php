<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\SetupGuide;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SetupGuideStep
{
    public const BASIC_SETTINGS = 'basicSettings';
    public const CARRIERS_UPDATE = 'carriersUpdate';
    public const CARRIERS = 'carriers';
    public const COD_PAYMENTS = 'codPayments';
    public const PACKET_STATUS_TRACKING = 'packetStatusTracking';
    public const ORDER_STATUS_CHANGE = 'orderStatusChange';
    public const CRON_JOBS = 'cronJobs';
    // Informative step without a state, it is not in SetupGuide::STEPS
    public const ADULT_PRODUCTS = 'adultProducts';
}
