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
 * Immutable order-level input for return eligibility (no PrestaShop objects).
 * `daysSinceDelivery` is null when the delivery/completion date is unknown.
 */
class ReturnOrderContext
{
    /** @var bool */
    private $customerRegistered;

    /** @var int|null */
    private $daysSinceDelivery;

    /** @var string|null */
    private $deliveryCountryIso;

    /** @var int[] */
    private $customerGroupIds;

    /** @var int|null carrier id_reference (stable across carrier edits, unlike id_carrier) */
    private $carrierReference;

    /** @var ReturnOrderItem[] */
    private $items;

    /**
     * @param int[] $customerGroupIds
     * @param ReturnOrderItem[] $items
     */
    public function __construct(
        bool $customerRegistered,
        ?int $daysSinceDelivery,
        ?string $deliveryCountryIso,
        array $customerGroupIds,
        ?int $carrierReference,
        array $items
    ) {
        $this->customerRegistered = $customerRegistered;
        $this->daysSinceDelivery = $daysSinceDelivery;
        $this->deliveryCountryIso = $deliveryCountryIso;
        $this->customerGroupIds = $customerGroupIds;
        $this->carrierReference = $carrierReference;
        $this->items = $items;
    }

    public function isCustomerRegistered(): bool
    {
        return $this->customerRegistered;
    }

    public function getDaysSinceDelivery(): ?int
    {
        return $this->daysSinceDelivery;
    }

    public function getDeliveryCountryIso(): ?string
    {
        return $this->deliveryCountryIso;
    }

    /** @return int[] */
    public function getCustomerGroupIds(): array
    {
        return $this->customerGroupIds;
    }

    public function getCarrierReference(): ?int
    {
        return $this->carrierReference;
    }

    /** @return ReturnOrderItem[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
