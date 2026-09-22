---
title: "prestashop — dependencies and external links"
repo: prestashop
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers: [composer.json, composer.lock, packetery/libs/Module/SoapApi.php, packetery/libs/ApiCarrier/Downloader.php, packetery/libs/PickupPointValidate/PickupPointValidate.php, packetery/libs/Module/VersionChecker.php]
confidence: reviewed
tags: [ai-generated, repo-prestashop, type-dependencies]
---

Repo: prestashop · Module: — · Type: dependencies · Status: current

## prestashop: links outside the repo

prestashop communicates with 5 systems outside its own code.

calls → packeta-soap-api (sync, SOAP)
calls → packeta-pickup-point-api (sync, HTTP/JSON)
calls → packeta-widget (sync, HTTP/JSON)
calls → github-api (sync, HTTP/JSON)
calls → mysql (sync, SQL)

| Counterpart | Direction | Sync/async | Protocol | Where in code | Anchor |
|---|---|---|---|---|---|
| packeta-soap-api | calls | sync | SOAP | `packetery/libs/Module/SoapApi.php` | [VERIFY: packetery/libs/Module/SoapApi.php#_PACKETERY_SOAP_WSDL_URL_] |
| packeta-pickup-point-api | calls | sync | HTTP/JSON | `packetery/libs/ApiCarrier/Downloader.php` | [VERIFY: packetery/libs/ApiCarrier/Downloader.php#API_URL] |
| packeta-widget | calls | sync | HTTP/JSON | `packetery/libs/PickupPointValidate/PickupPointValidate.php` | [VERIFY: packetery/libs/PickupPointValidate/PickupPointValidate.php#URL_VALIDATE_ENDPOINT] |
| github-api | calls | sync | HTTP/JSON | `packetery/libs/Module/VersionChecker.php` | [VERIFY: packetery/libs/Module/VersionChecker.php#GITHUB_RELEASES_ENDPOINT_URL] |
| mysql | calls | sync | SQL | `packetery/libs/Tools/DbTools.php` | [VERIFY: packetery/libs/Tools/DbTools.php#execute] |

packeta-soap-api carries everything that changes a packet: creating it, cancelling it, asking for
its labels, its barcode and its delivery history, and checking the API password and the sender
indication. packeta-pickup-point-api delivers the carrier catalogue that the pairing form offers,
and it is read-only. packeta-widget is reached twice — the browser loads the widget library from it
and the server re-checks a chosen point against it before the delivery step may finish. github-api
is only the update notice; when it is unreachable the module keeps the last stored answer and
retries after the check interval [VERIFY: packetery/libs/Module/VersionChecker.php#checkForUpdate].
mysql is the shop's own database; a failed statement becomes a `DatabaseException` that the callers
handle case by case — `executeQueries` logs it and carries on, and the consignment-code task skips
the failing order [VERIFY: packetery/libs/Cron/Tasks/GetConsignPassword.php#DatabaseException].

## prestashop: links between modules

prestashop holds together through in-process PHP calls; no module of this repository calls another
over a network.

calls → packetery-order (sync, in-process)
calls → packetery-carrier (sync, in-process)
calls → packetery-module (sync, in-process)
calls → packetery-packet-tracking (sync, in-process)
calls → packetery-tools (sync, in-process)

| From module | To module | Link | Sync/async | Protocol | Anchor |
|---|---|---|---|---|---|
| packetery-checkout | packetery-order | calls | sync | in-process | [VERIFY: packetery/controllers/front/checkout.php#savePickupPointInCart] |
| packetery-checkout | packetery-module | calls | sync | in-process | [VERIFY: packetery/controllers/front/checkout.php#fetchExtraContent] |
| packetery-checkout | packetery-carrier | calls | sync | in-process | [VERIFY: packetery/libs/PickupPointValidate/PickupPointValidator.php#CarrierTools] |
| packetery-admin-ui | packetery-order | calls | sync | in-process | [VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#processBulkCreatePacket] |
| packetery-admin-ui | packetery-carrier | calls | sync | in-process | [VERIFY: packetery/controllers/admin/PacketeryCarrierGridController.php#CarrierAdminForm] |
| packetery-admin-ui | packetery-packet-tracking | calls | sync | in-process | [VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#PacketStatusFactory] |
| packetery-packet-tracking | packetery-order | calls | sync | in-process | [VERIFY: packetery/libs/Cron/Tasks/GetConsignPassword.php#execute] |
| packetery-packet-tracking | packetery-carrier | calls | sync | in-process | [VERIFY: packetery/libs/Cron/Tasks/DownloadCarriers.php#Downloader] |
| packetery-order | packetery-module | calls | sync | in-process | [VERIFY: packetery/libs/Order/PacketCanceller.php#soapApi] |
| packetery-order | packetery-tools | calls | sync | in-process | [VERIFY: packetery/libs/Order/OrderRepository.php#getWithShopById] |

The table lists one representative call site per edge; packetery-tools is the one module every
other module depends on and that depends on none of them.
packetery-module is both the entry point and a service provider: it registers the hooks that call
the other modules, and it owns the SOAP facade they call back into. The container resolves these
edges by class name, so a static call graph shows them only through the constructor signatures
[VERIFY: packetery/libs/DI/Container.php#ReflectionClass].

## prestashop: external packages

prestashop declares no runtime package dependency at all. `composer.json` requires PHP 8.1 or newer
and nothing else for production; every other entry is a development tool
[VERIFY: composer.json#require-dev]. The module gets its framework classes from the PrestaShop
installation it is deployed into, not from Composer, and the repository ships no `vendor/`
directory for runtime use.

| Package | Version | Role in prestashop | Anchor |
|---|---|---|---|
| php | ^8.1 | the only production requirement | [VERIFY: composer.json:3] |
| phpstan/phpstan | ^2.1 | static analysis in the `phpstan` Composer script | [VERIFY: composer.json:9] |
| stancer/php-stubs-prestashop | ^8.2 | PrestaShop class stubs, so static analysis can resolve the framework classes | [VERIFY: composer.json:11] |
| squizlabs/php_codesniffer | ^3.12 | the coding-standard check behind `check:phpcs` | [VERIFY: composer.json:6] |
| prestashop/php-dev-tools | ^4.0 | the PrestaShop fixer preset used by `PacketeryCsFixerConfig` | [VERIFY: PacketeryCsFixerConfig.php#getRules] |

Transitive packages and the remaining development tools are left out of the table; they change no
runtime behaviour. Two PHP extensions are hard requirements the code checks for itself: the install
refuses to run without `curl`, and the configuration page reports a missing `soap` extension
[VERIFY: packetery/packetery.php:277].

## prestashop: environment configuration

prestashop reads these keys that point at external systems. The first two are configuration rows in
the shop database; the last three are PHP constants a deployment may define outside this repository.

| Key | Read in | Where the value lives | Anchor |
|---|---|---|---|
| `PACKETERY_APIPASS` | `packetery/libs/Tools/ConfigHelper.php` | the shop's `configuration` table | [VERIFY: packetery/libs/Tools/ConfigHelper.php#KEY_APIPASS] |
| `PACKETERY_ESHOP_ID` | `packetery/libs/Order/OrderExporter.php` | the shop's `configuration` table, stored per shop | [VERIFY: packetery/libs/Tools/ConfigHelper.php#KEY_ESHOP_ID] |
| `_PACKETERY_SOAP_WSDL_URL_` | `packetery/libs/Module/SoapApi.php` | a PHP constant of the deployment; unset falls back to the built-in default | [VERIFY: packetery/libs/Module/SoapApi.php#_PACKETERY_SOAP_WSDL_URL_] |
| `_GITHUB_ACCESS_TOKEN_` | `packetery/libs/Module/ApiClientFacade.php` | a PHP constant of the deployment | [VERIFY: packetery/libs/Module/ApiClientFacade.php:37] |
| `_PACKETERY_ALLOWED_RELEASE_TYPES_` | `packetery/libs/Module/VersionChecker.php` | a PHP constant of the deployment | [VERIFY: packetery/libs/Module/VersionChecker.php:90] |

`PACKETERY_APIPASS` is the credential every Packeta call authenticates with, and the API key the
carrier feed and the widget use is derived from it rather than stored separately
[VERIFY: packetery/libs/Tools/ConfigHelper.php#getValidApiKey]. Whether the three constants are
defined in a given installation cannot be answered from this repository; that gap is recorded in
`manifest.yaml` under `unknowns`.

## prestashop: aliases

prestashop has no documented alias. `manifest.yaml` carries no `aliases` section, because no second
name for the repository or for any of its modules has support in the code: the module class, the
module name, the directory and the translation domain all use `packetery`
[VERIFY: packetery/packetery.php#MODULE_SLUG], and the seven module ids were coined by this
documentation run rather than taken from a name the code already uses.
