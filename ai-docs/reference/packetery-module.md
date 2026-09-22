---
title: "prestashop — packetery-module module"
repo: prestashop
module: packetery-module
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers:
  - packetery/packetery.php
  - packetery/autoload.php
  - packetery/libs/Module/
  - packetery/libs/Hooks/
  - packetery/libs/DI/
  - packetery/libs/AbstractFormService.php
  - packetery/libs/Product/
  - packetery/views/templates/hook/
  - packetery/upgrade/
confidence: reviewed
tags: [ai-generated, repo-prestashop, module-packetery-module, type-reference]
---

Repo: prestashop · Module: packetery-module · Type: reference · Status: current

## packetery-module: purpose

packetery-module is the PrestaShop entry point of the Packeta integration: the `Packetery` class that
PrestaShop loads, installs, configures and calls back through hooks. The class extends
`CarrierModule`, declares the module name `packetery`, its version and the supported PrestaShop
range [VERIFY: packetery/packetery.php#ps_versions_compliancy], and builds a dependency-injection
container in its constructor, so that every other module of this repository is reached through
`diContainer` instead of through `new` [VERIFY: packetery/libs/DI/ContainerFactory.php#create].

packetery-module carries four responsibilities that no other module of this repository holds. It
owns the install and uninstall lifecycle — database schema, PrestaShop configuration keys, admin
menu tabs and hook registration [VERIFY: packetery/libs/Module/Installer.php#run]. It owns the
hook surface: `getModuleHooksList` names the hooks the module registers, and the handler methods on
the `Packetery` class receive them [VERIFY: packetery/packetery.php#getModuleHooksList]. It owns the
module configuration screen in the PrestaShop back office, including the option map, the validation
of the entered values and the rendered form [VERIFY: packetery/packetery.php#getConfigurationOptions].
And it owns the SOAP facade `SoapApi` [VERIFY: packetery/libs/Module/SoapApi.php#WSDL_URL] and the
release checker `VersionChecker` [VERIFY: packetery/libs/Module/VersionChecker.php#checkForUpdate],
which the other modules reach through the container; those two are not the repository's only
outbound clients.

packetery-module also holds a small amount of product state: a per-product age-verification flag
edited in the product detail of the back office [VERIFY: packetery/packetery.php#hookDisplayAdminProductsExtra].
The class-level autoloader maps the `Packetery` namespace onto `packetery/libs/`
[VERIFY: packetery/autoload.php#spl_autoload_register].

> ⚠ add business context (elicitation)

## packetery-module: public interface

packetery-module exposes 111 public methods on 20 types, of which the `Packetery` class itself is
the interface PrestaShop calls. The table names the entry points; the remaining members are
getters, helper methods and the per-hook classes in `packetery/libs/Hooks/`.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| `install` / `uninstall` | `Packetery::install()`, `Packetery::uninstall()` | Refuse to install without the cURL extension, then delegate the whole lifecycle to `Installer` and `Uninstaller` | [VERIFY: packetery/libs/Module/Installer.php#run] |
| `Packetery::getContent` | module configuration page in the back office | Renders the settings form, applies the three submit actions and reports SOAP absence and a newer release | [VERIFY: packetery/packetery.php#getContent] |
| `displayForm` | `Packetery::displayForm()` | Builds a `HelperForm` from the option map and appends the packet-tracking, order-status and cron blocks | [VERIFY: packetery/packetery.php#displayForm] |
| `getModuleHooksList` | `Packetery::getModuleHooksList()` | Returns the hook names registered at install; the list branches on the PrestaShop version for the admin order hook | [VERIFY: packetery/packetery.php#getModuleHooksList] |
| `hookDisplayCarrierExtraContent` | checkout, per carrier | Renders the pickup-point widget block or the address-validation block from the saved order row | [VERIFY: packetery/packetery.php#hookDisplayCarrierExtraContent] |
| `packeteryHookDisplayAdminOrder` | admin order detail | Assembles the Packeta panel: packet status, widget options, weight, prices, submit and cancel buttons | [VERIFY: packetery/packetery.php#packeteryHookDisplayAdminOrder] |
| `hookActionValidateOrder` | order creation | Hands the cart and the order to the order saver, or logs the parameter types and returns | [VERIFY: packetery/packetery.php#hookActionValidateOrder] |
| `hookActionProductUpdate` | product save | Inserts or updates the age-verification row for the product | [VERIFY: packetery/packetery.php#hookActionProductUpdate] |
| `Container::get` / `Container::register` | `packetery/libs/DI/Container.php` | Resolves a class by reflection over constructor parameter types and caches one instance per class | [VERIFY: packetery/libs/DI/Container.php#getParamInstances] |
| `SoapApi` methods | `packetery/libs/Module/SoapApi.php` | `senderGetReturnRouting`, `getPacketInfo`, `packetCarrierNumber`, `getPacketTracking`, `getPacketsLabelsPdf`, `getPacketsCourierLabelsPdf`, `cancelPacket`, `createShipment`, `barcodePng` | [VERIFY: packetery/libs/Module/SoapApi.php#senderGetReturnRouting] |

Each hook handler is thin. `hookSendMailAlterTemplateVars`, `hookActionObjectOrderUpdateBefore` and
`hookActionValidateStepComplete` only resolve a class from `packetery/libs/Hooks/` and call its
`execute` method [VERIFY: packetery/libs/Hooks/ActionValidateStepComplete.php#execute]. The two
extra form blocks on the configuration page come from `AbstractFormService`, which builds a
`HelperForm`, fills it from the stored configuration and persists the submitted values through
`Options` [VERIFY: packetery/libs/AbstractFormService.php#generateForm]. The templates rendered by
these handlers live in `packetery/views/templates/hook/`, the admin order panel among them
[VERIFY: packetery/views/templates/hook/displayOrderMain.tpl#packetaPickupPointChange].

## packetery-module: install and upgrade lifecycle

packetery-module performs the install in four steps, and a failure of any step fails the install:
seed the configuration keys, create the database tables, register the hooks, insert the admin menu
items [VERIFY: packetery/libs/Module/Installer.php#run]. The schema step drops and recreates
`packetery_order`, `packetery_payment` and `packetery_address_delivery` inline, and takes the
remaining tables from the repositories that own them, each of which supplies its own create and
drop statement [VERIFY: packetery/libs/Module/Installer.php#installDatabase]. The configuration
step writes the default label formats, the widget settings, a generated cron token, the default
package price and weight and the consignment-code mode [VERIFY: packetery/libs/Module/Installer.php#updateConfiguration].
The menu step adds a `Packetery` tab under `SELL` and four child tabs — `PacketeryOrderGrid`,
`PacketeryCarrierGrid`, `PacketerySetting` and `PacketeryLogGrid` — whose names are translated only
for the languages in `TRANSLATED_LANGUAGES` [VERIFY: packetery/libs/Module/Installer.php#insertMenuItems].

packetery-module uninstalls in the mirror order: delete the five tabs, drop the tables, unregister
the hooks, delete the configuration keys [VERIFY: packetery/libs/Module/Uninstaller.php#uninstallDatabase].
Two details differ from a plain reversal. The order table is not dropped but renamed to a backup
table, so the export state of past orders survives an uninstall, and the shop `carrier` rows that
name this module are reset instead of removed. A hook that cannot be unregistered is collected and
logged, and the step still reports success [VERIFY: packetery/libs/Module/Uninstaller.php#unregisterHooks].
The key list of the uninstall and the key list of the settings form differ in both directions: the
uninstall deletes five keys the form never writes, and leaves two keys the form does write
[VERIFY: packetery/libs/Module/Uninstaller.php#deleteConfiguration].

packetery-module ships the migration scripts in `packetery/upgrade/` as one file per released
version, each declaring a single `upgrade_module_*` function that PrestaShop runs when it detects a
newer module version [VERIFY: packetery/upgrade/upgrade-3.4.0.php#upgrade_module_3_4_0]. The scripts
are migration code and stay outside the 2940 lines of logic counted for this module; they are not
documented file by file, because each one is a one-shot transformation of a schema or a
configuration value that the current code no longer reads.

## packetery-module: dependencies

packetery-module reaches two systems outside the shop and one data store.

calls → packeta-soap-api (sync, SOAP over HTTP)
calls → github-api (sync, HTTP/JSON)
calls → mysql (sync, SQL)

The SOAP link is the shipping API. `SoapApi` creates a `SoapClient` per call against the WSDL
address, which a deployment can override with the `_PACKETERY_SOAP_WSDL_URL_` constant
[VERIFY: packetery/libs/Module/SoapApi.php#WSDL_URL]. Every call but `senderGetReturnRouting`, which takes the password as an argument so the settings
form can check a value before it is stored, sends the password kept in the configuration
[VERIFY: packetery/libs/Module/SoapApi.php#senderGetReturnRouting]. Faults are mapped to typed
responses or to `IncorrectApiPasswordException` and `SenderNotExistsException`, which the settings
validation turns into a page-level alert above the form
[VERIFY: packetery/libs/Module/Options.php#validate]. The GitHub link is the update check:
`VersionChecker` fetches the release list at most once per `CHECK_INTERVAL_IN_SECONDS`, accepts only
the release types allowed by `_PACKETERY_ALLOWED_RELEASE_TYPES_`, and stores the resulting version,
download address and release notes in the shop configuration
[VERIFY: packetery/libs/Module/VersionChecker.php#checkForUpdate]. The request carries a GitHub
token when `_GITHUB_ACCESS_TOKEN_` is defined [VERIFY: packetery/libs/Module/ApiClientFacade.php#getWithGithubAuthorizationToken].

Inside the repository the coupling runs through the container, not through imports: the `Packetery`
class resolves repositories and services of the other modules by class name when a hook fires
[VERIFY: packetery/packetery.php#packeteryHookDisplayAdminOrder]. The traffic also runs inwards.
`SoapApi` is resolved by the packet submitter of packetery-order
[VERIFY: packetery/libs/Order/PacketSubmitter.php#SoapApi], by the tracking cron of
packetery-packet-tracking [VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#SoapApi] and
by the order grid of packetery-admin-ui
[VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#SoapApi];
`Packetery\Module\Cart` is called by the
front checkout controller of packetery-checkout to rebuild the carrier extra content on
PrestaShop 1.6 [VERIFY: packetery/controllers/front/checkout.php#packeteryCreateExtraContent]; and
`CompanyAddress` is read by the collection-print handler of packetery-order
[VERIFY: packetery/libs/Order/CollectionPrintHandler.php#CompanyAddress].

packetery-module is bound to the PrestaShop platform itself rather than to a service: it extends
`CarrierModule`, reads and writes settings through `Configuration`, renders through Smarty and
`HelperForm`, and registers its own hook names, which PrestaShop dispatches in process.

## packetery-module: data model

packetery-module is the source of truth for one table, `packetery_product_attribute`, and for the
module-level configuration keys the settings form writes.

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| `packetery_product_attribute` | `id_product` | `int(11)`, primary key | One row per product; the row is deleted with the product | [VERIFY: packetery/libs/Product/ProductAttributeRepository.php#getCreateTableSql] |
| `packetery_product_attribute` | `is_adult` | `tinyint(1)`, default 0 | Set from the checkbox in the product detail of the back office | [VERIFY: packetery/packetery.php#hookActionProductUpdate] |
| `ProductAttributes` | `isForAdults` | bool | Read model built from the table row | [VERIFY: packetery/libs/Product/ProductAttributes.php#fromDbRow] |
| `CompanyAddress` | `country`, `company`, `street`, `zip`, `city` | string | Built from the country map in the module class, falling back to `DEFAULT_COUNTRY` | [VERIFY: packetery/libs/Module/CompanyAddress.php#fromCountry] |

The configuration keys are ordinary PrestaShop `Configuration` rows. The settings form writes the
API password key `PACKETERY_APIPASS`, the sender indication `PACKETERY_ESHOP_ID`, the label formats
`PACKETERY_LABEL_FORMAT` and `PACKETERY_CARRIER_LABEL_FORMAT`, the label note
`PACKETERY_LABEL_NOTE`, the order-id preference `PACKETERY_ID_PREFERENCE`, the widget keys
`PACKETERY_WIDGET_AUTOOPEN` and `PACKETERY_WIDGET_VALIDATION_MODE`, the default package price and
weights, the currency-conversion switch `PACKETERY_USE_PS_CURRENCY_CONVERSION` and the two
consignment-code keys `PACKETERY_SHOW_CONSIGN_PASSWORD` and `PACKETERY_CONSIGN_PASSWORD_RETRIEVAL`
[VERIFY: packetery/packetery.php#getConfigurationOptions]. Six further keys
are written by the code and never shown in the form: the cron token generated at install, the
carrier-update timestamp `PACKETERY_LAST_CARRIERS_UPDATE`, the check timestamp
`PACKETERY_LAST_VERSION_CHECK_TIMESTAMP`, and the version-check triple
`PACKETERY_LAST_VERSION`, `PACKETERY_LAST_VERSION_URL` and `PACKETERY_LAST_RELEASE_NOTES`, which
`LatestReleaseResponse` fills [VERIFY: packetery/libs/Module/VersionChecker.php#getLatestReleaseResponse].
The address constant that supplies `CompanyAddress` is a hardcoded table of five country entries in
the module class [VERIFY: packetery/packetery.php#PACKETA_ADDRESS].

## packetery-module: known limitations

packetery-module carries several limitations that the code states outright. The container resolves
constructor parameters by class type only and throws on a builtin type, so a service that needs a
scalar argument cannot be registered without a factory closure
[VERIFY: packetery/libs/DI/Container.php#getParamInstances]. The module file may not use the PHP
`use` keyword, because PrestaShop 1.6 fails to load the main file with it, which is why every class
in `packetery.php` is written with its full name [VERIFY: packetery/packetery.php#ps_versions_compliancy].
The extra-content toggle passed to the front end is hardcoded to `false` with a TODO asking whether
it should become configurable [VERIFY: packetery/packetery.php#toggleExtraContent]. Address
validation is marked as unfixed for PrestaShop 1.6 in the 1.6-only carrier-content path
[VERIFY: packetery/libs/Module/Cart.php#TODO].

The install refuses to continue without the cURL extension. The configuration check only collects
warnings — that neither cURL nor `allow_url_fopen` is available, and that the module directory is
not writable, which its own message ties to the pickup-point selection; neither warning blocks
anything [VERIFY: packetery/packetery.php#configurationErrors]. The configuration page reports the absence of
the SOAP extension as an error message rather than blocking the page
[VERIFY: packetery/packetery.php#getContent]. The API password is validated by length against
`API_PASSWORD_LENGTH` and then against the API itself, so a wrong length is rejected before any
call goes out [VERIFY: packetery/libs/Module/Options.php#API_PASSWORD_LENGTH].

The version check swallows its own failures: outside developer mode the exception is logged only
when `_PACKETERY_DEBUG_LOG_` is defined and true, and the check timestamp is written anyway, so a
shop that cannot reach the release endpoint keeps silent until the next interval elapses
[VERIFY: packetery/libs/Module/VersionChecker.php#checkForUpdate]. Whether these optional
constants are defined in a given installation does not follow from this repository, and the
`unknowns` of the manifest fragment record that.
