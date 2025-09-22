<?php
/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
        PacketStatusFactory $packetStatusFactory,
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
