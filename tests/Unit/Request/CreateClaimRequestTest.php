<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Request;

use Packetery\Request\CreateClaimRequest;
use PHPUnit\Framework\TestCase;

class CreateClaimRequestTest extends TestCase
{
    public function testGetSubmittableDataMapsAllFields(): void
    {
        $request = new CreateClaimRequest(
            'REF123',
            'customer@example.com',
            '+420777123456',
            1234.5,
            'CZK',
            'muj-eshop.cz',
            'CZ',
            true
        );

        $this->assertSame(
            [
                'number' => 'REF123',
                'email' => 'customer@example.com',
                'phone' => '+420777123456',
                'value' => 1234.5,
                'currency' => 'CZK',
                'eshop' => 'muj-eshop.cz',
                'consignCountry' => 'CZ',
                'sendEmailToCustomer' => true,
            ],
            $request->getSubmittableData()
        );
    }

    public function testGetSubmittableDataConvertsNullablesToEmptyStrings(): void
    {
        $request = new CreateClaimRequest(
            'REF123',
            null,
            null,
            0.0,
            'CZK',
            'muj-eshop.cz',
            null,
            false
        );

        $data = $request->getSubmittableData();

        $this->assertSame('', $data['email']);
        $this->assertSame('', $data['phone']);
        $this->assertSame('', $data['consignCountry']);
        $this->assertFalse($data['sendEmailToCustomer']);
    }
}
