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

use Packetery\Exceptions\DatabaseException;
use Packetery\Order\OrderRepository;

class SendMailAlterTemplateVars
{
    /** @var OrderRepository */
    private $orderRepository;

    public function __construct(
        OrderRepository $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
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
        if ($orderData !== null) {
            $additionalCarrierData = $this->getAdditionalCarrierInfo($orderData);
            $params['template_vars']['{carrier}'] .= $additionalCarrierData;
        }
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
