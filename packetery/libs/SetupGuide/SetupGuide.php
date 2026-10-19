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

use Packetery\Cron\Tasks\DownloadCarriers;
use Packetery\Cron\Tasks\GetConsignPassword;
use Packetery\Cron\Tasks\UpdatePacketStatus;

/**
 * "Products for adults only" is informative, the presenter adds it
 */
class SetupGuide
{
    /**
     * Step id => is optional, in display order
     */
    private const STEPS = [
        SetupGuideStep::BASIC_SETTINGS => false,
        SetupGuideStep::CARRIERS_UPDATE => false,
        SetupGuideStep::CARRIERS => false,
        SetupGuideStep::COD_PAYMENTS => true,
        SetupGuideStep::PACKET_STATUS_TRACKING => true,
        SetupGuideStep::ORDER_STATUS_CHANGE => true,
        SetupGuideStep::CRON_JOBS => true,
    ];

    /** @var SetupGuideState */
    private $state;

    public function __construct(SetupGuideState $state)
    {
        $this->state = $state;
    }

    /**
     * @return list<string>
     */
    public function getStepIds(): array
    {
        return array_keys(self::STEPS);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function isStepOptional(string $stepId): bool
    {
        $this->assertStepExists($stepId);

        return self::STEPS[$stepId];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function isStepDone(string $stepId): bool
    {
        $this->assertStepExists($stepId);

        switch ($stepId) {
            case SetupGuideStep::BASIC_SETTINGS:
                return $this->state->isApiPasswordSet() && $this->state->isSenderLabelSet();
            case SetupGuideStep::CARRIERS_UPDATE:
                return $this->state->areCarriersManuallyUpdated();
            case SetupGuideStep::CARRIERS:
                return $this->state->hasActivePairedCarrier();
            case SetupGuideStep::COD_PAYMENTS:
                return $this->state->hasCodPayment();
            case SetupGuideStep::PACKET_STATUS_TRACKING:
                return $this->state->isPacketStatusTrackingEnabled();
            case SetupGuideStep::ORDER_STATUS_CHANGE:
                return $this->state->isOrderStatusChangeEnabled();
            case SetupGuideStep::CRON_JOBS:
                return $this->areVisibleCronTasksRun();
            default:
                throw new \LogicException("Setup guide step without evaluation: {$stepId}");
        }
    }

    public function getFirstPaymentModuleName(): ?string
    {
        return $this->state->getFirstPaymentModuleName();
    }

    /**
     * @return array<string, int|null> last run by task name
     */
    public function getVisibleCronTasks(): array
    {
        $taskNames = [DownloadCarriers::getTaskName()];
        if ($this->state->isPacketStatusTrackingEnabled()) {
            $taskNames[] = UpdatePacketStatus::getTaskName();
        }
        if ($this->state->isConsignmentCodeCronEnabled()) {
            $taskNames[] = GetConsignPassword::getTaskName();
        }

        $visibleCronTasks = [];
        foreach ($taskNames as $taskName) {
            $visibleCronTasks[$taskName] = $this->state->getCronLastRun($taskName);
        }

        return $visibleCronTasks;
    }

    private function areVisibleCronTasksRun(): bool
    {
        foreach ($this->getVisibleCronTasks() as $lastRun) {
            if ($lastRun === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function assertStepExists(string $stepId): void
    {
        if (!isset(self::STEPS[$stepId])) {
            throw new \InvalidArgumentException("Unknown setup guide step: {$stepId}");
        }
    }
}
