---
title: "prestashop — documentation index"
repo: prestashop
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers: [.]
confidence: reviewed
tags: [ai-generated, repo-prestashop, type-index]
---

Repo: prestashop · Module: — · Type: index · Status: current

## prestashop: documentation map

prestashop is the Packeta delivery module for PrestaShop shops. The documentation of prestashop is
split into these pages:

| Page | Content | Open it when |
|---|---|---|
| [manifest.yaml](manifest.yaml) | machine-readable inventory: routes, outbound calls, owned tables, external data, assumptions and blind spots | you grep for a route, a table or an external system — **start here** |
| [overview.md](overview.md) | what the repository is, the module table, startup and the files that belong to no module | you need orientation or the line counts |
| [architecture.md](architecture.md) | how the seven modules hold together, the two runtime flows, authorisation, persistence and the hook system | you trace where a request or a cron run flows |
| [dependencies.md](dependencies.md) | the five external systems, the links between modules, the Composer manifest and the environment keys | you look for what the module needs from outside |
| `reference/` | one page per module: purpose, public interface, dependencies, data model and limitations | you work inside one module |

The seven module pages are [packetery-module](reference/packetery-module.md) (entry point, install,
hooks, settings, SOAP facade), [packetery-order](reference/packetery-order.md) (the order row,
packet submission, labels, exports), [packetery-carrier](reference/packetery-carrier.md) (carrier
pairing and the carrier catalogue), [packetery-admin-ui](reference/packetery-admin-ui.md) (the four
admin controllers and their grids), [packetery-packet-tracking](reference/packetery-packet-tracking.md)
(the cron controller, its five tasks and the packet-status history),
[packetery-checkout](reference/packetery-checkout.md) (the storefront controller, pickup-point
validation and the theme adapters) and [packetery-tools](reference/packetery-tools.md)
(configuration keys, database wrapper, HTTP client, logging and exceptions).

## prestashop: modules

prestashop contains 7 modules. Modules of prestashop and their documentation:

| Module | Path | Document |
|---|---|---|
| packetery-module | `packetery/libs/Module` | [reference/packetery-module.md](reference/packetery-module.md) |
| packetery-order | `packetery/libs/Order` | [reference/packetery-order.md](reference/packetery-order.md) |
| packetery-carrier | `packetery/libs/Carrier` | [reference/packetery-carrier.md](reference/packetery-carrier.md) |
| packetery-admin-ui | `packetery/controllers/admin` | [reference/packetery-admin-ui.md](reference/packetery-admin-ui.md) |
| packetery-packet-tracking | `packetery/libs/PacketTracking` | [reference/packetery-packet-tracking.md](reference/packetery-packet-tracking.md) |
| packetery-tools | `packetery/libs/Tools` | [reference/packetery-tools.md](reference/packetery-tools.md) |
| packetery-checkout | `packetery/libs/PickupPointValidate` | [reference/packetery-checkout.md](reference/packetery-checkout.md) |

Every module of prestashop passed the threshold for a standalone document, so no module is
described in [overview.md](overview.md). The module ids were coined by this run, because the
repository is one PrestaShop module that is too large for a single reference page and the code
declares no internal boundaries.

## prestashop: generation assumptions

prestashop was documented under these assumptions (uncertainties the skill resolved on its own so
that it could finish non-interactively). `manifest.yaml` carries all 32 of them under
`assumptions:`; the ones that shape what the reader sees are:

- The repository was read on branch `docs/packetery` at commit `b85243bb` with a clean working tree.
- The repo id `prestashop` is the repository name from the origin URL. It denotes this Packeta
  module repository, not the PrestaShop platform, which is described in prose only and has no
  manifest entry.
- The repository is one PrestaShop module of about 9 700 lines of logic, above the 3 000-line
  ceiling for one reference page, so it was split into seven pages along namespaces and
  directories. Nothing in the code declares that split, and the seven module ids were coined here.
- `modules[].path` names the primary directory of each slice; the full set of covered paths is in
  the `covers` front matter of each reference page.
- Line, type and public-method counts come from `ai-docs/ast/map.json` and exclude the PrestaShop
  directory-guard stub files named `index.php`. The map was generated at commit `801bb6bd`, and
  `git diff --stat 801bb6b..HEAD` touches no source file, so it was used without regeneration.
- `packetery/upgrade/`, `packetery/translations/` and the tooling configuration in the repository
  root belong to no module and are excluded from the line counts.
- The storefront and back-office JavaScript is not covered by the AST map and was read from source.
- Every table lives in the shop's own MySQL database. `store: mysql/prestashop` names the engine and
  a logical name only; the real database name is configured outside the module and was not looked up.
- Configuration values are never copied — only key names, with an anchor at the place the value
  lives.
- SOAP calls are recorded as `kind: other`, because the manifest schema has no SOAP kind.
- The five cron tasks and the three `checkout` actions are recorded as separate `exposes` entries
  although each group is served by one registered front controller.
- The skill `esterka:writing-technical-docs-in-ste` is not installed in this environment, so the
  prose follows `templates/writing-sections.md` alone.

## prestashop: generation status

prestashop — documentation generated from commit `b85243bbeb5f9f91c0bde739f5195b2285b867f9`. Each
module page was verified against the code by an agent that did not write it; every INCORRECT finding
was then fixed by the generator.

