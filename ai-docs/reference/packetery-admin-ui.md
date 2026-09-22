---
title: "prestashop — packetery-admin-ui module"
repo: prestashop
module: packetery-admin-ui
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers: [packetery/controllers/admin/, packetery/views/templates/admin/, packetery/views/js/back.js, packetery/views/js/collectionPrint.js, packetery/views/js/collectionPrintBulkAction.js]
confidence: reviewed
tags: [ai-generated, repo-prestashop, module-packetery-admin-ui, type-reference]
---

Repo: prestashop · Module: packetery-admin-ui · Type: reference · Status: current

## packetery-admin-ui: purpose

packetery-admin-ui builds the Packeta back office of the PrestaShop shop: four admin controllers,
the Smarty templates they render and the three JavaScript files that run on those pages. Each
controller extends `ModuleAdminController` and becomes a PrestaShop tab under the parent tab
`Packetery`, which the installer creates together with the tabs `PacketeryOrderGrid`,
`PacketeryCarrierGrid`, `PacketerySetting` and `PacketeryLogGrid`
[VERIFY: packetery/libs/Module/Installer.php#insertMenuItems].

`PacketeryOrderGrid` is the working page of the module. It lists shop orders joined with the Packeta
order data and the newest packet status, adds Packeta columns (pickup point, tracking number, packet
status, editable weight) and carries the bulk actions that submit packets, print labels, export CSV
and print the bill of delivery [VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#fields_list].
`PacketeryCarrierGrid` lists the shop carriers together with their Packeta mapping and opens the
carrier edit form as a PrestaShop view page
[VERIFY: packetery/controllers/admin/PacketeryCarrierGridController.php#renderView].
`PacketeryLogGrid` is a read-only list over the module log table `packetery_log`, filterable by
status, action and order
[VERIFY: packetery/controllers/admin/PacketeryLogGridController.php#packetery_log].
`PacketerySetting` holds no page of its own: it exists so the menu has a Configuration item and
redirects to the module configuration screen of `AdminModules`
[VERIFY: packetery/controllers/admin/PacketerySettingController.php#initContent].

The three grids are built with the PrestaShop list helper, so packetery-admin-ui declares the SQL
fragments (`_select`, `_join`, `_where`, `_orderBy`) and a `fields_list` instead of writing queries
and HTML itself. The rows the helper returns are post-processed by hook handlers that live outside
packetery-admin-ui, and column values are rendered through the callbacks the `fields_list` names.

> ⚠ add business context (elicitation)

## packetery-admin-ui: public interface

packetery-admin-ui exposes four back-office routes and 39 public methods across its four controller
classes. A route is addressed by its tab class name plus the PrestaShop admin token; authorisation is
the employee tab permission PrestaShop attaches to that tab, and no controller overrides it.

| Route (tab class) | Controller | Behaviour | Anchor |
|---|---|---|---|
| `PacketeryOrderGrid` | `PacketeryOrderGridController` | Order list with Packeta columns, row actions and five bulk actions | [VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#PacketeryOrderGridController] |
| `PacketeryCarrierGrid` | `PacketeryCarrierGridController` | Carrier list plus the carrier edit view opened with `viewcarrier` | [VERIFY: packetery/controllers/admin/PacketeryCarrierGridController.php#PacketeryCarrierGridController] |
| `PacketeryLogGrid` | `PacketeryLogGridController` | Read-only log list, narrowed to one order by `id_order` | [VERIFY: packetery/controllers/admin/PacketeryLogGridController.php#PacketeryLogGridController] |
| `PacketerySetting` | `PacketerySettingController` | Menu item only; redirects to the module configuration page | [VERIFY: packetery/controllers/admin/PacketerySettingController.php#PacketerySettingController] |

The order grid declares the bulk actions `CreatePacket`, `LabelPdf`, `CarrierLabelPdf`, `CsvExport`
and `CollectionPrint`, which PrestaShop dispatches to the matching `processBulk` methods
[VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#bulk_actions]. Label printing
is a two-step flow: `renderList` first shows an offset form when the configured label format allows
an offset, and only the second request, recognised by `submitPrepareLabels`, streams the PDF
[VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#prepareLabels]. Packeta labels
and carrier labels are split by the `is_carrier` and `is_ad` flags of the order, so an employee never
mixes the two label types in one file
[VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#prepareOnlyCarrierPacketNumbers].

Per-row links are built from the order state: `print` and `cancel` when the order already has a
tracking number, `submit` when it has none, and `cancel` disappears once the packet moved past the
first tracked status [VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#getActionLinks].
The links carry an `action` query parameter that PrestaShop turns into the `processPrint`,
`processCancel` and `processSubmit` calls. Inline weight editing has no action of its own: `postProcess`
scans the whole POST body for keys shaped like `weight_` plus an order id, normalises decimal comma and
spaces, and stores each value
[VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#postProcess].

## packetery-admin-ui: views and scripts

packetery-admin-ui renders every page fragment through Smarty templates under
`packetery/views/templates/admin/`, split into grid overrides, reusable column snippets and standalone
documents. The override directories `tery_order_grid`, `tery_carrier_grid` and `tery_log_grid` follow
the PrestaShop helper naming convention: each `list_header.tpl` extends the core helper template and
fills the `leadin` block with the new-version warning, and the order grid adds the label-offset form and
the bill-of-delivery form there
[VERIFY: packetery/views/templates/admin/tery_order_grid/helpers/list/list_header.tpl#prepareLabelsMode].

| Template or script | Rendered by | Behaviour | Anchor |
|---|---|---|---|
| `grid/weightEditable.tpl` | order grid | Text input named after the order id, disabled once a tracking number exists | [VERIFY: packetery/views/templates/admin/grid/weightEditable.tpl#disabled] |
| `grid/booleanIcon.tpl` | order and carrier grid | Check or cross icon for a boolean column | [VERIFY: packetery/views/templates/admin/grid/booleanIcon.tpl#prependText] |
| `grid/targetBlankLink.tpl` | order and log grid | Column value linked to the order or customer detail in a new tab | [VERIFY: packetery/views/templates/admin/grid/targetBlankLink.tpl#columnValue] |
| `grid/link.tpl` | row and edit actions | Icon link with a tooltip title | [VERIFY: packetery/views/templates/admin/grid/link.tpl#data-original-title] |
| `trackingLink.tpl` | order grid | Tracking number linked to the Packeta tracking page | [VERIFY: packetery/views/templates/admin/trackingLink.tpl#trackingNumber] |
| `collectionPrintForm.tpl` | order grid bulk action | Hidden form posting `packetery_order_ids` to a new tab | [VERIFY: packetery/views/templates/admin/collectionPrintForm.tpl#packetery_order_ids] |
| `collectionPrint.tpl` | bill of delivery | Standalone printable page with barcode, sender, recipient and one row per order | [VERIFY: packetery/views/templates/admin/collectionPrint.tpl#showConsignPassword] |
| `carriersInfo.tpl` | carrier grid footer | Carrier count, last update time and the manual update button | [VERIFY: packetery/views/templates/admin/carriersInfo.tpl#updateCarriersLink] |

The remaining templates belong to the module configuration screen and the carrier form:
`generateCronInfoBlock.tpl` documents the CRON entry points and their parameters `number_of_days`,
`number_of_files`, `max_orders` and `max_order_age_days`
[VERIFY: packetery/views/templates/admin/generateCronInfoBlock.tpl#max_order_age_days],
`vendors.tpl` renders the `allowed_vendors` checkboxes per country
[VERIFY: packetery/views/templates/admin/vendors.tpl#allowed_vendors],
`disableCarrierOptions.tpl` disables the options of the `id_branch` select whose Packeta carrier is no longer supported
[VERIFY: packetery/views/templates/admin/disableCarrierOptions.tpl#disabledCarriers] and
`newVersionMessage.tpl` formats the update notice with its release notes
[VERIFY: packetery/views/templates/admin/newVersionMessage.tpl#releaseNotes].

Three scripts complete the pages. `back.js` runs on every back-office page, binds the pickup-point and
home-delivery widget buttons, asks for confirmation before posting or cancelling a parcel, and hides the
Packeta product tab for virtual products [VERIFY: packetery/views/js/back.js#disableTabByProductType].
`collectionPrintBulkAction.js` submits the hidden bill-of-delivery form as soon as the list page loads
[VERIFY: packetery/views/js/collectionPrintBulkAction.js#collection-print-form], and `collectionPrint.js`
opens the browser print dialog on the printable page
[VERIFY: packetery/views/js/collectionPrint.js#print].

## packetery-admin-ui: dependencies

packetery-admin-ui holds no business logic of its own: every controller resolves the services it needs
from the module DI container and turns the result into list rows, messages or a downloaded file. The
order grid pulls `PacketSubmitter`, `Labels`, `CsvExporter`, `PacketCanceller`, `Tracking`,
`OrderRepository` and `CollectionPrintHandler`, which are the entry points of the module id
`packetery-order` [VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#PacketSubmitter].
Packet statuses shown in the status column and the decision whether a packet may still be cancelled come
from `PacketStatusFactory` and `PacketTrackingRepository` of the module id `packetery-packet-tracking`
[VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#PacketStatusFactory].

The carrier grid uses `ApiCarrierRepository`, `CarrierRepository`, `CarrierTools` and `CarrierAdminForm`
of the module id `packetery-carrier`; `CarrierAdminForm` builds both the edit form of the view page and
the per-row warnings that the list adds to its own warning box
[VERIFY: packetery/controllers/admin/PacketeryCarrierGridController.php#CarrierAdminForm]. The log grid
resolves `LogRepository` for the action translations and the status list
[VERIFY: packetery/controllers/admin/PacketeryLogGridController.php#LogRepository]; the carrier grid
reads one-shot messages through `MessageManager` and the order grid reads the label-format keys
`PACKETERY_LABEL_FORMAT` and `PACKETERY_CARRIER_LABEL_FORMAT` through `ConfigHelper`, both of the module
id `packetery-tools` [VERIFY: packetery/controllers/admin/PacketeryCarrierGridController.php#MessageManager].
All three grids ask `VersionChecker` of the module id `packetery-module` whether a newer module release
exists and put the rendered warning into the list header
[VERIFY: packetery/controllers/admin/PacketeryLogGridController.php#isNewVersionAvailable].

The coupling to the PrestaShop platform is direct and not mediated by any of those modules: the
controllers extend `ModuleAdminController`, describe their lists with `_select`, `_join` and
`fields_list`, read request data with `Tools::getValue`, redirect with `Tools::redirectAdmin` and render
snippets with their own `Smarty` instances. Two of the hooks that shape the lists —
`actionPacketeryOrderGridListingResultsModifier` and `actionPacketeryCarrierGridListingResultsModifier` —
are named after these controllers and are fired by the PrestaShop list helper, not by packetery-admin-ui;
their handlers compute the fallback weight and the carrier zones that the grids display.

## packetery-admin-ui: external links

packetery-admin-ui reaches two Packeta systems outside the repository, one from the server and one from
the employee browser.

calls → packeta-soap-api (sync, SOAP)
calls → packeta-widget (sync, HTTPS)

Carrier label printing needs the carrier tracking numbers that only Packeta knows, so the order grid
calls `getPacketIdsWithCarrierNumbers` before it renders the offset form and again before it builds the
PDF; an empty result stops the flow and the page points the employee at the Packeta log instead
[VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#getPacketIdsWithCarrierNumbers].
The request itself is sent by the SOAP client of the module id `packetery-module`, and the endpoint is
the WSDL constant that lives there [VERIFY: packetery/libs/Module/SoapApi.php#WSDL_URL].

In the browser, `back.js` loads the Packeta widget library with `jQuery.getScript` whenever the page
contains a widget button, and reports a console error when the library cannot be loaded
[VERIFY: packetery/views/js/back.js#getScript]. The library address is a literal in that file. The
picked pickup point or the validated home-delivery address is written back into the hidden form fields
`pickup_point` and `address`, and the visible parts of the page are rewritten from the widget result;
the buttons themselves, together with the API key and the widget options, come from the order-detail
template of the module id `packetery-module`
[VERIFY: packetery/views/templates/hook/displayOrderMain.tpl#open-packeta-hd-widget].

## packetery-admin-ui: known limitations

packetery-admin-ui carries these limitations that the code shows. The packet status column falls back to
an empty cell for a status code the module does not know, with a TODO that names a database column that
does not exist yet [VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#TODO]. The
pagination sizes of the order grid are commented out, so the list uses the PrestaShop default while the
log grid sets its own `_pagination` [VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php:114].
In the same constructor the shop filter overwrites the shop-group filter instead of adding to it, because
both assignments write the whole `_where` string [VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php:108].

Each rendering helper creates a fresh `Smarty` instance and fetches a template by a path relative to the
controller file, so the templates are outside the PrestaShop template cache and outside theme overriding
[VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#getColumnLink]. The controllers
also instantiate the module class directly instead of receiving it, once per controller instance
[VERIFY: packetery/controllers/admin/PacketeryCarrierGridController.php#getModule].

Two addresses are hardcoded in the front-end files rather than configured: the widget library address in
`back.js` [VERIFY: packetery/views/js/back.js:9] and the releases page behind the "Read more" link of
the update notice [VERIFY: packetery/views/templates/admin/newVersionMessage.tpl:15]. Finally, `back.js`
hides or shows the Packeta product tab from a `setTimeout` scheduled 500 ms after load, a documented
workaround for the PrestaShop 1.6 product page hiding any tab whose label contains the word pack
[VERIFY: packetery/views/js/back.js:98].
