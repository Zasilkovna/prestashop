{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

{foreach $vendorsData as $countryCode => $country}
    <div class="form-group">
        <label class="control-label" for="country_{$countryCode|escape:'htmlall':'UTF-8'}"><strong>{$country['countryName']|escape:'htmlall':'UTF-8'}</strong></label>

        <fieldset>
            {foreach $country['groups'] as $vendorGroup}
                <div class="checkbox">
                    <label for="allowed_vendors_{$vendorGroup.id|escape:'htmlall':'UTF-8'}">
                        <input type="checkbox"
                               name="allowed_vendors[{$countryCode|escape:'htmlall':'UTF-8'}][{$vendorGroup.name|escape:'htmlall':'UTF-8'}]"
                               id="allowed_vendors_{$vendorGroup.id|escape:'htmlall':'UTF-8'}"
                               {if $vendorGroup.checked}checked="checked"{/if}>
                        {$vendorGroup.label|escape:'htmlall':'UTF-8'}
                    </label>
                </div>
            {/foreach}
        </fieldset>
    </div>
{/foreach}
