<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\PacketTracking\PacketStatusFactory;
use Packetery\PacketTracking\PacketTrackingRepository;

class OrderDetailView
{
    /**
     * @var PacketTrackingRepository
     */
    private $packetTrackingRepository;

    /**
     * @var PacketStatusFactory
     */
    private $packetStatusFactory;

    public function __construct(
        PacketTrackingRepository $packetTrackingRepository,
        PacketStatusFactory $packetStatusFactory
    ) {
        $this->packetTrackingRepository = $packetTrackingRepository;
        $this->packetStatusFactory = $packetStatusFactory;
    }

    /**
     * @param \Smarty $smarty
     * @param array $packeteryOrder
     *
     * @return void
     */
    public function addPacketStatus(\Smarty $smarty, array $packeteryOrder)
    {
        if (!$packeteryOrder['tracking_number']) {
            return;
        }

        $lastStatusCode = $this->packetTrackingRepository->getLastStatusCodeByOrderAndPacketId(
            $packeteryOrder['id_order'],
            $packeteryOrder['tracking_number']
        );
        if ($lastStatusCode !== null) {
            $packetStatuses = $this->packetStatusFactory->getPacketStatuses();
            if (isset($packetStatuses[$lastStatusCode])) {
                $packetStatus = $packetStatuses[$lastStatusCode];
                $statusCssClass = str_replace(' ', '-', $packetStatus->getCode());
                $smarty->assign('packetStatusTranslatedCode', $packetStatus->getTranslatedCode());
                $smarty->assign('statusCssClass', $statusCssClass);
            }
            // else TODO: after adding a new column code_text to the db, return the value from the db
        }
    }
}
