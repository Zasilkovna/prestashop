<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Order;

use Packetery\Exceptions\ClaimRequestException;
use Packetery\Order\ClaimFault;
use Packetery\Order\ClaimRequestFactory;
use Packetery\Order\OrderExporter;
use Packetery\Order\OrderNumberResolver;
use Packetery\Order\OrderRepository;
use Packetery\Tools\ConfigHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClaimRequestFactoryTest extends TestCase
{
    private const ORDER_ID = 42;
    private const ADDRESS_ID = 7;
    private const ORDER_NUMBER = 'ORD-15';
    private const VALUE = 12.5;
    private const CURRENCY = 'CZK';
    private const ESHOP_ID = 'muj-eshop.cz';
    private const COUNTRY = 'CZ';
    private const EMAIL = 'buyer@example.com';
    private const PHONE_MOBILE = '732111222';
    private const PHONE_LANDLINE = '571000000';

    protected function tearDown(): void
    {
        \Order::reset();
        \Address::reset();
        \Configuration::reset();
        parent::tearDown();
    }

    #[DataProvider('providePhoneFallback')]
    public function testBuildRequestResolvesPhone(string $phoneMobile, string $phone, string $expectedPhone): void
    {
        $data = $this->factory()
            ->buildRequest(
                self::ORDER_NUMBER,
                'buyer@example.com',
                $phoneMobile,
                $phone,
                self::VALUE,
                self::CURRENCY,
                self::ESHOP_ID,
                self::COUNTRY
            )
            ->getSubmittableData();

        $this->assertSame($expectedPhone, $data['phone']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function providePhoneFallback(): array
    {
        return [
            'mobile preferred over landline' => ['732111222', '571000000', '732111222'],
            'falls back to landline when mobile empty' => ['', '571000000', '571000000'],
            'mobile is trimmed' => ['  732111222  ', '', '732111222'],
            'whitespace-only mobile does not fall back' => ['   ', '571000000', ''],
            'both empty yields empty wire value' => ['', '', ''],
        ];
    }

    #[DataProvider('provideEmailAndCountry')]
    public function testBuildRequestMapsEmailAndCountry(
        string $email,
        string $country,
        string $expectedEmail,
        string $expectedCountry
    ): void {
        $data = $this->factory()
            ->buildRequest(
                self::ORDER_NUMBER,
                $email,
                '732111222',
                '',
                self::VALUE,
                self::CURRENCY,
                self::ESHOP_ID,
                $country
            )
            ->getSubmittableData();

        $this->assertSame($expectedEmail, $data['email']);
        $this->assertSame($expectedCountry, $data['consignCountry']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function provideEmailAndCountry(): array
    {
        return [
            'empty email and country become empty wire values' => [
                '',
                '',
                '',
                '',
            ],
            'provided email and country are kept' => [
                'buyer@example.com',
                self::COUNTRY,
                'buyer@example.com',
                self::COUNTRY,
            ],
        ];
    }

    public function testBuildRequestMapsScalars(): void
    {
        $data = $this->factory()
            ->buildRequest(
                self::ORDER_NUMBER,
                'buyer@example.com',
                '732111222',
                '',
                self::VALUE,
                self::CURRENCY,
                self::ESHOP_ID,
                self::COUNTRY
            )
            ->getSubmittableData();

        $this->assertSame(self::ORDER_NUMBER, $data['number']);
        $this->assertSame(self::VALUE, $data['value']);
        $this->assertSame(self::CURRENCY, $data['currency']);
        $this->assertSame(self::ESHOP_ID, $data['eshop']);
    }

    #[DataProvider('provideEmailFlag')]
    public function testBuildRequestSetsEmailFlagFromEmailPresence(string $email, bool $expectedFlag): void
    {
        $data = $this->factory()
            ->buildRequest(
                self::ORDER_NUMBER,
                $email,
                '732111222',
                '',
                self::VALUE,
                self::CURRENCY,
                self::ESHOP_ID,
                self::COUNTRY
            )
            ->getSubmittableData();

        $this->assertSame($expectedFlag, $data['sendEmailToCustomer']);
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function provideEmailFlag(): array
    {
        return [
            'email present → notify' => ['buyer@example.com', true],
            'no email → do not notify' => ['', false],
        ];
    }

    /**
     * @param mixed $packeteryOrder
     */
    #[DataProvider('provideInsufficientOrderData')]
    public function testBuildValidatedRequestRejectsInsufficientOrderData(
        $packeteryOrder,
        string $eshopId,
        ?float $value,
        string $expectedFault
    ): void {
        try {
            $this->factory()
                ->buildValidatedRequest(
                    $packeteryOrder,
                    $eshopId,
                    $value,
                    self::CURRENCY,
                    self::ORDER_NUMBER,
                    'buyer@example.com',
                    '732111222',
                    ''
                );
            $this->fail('Expected ClaimRequestException was not thrown.');
        } catch (ClaimRequestException $exception) {
            $this->assertSame($expectedFault, $exception->getFaultCode());
        }
    }

    /**
     * @return array<string, array{0: mixed, 1: string, 2: ?float, 3: string}>
     */
    public static function provideInsufficientOrderData(): array
    {
        return [
            'packetery order not found (null)' => [
                null,
                self::ESHOP_ID,
                self::VALUE,
                ClaimFault::ORDER_NOT_FOUND,
            ],
            'packetery order not found (non-array)' => [
                'nope',
                self::ESHOP_ID,
                self::VALUE,
                ClaimFault::ORDER_NOT_FOUND,
            ],
            'eshop id missing' => [
                ['ps_country' => self::COUNTRY],
                '',
                self::VALUE,
                ClaimFault::ESHOP_ID_MISSING,
            ],
            'order value unresolved' => [
                ['ps_country' => self::COUNTRY],
                self::ESHOP_ID,
                null,
                ClaimFault::VALUE_UNRESOLVED,
            ],
        ];
    }

    #[DataProvider('provideMissingContact')]
    public function testBuildValidatedRequestRejectsMissingContact(
        string $email,
        string $phoneMobile,
        string $phone,
        string $expectedFault
    ): void {
        try {
            $this->factory()
                ->buildValidatedRequest(
                    ['ps_country' => self::COUNTRY],
                    self::ESHOP_ID,
                    self::VALUE,
                    self::CURRENCY,
                    self::ORDER_NUMBER,
                    $email,
                    $phoneMobile,
                    $phone
                );
            $this->fail('Expected ClaimRequestException was not thrown.');
        } catch (ClaimRequestException $exception) {
            $this->assertSame($expectedFault, $exception->getFaultCode());
        }
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function provideMissingContact(): array
    {
        return [
            'missing email' => [
                '',
                self::PHONE_MOBILE,
                '',
                ClaimFault::EMAIL_MISSING,
            ],
            'both missing → email reported first' => [
                '',
                '',
                '',
                ClaimFault::EMAIL_MISSING,
            ],
            'missing phone (both empty)' => [
                self::EMAIL,
                '',
                '',
                ClaimFault::PHONE_MISSING,
            ],
            'whitespace-only phone' => [
                self::EMAIL,
                '   ',
                '   ',
                ClaimFault::PHONE_MISSING,
            ],
        ];
    }

    public function testBuildValidatedRequestBuildsRequestFromResolvedInputs(): void
    {
        $data = $this->factory()
            ->buildValidatedRequest(
                ['ps_country' => self::COUNTRY],
                self::ESHOP_ID,
                self::VALUE,
                self::CURRENCY,
                self::ORDER_NUMBER,
                'buyer@example.com',
                '732111222',
                ''
            )
            ->getSubmittableData();

        $this->assertSame(self::ORDER_NUMBER, $data['number']);
        $this->assertSame(self::VALUE, $data['value']);
        $this->assertSame(self::CURRENCY, $data['currency']);
        $this->assertSame(self::ESHOP_ID, $data['eshop']);
        $this->assertSame(self::COUNTRY, $data['consignCountry']);
    }

    public function testCreateMapsOrderObjectsToRequest(): void
    {
        \Configuration::set(ConfigHelper::KEY_ESHOP_ID, self::ESHOP_ID);
        \Order::loadFixture(
            self::ORDER_ID,
            [
                'id_shop_group' => 1,
                'id_shop' => 1,
                'id_address_delivery' => self::ADDRESS_ID,
                'customer' => new \Customer(self::EMAIL),
            ]
        );
        \Address::loadFixture(
            self::ADDRESS_ID,
            [
                'phone_mobile' => self::PHONE_MOBILE,
                'phone' => self::PHONE_LANDLINE,
            ]
        );

        $orderRepository = $this->createStub(OrderRepository::class);
        $orderRepository
            ->method('getOrderWithCountry')
            ->willReturn(['ps_country' => self::COUNTRY]);

        $orderExporter = $this->createStub(OrderExporter::class);
        $orderExporter
            ->method('findCurrencyAndTotalValue')
            ->willReturn([self::CURRENCY, self::VALUE]);

        $orderNumberResolver = $this->createStub(OrderNumberResolver::class);
        $orderNumberResolver
            ->method('getPreferredOrderNumber')
            ->willReturn(self::ORDER_NUMBER);

        $data = (new ClaimRequestFactory($orderRepository, $orderExporter, $orderNumberResolver))
            ->create(self::ORDER_ID)
            ->getSubmittableData();

        $this->assertSame(self::ORDER_NUMBER, $data['number']);
        $this->assertSame(self::EMAIL, $data['email']);
        $this->assertSame(self::PHONE_MOBILE, $data['phone']);
        $this->assertSame(self::VALUE, $data['value']);
        $this->assertSame(self::CURRENCY, $data['currency']);
        $this->assertSame(self::ESHOP_ID, $data['eshop']);
        $this->assertSame(self::COUNTRY, $data['consignCountry']);
    }

    public function testCreateRejectsOrderWithoutPacketeryRecord(): void
    {
        \Order::loadFixture(self::ORDER_ID, ['id_address_delivery' => self::ADDRESS_ID]);

        $orderRepository = $this->createStub(OrderRepository::class);
        $orderRepository
            ->method('getOrderWithCountry')
            ->willReturn(false);

        $orderExporter = $this->createMock(OrderExporter::class);
        $orderExporter
            ->expects($this->never())
            ->method('findCurrencyAndTotalValue');

        try {
            (new ClaimRequestFactory($orderRepository, $orderExporter, $this->createStub(OrderNumberResolver::class)))
                ->create(self::ORDER_ID);
            $this->fail('Expected ClaimRequestException was not thrown.');
        } catch (ClaimRequestException $exception) {
            $this->assertSame(ClaimFault::ORDER_NOT_FOUND, $exception->getFaultCode());
        }
    }

    private function factory(): ClaimRequestFactory
    {
        return new ClaimRequestFactory(
            $this->createStub(OrderRepository::class),
            $this->createStub(OrderExporter::class),
            $this->createStub(OrderNumberResolver::class)
        );
    }
}
