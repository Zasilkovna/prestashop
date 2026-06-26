<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests;

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Carrier\CarrierTools;
use Packetery\Carrier\CarrierVendors;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    protected const CARRIER_ID = 5;

    protected const COUNTRY_CZ = 'cz';
    protected const COUNTRY_SK = 'sk';
    protected const COUNTRY_DE = 'de';

    protected const VENDORS_CZ = [
        self::COUNTRY_CZ => [
            [
                'group' => CarrierVendors::VENDOR_GROUP_ZPOINT,
                'country' => self::COUNTRY_CZ,
                'name' => 'Packeta Pick-up Points',
            ],
            [
                'group' => 'zbox',
                'country' => self::COUNTRY_CZ,
                'name' => 'Packeta Z-BOX',
            ],
        ],
    ];

    // CZ + SK both run Packeta zpoint + Z-BOX
    protected const VENDORS_CZ_SK = [
        self::COUNTRY_CZ => [
            [
                'group' => CarrierVendors::VENDOR_GROUP_ZPOINT,
                'country' => self::COUNTRY_CZ,
                'name' => 'Packeta Pick-up Points',
            ],
            [
                'group' => 'zbox',
                'country' => self::COUNTRY_CZ,
                'name' => 'Packeta Z-BOX',
            ],
        ],
        self::COUNTRY_SK => [
            [
                'group' => CarrierVendors::VENDOR_GROUP_ZPOINT,
                'country' => self::COUNTRY_SK,
                'name' => 'Packeta Pick-up Points',
            ],
            [
                'group' => 'zbox',
                'country' => self::COUNTRY_SK,
                'name' => 'Packeta Z-BOX',
            ],
        ],
    ];

    /**
     * \Packetery stub whose l() returns the marker unchanged.
     *
     * @return Stub&\Packetery
     */
    protected function createPacketeryStub(): \Packetery
    {
        $module = $this->createStub(\Packetery::class);
        $module
            ->method('l')
            ->willReturnArgument(0);

        return $module;
    }

    /**
     * @return Stub&ApiCarrierRepository
     */
    protected function createApiCarrierRepositoryStub(): ApiCarrierRepository
    {
        return $this->createStub(ApiCarrierRepository::class);
    }

    /**
     * @return Stub&CarrierTools
     */
    protected function createCarrierToolsStub(): CarrierTools
    {
        return $this->createStub(CarrierTools::class);
    }

    /**
     * Invoke a private or protected method via reflection
     *
     * @param array<int, mixed> $args
     * @throws \ReflectionException
     */
    protected function invokeMethod(object $object, string $method, array $args = []): mixed
    {
        return (new \ReflectionClass($object))
            ->getMethod($method)
            ->invokeArgs($object, $args);
    }

    /**
     * Set a private or protected property via reflection
     *
     * @param mixed $value
     * @throws \ReflectionException
     */
    protected function mockPrivateProperty(
        object $target,
        string $property,
        $value
    ): void {
        (new \ReflectionClass($target))
            ->getProperty($property)
            ->setValue($target, $value);
    }
}
