---
title: "prestashop — packetery-checkout module"
repo: prestashop
module: packetery-checkout
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers:
  - packetery/controllers/front/checkout.php
  - packetery/libs/PickupPointValidate/
  - packetery/libs/Cart/
  - packetery/libs/Address/
  - packetery/libs/Weight/
  - packetery/views/js/front.js
  - packetery/views/js/checkout-modules/
  - packetery/views/templates/front/
confidence: reviewed
tags: [ai-generated, repo-prestashop, module-packetery-checkout, type-reference]
---

Repo: prestashop · Module: packetery-checkout · Type: reference · Status: current

## packetery-checkout: purpose

packetery-checkout puts the Packeta pickup-point widget into the storefront delivery step and keeps
the customer's choice in the cart until the order is placed. The module has three layers. The
storefront JavaScript mounts the widget into whichever checkout theme the shop runs, reads the
picked point out of the widget and posts it back. One PrestaShop front controller receives those
posts and hands each of them to the class that persists or renders the result
[VERIFY: packetery/controllers/front/checkout.php#PacketeryCheckoutModuleFrontController]. The PHP
service classes answer the questions the widget and the delivery step need: whether a chosen pickup
point is still acceptable, which country the cart delivers to, whether the cart contains goods with
an age limit, and how much the shipment weighs in kilograms.

The two Smarty templates hold the state the JavaScript works with. `widget.tpl` renders the
pickup-point button together with a hidden input per widget field, so the selected branch survives a
page render [VERIFY: packetery/views/templates/front/widget.tpl#packeta-branch-id]. `widgetHd.tpl`
renders the home-delivery variant, which validates the customer address instead of picking a branch
and records the outcome in the `addressValidated` input
[VERIFY: packetery/views/templates/front/widgetHd.tpl#addressValidated]. A third template,
`display-before-carrier.tpl`, carries the whole page configuration as a JSON attribute and starts
the JavaScript once the delivery options are on the page
[VERIFY: packetery/views/templates/front/display-before-carrier.tpl#packetaModuleConfig].

packetery-checkout holds 411 lines of PHP logic in 10 types with 18 public methods, plus the
storefront JavaScript, which the AST map does not cover. The front controller runs with
`$auth = false` and `$ajax = true`, so it is reachable for guests; the only gate is a comparison of
the `token` request value with `Tools::getToken('ajax_front')`, and a mismatch ends the request with
no output at all [VERIFY: packetery/controllers/front/checkout.php#display].

> ⚠ add business context (elicitation)

## packetery-checkout: public interface

packetery-checkout registers one PrestaShop module front controller, `checkout`, and dispatches it
by the `action` request value into three branches.

| Action | Response | Behaviour | Anchor |
|---|---|---|---|
| `checkout?action=savePickupPointInCart` | JSON | Sets the JSON content type and echoes `OrderSaver::savePickupPointInCartGetJson` | [VERIFY: packetery/controllers/front/checkout.php#savePickupPointInCart] |
| `checkout?action=fetchExtraContent` | HTML | Echoes `Cart::packeteryCreateExtraContent`, the carrier extra content PrestaShop 1.6 cannot hook | [VERIFY: packetery/controllers/front/checkout.php#fetchExtraContent] |
| `checkout?action=saveAddressInCart` | none | Calls `Ajax::saveAddressInCart`, which writes the validated address itself | [VERIFY: packetery/controllers/front/checkout.php#saveAddressInCart] |

The PHP services behind the checkout carry the rest of the interface.

| Member | Signature | Behaviour | Anchor |
|---|---|---|---|
| `PickupPointValidator::validate` | `validate(PickupPointValidateRequest)` | Obtains the API key, calls the widget API, logs the outcome, returns a valid response on any failure | [VERIFY: packetery/libs/PickupPointValidate/PickupPointValidator.php#validate] |
| `PickupPointValidator::createPickupPointValidateRequest` | `createPickupPointValidateRequest(array, CartCore, array)` | Builds the request from the stored order row, the cart and the carrier row | [VERIFY: packetery/libs/PickupPointValidate/PickupPointValidator.php#createPickupPointValidateRequest] |
| `PickupPointValidate::createWithValidApiKey` | `createWithValidApiKey($apiKey, HttpClientWrapper)` | Private constructor wrapper; the caller must have resolved a valid key first | [VERIFY: packetery/libs/PickupPointValidate/PickupPointValidate.php#createWithValidApiKey] |
| `CartService::isAgeVerificationRequired` | `isAgeVerificationRequired(CartCore): bool` | True as soon as one cart product has the for-adults attribute | [VERIFY: packetery/libs/Cart/CartService.php#isForAdults] |
| `AddressTools::hasValidatedAddress` | `hasValidatedAddress(array): bool` | Treats a non-empty `zip` in the stored order row as proof that the widget returned an address | [VERIFY: packetery/libs/Address/AddressTools.php#hasValidatedAddress] |
| `AddressTools::getCountryFromCart` | `getCountryFromCart(CartCore): string` | Lowercase ISO code of the delivery address, empty string when the cart has none | [VERIFY: packetery/libs/Address/AddressTools.php#getCountryFromCart] |
| `Calculator::getComputedOrDefaultWeight` | `getComputedOrDefaultWeight(OrderCore)` | Converts the order weight, substitutes the default package weight when it is zero, adds the packaging weight, returns null when the sum stays zero | [VERIFY: packetery/libs/Weight/Calculator.php#getComputedOrDefaultWeight] |
| `Calculator::getFinalWeight` | `getFinalWeight(array)` | Returns the manually stored weight of the Packeta order row when it is set, otherwise the computed one | [VERIFY: packetery/libs/Weight/Calculator.php#getFinalWeight] |
| `Converter::getKilograms` | `getKilograms($value)` | Multiplies by the factor of the shop weight unit, null for an unmapped unit | [VERIFY: packetery/libs/Weight/Converter.php#getKilograms] |
| `Converter::isKgConversionSupported` | `isKgConversionSupported(): bool` | True when the shop weight unit has a factor | [VERIFY: packetery/libs/Weight/Converter.php#isKgConversionSupported] |

The remaining public members are the four constructors, the two `getSubmittableData` accessors of the
request value objects [VERIFY: packetery/libs/PickupPointValidate/ValidatedPoint.php#getSubmittableData]
and `PickupPointValidate::validate`, which performs the HTTP call itself
[VERIFY: packetery/libs/PickupPointValidate/PickupPointValidate.php#validate].

## packetery-checkout: pickup point validation

packetery-checkout re-checks a chosen pickup point against the Packeta widget API before the order
is confirmed, and `PickupPointValidator` is the class that runs that check. The validator first asks
for a usable API key; when none is configured it writes an error record under
`LogRepository::ACTION_PICKUP_POINT_VALIDATE` and returns a response that reports the point as valid
[VERIFY: packetery/libs/PickupPointValidate/PickupPointValidator.php#ACTION_PICKUP_POINT_VALIDATE].
The same happens for any exception raised while the request runs, so a validation failure never
blocks an order by itself; only an API answer that explicitly reports the point as invalid does.

`createPickupPointValidateRequest` assembles the payload from three sources. The country comes from
the cart delivery address, the carrier id from `CarrierTools::findExternalCarrierId` with
`CarrierVendors::INTERNAL_PICKUP_POINT_CARRIER` as the fallback, and the point identification from
the stored order row: an internal point is sent as `id`, an external one as `carrierId` together
with `carrierPickupPointId`
[VERIFY: packetery/libs/PickupPointValidate/PickupPointValidator.php#createPickupPointValidateRequest].
For internal points the carrier's `allowed_vendors` JSON is expanded into one vendor entry per
country and group, and the `CarrierVendors::VENDOR_GROUP_ZPOINT` group is sent without a group key
[VERIFY: packetery/libs/PickupPointValidate/PickupPointValidator.php#allowed_vendors]. The
`livePickupPoint` flag mirrors `CartService::isAgeVerificationRequired`, so a cart with age-limited
goods asks for a staffed point [VERIFY: packetery/libs/PickupPointValidate/ValidatedOptions.php#livePickupPoint].

`ValidatedOptions` and `ValidatedPoint` are plain value objects whose `getSubmittableData` runs
`array_filter` over their own properties, so every null, false and empty value disappears from the
payload rather than being sent as an explicit null
[VERIFY: packetery/libs/PickupPointValidate/ValidatedOptions.php#getSubmittableData]. The answer is
accepted only when it is a JSON object with a boolean `isValid` and an array `errors`; anything else
raises `HttpRequestException`, which the validator turns into the permissive response
[VERIFY: packetery/libs/PickupPointValidate/PickupPointValidate.php#isValid].

## packetery-checkout: checkout javascript

packetery-checkout ships the storefront behaviour as `front.js` plus one adapter per supported
checkout theme, because every theme names its delivery inputs and its submit button differently.
`front.js` loads the Packeta widget library asynchronously, waits for it, then binds on document
ready and stops immediately when the page carries no module configuration
[VERIFY: packetery/views/js/front.js#onThisScriptLoad]. `PacketeryCheckoutModulesManager` walks a
fixed list of adapter names, keeps the ones whose global object exists, and picks the first one
whose `isActive()` is true; the order matters and `Unknown` is deliberately last
[VERIFY: packetery/views/js/front.js#supportedModules].

| Adapter | Selected input | Activates when | Anchor |
|---|---|---|---|
| `PacketeryCheckoutModulePs16` | `.delivery_option input:checked` | PrestaShop 1.6 and delivery options present | [VERIFY: packetery/views/js/checkout-modules/ps16.js#PacketeryCheckoutModulePs16] |
| `PacketeryCheckoutModulePs17` | `.delivery-option input:checked` | Delivery options present, no version test | [VERIFY: packetery/views/js/checkout-modules/ps17.js#PacketeryCheckoutModulePs17] |
| `PacketeryCheckoutModuleSupercheckout` | `#shipping-method input:checked` | Delivery options present | [VERIFY: packetery/views/js/checkout-modules/supercheckout.js#PacketeryCheckoutModuleSupercheckout] |
| `PacketeryCheckoutModuleOpcZelarg` | `.delivery_option input:checked` | PrestaShop 1.6 and a `carriers_section` form | [VERIFY: packetery/views/js/checkout-modules/opczelarg.js#PacketeryCheckoutModuleOpcZelarg] |
| `PacketeryCheckoutModuleHummingbird` | `.js-delivery-option input[id^=delivery_option]:checked` | PrestaShop 8 or 9 and delivery options present | [VERIFY: packetery/views/js/checkout-modules/hummingbird.js#PacketeryCheckoutModuleHummingbird] |
| `PacketeryCheckoutModuleUnknown` | `.delivery_option.selected input` | Delivery options present | [VERIFY: packetery/views/js/checkout-modules/unknown.js#PacketeryCheckoutModuleUnknown] |

Each adapter answers the same questions: which delivery inputs exist, which one is selected, and how
to enable or disable the submit control. The shared code finds the widget markup of a carrier by the
element id `packetery-carrier-` followed by the carrier id
[VERIFY: packetery/views/js/front.js#getWidgetParent]. On a delivery change the submit control is
disabled first, and every later path ends in `toggleSubmit`, which enables it again unless a pickup
point is missing or a required address validation is unsatisfied
[VERIFY: packetery/views/js/front.js#isPickupPointInvalid]. A home-delivery carrier whose validation level
is not `required` therefore gets the control back with no branch id and no validated address
[VERIFY: packetery/views/js/front.js#isAddressValidationUnsatisfied]. PrestaShop 1.6 has no carrier extra content,
so the module fetches the markup per carrier over AJAX and caches the answer per carrier id
[VERIFY: packetery/views/js/front.js#extraContentCache]. Validated addresses are kept per carrier in
a page-lifetime object and re-sent when the customer switches back
[VERIFY: packetery/views/js/front.js#isAddressValidationSatisfied].

The widget itself is opened from the same file. `Packeta.Widget.pick` receives the API key from the
page configuration, the shop language, the app identity and either an explicit vendor list from the
`widget_vendors` input or a country, and the age-verification flag adds `livePickupPoint`
[VERIFY: packetery/views/js/front.js#initializeWidget]. The pick callback copies the returned `id`,
`name`, `currency`, `pickupPointType`, `carrierId` and `carrierPickupPointId` into the hidden inputs
of `widget.tpl` and posts the whole point to the checkout controller
[VERIFY: packetery/views/templates/front/widget.tpl#widget_vendors]. The four address fields are
written to `.packeta-place`, `.packeta-street`, `.packeta-city` and `.packeta-zip`
[VERIFY: packetery/views/js/front.js#packeta-place], while `widget.tpl` declares the inputs under
`packeta-point-place`, `packeta-point-street`, `packeta-point-city` and `packeta-point-zip`
[VERIFY: packetery/views/templates/front/widget.tpl#packeta-point-place], so those four selectors
match no element. The home-delivery button opens
the same widget with the `hd` layout and one carrier id; the returned address is accepted only when
its country matches the delivery country, otherwise the page shows the country-differs message and
calls `toggleSubmit`, which leaves the control disabled only when the validation level is `required`
[VERIFY: packetery/views/js/front.js#open-packeta-widget-hd].

## packetery-checkout: dependencies

packetery-checkout reaches one system outside this repository and is otherwise wired to the other
modules of the repository through the dependency container and through static helper calls.

calls → packeta-widget (sync, HTTP/JSON)

`PickupPointValidate::validate` posts the request JSON with the API key added to the validation
endpoint held in `URL_VALIDATE_ENDPOINT` and reads the answer back as `isValid` plus `errors`
[VERIFY: packetery/libs/PickupPointValidate/PickupPointValidate.php#URL_VALIDATE_ENDPOINT]; the call
carries no customer identity, only the country, the carrier, the age-verification flag, the vendor
list and the identification of the point that is re-checked. The browser reaches the same system
once more, because `front.js` pulls the widget library over HTTP before it binds anything
[VERIFY: packetery/views/js/front.js#onThisScriptLoad]. Both addresses are values and are not copied
here; the constant and that script call are where they live.

The front controller resolves three collaborators from `$this->module->diContainer` and does nothing else with them: `OrderSaver` and
`Ajax` of `packetery-order` persist the pickup point and the validated address, and
`Packetery\Module\Cart` of `packetery-module` renders the carrier extra content
[VERIFY: packetery/controllers/front/checkout.php#display]. `PickupPointValidator` is constructed
with `ConfigHelper`, `LogRepository` and `HttpClientWrapper` of `packetery-tools`, the module class
of `packetery-module` and the local `CartService`
[VERIFY: packetery/libs/PickupPointValidate/PickupPointValidator.php#__construct], and
it reaches into `packetery-carrier` for `CarrierTools` and `CarrierVendors`. `CartService` itself
depends on `ProductAttributeRepository` of `packetery-module`
[VERIFY: packetery/libs/Cart/CartService.php#__construct]. The request and response value objects
`PickupPointValidateRequest` and `PickupPointValidateResponse` live in `packetery-order`.

In the other direction, `packetery-module` drives the whole checkout: its
`ActionValidateStepComplete` hook calls `PickupPointValidator` and `AddressTools::hasValidatedAddress`
before it lets the delivery step complete
[VERIFY: packetery/libs/Hooks/ActionValidateStepComplete.php#pickupPointValidator], and
`hookDisplayBeforeCarrier` builds the JSON page configuration the storefront JavaScript reads
[VERIFY: packetery/packetery.php#packetaModuleConfig]. `packetery-order` uses `Weight\Calculator`
when it exports an order [VERIFY: packetery/libs/Order/OrderExporter.php#weightCalculator].

The platform coupling is PrestaShop itself and is not a dependency edge. The controller extends
`ModuleFrontController`, the templates are Smarty files fetched by the hooks of the main module
class, and the storefront code assumes jQuery is present. Three configuration keys are read through
`Configuration`: `PS_WEIGHT_UNIT` decides the weight factor
[VERIFY: packetery/libs/Weight/Converter.php#PS_WEIGHT_UNIT], and
`PACKETERY_DEFAULT_PACKAGE_WEIGHT` together with `PACKETERY_DEFAULT_PACKAGING_WEIGHT` fill in the
missing order weight [VERIFY: packetery/libs/Weight/Calculator.php#PACKETERY_DEFAULT_PACKAGING_WEIGHT].
The extra validation round is switched by `ConfigHelper::isApiWidgetValidationModeEnabled`, which the
hook reads before it calls the validator
[VERIFY: packetery/libs/Hooks/ActionValidateStepComplete.php#isApiWidgetValidationModeEnabled].

## packetery-checkout: known limitations

packetery-checkout carries several limitations that are visible in the code itself. Validation fails
open: a missing API key, a transport error or an unparsable answer all end in a response that
reports the point as valid, so an outage of the widget API silently disables the extra check
[VERIFY: packetery/libs/PickupPointValidate/PickupPointValidate.php#validate].

The Supercheckout adapter declares its extra-content flag as `toggleExtracContent`, while the shared
code reads `module.toggleExtraContent`; the two names differ, so the declared value never reaches
the reader [VERIFY: packetery/views/js/checkout-modules/supercheckout.js#toggleExtracContent]. The
same adapter cannot enable or disable the theme submit button at all — both methods are empty and a
validator callback is registered instead
[VERIFY: packetery/views/js/checkout-modules/supercheckout.js#addSupercheckoutOrderValidator].

The `Unknown` adapter is marked in the source as a candidate for removal and as untestable
[VERIFY: packetery/views/js/checkout-modules/unknown.js#PacketeryCheckoutModuleUnknown]. The
PrestaShop 1.6 extra-content path carries an open task about address validation and adds the widget
markup only for carriers listed as pickup-point carriers, so address validation is not wired up
there [VERIFY: packetery/views/js/front.js#addAllExtraContents]. The AJAX helper toggles a `wait`
CSS class whose owning checkout module the comment states is unknown
[VERIFY: packetery/views/js/front.js#beforeSend], and the automatic widget opening keeps a
commented-out guard with an open question
[VERIFY: packetery/views/js/front.js#autoOpenWidget].

Weight conversion is limited to the units that have a factor in the static mapping; for any other
shop unit `Calculator` treats the order weight as zero rather than reporting a problem, and only the
configured default weights can then make the shipment non-empty
[VERIFY: packetery/libs/Weight/Calculator.php#convertUnits].
