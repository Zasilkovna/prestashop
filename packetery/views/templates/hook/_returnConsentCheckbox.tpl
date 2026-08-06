{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * Mandatory return consent checkbox, shared by the registered (order detail) and guest return forms.
 * Expects consentTermsTag and consentPrivacyTag (the opening <a> tags, built in ReturnConsentProvider).
 * The links go inside the sentence via PS `tags`, so the translation stays one legal sentence
 * (postProcessTranslation inserts them after escaping; works on 1.7.8/8/9).
 *}
<div class="form-check mb-3">
    <input type="checkbox" id="packetery_return_consent" name="packetery_return_consent" value="1" class="form-check-input" required>
    <label class="form-check-label packetery-required" for="packetery_return_consent">
        {l s='I agree to the [1]Terms of Service[/1] and acknowledge the [2]Privacy Policy[/2] governing the personal data required to process the return.' tags=[$consentTermsTag, $consentPrivacyTag] mod='packetery'}
    </label>
</div>
