---
title: "prestashop — packetery-carrier module"
repo: prestashop
module: packetery-carrier
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers: [packetery/libs/Carrier, packetery/libs/ApiCarrier]
confidence: reviewed
tags: [ai-generated, repo-prestashop, module-packetery-carrier, type-reference]
---

Repo: prestashop · Module: packetery-carrier · Type: reference · Status: current

## packetery-carrier: purpose

packetery-carrier pairs every PrestaShop shipping carrier with one Packeta carrier and keeps the
delivery parameters that the pairing implies. The pairing itself is one row of the
`packetery_address_delivery` table, written by a single method that also flips the PrestaShop
carrier to `is_module` and `external_module_name` when a Packeta carrier is selected, and clears
those flags when the pairing is removed [VERIFY: packetery/libs/Carrier/CarrierRepository.php#setPacketeryCarrier].

packetery-carrier also holds the catalogue the pairing chooses from. A downloader reads the Packeta
carrier feed, validates that every record carries the fourteen keys the mapper expects, and stores
the result in the `packetery_carriers` table
[VERIFY: packetery/libs/ApiCarrier/Downloader.php#validateCarrierData]. Two catalogue entries are
not in the feed at all: the mapper appends the internal pickup-point rows `\Packetery::ZPOINT` and
`\Packetery::PP_ALL` with default values before the save
[VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#addNonApiCarriers]. Records that stop
appearing in the feed are not deleted; they are flagged
[VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#setOthersAsDeleted].

packetery-carrier derives three kinds of parameter from that catalogue. The `pickup_point_type`
tells the checkout whether the carrier delivers to a Packeta pickup point (`internal` for
`\Packetery::ZPOINT`, `external` otherwise) or to an address
[VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#getPickupPointType]. The
`address_validation` level (`none`, `required`, `optional`) applies to home delivery and is offered
only when the PrestaShop carrier serves a country in `ADDRESS_VALIDATION_COUNTRIES`
[VERIFY: packetery/libs/Carrier/CarrierRepository.php#ADDRESS_VALIDATION_COUNTRIES]. The
`allowed_vendors` JSON narrows the pickup-point widget to selected vendor groups per country, and
is translated into the widget's vendor parameter at checkout time
[VERIFY: packetery/libs/Carrier/CarrierVendors.php#getWidgetParameter].

> ⚠ add business context (elicitation)

## packetery-carrier: public interface

packetery-carrier offers 53 public methods on 6 classes; six of them are constructors, so 47
carry behaviour. The classes group into a repository pair over the two tables, an admin form, a
vendor catalogue and a set of PrestaShop-facing helpers.

| Class | Public methods | Behaviour | Anchor |
|---|---|---|---|
| `CarrierRepository` | `existsById`, `getPacketeryCarriersList`, `getPickupPointCarriers`, `getAddressValidationLevels`, `getPacketeryCarrierById`, `getInternalPickupPointCarriers`, `getById`, `swapId`, `deleteById`, `updatePresta`, `updatePacketery`, `setPacketeryCarrier` | Reads and writes the pairing row; `setPacketeryCarrier` decides between insert, update and delete and keeps the PrestaShop `carrier` row in step | [VERIFY: packetery/libs/Carrier/CarrierRepository.php#setPacketeryCarrier] |
| `ApiCarrierRepository` | `save`, `insert`, `update`, `getCarrierIds`, `setOthersAsDeleted`, `getAdAndExternalCount`, `getByCountries`, `getById`, `getExternalPickupPointCountries`, `isExternalPickupPointCarrier`, `isPacketaCarrierEnabled`, `getCreateTableSql`, `getDropTableSql` | Owns the catalogue table, including its DDL; `isPacketaCarrierEnabled` treats an empty selection as valid | [VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#isPacketaCarrierEnabled] |
| `Downloader` | `run`, `validateCarrierData` | `run` never throws on a failed download — it returns a message array with `class` set to `danger` or `success` | [VERIFY: packetery/libs/ApiCarrier/Downloader.php#run] |
| `CarrierAdminForm` | `build`, `buildCarrierForm`, `buildCarrierOptionsForm`, `saveCarrier`, `saveCarrierOptions`, `getError`, `getHtml`, `addHtml`, `getAvailableCarriers`, `getCarrierWarning`, `getDefaultAllowedVendors` | Renders two `HelperForm` forms, handles the submits `submitCarrierForm` and `submitCarrierOptionsForm`, and redirects back to the edit screen of the same carrier after each save | [VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#saveCarrierOptions] |
| `CarrierVendors` | `getVendorsByCountries`, `getVendors`, `getWidgetParameter` | Holds the fixed vendor-group catalogue and converts a stored `allowed_vendors` value into widget vendor entries for one customer country | [VERIFY: packetery/libs/Carrier/CarrierVendors.php#getVendorsByCountries] |
| `CarrierTools` | `getZonesAndCountries`, `getCountries`, `getCarrierNameFromShopName`, `getEditLink`, `orderSupportsAgeVerification`, `findExternalCarrierId` | Resolves the active countries of a PrestaShop carrier from its zones and answers two questions about a stored order row | [VERIFY: packetery/libs/Carrier/CarrierTools.php#getZonesAndCountries] |

The first form lets the user pick the Packeta carrier. Its option list is filtered twice: the
internal pickup-point entries are dropped unless the PrestaShop carrier serves a country that has
them, and a carrier that is no longer available stays selectable only when it is the one already
stored [VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#getAvailableCarriers]. Options that are
disabled are also greyed out by a Smarty template rendered into the form
[VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#renderDisableCarriersScript]. The second form
shows the address-validation radio, the vendor checkboxes or the COD radio, depending on what the
paired catalogue entry allows, and renders nothing when none of the three applies
[VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#buildCarrierOptionsForm].

## packetery-carrier: data model

packetery-carrier works with two tables, both in the shop database with the PrestaShop table
prefix. `packetery_address_delivery` holds one row per paired PrestaShop carrier and is the shop's
own decision; `packetery_carriers` is the local copy of the Packeta carrier feed and is rewritten
on every download.

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| `packetery_address_delivery` | `id_carrier` | int, primary key | The PrestaShop carrier id; renumbered when PrestaShop replaces a carrier version | [VERIFY: packetery/libs/Carrier/CarrierRepository.php#swapId] |
| `packetery_address_delivery` | `id_branch` | varchar(255) | The catalogue id, numeric for a feed carrier or one of the internal constants | [VERIFY: packetery/libs/Carrier/CarrierRepository.php#getInternalPickupPointCarriers] |
| `packetery_address_delivery` | `pickup_point_type` | varchar(40), nullable | `internal`, `external`, or `NULL` for home delivery | [VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#getPickupPointType] |
| `packetery_address_delivery` | `address_validation` | varchar(40), nullable | Set only for home delivery, defaulting to `none`; forced to `NULL` for a pickup-point carrier | [VERIFY: packetery/libs/Carrier/CarrierRepository.php#setPacketeryCarrier] |
| `packetery_address_delivery` | `allowed_vendors` | text, nullable | JSON, country code to a list of vendor groups; all groups are allowed by default | [VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#getDefaultAllowedVendors] |
| `packetery_address_delivery` | `currency_branch` | char(3), nullable | Copied from the catalogue entry, but cleared for the two internal pickup-point ids | [VERIFY: packetery/libs/Carrier/CarrierRepository.php#setPacketeryCarrier] |
| `packetery_carriers` | `id` | varchar(255), unique | The feed carrier id, or an internal pickup-point constant | [VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#getCreateTableSql] |
| `packetery_carriers` | `is_pickup_points` | boolean | Mapped from the feed key `pickupPoints`; drives the form branch and the checkout | [VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#carriersMapper] |
| `packetery_carriers` | `disallows_cod` | boolean | Mapped from the feed key `disallowsCod`; hides the COD radio in the options form | [VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#disallows_cod] |
| `packetery_carriers` | `deleted` | boolean | Set on every row missing from the last feed, instead of removing the row | [VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#setOthersAsDeleted] |

The remaining catalogue columns are declared in the same DDL
[VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#getCreateTableSql] and reach the row by
two different paths. `name`, `country`, `currency` and `max_weight` are assigned in the mapper
itself, `max_weight` from the feed key `maxWeight`
[VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#carriersMapper]. The boolean columns
`available`, `has_carrier_direct_label`, `separate_house_number`, `customs_declarations`,
`requires_email`, `requires_phone` and `requires_size` take their feed key from the `apiName` entry
of the mapping table, which carries an `apiName` for boolean columns only
[VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#columnDefinitions]. A boolean column is
true only when the feed sends the literal string `true`
[VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#carriersMapper].

## packetery-carrier: dependencies

packetery-carrier reaches outside the repository for one thing only, the carrier catalogue.

calls → packeta-pickup-point-api (sync, HTTP/JSON)

The downloader builds the request URL from a constant and the configured API key, sends it through
the shared HTTP client wrapper and turns any transport failure into a `DownloadException`
[VERIFY: packetery/libs/ApiCarrier/Downloader.php#downloadJson]. The URL constant lives in
[VERIFY: packetery/libs/ApiCarrier/Downloader.php#API_URL]. After a successful save the run stamps
the configuration key `PACKETERY_LAST_CARRIERS_UPDATE` with the current time
[VERIFY: packetery/libs/ApiCarrier/Downloader.php#run].

Inside the repository packetery-carrier is a library, not a service: other modules resolve its
classes from the DI container and call them in process. The admin grid constructs `CarrierAdminForm`
for the edit screen [VERIFY: packetery/controllers/admin/PacketeryCarrierGridController.php#CarrierAdminForm];
the cron task `DownloadCarriers` wraps `Downloader::run` and reports only its failure text
[VERIFY: packetery/libs/Cron/Tasks/DownloadCarriers.php#execute]; the module class renumbers the
pairing row when PrestaShop replaces a carrier
[VERIFY: packetery/packetery.php#hookActionCarrierUpdate] and turns a stored pairing into widget
vendor parameters [VERIFY: packetery/packetery.php#getAllowedVendorsForOrder]; order handling reads
the pairing when it saves an order
[VERIFY: packetery/libs/Order/OrderSaver.php#getPacketeryCarrierById]; and pickup-point validation
resolves the carrier of a stored order through `CarrierTools`
[VERIFY: packetery/libs/PickupPointValidate/PickupPointValidator.php#findExternalCarrierId].

packetery-carrier is coupled to the PrestaShop platform directly rather than through an interface.
It instantiates `\Carrier` and reads its zones, it queries the platform `carrier` table in a join
[VERIFY: packetery/libs/Carrier/CarrierRepository.php#getPacketeryCarriersList], it writes the
platform carrier flags [VERIFY: packetery/libs/Carrier/CarrierRepository.php#updatePresta], and it
builds its admin screens with `\HelperForm` and `\Smarty`. The DDL of
`packetery_address_delivery` is not in this module; only the DDL of `packetery_carriers` is
[VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#getDropTableSql].

## packetery-carrier: known limitations

packetery-carrier carries several hardcoded lists that the code cannot explain. Age verification is
decided from a two-element array of Packeta carrier ids described in a comment as Czech and Slovak
home delivery; a carrier outside that array and outside the pickup-point case is reported as not
supporting it [VERIFY: packetery/libs/Carrier/CarrierTools.php#CARRIERS_SUPPORTING_AGE_VERIFICATION].
The countries that have internal Packeta pickup points are a fixed four-element list in the admin
form [VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#countriesWithInternalPickupPoints], and
the vendor groups offered for them are a fixed table of the same four countries with two groups
each [VERIFY: packetery/libs/Carrier/CarrierVendors.php#getVendors]. None of these three lists is
derived from the downloaded catalogue, so a change on the Packeta side needs a code change here.

Address validation is limited in the same way. The admin form offers the radio only when the
PrestaShop carrier serves a country in a two-element constant, and the comment next to that check
records that following the country of the paired Packeta carrier would be better but was avoided
because it would have to be read out of the carrier name
[VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#validationPossible].

The per-carrier COD flag is marked obsolete in the form's own help text, which recommends not using
it and announces its removal; the input is rendered only when the flag is already switched on, so
an existing setting can be turned off but not turned back on
[VERIFY: packetery/libs/Carrier/CarrierAdminForm.php#is_cod_1].

Two further limitations sit in the download path. `Downloader::run` catches exceptions only around
the fetch step and reports them as a message array, so a caller cannot distinguish a network failure
from an invalid response other than by the message text; the save step sits outside that catch and
its `DatabaseException` leaves the method
[VERIFY: packetery/libs/ApiCarrier/Downloader.php#run]. The
save is not transactional: rows are inserted or updated one by one and the deletion flag is applied
afterwards, so an interrupted run leaves the catalogue half-updated
[VERIFY: packetery/libs/ApiCarrier/ApiCarrierRepository.php#save].
