<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Returns;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Address\AddressTools;

/**
 * Builds a ReturnOrderContext for the eligibility engine from a PrestaShop order.
 * PrestaShop object access stays in create(); buildContext() is object-free and unit-testable.
 */
class ReturnOrderContextFactory
{
    /**
     * @throws \PrestaShopException
     */
    public function create(int $orderId): ReturnOrderContext
    {
        $psOrder = new \Order($orderId);
        $customer = new \Customer((int) $psOrder->id_customer);

        $items = [];
        foreach ($psOrder->getProducts() as $product) {
            $productId = (int) $product['product_id'];
            $items[] = [
                'value' => (float) $product['unit_price_tax_incl'],
                'weight' => (float) $product['product_weight'],
                'quantity' => (int) $product['product_quantity'],
                'categoryIds' => array_map('intval', \Product::getProductCategories($productId)),
                'virtual' => (bool) (new \Product($productId))->is_virtual,
            ];
        }

        return $this->buildContext(
            !(bool) $customer->is_guest,
            $this->resolveDaysSinceDelivery($psOrder),
            AddressTools::getDeliveryCountryIso($psOrder),
            array_map('intval', $customer->getGroups()),
            $this->resolveCarrierReference($psOrder),
            $items
        );
    }

    /**
     * @param array<int, array{value: float, weight: float, quantity: int, categoryIds: int[], virtual: bool}> $items
     * @param int[] $customerGroupIds
     */
    public function buildContext(
        bool $customerRegistered,
        ?int $daysSinceDelivery,
        ?string $deliveryCountryIso,
        array $customerGroupIds,
        ?int $carrierReference,
        array $items
    ): ReturnOrderContext {
        $orderItems = [];
        foreach ($items as $item) {
            $orderItems[] = new ReturnOrderItem(
                $item['value'],
                $item['weight'],
                $item['quantity'],
                $item['categoryIds'],
                $item['virtual']
            );
        }

        return new ReturnOrderContext(
            $customerRegistered,
            $daysSinceDelivery,
            $deliveryCountryIso,
            $customerGroupIds,
            $carrierReference,
            $orderItems
        );
    }

    /**
     * Days since the order entered its delivered state, or null when that date is not set.
     *
     * The return window is measured uniformly from the order's delivery date for every carrier.
     * The module can move the order into the delivered state automatically on a packet-status
     * change, or the merchant sets it manually; either way this is the single source of truth
     * (more robust than reading packet tracking, which may diverge from the order state).
     * An unset delivery date yields null and does not block the window.
     */
    private function resolveDaysSinceDelivery(\Order $psOrder): ?int
    {
        $deliveryDate = (string) $psOrder->delivery_date;
        if ($deliveryDate === '' || strpos($deliveryDate, '0000') === 0) {
            return null;
        }

        $delivered = date_create($deliveryDate);
        if ($delivered === false) {
            return null;
        }

        $days = (int) $delivered->diff(new \DateTime('now'))->days;

        return $days >= 0 ? $days : null;
    }

    /**
     * Carrier id_reference of the order's carrier, or null when it has none. The whitelist is matched
     * on id_reference, not id_carrier: PrestaShop replaces id_carrier on every carrier edit while
     * id_reference stays stable, so comparing id_carrier would silently break after an edit.
     */
    private function resolveCarrierReference(\Order $psOrder): ?int
    {
        if ((int) $psOrder->id_carrier === 0) {
            return null;
        }

        $reference = (int) (new \Carrier((int) $psOrder->id_carrier))->id_reference;

        return $reference !== 0 ? $reference : null;
    }
}
