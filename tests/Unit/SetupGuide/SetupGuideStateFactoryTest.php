<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\SetupGuide;

use Packetery\Carrier\CarrierRepository;
use Packetery\Cron\CronRunLog;
use Packetery\Cron\Tasks\DownloadCarriers;
use Packetery\Cron\Tasks\GetConsignPassword;
use Packetery\Cron\Tasks\UpdatePacketStatus;
use Packetery\Order\ConsignPasswordSettings;
use Packetery\Payment\PaymentRepository;
use Packetery\SetupGuide\SetupGuideState;
use Packetery\SetupGuide\SetupGuideStateFactory;
use Packetery\Tools\ConfigHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SetupGuideStateFactoryTest extends TestCase
{
    private const LAST_RUN = 1760000000;
    private const SENDER_LABEL = 'my-shop';

    private const CONTEXT_SHOP_GROUP_ID = 1;
    private const CONTEXT_SHOP_ID = 2;
    private const OTHER_SHOP_ID = 3;

    private const COD_PAYMENT = [
        'name' => 'Cash on delivery',
        'is_cod' => 1,
        'module_name' => 'ps_cashondelivery',
    ];

    private const CARD_PAYMENT = [
        'name' => 'Card',
        'is_cod' => 0,
        'module_name' => 'ps_card',
    ];

    protected function tearDown(): void
    {
        \Configuration::reset();
        \Shop::reset();
    }

    /**
     * @return array<string, array{int|null, int|null, bool}>
     */
    public static function multistoreSenderLabelProvider(): array
    {
        return [
            'value only global is used' => [null, null, true],
            'value for the shop group is used' => [self::CONTEXT_SHOP_GROUP_ID, null, true],
            'value for the context shop is used' => [self::CONTEXT_SHOP_GROUP_ID, self::CONTEXT_SHOP_ID, true],
            'value only for another shop is not used' => [self::CONTEXT_SHOP_GROUP_ID, self::OTHER_SHOP_ID, false],
        ];
    }

    #[DataProvider('multistoreSenderLabelProvider')]
    public function testSenderLabelIsResolvedForContextShopInMultistore(
        ?int $valueShopGroupId,
        ?int $valueShopId,
        bool $expected
    ): void {
        \Shop::setContext(self::CONTEXT_SHOP_GROUP_ID, self::CONTEXT_SHOP_ID);
        \Configuration::set(
            ConfigHelper::KEY_ESHOP_ID,
            self::SENDER_LABEL,
            $valueShopGroupId,
            $valueShopId
        );

        $state = $this->createFactory([], false)->create();

        $this->assertSame($expected, $state->isSenderLabelSet());
    }

    /**
     * @return array<string, array{\Closure(SetupGuideState): bool, array<string, string>, bool}>
     */
    public static function configConditionProvider(): array
    {
        return [
            'API password set' => [
                static fn (SetupGuideState $state): bool => $state->isApiPasswordSet(),
                [ConfigHelper::KEY_APIPASS => 'secret'],
                true,
            ],
            'API password empty' => [
                static fn (SetupGuideState $state): bool => $state->isApiPasswordSet(),
                [ConfigHelper::KEY_APIPASS => ''],
                false,
            ],
            'API password missing' => [
                static fn (SetupGuideState $state): bool => $state->isApiPasswordSet(),
                [],
                false,
            ],
            'sender label set' => [
                static fn (SetupGuideState $state): bool => $state->isSenderLabelSet(),
                [ConfigHelper::KEY_ESHOP_ID => self::SENDER_LABEL],
                true,
            ],
            'sender label empty' => [
                static fn (SetupGuideState $state): bool => $state->isSenderLabelSet(),
                [ConfigHelper::KEY_ESHOP_ID => ''],
                false,
            ],
            'carriers manually updated' => [
                static fn (SetupGuideState $state): bool => $state->areCarriersManuallyUpdated(),
                [ConfigHelper::KEY_CARRIERS_MANUALLY_UPDATED => '1'],
                true,
            ],
            'carriers not manually updated' => [
                static fn (SetupGuideState $state): bool => $state->areCarriersManuallyUpdated(),
                [],
                false,
            ],
            'packet status tracking enabled' => [
                static fn (SetupGuideState $state): bool => $state->isPacketStatusTrackingEnabled(),
                [ConfigHelper::KEY_PACKET_STATUS_TRACKING_ENABLED => '1'],
                true,
            ],
            'packet status tracking disabled' => [
                static fn (SetupGuideState $state): bool => $state->isPacketStatusTrackingEnabled(),
                [ConfigHelper::KEY_PACKET_STATUS_TRACKING_ENABLED => '0'],
                false,
            ],
            'order status change enabled' => [
                static fn (SetupGuideState $state): bool => $state->isOrderStatusChangeEnabled(),
                [ConfigHelper::KEY_ORDER_STATUS_CHANGE_ENABLED => '1'],
                true,
            ],
            'order status change disabled' => [
                static fn (SetupGuideState $state): bool => $state->isOrderStatusChangeEnabled(),
                [ConfigHelper::KEY_ORDER_STATUS_CHANGE_ENABLED => '0'],
                false,
            ],
            'consignment code by cron' => [
                static fn (SetupGuideState $state): bool => $state->isConsignmentCodeCronEnabled(),
                [
                    ConfigHelper::KEY_SHOW_CONSIGN_PASSWORD => '1',
                    ConfigHelper::KEY_CONSIGN_PASSWORD_RETRIEVAL => ConsignPasswordSettings::MODE_CRON,
                ],
                true,
            ],
            'consignment code immediately' => [
                static fn (SetupGuideState $state): bool => $state->isConsignmentCodeCronEnabled(),
                [
                    ConfigHelper::KEY_SHOW_CONSIGN_PASSWORD => '1',
                    ConfigHelper::KEY_CONSIGN_PASSWORD_RETRIEVAL => ConsignPasswordSettings::MODE_IMMEDIATE,
                ],
                false,
            ],
            'consignment code hidden' => [
                static fn (SetupGuideState $state): bool => $state->isConsignmentCodeCronEnabled(),
                [
                    ConfigHelper::KEY_SHOW_CONSIGN_PASSWORD => '0',
                    ConfigHelper::KEY_CONSIGN_PASSWORD_RETRIEVAL => ConsignPasswordSettings::MODE_CRON,
                ],
                false,
            ],
        ];
    }

    /**
     * @param array<string, string> $configValues
     */
    #[DataProvider('configConditionProvider')]
    public function testConditionIsReadFromConfig(
        \Closure $getter,
        array $configValues,
        bool $expected
    ): void {
        foreach ($configValues as $key => $value) {
            \Configuration::set($key, $value);
        }

        $state = $this->createFactory([], false)->create();

        $this->assertSame($expected, $getter($state));
    }

    /**
     * @return array<string, array{array<int, array{name: string, is_cod: int, module_name: string}>, bool, string|null}>
     */
    public static function codPaymentProvider(): array
    {
        return [
            'COD payment among payments' => [[self::CARD_PAYMENT, self::COD_PAYMENT], true, self::CARD_PAYMENT['module_name']],
            'COD payment first' => [[self::COD_PAYMENT, self::CARD_PAYMENT], true, self::COD_PAYMENT['module_name']],
            'no COD payment' => [[self::CARD_PAYMENT], false, self::CARD_PAYMENT['module_name']],
            'no payment' => [[], false, null],
        ];
    }

    /**
     * @param array<int, array{name: string, is_cod: int, module_name: string}> $payments
     */
    #[DataProvider('codPaymentProvider')]
    public function testCodPaymentIsReadFromInstalledPayments(
        array $payments,
        bool $expectedCodPayment,
        ?string $expectedFirstPaymentModuleName
    ): void {
        $state = $this->createFactory($payments, false)->create();

        $this->assertSame($expectedCodPayment, $state->hasCodPayment());
        $this->assertSame($expectedFirstPaymentModuleName, $state->getFirstPaymentModuleName());
    }

    public function testPaymentsAreLoadedOnce(): void
    {
        $paymentRepository = $this->createMock(PaymentRepository::class);
        $paymentRepository
            ->expects($this->once())
            ->method('getListPayments')
            ->willReturn([self::COD_PAYMENT]);

        $factory = new SetupGuideStateFactory(
            $this->createStub(CarrierRepository::class),
            $paymentRepository,
            new CronRunLog()
        );

        $factory->create();
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function activePairedCarrierProvider(): array
    {
        return [
            'active paired carrier exists' => [true],
            'no active paired carrier' => [false],
        ];
    }

    #[DataProvider('activePairedCarrierProvider')]
    public function testActivePairedCarrierIsReadFromRepository(bool $hasActivePairedCarrier): void
    {
        $state = $this->createFactory([], $hasActivePairedCarrier)->create();

        $this->assertSame($hasActivePairedCarrier, $state->hasActivePairedCarrier());
    }

    public function testCronLastRunsAreReadFromCronRunLog(): void
    {
        \Configuration::set(ConfigHelper::KEY_CRON_LAST_RUN_DOWNLOAD_CARRIERS, (string) self::LAST_RUN);

        $state = $this->createFactory([], false)->create();

        $this->assertSame(self::LAST_RUN, $state->getCronLastRun(DownloadCarriers::getTaskName()));
        $this->assertNull($state->getCronLastRun(UpdatePacketStatus::getTaskName()));
        $this->assertNull($state->getCronLastRun(GetConsignPassword::getTaskName()));
    }

    /**
     * @param array<int, array{name: string, is_cod: int, module_name: string}> $payments
     */
    private function createFactory(array $payments, bool $hasActivePairedCarrier): SetupGuideStateFactory
    {
        $carrierRepository = $this->createStub(CarrierRepository::class);
        $carrierRepository
            ->method('hasActivePairedCarrier')
            ->willReturn($hasActivePairedCarrier);

        $paymentRepository = $this->createStub(PaymentRepository::class);
        $paymentRepository
            ->method('getListPayments')
            ->willReturn($payments);

        return new SetupGuideStateFactory($carrierRepository, $paymentRepository, new CronRunLog());
    }
}
