<?php

namespace Packetery\Cron\Tasks;

use Packetery;
use Packetery\PacketTracking\PacketTrackingCron;

class UpdatePacketStatus extends Base
{
    /** @var Packetery */
    public $module;

    /** @var PacketTrackingCron */
    private $packetTrackingCron;

    /**
     * @param Packetery $module
     */
    public function __construct(Packetery $module)
    {
        $this->module = $module;
        $this->packetTrackingCron = $this->module->diContainer->get(PacketTrackingCron::class);
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
