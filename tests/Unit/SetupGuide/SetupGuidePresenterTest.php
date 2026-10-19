<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\SetupGuide;

use Packetery\Cron\Tasks\DownloadCarriers;
use Packetery\Cron\Tasks\GetConsignPassword;
use Packetery\Cron\Tasks\UpdatePacketStatus;
use Packetery\SetupGuide\SetupGuide;
use Packetery\SetupGuide\SetupGuidePresenter;
use Packetery\SetupGuide\SetupGuideState;
use Packetery\SetupGuide\SetupGuideStep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SetupGuidePresenterTest extends TestCase
{
    private const LAST_RUN = 1760000000;
    private const STEP_COUNT = 7;

    private const CONFIGURATION_URL = '/admin/AdminModules?configure=packetery';
    private const CARRIER_SETTINGS_URL = '/admin/PacketeryCarrierGrid';
    private const CRON_JOBS_URL = self::CONFIGURATION_URL . '#packetery-cron-jobs';
    private const PACKET_STATUS_TRACKING_URL = self::CONFIGURATION_URL . '&packetery_tab=packet_status_tracking';
    private const ORDER_STATUS_CHANGE_URL = self::PACKET_STATUS_TRACKING_URL . '#packetery-order-status-change';
    private const PAYMENT_MODULE_NAME = 'ps_cashondelivery';
    private const CONFIGURATION_LOCATION = 'Packeta › Configuration';
    private const SET_UP_LABEL = 'Set up';

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

    private const ALL_DONE = [
        self::API_PASSWORD_SET => true,
        self::SENDER_LABEL_SET => true,
        self::CARRIERS_MANUALLY_UPDATED => true,
        self::ACTIVE_PAIRED_CARRIER_EXISTS => true,
        self::COD_PAYMENT_EXISTS => true,
        self::PACKET_STATUS_TRACKING_ENABLED => true,
        self::ORDER_STATUS_CHANGE_ENABLED => true,
        self::CONSIGNMENT_CODE_CRON_ENABLED => true,
        self::CRON_CARRIERS_RUN => true,
        self::CRON_PACKET_STATUS_RUN => true,
        self::CRON_CONSIGNMENT_CODE_RUN => true,
    ];

    protected function tearDown(): void
    {
        \Tools::$values = [];
    }

    public function testStepsAreInDisplayOrderWithInformativeStepLast(): void
    {
        $data = $this->present($this->createState([]));

        $this->assertSame(
            [
                SetupGuideStep::BASIC_SETTINGS,
                SetupGuideStep::CARRIERS_UPDATE,
                SetupGuideStep::CARRIERS,
                SetupGuideStep::COD_PAYMENTS,
                SetupGuideStep::PACKET_STATUS_TRACKING,
                SetupGuideStep::ORDER_STATUS_CHANGE,
                SetupGuideStep::CRON_JOBS,
                SetupGuideStep::ADULT_PRODUCTS,
            ],
            array_column($data['steps'], 'id')
        );
        $this->assertSame(array_merge(range(1, self::STEP_COUNT), [null]), array_column($data['steps'], 'number'));

        $informativeStep = $data['steps'][self::STEP_COUNT];
        $this->assertTrue($informativeStep['isInformative']);
        $this->assertFalse($informativeStep['isDone']);
        $this->assertFalse($informativeStep['showOptionalLabel']);
        $this->assertSame(
            [['label' => 'Go to products', 'url' => '/admin/AdminProducts', 'isPrimary' => false]],
            $informativeStep['buttons']
        );
    }

    /**
     * @return array<string, array{array<string, bool>, int, int}>
     */
    public static function progressProvider(): array
    {
        return [
            'nothing done' => [[], 0, 0],
            'required steps done' => [
                [
                    self::API_PASSWORD_SET => true,
                    self::SENDER_LABEL_SET => true,
                    self::CARRIERS_MANUALLY_UPDATED => true,
                    self::ACTIVE_PAIRED_CARRIER_EXISTS => true,
                ],
                3,
                43,
            ],
            'only cron jobs done' => [[self::CRON_CARRIERS_RUN => true], 1, 14],
            'all steps done except cron jobs' => [
                [
                    self::API_PASSWORD_SET => true,
                    self::SENDER_LABEL_SET => true,
                    self::CARRIERS_MANUALLY_UPDATED => true,
                    self::ACTIVE_PAIRED_CARRIER_EXISTS => true,
                    self::COD_PAYMENT_EXISTS => true,
                    self::PACKET_STATUS_TRACKING_ENABLED => true,
                    self::ORDER_STATUS_CHANGE_ENABLED => true,
                    self::CRON_CARRIERS_RUN => true,
                ],
                6,
                86,
            ],
            'everything done' => [self::ALL_DONE, self::STEP_COUNT, 100],
        ];
    }

    /**
     * @param array<string, bool> $stateValues
     */
    #[DataProvider('progressProvider')]
    public function testProgressCountsOnlyNumberedSteps(
        array $stateValues,
        int $expectedDoneCount,
        int $expectedPercent
    ): void {
        $data = $this->present($this->createState($stateValues));

        $this->assertSame($expectedDoneCount, $data['doneCount']);
        $this->assertSame(self::STEP_COUNT, $data['stepCount']);
        $this->assertSame($expectedPercent, $data['progressPercent']);
    }

    public function testMixedStateMarksOnlyDoneSteps(): void
    {
        $state = $this->createState([
            self::API_PASSWORD_SET => true,
            self::SENDER_LABEL_SET => true,
            self::ACTIVE_PAIRED_CARRIER_EXISTS => true,
            self::PACKET_STATUS_TRACKING_ENABLED => true,
            self::CRON_CARRIERS_RUN => true,
            self::CRON_PACKET_STATUS_RUN => true,
        ]);

        $data = $this->present($state);

        $this->assertSame(
            [
                SetupGuideStep::BASIC_SETTINGS => true,
                SetupGuideStep::CARRIERS_UPDATE => false,
                SetupGuideStep::CARRIERS => true,
                SetupGuideStep::COD_PAYMENTS => false,
                SetupGuideStep::PACKET_STATUS_TRACKING => true,
                SetupGuideStep::ORDER_STATUS_CHANGE => false,
                SetupGuideStep::CRON_JOBS => true,
                SetupGuideStep::ADULT_PRODUCTS => false,
            ],
            array_column($data['steps'], 'isDone', 'id')
        );
        $this->assertSame(4, $data['doneCount']);
        $this->assertSame(57, $data['progressPercent']);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function stepTextsProvider(): array
    {
        return [
            'basic settings' => [
                SetupGuideStep::BASIC_SETTINGS,
                'Basic module settings',
                self::CONFIGURATION_LOCATION,
            ],
            'carriers update' => [
                SetupGuideStep::CARRIERS_UPDATE,
                'Packeta carriers update',
                'Packeta › Carrier settings',
            ],
            'carriers' => [
                SetupGuideStep::CARRIERS,
                'Carriers for Packeta',
                'Shipping › Carriers · Packeta › Carrier settings',
            ],
            'cash on delivery payments' => [
                SetupGuideStep::COD_PAYMENTS,
                'Cash on delivery payments',
                self::CONFIGURATION_LOCATION,
            ],
            'packet status tracking' => [
                SetupGuideStep::PACKET_STATUS_TRACKING,
                'Packet status tracking',
                self::CONFIGURATION_LOCATION,
            ],
            'order status change' => [
                SetupGuideStep::ORDER_STATUS_CHANGE,
                'Order status change',
                self::CONFIGURATION_LOCATION,
            ],
            'cron jobs' => [
                SetupGuideStep::CRON_JOBS,
                'CRON jobs',
                self::CONFIGURATION_LOCATION,
            ],
            'products for adults only' => [
                SetupGuideStep::ADULT_PRODUCTS,
                'Products for adults only',
                'Catalog › Products › product page',
            ],
        ];
    }

    #[DataProvider('stepTextsProvider')]
    public function testStepHasTitleAndLocation(
        string $stepId,
        string $expectedTitle,
        string $expectedLocation
    ): void {
        $step = $this->findStep($this->present($this->createState([]))['steps'], $stepId);

        $this->assertSame($expectedTitle, $step['title']);
        $this->assertSame($expectedLocation, $step['location']);
    }

    public function testTranslatedTextIsDecodedSoTheTemplateEscapesItOnce(): void
    {
        $module = new \Packetery();
        $module->translations = ['Basic module settings' => 'Basic "module" settings & more'];

        $data = (new SetupGuidePresenter($module))->present(new SetupGuide($this->createState([])));
        $title = $this->findStep($data['steps'], SetupGuideStep::BASIC_SETTINGS)['title'];

        $this->assertSame('Basic "module" settings & more', $title);
    }

    public function testProgressPercentIsRounded(): void
    {
        $state = $this->createState([
            self::API_PASSWORD_SET => true,
            self::SENDER_LABEL_SET => true,
        ]);

        $data = $this->present($state);

        $this->assertSame(1, $data['doneCount']);
        $this->assertSame(14, $data['progressPercent']);
    }

    /**
     * @return array<string, array{bool, array<string, bool>}>
     */
    public static function optionalLabelProvider(): array
    {
        return [
            'optional label is shown for optional steps that are not done' => [
                false,
                [
                    SetupGuideStep::BASIC_SETTINGS => false,
                    SetupGuideStep::CARRIERS_UPDATE => false,
                    SetupGuideStep::CARRIERS => false,
                    SetupGuideStep::COD_PAYMENTS => true,
                    SetupGuideStep::PACKET_STATUS_TRACKING => true,
                    SetupGuideStep::ORDER_STATUS_CHANGE => true,
                    SetupGuideStep::CRON_JOBS => true,
                    SetupGuideStep::ADULT_PRODUCTS => false,
                ],
            ],
            'optional label is hidden for done steps' => [
                true,
                [
                    SetupGuideStep::BASIC_SETTINGS => false,
                    SetupGuideStep::CARRIERS_UPDATE => false,
                    SetupGuideStep::CARRIERS => false,
                    SetupGuideStep::COD_PAYMENTS => false,
                    SetupGuideStep::PACKET_STATUS_TRACKING => false,
                    SetupGuideStep::ORDER_STATUS_CHANGE => false,
                    SetupGuideStep::CRON_JOBS => false,
                    SetupGuideStep::ADULT_PRODUCTS => false,
                ],
            ],
        ];
    }

    /**
     * @param array<string, bool> $expectedLabels
     */
    #[DataProvider('optionalLabelProvider')]
    public function testOptionalLabelIsHiddenOnceStepIsDone(bool $allDone, array $expectedLabels): void
    {
        $data = $this->present($this->createState($allDone ? self::ALL_DONE : []));

        $this->assertSame($expectedLabels, array_column($data['steps'], 'showOptionalLabel', 'id'));
    }

    /**
     * @return array<string, array{string, list<array{label: string, url: string, isPrimary: bool}>}>
     */
    public static function notDoneButtonsProvider(): array
    {
        return [
            'basic settings' => [
                SetupGuideStep::BASIC_SETTINGS,
                [['label' => self::SET_UP_LABEL, 'url' => self::CONFIGURATION_URL, 'isPrimary' => true]],
            ],
            'carriers update' => [
                SetupGuideStep::CARRIERS_UPDATE,
                [['label' => 'Try it', 'url' => self::CARRIER_SETTINGS_URL, 'isPrimary' => true]],
            ],
            'carriers' => [
                SetupGuideStep::CARRIERS,
                [
                    ['label' => 'Create carrier', 'url' => '/admin/AdminCarriers', 'isPrimary' => false],
                    ['label' => 'Pair', 'url' => self::CARRIER_SETTINGS_URL, 'isPrimary' => true],
                ],
            ],
            'cash on delivery payments' => [
                SetupGuideStep::COD_PAYMENTS,
                [['label' => self::SET_UP_LABEL, 'url' => self::CONFIGURATION_URL, 'isPrimary' => true]],
            ],
            'packet status tracking' => [
                SetupGuideStep::PACKET_STATUS_TRACKING,
                [['label' => self::SET_UP_LABEL, 'url' => self::PACKET_STATUS_TRACKING_URL, 'isPrimary' => true]],
            ],
            'order status change' => [
                SetupGuideStep::ORDER_STATUS_CHANGE,
                [['label' => self::SET_UP_LABEL, 'url' => self::ORDER_STATUS_CHANGE_URL, 'isPrimary' => true]],
            ],
            'cron jobs' => [
                SetupGuideStep::CRON_JOBS,
                [['label' => 'Show URLs', 'url' => self::CRON_JOBS_URL, 'isPrimary' => true]],
            ],
        ];
    }

    /**
     * @param list<array{label: string, url: string, isPrimary: bool}> $expectedButtons
     */
    #[DataProvider('notDoneButtonsProvider')]
    public function testStepThatIsNotDoneHasActionButtons(string $stepId, array $expectedButtons): void
    {
        $data = $this->present($this->createState([]));

        $this->assertSame($expectedButtons, $this->findStep($data['steps'], $stepId)['buttons']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function doneEditUrlProvider(): array
    {
        return [
            'basic settings' => [SetupGuideStep::BASIC_SETTINGS, self::CONFIGURATION_URL],
            'carriers update' => [SetupGuideStep::CARRIERS_UPDATE, self::CARRIER_SETTINGS_URL],
            'carriers' => [SetupGuideStep::CARRIERS, self::CARRIER_SETTINGS_URL],
            'cash on delivery payments' => [SetupGuideStep::COD_PAYMENTS, self::CONFIGURATION_URL],
            'packet status tracking' => [SetupGuideStep::PACKET_STATUS_TRACKING, self::PACKET_STATUS_TRACKING_URL],
            'order status change' => [SetupGuideStep::ORDER_STATUS_CHANGE, self::ORDER_STATUS_CHANGE_URL],
            'cron jobs' => [SetupGuideStep::CRON_JOBS, self::CRON_JOBS_URL],
        ];
    }

    /**
     * @return array<string, array{bool, string|null, string}>
     */
    public static function codPaymentsUrlProvider(): array
    {
        return [
            'not done, anchor of the first payment' => [
                false,
                self::PAYMENT_MODULE_NAME,
                self::CONFIGURATION_URL . '#payment_cod_' . self::PAYMENT_MODULE_NAME,
            ],
            'done, anchor of the first payment' => [
                true,
                self::PAYMENT_MODULE_NAME,
                self::CONFIGURATION_URL . '#payment_cod_' . self::PAYMENT_MODULE_NAME,
            ],
            'no payment, no anchor' => [false, null, self::CONFIGURATION_URL],
        ];
    }

    #[DataProvider('codPaymentsUrlProvider')]
    public function testCodPaymentsUrlLeadsToFirstPayment(
        bool $isDone,
        ?string $firstPaymentModuleName,
        string $expectedUrl
    ): void {
        $state = $this->createState([self::COD_PAYMENT_EXISTS => $isDone], $firstPaymentModuleName);

        $buttons = $this->findStep($this->present($state)['steps'], SetupGuideStep::COD_PAYMENTS)['buttons'];

        $this->assertSame([$expectedUrl], array_column($buttons, 'url'));
    }

    /**
     * @return array<string, array{array<string, string>, bool}>
     */
    public static function packetStatusTrackingTabRequestProvider(): array
    {
        return [
            'tab requested' => [['packetery_tab' => 'packet_status_tracking'], true],
            'other tab requested' => [['packetery_tab' => 'returns'], false],
            'no tab requested' => [[], false],
        ];
    }

    /**
     * @param array<string, string> $requestValues
     */
    #[DataProvider('packetStatusTrackingTabRequestProvider')]
    public function testPacketStatusTrackingTabIsRequestedByTabParameter(array $requestValues, bool $expected): void
    {
        \Tools::$values = $requestValues;

        $this->assertSame($expected, SetupGuidePresenter::isPacketStatusTrackingTabRequested());
    }

    #[DataProvider('doneEditUrlProvider')]
    public function testDoneStepHasOnlyEditButtonWithMainActionUrl(string $stepId, string $expectedUrl): void
    {
        $data = $this->present($this->createState(self::ALL_DONE));

        $this->assertSame(
            [['label' => 'Edit', 'url' => $expectedUrl, 'isPrimary' => false]],
            $this->findStep($data['steps'], $stepId)['buttons']
        );
    }

    public function testCronTasksHaveLastRunOrNull(): void
    {
        $state = $this->createState([
            self::PACKET_STATUS_TRACKING_ENABLED => true,
            self::CONSIGNMENT_CODE_CRON_ENABLED => true,
            self::CRON_CARRIERS_RUN => true,
        ]);

        $data = $this->present($state);

        $this->assertSame(
            [
                [
                    'id' => DownloadCarriers::getTaskName(),
                    'name' => 'Carrier list update',
                    'lastRun' => date('Y-m-d H:i:s', self::LAST_RUN),
                ],
                [
                    'id' => UpdatePacketStatus::getTaskName(),
                    'name' => 'Packet tracking status update',
                    'lastRun' => null,
                ],
                [
                    'id' => GetConsignPassword::getTaskName(),
                    'name' => 'Get consignment code',
                    'lastRun' => null,
                ],
            ],
            $this->findStep($data['steps'], SetupGuideStep::CRON_JOBS)['cronTasks']
        );
    }

    public function testOnlyCronStepHasCronTasks(): void
    {
        $data = $this->present($this->createState(self::ALL_DONE));

        $stepIdsWithCronTasks = [];
        foreach ($data['steps'] as $step) {
            if ($step['cronTasks'] !== []) {
                $stepIdsWithCronTasks[] = $step['id'];
            }
        }

        $this->assertSame([SetupGuideStep::CRON_JOBS], $stepIdsWithCronTasks);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SetupGuideState $state): array
    {
        return (new SetupGuidePresenter(new \Packetery()))->present(new SetupGuide($state));
    }

    /**
     * @param array<string, bool> $values
     */
    private function createState(array $values, ?string $firstPaymentModuleName = null): SetupGuideState
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
            $firstPaymentModuleName,
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

    /**
     * @param list<array<string, mixed>> $steps
     *
     * @return array<string, mixed>
     */
    private function findStep(array $steps, string $stepId): array
    {
        foreach ($steps as $step) {
            if ($step['id'] === $stepId) {
                return $step;
            }
        }

        $this->fail("Step {$stepId} not found");
    }
}
