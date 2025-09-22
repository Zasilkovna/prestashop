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

namespace Packetery\Hooks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use AddressCore as Address;
use OrderCore as Order;
use Packetery\Carrier\CarrierRepository;
use Packetery\Carrier\CarrierTools;
use Packetery\Order\OrderRepository;
use Packetery\Order\OrderSaver;

class ActionObjectOrderUpdateBefore
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var OrderSaver */
    private $orderSaver;

    /** @var CarrierTools */
    private $carrierTools;

    /** @var CarrierRepository */
    private $carrierRepository;

    /**
     * ActionObjectOrderUpdateBefore constructor.
     *
     * @param OrderRepository $orderRepository
     * @param OrderSaver $orderSaver
     * @param CarrierTools $carrierTools
     * @param CarrierRepository $carrierRepository
     */
    public function __construct(
        OrderRepository $orderRepository,
        OrderSaver $orderSaver,
        CarrierTools $carrierTools,
        CarrierRepository $carrierRepository,
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderSaver = $orderSaver;
        $this->carrierTools = $carrierTools;
        $this->carrierRepository = $carrierRepository;
    }

    public function execute($params)
    {
        if (!isset($params['object'], $params['object']->id, $params['object']->id_carrier)) {
            return;
        }
        $orderId = (int) $params['object']->id;
        $idCarrier = (int) $params['object']->id_carrier;
        $orderOldVersion = new Order($orderId);

        $packeteryCarrier = $this->carrierRepository->getPacketeryCarrierById($idCarrier);
        $packeteryOrderData = $this->orderRepository->getById($orderId);
        if (!$packeteryOrderData) {
            if ($packeteryCarrier && $idCarrier !== (int) $orderOldVersion->id_carrier) {
                $this->orderSaver->save($params['object'], $packeteryCarrier);
            }

            return;
        }
        if ((int) $packeteryOrderData['id_carrier'] !== $idCarrier) {
            if ($packeteryCarrier) {
                $this->orderSaver->save($params['object'], $packeteryCarrier, true);
            } else {
                $this->orderRepository->deleteByOrder($orderId);
            }

            return;
        }

        $addressId = (int) $params['object']->id_address_delivery;
        $oldAddressId = (int) $orderOldVersion->id_address_delivery;
        if ($oldAddressId === $addressId) {
            return;
        }

        $address = new Address($addressId);
        $oldAddress = new Address($oldAddressId);
        if ($oldAddress->id_country === $address->id_country) {
            return;
        }

        list($carrierZones, $carrierCountries) = $this->carrierTools->getZonesAndCountries($idCarrier, 'id_country');
        if (!in_array($address->id_country, $carrierCountries)) {
            $this->orderRepository->deleteByOrder($orderId);

            return;
        }

        if ($packeteryCarrier && $packeteryCarrier['pickup_point_type'] !== null) {
            $this->orderSaver->save($params['object'], $packeteryCarrier, true);
        }
    }
}
