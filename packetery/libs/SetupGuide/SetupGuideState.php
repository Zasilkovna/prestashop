<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\SetupGuide;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SetupGuideState
{
    /** @var bool */
    private $apiPasswordSet;

    /** @var bool */
    private $senderLabelSet;

    /** @var bool */
    private $carriersManuallyUpdated;

    /** @var bool */
    private $activePairedCarrierExists;

    /** @var bool */
    private $codPaymentExists;

    /** @var string|null */
    private $firstPaymentModuleName;

    /** @var bool */
    private $packetStatusTrackingEnabled;

    /** @var bool */
    private $orderStatusChangeEnabled;

    /** @var bool */
    private $consignmentCodeCronEnabled;

    /** @var array<string, int|null> */
    private $cronLastRuns;

    /**
     * @param array<string, int|null> $cronLastRuns
     */
    public function __construct(
        bool $apiPasswordSet,
        bool $senderLabelSet,
        bool $carriersManuallyUpdated,
        bool $activePairedCarrierExists,
        bool $codPaymentExists,
        ?string $firstPaymentModuleName,
        bool $packetStatusTrackingEnabled,
        bool $orderStatusChangeEnabled,
        bool $consignmentCodeCronEnabled,
        array $cronLastRuns
    ) {
        $this->apiPasswordSet = $apiPasswordSet;
        $this->senderLabelSet = $senderLabelSet;
        $this->carriersManuallyUpdated = $carriersManuallyUpdated;
        $this->activePairedCarrierExists = $activePairedCarrierExists;
        $this->codPaymentExists = $codPaymentExists;
        $this->firstPaymentModuleName = $firstPaymentModuleName;
        $this->packetStatusTrackingEnabled = $packetStatusTrackingEnabled;
        $this->orderStatusChangeEnabled = $orderStatusChangeEnabled;
        $this->consignmentCodeCronEnabled = $consignmentCodeCronEnabled;
        $this->cronLastRuns = $cronLastRuns;
    }

    public function isApiPasswordSet(): bool
    {
        return $this->apiPasswordSet;
    }

    public function isSenderLabelSet(): bool
    {
        return $this->senderLabelSet;
    }

    public function areCarriersManuallyUpdated(): bool
    {
        return $this->carriersManuallyUpdated;
    }

    public function hasActivePairedCarrier(): bool
    {
        return $this->activePairedCarrierExists;
    }

    public function hasCodPayment(): bool
    {
        return $this->codPaymentExists;
    }

    public function getFirstPaymentModuleName(): ?string
    {
        return $this->firstPaymentModuleName;
    }

    public function isPacketStatusTrackingEnabled(): bool
    {
        return $this->packetStatusTrackingEnabled;
    }

    public function isOrderStatusChangeEnabled(): bool
    {
        return $this->orderStatusChangeEnabled;
    }

    public function isConsignmentCodeCronEnabled(): bool
    {
        return $this->consignmentCodeCronEnabled;
    }

    public function getCronLastRun(string $taskName): ?int
    {
        return $this->cronLastRuns[$taskName] ?? null;
    }
}
