<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Returns\ReturnConsentProvider;
use PHPUnit\Framework\TestCase;

class ReturnConsentProviderTest extends TestCase
{
    /** @var ReturnConsentProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ReturnConsentProvider();
    }

    public function testPrivacyPolicyUrlIsLocalisedForKnownLanguages(): void
    {
        $this->assertStringContainsString('GDPR.pdf', $this->provider->getPrivacyPolicyUrl('cs'));
        $this->assertStringContainsString('SK_Zasady', $this->provider->getPrivacyPolicyUrl('sk'));
        $this->assertStringContainsString('Privacy-Policy', $this->provider->getPrivacyPolicyUrl('en'));
    }

    public function testPrivacyPolicyUrlFallsBackToEnglishForUnknownLanguage(): void
    {
        $this->assertSame(
            $this->provider->getPrivacyPolicyUrl('en'),
            $this->provider->getPrivacyPolicyUrl('de')
        );
    }

    public function testTermsUrlIsLocalisedForKnownLanguages(): void
    {
        $this->assertStringContainsString('VOP_Zasilkovna', $this->provider->getTermsUrl('cs'));
        $this->assertStringContainsString('SK_Obchodne_podmienky', $this->provider->getTermsUrl('sk'));
        $this->assertStringContainsString('GTC_Zasilkovna', $this->provider->getTermsUrl('en'));
    }

    public function testTermsUrlFallsBackToEnglishForUnknownLanguage(): void
    {
        $this->assertSame(
            $this->provider->getTermsUrl('en'),
            $this->provider->getTermsUrl('de')
        );
    }

    public function testLinkOpenTagsWrapTheLocalisedUrl(): void
    {
        $tag = $this->provider->getPrivacyPolicyLinkOpenTag('cs');

        $this->assertStringStartsWith('<a href="', $tag);
        $this->assertStringContainsString($this->provider->getPrivacyPolicyUrl('cs'), $tag);
        $this->assertStringContainsString('target="_blank"', $tag);
        $this->assertStringContainsString('rel="noopener noreferrer"', $tag);
        $this->assertStringContainsString('<a href="', $this->provider->getTermsLinkOpenTag('cs'));
    }
}
