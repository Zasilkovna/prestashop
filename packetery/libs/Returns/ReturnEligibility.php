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

/**
 * Pure logic deciding whether a delivered order may be returned by the customer
 * under the e-shop's configured restrictions. No PrestaShop objects — fully
 * unit-testable. Data is supplied via value objects.
 *
 * The whole order is returned as one parcel, so item-level restrictions are
 * evaluated order-level: a single excluded item (excluded category / virtual)
 * blocks the whole order, and the value/weight caps apply to the order totals.
 * Returning only part of an order is a future feature (partial-returns follow-up).
 */
class ReturnEligibility
{
    public function evaluate(ReturnSettings $settings, ReturnOrderContext $context): bool
    {
        if (!$settings->isEnabled() || !$this->passesOrderLevelGates($settings, $context)) {
            return false;
        }

        return $this->passesItemConstraints($settings, $context);
    }

    private function passesOrderLevelGates(ReturnSettings $settings, ReturnOrderContext $context): bool
    {
        if (!$context->isCustomerRegistered() && !$settings->allowsUnregistered()) {
            return false;
        }
        if (!$this->isWithinWindow($settings, $context)) {
            return false;
        }
        if (!$this->isCountryAllowed($settings, $context)) {
            return false;
        }
        if (!$this->isCustomerGroupAllowed($settings, $context)) {
            return false;
        }

        return $this->isCarrierAllowed($settings, $context);
    }

    private function isWithinWindow(ReturnSettings $settings, ReturnOrderContext $context): bool
    {
        $daysSinceDelivery = $context->getDaysSinceDelivery();
        // The window is counted from the order's delivered-state date; an unknown date does not block.
        if ($daysSinceDelivery === null) {
            return true;
        }

        return $daysSinceDelivery <= $settings->getWindowDays();
    }

    private function isCountryAllowed(ReturnSettings $settings, ReturnOrderContext $context): bool
    {
        $countryIso = $context->getDeliveryCountryIso();
        if ($countryIso === null || $countryIso === '') {
            return false;
        }

        return in_array(strtoupper($countryIso), $this->toUpper($settings->getAllowedCountryIsos()), true);
    }

    private function isCustomerGroupAllowed(ReturnSettings $settings, ReturnOrderContext $context): bool
    {
        $allowedGroupIds = $settings->getAllowedCustomerGroupIds();
        if ($allowedGroupIds === []) {
            return true;
        }

        return array_intersect($context->getCustomerGroupIds(), $allowedGroupIds) !== [];
    }

    private function isCarrierAllowed(ReturnSettings $settings, ReturnOrderContext $context): bool
    {
        $allowedCarrierReferences = $settings->getAllowedCarrierReferences();
        if ($allowedCarrierReferences === []) {
            return true;
        }

        return $context->getCarrierReference() !== null
            && in_array($context->getCarrierReference(), $allowedCarrierReferences, true);
    }

    private function passesItemConstraints(ReturnSettings $settings, ReturnOrderContext $context): bool
    {
        $totalValue = 0.0;
        $totalWeight = 0.0;
        foreach ($context->getItems() as $item) {
            if ($settings->excludesVirtual() && $item->isVirtual()) {
                return false;
            }
            if (array_intersect($item->getCategoryIds(), $settings->getExcludedCategoryIds()) !== []) {
                return false;
            }
            $totalValue += $item->getValue() * $item->getQuantity();
            $totalWeight += $item->getWeight() * $item->getQuantity();
        }

        $maxValue = $settings->getMaxItemValue();
        if ($maxValue !== null && $totalValue > $maxValue) {
            return false;
        }

        $maxWeight = $settings->getMaxItemWeight();

        return $maxWeight === null || $totalWeight <= $maxWeight;
    }

    /**
     * @param string[] $isoCodes
     *
     * @return string[]
     */
    private function toUpper(array $isoCodes): array
    {
        return array_map('strtoupper', $isoCodes);
    }
}
