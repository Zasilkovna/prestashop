<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Carrier;

use Packetery\Carrier\CarrierAdminForm;
use Packetery\Carrier\CarrierFieldsResolver;
use Packetery\Carrier\CarrierVendors;
use Packetery\Tests\BaseTest;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

/**
 * Reflection test for the private CarrierAdminForm::getAllowedVendorsFromForm() validation branches,
 * including "selected vendor not available", which the admin UI cannot reach.
 */
class CarrierAdminFormAllowedVendorsTest extends BaseTest
{
    private const ERROR_KEY = 'error';

    /**
     * @param array<string, mixed> $possibleVendors
     * @param array<string, mixed> $formData
     */
    #[DataProvider('invalidSelectionProvider')]
    public function testReturnsErrorForInvalidSelection(array $possibleVendors, array $formData): void
    {
        $result = $this->invokeWithPossibleVendors($possibleVendors, $formData);

        $this->assertArrayHasKey(self::ERROR_KEY, $result);
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function invalidSelectionProvider(): array
    {
        return [
            'no vendor selected' => [
                self::VENDORS_CZ,
                [],
            ],
            'country missing in form' => [
                self::VENDORS_CZ,
                [
                    'allowed_vendors' => [
                        self::COUNTRY_SK => [
                            CarrierVendors::VENDOR_GROUP_ZPOINT => 'on',
                        ],
                    ],
                ],
            ],
            'vendor not available' => [
                self::VENDORS_CZ,
                [
                    'allowed_vendors' => [
                        self::COUNTRY_CZ => [
                            'nonexistent' => 'on',
                        ],
                    ],
                ],
            ],
            'no possible vendors' => [
                [],
                [
                    'allowed_vendors' => [
                        self::COUNTRY_CZ => [
                            CarrierVendors::VENDOR_GROUP_ZPOINT => 'on',
                        ],
                    ],
                ],
            ],
            'multi-country partial selection' => [
                self::VENDORS_CZ_SK,
                [
                    'allowed_vendors' => [
                        self::COUNTRY_CZ => [
                            CarrierVendors::VENDOR_GROUP_ZPOINT => 'on',
                            'zbox' => 'on',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $possibleVendors
     * @param array<string, mixed> $formData
     * @param array<string, list<string>> $expected
     */
    #[DataProvider('validSelectionProvider')]
    public function testReturnsAllowedVendorsForValidSelection(
        array $possibleVendors,
        array $formData,
        array $expected
    ): void {
        $result = $this->invokeWithPossibleVendors($possibleVendors, $formData);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>, array<string, list<string>>}>
     */
    public static function validSelectionProvider(): array
    {
        return [
            'single country' => [
                self::VENDORS_CZ,
                [
                    'allowed_vendors' => [
                        self::COUNTRY_CZ => [
                            CarrierVendors::VENDOR_GROUP_ZPOINT => 'on',
                            'zbox' => 'on',
                        ],
                    ],
                ],
                [
                    self::COUNTRY_CZ => [
                        CarrierVendors::VENDOR_GROUP_ZPOINT,
                        'zbox',
                    ],
                ],
            ],
            'multi-country cz+sk' => [
                self::VENDORS_CZ_SK,
                [
                    'allowed_vendors' => [
                        self::COUNTRY_CZ => [
                            CarrierVendors::VENDOR_GROUP_ZPOINT => 'on',
                            'zbox' => 'on',
                        ],
                        self::COUNTRY_SK => [
                            CarrierVendors::VENDOR_GROUP_ZPOINT => 'on',
                            'zbox' => 'on',
                        ],
                    ],
                ],
                [
                    self::COUNTRY_CZ => [
                        CarrierVendors::VENDOR_GROUP_ZPOINT,
                        'zbox',
                    ],
                    self::COUNTRY_SK => [
                        CarrierVendors::VENDOR_GROUP_ZPOINT,
                        'zbox',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $possibleVendors
     * @param array<string, mixed> $formData
     *
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    private function invokeWithPossibleVendors(array $possibleVendors, array $formData): array
    {
        $carrierFieldsResolver = $this->createStub(CarrierFieldsResolver::class);
        $carrierFieldsResolver
            ->method('getPossibleVendors')
            ->willReturn($possibleVendors);

        $form = (new ReflectionClass(CarrierAdminForm::class))->newInstanceWithoutConstructor();
        $this->mockPrivateProperty($form, 'carrierFieldsResolver', $carrierFieldsResolver);
        $this->mockPrivateProperty($form, 'carrierId', self::CARRIER_ID);
        $this->mockPrivateProperty($form, 'module', $this->createPacketeryStub());

        return $this->invokeMethod($form, 'getAllowedVendorsFromForm', [$formData, []]);
    }
}
