<?php

namespace Packetery\Order;

use Packetery\Exceptions\DatabaseException;
use Packetery;

class Tracking
{
    /** @var OrderRepository */
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Returns packetery order tracking number
     * @param string $id_orders Comma separated integers
     * @return array
     * @throws DatabaseException
     */
    public function getTrackingFromOrders($id_orders)
    {
        $result = $this->orderRepository->getTrackingNumbers($id_orders);
        $tracking = [];
        if ($result) {
            foreach ($result as $tn) {
                $tracking[] = $tn['tracking_number'];
            }
        }
        return $tracking;
    }

    /**
     * Updates eshop and packetery order tracking number
     * @param int $id_order
     * @param string $tracking_number numeric
     * @return bool
     * @throws DatabaseException
     */
    public function updateOrderTrackingNumber($id_order, $tracking_number)
    {
        if (!isset($id_order, $tracking_number)) {
            return false;
        }
        if ($this->orderRepository->existsByOrder((int)$id_order)) {
            return $this->orderRepository->setTrackingNumber((int)$id_order, $tracking_number);
        }

        return false;
    }

    /**
     * @param false|string $trackingNumber
     * @return false|string
     * @throws \SmartyException tracking link related exception
     */
    public function getTrackingLink($trackingNumber)
    {
        if (empty($trackingNumber)) {
            return '';
        }
        $smarty = new \Smarty();
        $smarty->assign('trackingNumber', $trackingNumber);
        $packetery = new Packetery();
        /** @var Packetery\Module\SoapApi $soapApi */
        $soapApi = $packetery->diContainer->get(Packetery\Module\SoapApi::class);
        $packetInfo = $soapApi->getPacketInfo($trackingNumber);
        if ($packetInfo->hasFault()) {
            $this->warnings = sprintf(
                '%s: %s',
                $this->l('Retrieving shipment information failed', 'packeteryordergridcontroller'),
                $trackingNumber
            );
        } else {
            $smarty->assign([
                'carrierNumber' => $packetInfo->getNumber(),
                'carrierTrackingUrl' => $packetInfo->getTrackingLink(),
            ]);
        }
        return $smarty->fetch(dirname(__FILE__) . '/../../views/templates/admin/trackingLink.tpl');
    }
}
