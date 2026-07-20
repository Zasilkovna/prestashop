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
 * Immutable snapshot of the e-shop's configured return-service restrictions.
 * Consumed by ReturnEligibility; carries no PrestaShop objects (unit-testable).
 *
 * Semantics:
 * - allowedCustomerGroupIds / allowedCarrierReferences: empty array = no restriction (all allowed);
 *   non-empty = whitelist. Carriers are matched on id_reference (stable across carrier edits).
 * - allowedCountryIsos: whitelist (default = countries with Packeta pickup points).
 * - excludedCategoryIds: blacklist (item in any of these categories is not returnable).
 * - maxItemValue / maxItemWeight: null = no limit; otherwise a cap on the order's total value / weight.
 */
class ReturnSettings
{
    /** @var bool */
    private $enabled;

    /** @var bool */
    private $allowUnregistered;

    /** @var int */
    private $windowDays;

    /** @var int[] */
    private $excludedCategoryIds;

    /** @var bool */
    private $excludeVirtual;

    /** @var float|null */
    private $maxItemValue;

    /** @var float|null */
    private $maxItemWeight;

    /** @var int[] */
    private $allowedCustomerGroupIds;

    /** @var int[] */
    private $allowedCarrierReferences;

    /** @var string[] */
    private $allowedCountryIsos;

    /** @var bool */
    private $approvalRequired;

    /**
     * @param int[] $excludedCategoryIds
     * @param int[] $allowedCustomerGroupIds
     * @param int[] $allowedCarrierReferences
     * @param string[] $allowedCountryIsos
     */
    public function __construct(
        bool $enabled,
        bool $allowUnregistered,
        int $windowDays,
        array $excludedCategoryIds,
        bool $excludeVirtual,
        ?float $maxItemValue,
        ?float $maxItemWeight,
        array $allowedCustomerGroupIds,
        array $allowedCarrierReferences,
        array $allowedCountryIsos,
        bool $approvalRequired
    ) {
        $this->enabled = $enabled;
        $this->allowUnregistered = $allowUnregistered;
        $this->windowDays = $windowDays;
        $this->excludedCategoryIds = $excludedCategoryIds;
        $this->excludeVirtual = $excludeVirtual;
        $this->maxItemValue = $maxItemValue;
        $this->maxItemWeight = $maxItemWeight;
        $this->allowedCustomerGroupIds = $allowedCustomerGroupIds;
        $this->allowedCarrierReferences = $allowedCarrierReferences;
        $this->allowedCountryIsos = $allowedCountryIsos;
        $this->approvalRequired = $approvalRequired;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function allowsUnregistered(): bool
    {
        return $this->allowUnregistered;
    }

    public function getWindowDays(): int
    {
        return $this->windowDays;
    }

    /** @return int[] */
    public function getExcludedCategoryIds(): array
    {
        return $this->excludedCategoryIds;
    }

    public function excludesVirtual(): bool
    {
        return $this->excludeVirtual;
    }

    public function getMaxItemValue(): ?float
    {
        return $this->maxItemValue;
    }

    public function getMaxItemWeight(): ?float
    {
        return $this->maxItemWeight;
    }

    /** @return int[] */
    public function getAllowedCustomerGroupIds(): array
    {
        return $this->allowedCustomerGroupIds;
    }

    /** @return int[] */
    public function getAllowedCarrierReferences(): array
    {
        return $this->allowedCarrierReferences;
    }

    /** @return string[] */
    public function getAllowedCountryIsos(): array
    {
        return $this->allowedCountryIsos;
    }

    /**
     * When true, a new return is not sent to Packeta until the e-shop approves it (it is stored as
     * pending). Independent of the multi-return rule (any further return of an order always waits).
     */
    public function isApprovalRequired(): bool
    {
        return $this->approvalRequired;
    }
}
