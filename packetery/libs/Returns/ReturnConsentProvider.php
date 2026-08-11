<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Returns;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Supplies the Terms of Service and Privacy Policy URLs shown next to the return consent checkbox,
 * chosen by the customer's language.
 */
class ReturnConsentProvider
{
    private const PRIVACY_URL_BY_ISO = [
        'cs' => 'https://files.packeta.com/web/files/GDPR.pdf',
        'sk' => 'https://files.packeta.com/web/files/sk/SK_Zasady-ochrany-osobnych-udajov-od-01-08-2024.pdf',
        'en' => 'https://files.packeta.com/web/files/Zasilkovna_Privacy-Policy.pdf',
    ];

    private const TERMS_URL_BY_ISO = [
        'cs' => 'https://files.packeta.com/web/files/VOP_Zasilkovna.pdf',
        'sk' => 'https://files.packeta.com/web/files/SK_Obchodne_podmienky_e-shopy_01_07_2026.pdf',
        'en' => 'https://files.packeta.com/web/files/GTC_Zasilkovna.pdf',
    ];

    public function getPrivacyPolicyUrl(string $iso): string
    {
        return self::PRIVACY_URL_BY_ISO[$iso] ?? self::PRIVACY_URL_BY_ISO['en'];
    }

    public function getTermsUrl(string $iso): string
    {
        return self::TERMS_URL_BY_ISO[$iso] ?? self::TERMS_URL_BY_ISO['en'];
    }

    /**
     * Opening <a> tag for the Terms of Service link, ready for the consent sentence's PS `tags` param.
     * Built here (not interpolated in the .tpl) so the {l} line stays free of {$...} — otherwise the
     * translation extractor stops at the brace and misses the string.
     */
    public function getTermsLinkOpenTag(string $iso): string
    {
        return $this->linkOpenTag($this->getTermsUrl($iso));
    }

    public function getPrivacyPolicyLinkOpenTag(string $iso): string
    {
        return $this->linkOpenTag($this->getPrivacyPolicyUrl($iso));
    }

    private function linkOpenTag(string $url): string
    {
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer">';
    }
}
