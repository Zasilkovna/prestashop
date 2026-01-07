{**
 * @copyright 2017-2026 Packeta s.r.o.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}

{foreach $vendorsData as $countryCode => $country}
    <div class="form-group">
        <label class="control-label" for="country_{$countryCode}"><strong>{$country['countryName']}</strong></label>

        <fieldset>
            {foreach $country['groups'] as $vendorGroup}
                <div class="checkbox">
                    <label for="allowed_vendors_{$vendorGroup.id}">
                        <input type="checkbox"
                               name="allowed_vendors[{$countryCode}][{$vendorGroup.name}]"
                               id="allowed_vendors_{$vendorGroup.id}"
                               {if $vendorGroup.checked}checked="checked"{/if}>
                        {$vendorGroup.label}
                    </label>
                </div>
            {/foreach}
        </fieldset>
    </div>
{/foreach}
