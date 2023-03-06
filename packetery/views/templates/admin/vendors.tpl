
{foreach $vendorsData as $countryCode => $country}
    <div class="form-group">
        <label class="control-label" for="country_{$countryCode}"><strong>{$country['countryName']}</strong></label>

        <fieldset>
            {foreach $country['groups'] as $vendorGroup}
                <div class="checkbox">
                    <label for="allowed_vendors_{$vendorGroup.id}">
                        <input type="checkbox"
                               name="allowed_vendors[{$countryCode}][{$vendorGroup.name}]"
                               id="allowed_vendors_{$vendorGroup.id}">
                        {$vendorGroup.label}
                    </label>
                </div>
            {/foreach}
        </fieldset>
    </div>
{/foreach}
