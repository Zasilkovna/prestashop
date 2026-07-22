<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\ApiCarrier;

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Tools\DbTools;
use PHPUnit\Framework\TestCase;

class ApiCarrierRepositoryGetEntityByIdTest extends TestCase
{
    public function testReturnsEntityWhenRowFound(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->db = new \Db();
        $dbTools->method('getRow')->willReturn([
            'id' => '106',
            'name' => 'CZ pickup points',
            'currency' => 'CZK',
            'is_pickup_points' => '1',
            'country' => 'cz',
            'disallows_cod' => '0',
            'requires_size' => '1',
        ]);

        $repository = new ApiCarrierRepository($dbTools);

        $carrier = $repository->getEntityById('106');

        $this->assertNotNull($carrier);
        $this->assertSame('106', $carrier->getId());
        $this->assertTrue($carrier->isPickupPoints());
    }

    public function testReturnsNullWhenRowMissing(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->db = new \Db();
        $dbTools->method('getRow')->willReturn(false);

        $repository = new ApiCarrierRepository($dbTools);

        $this->assertNull($repository->getEntityById('nope'));
    }
}
