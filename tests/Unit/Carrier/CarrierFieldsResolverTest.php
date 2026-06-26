<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Carrier;

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Carrier\CarrierFieldsResolver;
use Packetery\Carrier\CarrierTools;
use Packetery\Carrier\CarrierVendors;
use Packetery\Tests\BaseTest;
use PHPUnit\Framework\Attributes\DataProvider;

class CarrierFieldsResolverTest extends BaseTest
{
    private const string EXTERNAL_BRANCH_ID = '13';

    public function testGetPossibleVendorsEmptyWhenBranchMissing(): void
    {
        $apiCarrierRepository = $this->createMock(ApiCarrierRepository::class);
        $carrierVendors = $this->createMock(CarrierVendors::class);

        $apiCarrierRepository
            ->expects($this->never())
            ->method('getById');
        $carrierVendors
            ->expects($this->never())
            ->method('getVendorsByCountries');

        $resolver = new CarrierFieldsResolver(
            $apiCarrierRepository,
            $this->createCarrierToolsStub(),
            $carrierVendors
        );

        $this->assertSame([], $resolver->getPossibleVendors([], self::CARRIER_ID));
    }

    #[DataProvider('internalBranchProvider')]
    public function testGetPossibleVendorsForInternalCarrier(string $idBranch): void
    {
        $carrierTools = $this->createMock(CarrierTools::class);
        $carrierVendors = $this->createMock(CarrierVendors::class);

        $carrierTools
            ->expects($this->once())
            ->method('getCountries')
            ->with(self::CARRIER_ID, 'iso_code')
            ->willReturn([self::COUNTRY_CZ]);
        $carrierVendors
            ->expects($this->once())
            ->method('getVendorsByCountries')
            ->with([self::COUNTRY_CZ])
            ->willReturn(self::VENDORS_CZ);

        $resolver = new CarrierFieldsResolver(
            $this->createApiCarrierRepositoryStub(),
            $carrierTools,
            $carrierVendors
        );

        $result = $resolver->getPossibleVendors(
            ['id_branch' => $idBranch],
            self::CARRIER_ID,
            ['id' => $idBranch]
        );

        $this->assertSame(self::VENDORS_CZ, $result);
    }

    public function testGetPossibleVendorsForExternalCarrier(): void
    {
        $carrierTools = $this->createMock(CarrierTools::class);
        $carrierVendors = $this->createMock(CarrierVendors::class);

        $carrierTools
            ->expects($this->never())
            ->method('getCountries');
        $carrierVendors
            ->expects($this->once())
            ->method('getVendorsByCountries')
            ->with([self::COUNTRY_DE])
            ->willReturn([]);

        $resolver = new CarrierFieldsResolver(
            $this->createApiCarrierRepositoryStub(),
            $carrierTools,
            $carrierVendors
        );

        $result = $resolver->getPossibleVendors(
            ['id_branch' => self::EXTERNAL_BRANCH_ID],
            self::CARRIER_ID,
            [
                'id' => self::EXTERNAL_BRANCH_ID,
                'country' => self::COUNTRY_DE,
            ]
        );

        $this->assertSame([], $result);
    }

    public function testGetPossibleVendorsFetchesApiCarrierWhenNotProvided(): void
    {
        $apiCarrierRepository = $this->createMock(ApiCarrierRepository::class);
        $carrierTools = $this->createCarrierToolsStub();
        $carrierVendors = $this->createStub(CarrierVendors::class);

        $apiCarrierRepository
            ->expects($this->once())
            ->method('getById')
            ->with(\Packetery::ZPOINT)
            ->willReturn(['id' => \Packetery::ZPOINT, 'country' => self::COUNTRY_CZ]);
        $carrierTools
            ->method('getCountries')
            ->willReturn([self::COUNTRY_CZ]);
        $carrierVendors
            ->method('getVendorsByCountries')
            ->willReturn(self::VENDORS_CZ);

        $resolver = new CarrierFieldsResolver(
            $apiCarrierRepository,
            $carrierTools,
            $carrierVendors
        );

        $this->assertSame(
            self::VENDORS_CZ,
            $resolver->getPossibleVendors(
                ['id_branch' => \Packetery::ZPOINT],
                self::CARRIER_ID
            )
        );
    }

