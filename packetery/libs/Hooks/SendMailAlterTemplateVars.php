<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Hooks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Carrier\CarrierRepository;
use Packetery\Exceptions\DatabaseException;
use Packetery\Order\OrderRepository;

class SendMailAlterTemplateVars
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var ApiCarrierRepository */
    private $apiCarrierRepository;

    public function __construct(
        OrderRepository $orderRepository,
        CarrierRepository $carrierRepository,
        ApiCarrierRepository $apiCarrierRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->carrierRepository = $carrierRepository;
        $this->apiCarrierRepository = $apiCarrierRepository;
    }

    /**
     * Alters variables of order e-mails, append carrier with additional data
     * inspiration: https://github.com/PrestaShop/ps_legalcompliance/blob/dev/ps_legalcompliance.php
     *
     * @throws DatabaseException
     */
    public function execute(array &$params): void
    {
        if (
            $this->isOrderPage($params) === false
            || $this->hasCarrier($params) === false
            || $this->hasOrderOrCart($params) === false
        ) {
            return;
        }

        $orderData = $this->getOrderData($params);
        if ($orderData === null) {
            return;
        }

        $packeteryCarrier = $this->carrierRepository->getPacketeryCarrierById((int) $orderData['id_carrier']);
        if (
            (int) $orderData['is_ad'] === 0
            && (int) $orderData['is_carrier'] === 1
            && isset($packeteryCarrier['id_branch'])
            && $packeteryCarrier['id_branch'] !== $orderData['id_branch']
        ) {
            $originalPacketeryCarrier = $this->apiCarrierRepository->getById($orderData['id_branch']);
            if ($originalPacketeryCarrier) {
                $params['template_vars']['{carrier}'] = $originalPacketeryCarrier['name'];
            }
        }

        $params['template_vars']['{carrier}'] .= $this->getAdditionalCarrierInfo($orderData);
    }

    private function isOrderPage(array $params): bool
    {
        return isset($params['template']) && strpos((string) $params['template'], 'order') !== false;
    }

    private function hasCarrier(array $params): bool
    {
        return isset($params['template_vars']['{carrier}']) && is_string($params['template_vars']['{carrier}']);
    }

    private function hasOrderOrCart(array $params): bool
    {
        return isset($params['template_vars']['{id_order}']) || $this->hasCart($params);
    }

    private function hasCart(array $params): bool
    {
        return isset($params['cart']) && $params['cart'] instanceof \Cart;
    }

    /**
     * @throws DatabaseException
     */
    private function getOrderData(array $params): ?array
    {
        $orderData = null;
        if (isset($params['template_vars']['{id_order}'])) {
            $orderData = $this->orderRepository->getById((int) $params['template_vars']['{id_order}']);
        } elseif (isset($params['cart']->id)) {
            $orderData = $this->orderRepository->getByCart((int) $params['cart']->id);
        }

        if (!is_array($orderData)) {
            return null;
        }

        return $orderData;
    }

    private function getAdditionalCarrierInfo(array $orderData): string
    {
        $carrierText = ' - ' . htmlspecialchars($orderData['name_branch'] ?? '', ENT_QUOTES);
        if (isset($orderData['is_carrier']) && (bool) $orderData['is_carrier'] === false) {
            $carrierText .= sprintf(' (%s)', (int) ($orderData['id_branch'] ?? ''));
        }

        return $carrierText;
    }
}
