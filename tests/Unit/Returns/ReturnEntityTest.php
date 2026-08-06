<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Returns\ReturnEntity;
use PHPUnit\Framework\TestCase;

class ReturnEntityTest extends TestCase
{
    public function testFromDbRowCastsStringValuesToTypedProperties(): void
    {
        $return = ReturnEntity::fromDbRow([
            'id_return' => '5',
            'id_order' => '42',
            'claim_id' => 'C000000000001',
            'claim_password' => 'aBc123',
            'status' => ReturnEntity::STATUS_CREATED,
            'source' => ReturnEntity::SOURCE_ADMIN,
            'date_add' => '2026-07-09 12:00:00',
            'consent_at' => '2026-07-09 11:59:58',
            'consent_language' => 'cs',
        ]);

        $this->assertSame(5, $return->getIdReturn());
        $this->assertSame(42, $return->getIdOrder());
        $this->assertSame('C000000000001', $return->getClaimId());
        $this->assertSame('aBc123', $return->getClaimPassword());
        $this->assertSame('created', $return->getStatus());
        $this->assertSame('admin', $return->getSource());
        $this->assertSame('2026-07-09 12:00:00', $return->getDateAdd());
        $this->assertSame('2026-07-09 11:59:58', $return->getConsentGivenAt());
        $this->assertSame('cs', $return->getConsentLanguage());
    }

    public function testFromDbRowMapsMissingClaimPasswordToNull(): void
    {
        $return = ReturnEntity::fromDbRow([
            'id_return' => '6',
            'id_order' => '42',
            'claim_id' => '',
            'status' => ReturnEntity::STATUS_PENDING,
            'source' => ReturnEntity::SOURCE_CUSTOMER,
            'date_add' => '2026-07-09 12:00:00',
        ]);

        $this->assertNull($return->getClaimPassword());
    }

    public function testFromDbRowMapsMissingConsentToNull(): void
    {
        $return = ReturnEntity::fromDbRow([
            'id_return' => '7',
            'id_order' => '42',
            'claim_id' => 'C000000000002',
            'status' => ReturnEntity::STATUS_CREATED,
            'source' => ReturnEntity::SOURCE_ADMIN,
            'date_add' => '2026-07-09 12:00:00',
        ]);

        $this->assertNull($return->getConsentGivenAt());
        $this->assertNull($return->getConsentLanguage());
    }
}
