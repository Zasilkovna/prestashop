---
title: "prestashop — overview"
repo: prestashop
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers: [packetery/packetery.php, packetery/autoload.php, packetery/upgrade, packetery/translations, composer.json, PacketeryCsFixerConfig.php, phpcs.xml]
confidence: reviewed
tags: [ai-generated, repo-prestashop, type-overview]
---

Repo: prestashop · Module: — · Type: overview · Status: current

## prestashop: what the repo does

prestashop is a single PrestaShop module that connects a shop to the Packeta delivery network. The
module class `Packetery` extends `CarrierModule`, declares the module name `packetery` and the
version, and states that it supports PrestaShop from 1.7.7.0 upwards
[VERIFY: packetery/packetery.php#ps_versions_compliancy]. It lets a customer pick a Packeta pickup
point or a validated home-delivery address during checkout, lets an employee pair PrestaShop
carriers with Packeta services, submits the finished orders to Packeta as packets, prints labels
and the bill of delivery, and polls Packeta for packet statuses
[VERIFY: packetery/packetery.php#getModuleHooksList].

prestashop has no runtime of its own. PrestaShop loads `packetery/packetery.php`, and every entry
point of the repository is either a PrestaShop hook handler, a registered admin controller or a
registered front controller [VERIFY: packetery/libs/Module/Installer.php:52]. The
repository ships no server, no queue consumer and no scheduler.

> ⚠ add business context (elicitation)

## prestashop: modules

prestashop consists of 7 modules. All 7 passed the threshold for a standalone `reference/`
document, so `overview.md` carries no module summary of its own. The module boundaries are not
declared anywhere in the code: the repository is one PrestaShop module of about 9 700 lines of
logic, which is above the 3 000-line ceiling for one reference page, so it was split along
namespaces and directories. The `path` column names the primary directory of each slice; the full
set of covered paths is in the `covers` front matter of each reference page.

| Module | Path | Lines of logic | Document |
|---|---|---|---|
| packetery-module | `packetery/libs/Module` | 2940 | [reference/packetery-module.md](reference/packetery-module.md) |
| packetery-order | `packetery/libs/Order` | 2567 | [reference/packetery-order.md](reference/packetery-order.md) |
| packetery-carrier | `packetery/libs/Carrier` | 1195 | [reference/packetery-carrier.md](reference/packetery-carrier.md) |
| packetery-admin-ui | `packetery/controllers/admin` | 940 | [reference/packetery-admin-ui.md](reference/packetery-admin-ui.md) |
| packetery-packet-tracking | `packetery/libs/PacketTracking` | 939 | [reference/packetery-packet-tracking.md](reference/packetery-packet-tracking.md) |
| packetery-tools | `packetery/libs/Tools` | 743 | [reference/packetery-tools.md](reference/packetery-tools.md) |
| packetery-checkout | `packetery/libs/PickupPointValidate` | 411 | [reference/packetery-checkout.md](reference/packetery-checkout.md) |

The counts come from `ai-docs/ast/map.json` and leave out the PrestaShop directory-guard stub files
named `index.php`, the migration scripts and the translation files
[VERIFY: ai-docs/ast/map.md:19].

## prestashop: startup and configuration

prestashop starts when PrestaShop includes `packetery/packetery.php`. That file requires
`packetery/autoload.php` first, which registers an `spl_autoload_register` callback mapping the
`Packetery` namespace onto the `packetery/libs/` directory
[VERIFY: packetery/autoload.php#spl_autoload_register], and then defines the constant
`PACKETERY_PLUGIN_DIR`, which one place in the module reads — the label cleanup that globs the
stored label PDFs [VERIFY: packetery/libs/Cron/Tasks/DeleteLabels.php:51]. The constructor sets the
module name and version first, then builds the dependency container and stores it on the public
property `diContainer` [VERIFY: packetery/libs/DI/ContainerFactory.php#create]. The main module file deliberately uses no
`use` statements, because PrestaShop 1.6 cannot load a main module file that has them
[VERIFY: packetery/packetery.php:8].

Configuration keys of prestashop that switch a feature on or off. Each is read where it is used,
not at module load:

| Key | Read in | Where the value lives | Anchor |
|---|---|---|---|
| `PACKETERY_WIDGET_AUTOOPEN` | `packetery/packetery.php` | the shop's `configuration` table | [VERIFY: packetery/packetery.php#widgetAutoOpen] |
| `PACKETERY_WIDGET_VALIDATION_MODE` | `packetery/libs/Tools/ConfigHelper.php` | the shop's `configuration` table | [VERIFY: packetery/libs/Tools/ConfigHelper.php#isApiWidgetValidationModeEnabled] |
| `PACKETERY_USE_PS_CURRENCY_CONVERSION` | `packetery/libs/Order/OrderExporter.php` | the shop's `configuration` table | [VERIFY: packetery/libs/Order/OrderExporter.php#KEY_USE_PS_CURRENCY_CONVERSION] |
| `PACKETERY_ID_PREFERENCE` | `packetery/libs/Tools/ConfigHelper.php` | the shop's `configuration` table | [VERIFY: packetery/libs/Tools/ConfigHelper.php#KEY_ID_PREFERENCE] |
| `PACKETERY_SHOW_CONSIGN_PASSWORD` | `packetery/libs/Order/ConsignPasswordSettings.php` | the shop's `configuration` table | [VERIFY: packetery/libs/Order/ConsignPasswordSettings.php#KEY_SHOW_CONSIGN_PASSWORD] |
| `_PACKETERY_DEBUG_LOG_` | `packetery/libs/Module/VersionChecker.php` | a PHP constant defined by the shop outside this repository | [VERIFY: packetery/libs/Module/VersionChecker.php:58] |

The keys that point at Packeta or at GitHub are in [dependencies.md](dependencies.md). The eleven
keys declared as constants on `ConfigHelper`, with the code that reads each of them, are in
[reference/packetery-tools.md](reference/packetery-tools.md); the module uses further
`PACKETERY_*` keys as string literals outside that class
[VERIFY: packetery/packetery.php#PACKETERY_LABEL_FORMAT].

## prestashop: what belongs to no module

prestashop holds three groups of files that belong to no module and are excluded from the line
counts. `packetery/upgrade/` holds 14 migration scripts, each with an `upgrade_module_*` function
that PrestaShop calls when it finds a newer module version
[VERIFY: packetery/upgrade/upgrade-3.4.0.php#upgrade_module_3_4_0]. Not every released version
ships one, and a released script is still touched later by coding-standard and compatibility
passes; they are described in [reference/packetery-module.md](reference/packetery-module.md).
The translation catalogues `packetery/translations/cs.php` and `packetery/translations/sk.php` are
generated key-value files that PrestaShop fills from the `l()` calls in the code
[VERIFY: packetery/translations/cs.php:1].

The third group is the tooling configuration in the repository root: the coding-standard rule set
[VERIFY: phpcs.xml:1], the PHP-CS-Fixer configuration that loads the PrestaShop preset
[VERIFY: PacketeryCsFixerConfig.php#getRules] and the static-analysis configuration under
`phpstan/`. They shape the checks declared as Composer scripts and never run in a shop
[VERIFY: composer.json:22].
