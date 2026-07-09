<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Response;

use Packetery\Response\CreateClaimResponse;
use PHPUnit\Framework\TestCase;

class CreateClaimResponseTest extends TestCase
{
    public function testFreshResponseHasNoIdPasswordOrFault(): void
    {
        $response = new CreateClaimResponse();

        $this->assertNull($response->getId());
        $this->assertNull($response->getPassword());
        $this->assertFalse($response->hasFault());
    }

    public function testHoldsClaimIdAndPassword(): void
    {
        $response = new CreateClaimResponse();
        $response->setId('Z9012');
        $response->setPassword('abcd1234');

        $this->assertSame('Z9012', $response->getId());
        $this->assertSame('abcd1234', $response->getPassword());
        $this->assertFalse($response->hasFault());
    }

    public function testFaultIsReportedAndKeepsIdNull(): void
    {
        $response = new CreateClaimResponse();
        $response->setFault('PacketAttributesFault');
        $response->setFaultString('Email is required.');

        $this->assertTrue($response->hasFault());
        $this->assertSame('PacketAttributesFault', $response->getFault());
        $this->assertSame('Email is required.', $response->getFaultString());
        $this->assertNull($response->getId());
    }
}
