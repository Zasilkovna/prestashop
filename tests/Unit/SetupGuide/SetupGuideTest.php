<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\SetupGuide;

use Packetery\Cron\CronRunLog;
use Packetery\Cron\Tasks\DownloadCarriers;
use Packetery\Cron\Tasks\GetConsignPassword;
use Packetery\Cron\Tasks\UpdatePacketStatus;
use Packetery\SetupGuide\SetupGuide;
use Packetery\SetupGuide\SetupGuideState;
use Packetery\SetupGuide\SetupGuideStep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SetupGuideTest extends TestCase
{
    private const LAST_RUN = 1760000000;

    private const API_PASSWORD_SET = 'apiPasswordSet';
    private const SENDER_LABEL_SET = 'senderLabelSet';
    private const CARRIERS_MANUALLY_UPDATED = 'carriersManuallyUpdated';
    private const ACTIVE_PAIRED_CARRIER_EXISTS = 'activePairedCarrierExists';
    private const COD_PAYMENT_EXISTS = 'codPaymentExists';
    private const PACKET_STATUS_TRACKING_ENABLED = 'packetStatusTrackingEnabled';
    private const ORDER_STATUS_CHANGE_ENABLED = 'orderStatusChangeEnabled';
    private const CONSIGNMENT_CODE_CRON_ENABLED = 'consignmentCodeCronEnabled';
    private const CRON_CARRIERS_RUN = 'cronCarriersRun';
    private const CRON_PACKET_STATUS_RUN = 'cronPacketStatusRun';
    private const CRON_CONSIGNMENT_CODE_RUN = 'cronConsignmentCodeRun';

    private const STATE_KEYS = [
        self::API_PASSWORD_SET,
        self::SENDER_LABEL_SET,
        self::CARRIERS_MANUALLY_UPDATED,
        self::ACTIVE_PAIRED_CARRIER_EXISTS,
        self::COD_PAYMENT_EXISTS,
        self::PACKET_STATUS_TRACKING_ENABLED,
        self::ORDER_STATUS_CHANGE_ENABLED,
        self::CONSIGNMENT_CODE_CRON_ENABLED,
        self::CRON_CARRIERS_RUN,
        self::CRON_PACKET_STATUS_RUN,
        self::CRON_CONSIGNMENT_CODE_RUN,
    ];

    private const ALL_CRON_TASKS_RUN = [
        self::CRON_CARRIERS_RUN => true,
        self::CRON_PACKET_STATUS_RUN => true,
        self::CRON_CONSIGNMENT_CODE_RUN => true,
    ];

    /**
     * @return array<string, array{string, array<string, bool>, bool}>
     */
    public static function stepDoneProvider(): array
    {
        return [
            'basic settings with API password and sender label' => [
                SetupGuideStep::BASIC_SETTINGS,
                [
                    self::API_PASSWORD_SET => true,
                    self::SENDER_LABEL_SET => true,
                ],
                true,
            ],
            'basic settings with API password only' => [
                SetupGuideStep::BASIC_SETTINGS,
                [self::API_PASSWORD_SET => true],
                false,
            ],
            'basic settings with sender label only' => [
                SetupGuideStep::BASIC_SETTINGS,
                [self::SENDER_LABEL_SET => true],
                false,
            ],
            'basic settings without values' => [SetupGuideStep::BASIC_SETTINGS, [], false],
            'carriers manually updated' => [SetupGuideStep::CARRIERS_UPDATE, [self::CARRIERS_MANUALLY_UPDATED => true], true],
            'carriers not manually updated' => [SetupGuideStep::CARRIERS_UPDATE, [], false],
            'active paired carrier exists' => [SetupGuideStep::CARRIERS, [self::ACTIVE_PAIRED_CARRIER_EXISTS => true], true],
            'no active paired carrier' => [SetupGuideStep::CARRIERS, [], false],
            'COD payment exists' => [SetupGuideStep::COD_PAYMENTS, [self::COD_PAYMENT_EXISTS => true], true],
            'no COD payment' => [SetupGuideStep::COD_PAYMENTS, [], false],
            'packet status tracking enabled' => [
                SetupGuideStep::PACKET_STATUS_TRACKING,
                [self::PACKET_STATUS_TRACKING_ENABLED => true],
                true,
            ],
            'packet status tracking disabled' => [SetupGuideStep::PACKET_STATUS_TRACKING, [], false],
            'order status change enabled' => [
                SetupGuideStep::ORDER_STATUS_CHANGE,
                [self::ORDER_STATUS_CHANGE_ENABLED => true],
                true,
            ],
            'order status change disabled' => [SetupGuideStep::ORDER_STATUS_CHANGE, [], false],
            'cron jobs with carrier list update run and hidden tasks not run' => [
                SetupGuideStep::CRON_JOBS,
                [self::CRON_CARRIERS_RUN => true],
                true,
            ],
            'cron jobs without any run' => [SetupGuideStep::CRON_JOBS, [], false],
            'cron jobs with packet status update not run' => [
                SetupGuideStep::CRON_JOBS,
                [
                    self::PACKET_STATUS_TRACKING_ENABLED => true,
                    self::CRON_CARRIERS_RUN => true,
                ],
                false,
            ],
            'cron jobs with packet status update run' => [
                SetupGuideStep::CRON_JOBS,
                [
                    self::PACKET_STATUS_TRACKING_ENABLED => true,
                    self::CRON_CARRIERS_RUN => true,
                    self::CRON_PACKET_STATUS_RUN => true,
                ],
                true,
            ],
            'cron jobs with consignment code retrieval not run' => [
                SetupGuideStep::CRON_JOBS,
                [
                    self::CONSIGNMENT_CODE_CRON_ENABLED => true,
                    self::CRON_CARRIERS_RUN => true,
                ],
                false,
            ],
            'cron jobs with consignment code retrieval run' => [
                SetupGuideStep::CRON_JOBS,
                [
                    self::CONSIGNMENT_CODE_CRON_ENABLED => true,
                    self::CRON_CARRIERS_RUN => true,
                    self::CRON_CONSIGNMENT_CODE_RUN => true,
                ],
                true,
            ],
            'cron jobs with all tasks shown and carrier list update not run' => [
                SetupGuideStep::CRON_JOBS,
                [
                    self::PACKET_STATUS_TRACKING_ENABLED => true,
                    self::CONSIGNMENT_CODE_CRON_ENABLED => true,
                    self::CRON_PACKET_STATUS_RUN => true,
                    self::CRON_CONSIGNMENT_CODE_RUN => true,
                ],
                false,
            ],
            'cron jobs with all tasks shown and run' => [
                SetupGuideStep::CRON_JOBS,
                [
                    self::PACKET_STATUS_TRACKING_ENABLED => true,
                    self::CONSIGNMENT_CODE_CRON_ENABLED => true,
                ] + self::ALL_CRON_TASKS_RUN,
                true,
            ],
        ];
    }

    /**
     * @param array<string, bool> $stateValues
     */
    #[DataProvider('stepDoneProvider')]
    public function testStepIsDoneWhenItsConditionIsMet(
        string $stepId,
        array $stateValues,
        bool $expectedDone
    ): void {
        $setupGuide = new SetupGuide($this->createState($stateValues));

        $this->assertSame($expectedDone, $setupGuide->isStepDone($stepId));
    }

    /**
     * @return array<string, array{array<string, bool>, string[]}>
     */
    public static function visibleCronTasksProvider(): array
    {
        return [
            'carrier list update always' => [
                [],
                [DownloadCarriers::getTaskName()],
            ],
            'packet status update with packet status tracking' => [
                [self::PACKET_STATUS_TRACKING_ENABLED => true],
                [
                    DownloadCarriers::getTaskName(),
                    UpdatePacketStatus::getTaskName(),
                ],
            ],
            'consignment code retrieval with cron retrieval' => [
                [self::CONSIGNMENT_CODE_CRON_ENABLED => true],
                [
                    DownloadCarriers::getTaskName(),
                    GetConsignPassword::getTaskName(),
                ],
            ],
            'all tasks' => [
                [
                    self::PACKET_STATUS_TRACKING_ENABLED => true,
                    self::CONSIGNMENT_CODE_CRON_ENABLED => true,
                ],
                [
                    DownloadCarriers::getTaskName(),
                    UpdatePacketStatus::getTaskName(),
                    GetConsignPassword::getTaskName(),
                ],
            ],
        ];
    }

    /**
     * @param array<string, bool> $stateValues
     * @param string[] $expectedTaskNames
     */
    #[DataProvider('visibleCronTasksProvider')]
    public function testVisibleCronTasksDependOnSettings(array $stateValues, array $expectedTaskNames): void
    {
        $setupGuide = new SetupGuide($this->createState($stateValues));

        $this->assertSame($expectedTaskNames, array_keys($setupGuide->getVisibleCronTasks()));
    }

    public function testVisibleCronTasksHaveLastRun(): void
    {
        $setupGuide = new SetupGuide($this->createState([
            self::PACKET_STATUS_TRACKING_ENABLED => true,
            self::CRON_CARRIERS_RUN => true,
        ]));

        $this->assertSame(
            [
                DownloadCarriers::getTaskName() => self::LAST_RUN,
                UpdatePacketStatus::getTaskName() => null,
            ],
            $setupGuide->getVisibleCronTasks()
        );
    }

    public function testStepsAreInDisplayOrderWithOptionalFlag(): void
    {
        $setupGuide = new SetupGuide($this->createState([]));

        $optionalByStep = [];
        foreach ($setupGuide->getStepIds() as $stepId) {
            $optionalByStep[$stepId] = $setupGuide->isStepOptional($stepId);
        }

        $this->assertSame(
            [
                SetupGuideStep::BASIC_SETTINGS => false,
                SetupGuideStep::CARRIERS_UPDATE => false,
                SetupGuideStep::CARRIERS => false,
                SetupGuideStep::COD_PAYMENTS => true,
                SetupGuideStep::PACKET_STATUS_TRACKING => true,
                SetupGuideStep::ORDER_STATUS_CHANGE => true,
                SetupGuideStep::CRON_JOBS => true,
            ],
            $optionalByStep
        );
    }

    public function testUnknownStepThrows(): void
    {
        $setupGuide = new SetupGuide($this->createState([]));

        $this->expectException(\InvalidArgumentException::class);

        $setupGuide->isStepDone('adultProducts');
    }

    public function testVisibleCronTasksAreLoggedTasks(): void
    {
        $setupGuide = new SetupGuide($this->createState([
            self::PACKET_STATUS_TRACKING_ENABLED => true,
            self::CONSIGNMENT_CODE_CRON_ENABLED => true,
        ]));

        $this->assertSame(
            [],
            array_diff(array_keys($setupGuide->getVisibleCronTasks()), array_keys((new CronRunLog())->getLastRuns()))
        );
    }

    /**
     * @param array<string, bool> $values condition values, missing ones are false
     *
     * @throws \InvalidArgumentException when a key is not a known state key
     */
    private function createState(array $values): SetupGuideState
    {
        $unknownKeys = array_diff(array_keys($values), self::STATE_KEYS);
        if ($unknownKeys !== []) {
            throw new \InvalidArgumentException('Unknown state keys: ' . implode(', ', $unknownKeys));
        }

        $isSet = static function (string $key) use ($values): bool {
            if (!in_array($key, self::STATE_KEYS, true)) {
                throw new \InvalidArgumentException("Unknown state key: {$key}");
            }

            return $values[$key] ?? false;
        };
        $lastRun = static function (string $key) use ($isSet): ?int {
            return $isSet($key) ? self::LAST_RUN : null;
        };

        return new SetupGuideState(
            $isSet(self::API_PASSWORD_SET),
            $isSet(self::SENDER_LABEL_SET),
            $isSet(self::CARRIERS_MANUALLY_UPDATED),
            $isSet(self::ACTIVE_PAIRED_CARRIER_EXISTS),
            $isSet(self::COD_PAYMENT_EXISTS),
            null,
            $isSet(self::PACKET_STATUS_TRACKING_ENABLED),
            $isSet(self::ORDER_STATUS_CHANGE_ENABLED),
            $isSet(self::CONSIGNMENT_CODE_CRON_ENABLED),
            [
                DownloadCarriers::getTaskName() => $lastRun(self::CRON_CARRIERS_RUN),
                UpdatePacketStatus::getTaskName() => $lastRun(self::CRON_PACKET_STATUS_RUN),
                GetConsignPassword::getTaskName() => $lastRun(self::CRON_CONSIGNMENT_CODE_RUN),
            ]
        );
    }
}
