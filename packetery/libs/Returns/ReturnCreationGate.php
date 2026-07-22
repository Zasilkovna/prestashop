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
use Packetery\Carrier\CarrierTools;

/**
 * Decides whether a return may be created for an order (delivered + the e-shop's configured
 * return policy). The return is always created via Packeta regardless of the original carrier.
 * "Delivered" is taken uniformly from the PS order-state "delivered" flag for every carrier
 * (the module can advance the order state automatically on a packet-status change), so the
 * e-shop keeps a single rule instead of a Packeta-specific one; it also requires a
 * Packeta-serviceable destination country.
 */
class ReturnCreationGate
{
    /** @var ReturnSettingsFactory */
    private $returnSettingsFactory;
    /** @var ReturnOrderContextFactory */
    private $returnOrderContextFactory;
    /** @var ReturnEligibility */
    private $returnEligibility;

    public function __construct(
        ReturnSettingsFactory $returnSettingsFactory,
        ReturnOrderContextFactory $returnOrderContextFactory,
        ReturnEligibility $returnEligibility
    ) {
        $this->returnSettingsFactory = $returnSettingsFactory;
        $this->returnOrderContextFactory = $returnOrderContextFactory;
        $this->returnEligibility = $returnEligibility;
    }

    /**
     * @throws \PrestaShopException
     */
    public function canCreate(int $orderId): bool
    {
        $psOrder = new \Order($orderId);
        if (!\Validate::isLoadedObject($psOrder)) {
            return false;
        }

        if (!$this->isDeliveredReturnable($psOrder)) {
            return false;
        }

        $settings = $this->returnSettingsFactory->fromConfig();
        $context = $this->returnOrderContextFactory->create($orderId);

        return $this->returnEligibility->evaluate($settings, $context);
    }

    /**
     * Whether the order is delivered (per the PS order-state flag) and shipped to a
     * Packeta-serviceable country, regardless of the original carrier.
     */
    private function isDeliveredReturnable(\Order $psOrder): bool
    {
        if (!$psOrder->hasBeenDelivered()) {
            return false;
        }

        $countryIso = AddressTools::getDeliveryCountryIso($psOrder);

        return $countryIso !== null
            && in_array(strtoupper($countryIso), CarrierTools::COUNTRIES_WITH_INTERNAL_PICKUP_POINTS, true);
    }
}