    #[DataProvider('apiCarrierNotFoundProvider')]
    public function testGetPossibleVendorsEmptyWhenApiCarrierNotFound($getByIdResult): void
    {
        $apiCarrierRepository = $this->createMock(ApiCarrierRepository::class);
        $carrierVendors = $this->createMock(CarrierVendors::class);

        $apiCarrierRepository
            ->expects($this->once())
            ->method('getById')
            ->with(\Packetery::ZPOINT)
            ->willReturn($getByIdResult);
        $carrierVendors
            ->expects($this->never())
            ->method('getVendorsByCountries');

        $resolver = new CarrierFieldsResolver(
            $apiCarrierRepository,
            $this->createCarrierToolsStub(),
            $carrierVendors
        );

        $this->assertSame(
            [],
            $resolver->getPossibleVendors(
                ['id_branch' => \Packetery::ZPOINT],
                self::CARRIER_ID
            )
        );
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function apiCarrierNotFoundProvider(): array
    {
        return [
            'getById false' => [false],
            'getById null' => [null],
        ];
    }

    #[DataProvider('defaultAllowedVendorsProvider')]
    public function testGetDefaultAllowedVendorsBuildsJsonForInternalCarrier(
        string $idBranch,
        array $countries,
        array $vendors,
        string $expectedJson
    ): void {
        $carrierTools = $this->createCarrierToolsStub();
        $carrierVendors = $this->createStub(CarrierVendors::class);

        $carrierTools
            ->method('getCountries')
            ->willReturn($countries);
        $carrierVendors
            ->method('getVendorsByCountries')
            ->willReturn($vendors);

        $resolver = new CarrierFieldsResolver(
            $this->createApiCarrierRepositoryStub(),
            $carrierTools,
            $carrierVendors
        );

        $result = $resolver->getDefaultAllowedVendors(
            ['id_branch' => $idBranch],
            self::CARRIER_ID,
            ['id' => $idBranch]
        );

        $this->assertSame($expectedJson, $result);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function internalBranchProvider(): array
    {
        return [
            'zpoint branch' => [\Packetery::ZPOINT],
            'pp_all branch' => [\Packetery::PP_ALL],
        ];
    }

    /**
     * @return array<string, array{string, list<string>, array<string, mixed>, string}>
     */
    public static function defaultAllowedVendorsProvider(): array
    {
        return [
            'zpoint single country' => [
                \Packetery::ZPOINT,
                [self::COUNTRY_CZ],
                self::VENDORS_CZ,
                '{"cz":["zpoint","zbox"]}',
            ],
            'pp_all single country' => [
                \Packetery::PP_ALL,
                [self::COUNTRY_CZ],
                self::VENDORS_CZ,
                '{"cz":["zpoint","zbox"]}',
            ],
            'zpoint multiple countries' => [
                \Packetery::ZPOINT,
                [self::COUNTRY_CZ, self::COUNTRY_SK],
                self::VENDORS_CZ_SK,
                '{"cz":["zpoint","zbox"],"sk":["zpoint","zbox"]}',
            ],
        ];
    }

    public function testGetDefaultAllowedVendorsReturnsNullForExternalCarrier(): void
    {
        $carrierVendors = $this->createMock(CarrierVendors::class);

        $carrierVendors
            ->expects($this->never())
            ->method('getVendorsByCountries');

        $resolver = new CarrierFieldsResolver(
            $this->createApiCarrierRepositoryStub(),
            $this->createCarrierToolsStub(),
            $carrierVendors
        );

        $this->assertNull(
            $resolver->getDefaultAllowedVendors(
                ['id_branch' => self::EXTERNAL_BRANCH_ID],
                self::CARRIER_ID,
                [
                    'id' => self::EXTERNAL_BRANCH_ID,
                    'country' => self::COUNTRY_DE,
                ]
            )
        );
    }
}
