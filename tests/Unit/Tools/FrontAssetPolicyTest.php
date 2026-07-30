<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Tools;

use Packetery\Tools\FrontAssetPolicy;
use PHPUnit\Framework\TestCase;

class FrontAssetPolicyTest extends TestCase
{
    /**
     * The checkout is the trap: OrderController reports php_self 'order' but page name 'checkout',
     * so matching it by page name would stop loading the pick-up point widget.
     */
    public function testCheckoutGetsBothScriptsAndStylesheet(): void
    {
        $policy = new FrontAssetPolicy();

        $this->assertTrue($policy->needsCheckoutAssets('order'));
        $this->assertTrue($policy->needsStylesheet('order', 'checkout'));
    }

    public function testAccountOrderDetailGetsOnlyTheStylesheet(): void
    {
        $policy = new FrontAssetPolicy();

        $this->assertFalse($policy->needsCheckoutAssets('order-detail'));
        $this->assertTrue($policy->needsStylesheet('order-detail', 'order-detail'));
    }

    public function testGuestReturnPageGetsOnlyTheStylesheet(): void
    {
        $policy = new FrontAssetPolicy();

        // module front controllers leave php_self null
        $this->assertFalse($policy->needsCheckoutAssets(null));
        $this->assertTrue($policy->needsStylesheet(null, 'module-packetery-return'));
    }

    public function testUnrelatedPageGetsNothing(): void
    {
        $policy = new FrontAssetPolicy();

        $this->assertFalse($policy->needsCheckoutAssets('index'));
        $this->assertFalse($policy->needsStylesheet('index', 'index'));
    }
}
