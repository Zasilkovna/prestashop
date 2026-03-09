<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Cron\Tasks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\PacketTracking\PacketTrackingCron;

class UpdatePacketStatus extends Base
{
    /** @var \Packetery */
    public $module;

    /** @var PacketTrackingCron */
    private $packetTrackingCron;

    /**
     * @param \Packetery $module
     * @param PacketTrackingCron $packetTrackingCron
     */
    public function __construct(\Packetery $module, PacketTrackingCron $packetTrackingCron)
    {
        $this->module = $module;
        $this->packetTrackingCron = $packetTrackingCron;
    }

    /**
     * @return string[]
     */
    public function execute()
    {
        $result = $this->packetTrackingCron->run();
        if ($result['class'] === 'danger') {
            return [$result['text']];
        }

        return [];
    }
}