| Module document | confidence | Verification |
|---|---|---|
| reference/packetery-module.md | reviewed | 85/91 CONFIRMED, 6 INCORRECT — all fixed |
| reference/packetery-order.md | reviewed | 82/85 CONFIRMED, 3 INCORRECT — all fixed |
| reference/packetery-carrier.md | reviewed | 70/75 CONFIRMED, 4 INCORRECT — all fixed |
| reference/packetery-admin-ui.md | reviewed | 82/85 CONFIRMED, 2 INCORRECT — all fixed |
| reference/packetery-packet-tracking.md | reviewed | 93/98 CONFIRMED, 5 INCORRECT — all fixed |
| reference/packetery-tools.md | reviewed | 78/82 CONFIRMED, 4 INCORRECT — all fixed |
| reference/packetery-checkout.md | reviewed | 82/89 CONFIRMED, 7 INCORRECT — all fixed |

| Shared document | confidence | Verification |
|---|---|---|
| overview.md | reviewed | 28/35 CONFIRMED, 7 INCORRECT — all fixed |
| architecture.md | reviewed | second pass: 55/59 CONFIRMED, 3 INCORRECT — all fixed |
| dependencies.md | reviewed | 41/43 CONFIRMED, 2 INCORRECT — all fixed |
| index.md | reviewed | second pass: 42/43 CONFIRMED, 1 INCORRECT — fixed |

No page of prestashop reached `verified`: a page whose verification found an INCORRECT row can rise
to `reviewed` once the finding is fixed, but `verified` needs a pass that finds nothing to fix, by
someone who neither wrote nor fixed the page. `architecture.md` and `index.md` each took a second
pass over their corrected text, and each of those passes still found something. The verification
records are in `ai-docs-review/` and are never published.

## prestashop: what the documentation does not know

prestashop has these evidenced blind spots, carried machine-readably by `manifest.yaml` under
`unknowns`. They are findings to report when a query runs into them, not tasks for the reader. The
tables hold, in order, the Packeta side, the deployment, and the module's own code.

| What is unknown | What was searched | What would resolve it |
|---|---|---|
| The full attribute contract of the Packeta SOAP createPacket operation — which fields are accepted and which are required beyond the keys packetery-order sends. | the SOAP call sites and the response objects | the WSDL or the packeta-soap-api documentation |
| The full schema of the Packeta carrier feed; the code validates fourteen required keys and maps them, but neither the response contract nor its versioning is defined in the repository. | the downloader, the column map, the feed validation | the packeta-pickup-point-api contract |
| Whether the four countries and two vendor groups hardcoded in `CarrierVendors::getVendors` are the complete Packeta offering, and what changes them. | the module source and the mapped feed columns | the Packeta widget documentation, or a person |
| Which Packeta carriers the two ids in `CARRIERS_SUPPORTING_AGE_VERIFICATION` denote, and how the list is kept in step with the carrier feed. | the module source and the carrier catalogue columns | the Packeta carrier catalogue, or a person |
| Which packet attribute faults the Packeta SOAP API can raise; the fault detail is read generically and no list of possible faults exists in the repository. | the SOAP fault handling and the response objects | the WSDL or the packeta-soap-api documentation |

| What is unknown | What was searched | What would resolve it |
|---|---|---|
| How long the module's error log file is kept and whether anything rotates or truncates it. | the module's classes and their log calls | the shop's filesystem housekeeping, or a person |
| The retention period applied to `packetery_log`, because `LogRepository::purge` takes the number of days as an argument and declares no default. | LogRepository, DbTools | the caller that schedules the purge |
| Whether the optional constants `_PACKETERY_SOAP_WSDL_URL_`, `_PACKETERY_ALLOWED_RELEASE_TYPES_`, `_GITHUB_ACCESS_TOKEN_` and `_PACKETERY_DEBUG_LOG_` are defined in a real installation, and what they resolve to. | the module source and its constant definitions | the shop's PrestaShop configuration |
| Which employee profiles are allowed to open the four Packeta admin tabs. | the tab installation code and the four controllers | the installation's permission configuration |
| Which scheduler calls the `cron` front controller, and how often each task runs. | the front controller, the tasks, the cron info block | the shop's cron configuration, or the operator |
| Which systems are allowed to call the cron route, so `consumers` stays `[unknown]` for every entry. | the front controller authorisation and the config keys | deployment documentation, or the shop operator |
| Which systems or repositories reach the module configuration page; the code shows only that PrestaShop renders it for a signed-in back-office employee. | the hook and admin tab registration | the shop operator |

| What is unknown | What was searched | What would resolve it |
|---|---|---|
| Whether the missing configuration-page URL for `PurgeLogs` is intended or an omission. | the cron info block and the controller dispatch | a person on the module team |
| Whether the storefront widget-library load in `packetery/views/js/front.js` needs its own `calls: packeta-widget` entry for packetery-checkout; only the back-office load and the validation call were recorded. | the storefront and back-office JavaScript | a `--scope packetery-checkout` run |
| Which back-office pages besides the order detail contain the widget buttons that `back.js` binds. | this module's admin and hook templates | a back-office run, or the themes that render it |
| Which checkout module owns the `wait` CSS class that every checkout AJAX request toggles on the body element | the storefront adapters and the front templates | the third-party checkout modules, or a person |
| Which checkout themes the PacketeryCheckoutModuleUnknown adapter is meant to support | the storefront adapters and the detection list | a person; the source marks it a removal candidate |
| Why the uninstall deletes fewer configuration keys than the module writes (`PACKETERY_LAST_RELEASE_NOTES`, `PACKETERY_SHOW_CONSIGN_PASSWORD` and `PACKETERY_CONSIGN_PASSWORD_RETRIEVAL` survive an uninstall). | the install and uninstall paths, the upgrade scripts | the module authors |
