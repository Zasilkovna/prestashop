---
title: "prestashop — packetery-packet-tracking module"
repo: prestashop
module: packetery-packet-tracking
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers: [packetery/libs/PacketTracking, packetery/libs/Cron, packetery/controllers/front/cron.php]
confidence: reviewed
tags: [ai-generated, repo-prestashop, module-packetery-packet-tracking, type-reference]
---

Repo: prestashop · Module: packetery-packet-tracking · Type: reference · Status: current

## packetery-packet-tracking: purpose

packetery-packet-tracking keeps the shop record of Packeta delivery progress current and runs all
scheduled work of the Packeta module. The module holds one public front controller,
`PacketeryCronModuleFrontController`, which first authorises the request and then dispatches it to
one of five task classes [VERIFY: packetery/controllers/front/cron.php#validateToken]. Each task
class derives its name from its own short class name, so the name in the request and the class in
the code are always the same string [VERIFY: packetery/libs/Cron/Tasks/Base.php#getTaskName].

Three of the five tasks are thin: they call a service of another module and turn its result into
cron output. `DeleteLabels` calls no service; it works on the label files on disk directly
[VERIFY: packetery/libs/Cron/Tasks/DeleteLabels.php#execute]. The fifth task, `UpdatePacketStatus`, drives `PacketTrackingCron`, which reads the
tracking history of every open Packeta order from the Packeta API and writes it into the shop
database [VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#getPacketTracking].
packetery-packet-tracking also owns the catalogue of Packeta packet statuses, their translated
names and the flag that marks a status as final
[VERIFY: packetery/libs/PacketTracking/PacketStatusFactory.php#getPacketStatuses]. That catalogue
is the shared vocabulary for the order grid, the order detail page, the form that picks the
statuses to track [VERIFY: packetery/libs/PacketTracking/PacketStatusTrackingFormService.php#getPacketStatusChoices]
and the form that maps packet statuses onto PrestaShop order states
[VERIFY: packetery/libs/Order/OrderStatusChangeFormService.php#getConfigurationFormFields].

packetery-packet-tracking has no scheduler of its own. The module prints ready-made controller URLs
on its configuration page, and the shop operator or the hosting must call them
[VERIFY: packetery/packetery.php#generateCronInfoBlock]. Nothing in the repository starts a timer,
registers a system cron entry or subscribes to a PrestaShop cron module, so the run interval of
every task is decided outside the code.

> ⚠ add business context (elicitation)

## packetery-packet-tracking: public interface

packetery-packet-tracking exposes one registered front controller and five task names that select
the work it performs. Every request carries a shared secret in the `token` query parameter, which
the controller compares with the configuration key `PACKETERY_CRON_TOKEN`
[VERIFY: packetery/controllers/front/cron.php#PACKETERY_CRON_TOKEN]. A request without a matching
secret, and a request without a `task` value, both end with a rendered error row and no work
[VERIFY: packetery/controllers/front/cron.php#hasError].

| Entry | Path and parameters | Behaviour | Anchor |
|---|---|---|---|
| `PacketeryCronModuleFrontController` | `cron` | Validates the secret, resolves the task from the container, prints a BEGIN and an END row around the run | [VERIFY: packetery/controllers/front/cron.php#validateToken] |
| `DeleteLabels` | `cron?task=DeleteLabels`, `number_of_days`, `number_of_files` | Removes label PDF files older than the given number of days, in batches | [VERIFY: packetery/libs/Cron/Tasks/DeleteLabels.php#unlink] |
| `DownloadCarriers` | `cron?task=DownloadCarriers` | Runs the carrier feed download and reports only a `danger` result as an error | [VERIFY: packetery/libs/Cron/Tasks/DownloadCarriers.php#Downloader] |
| `UpdatePacketStatus` | `cron?task=UpdatePacketStatus` | Runs `PacketTrackingCron` over the selected orders | [VERIFY: packetery/libs/Cron/Tasks/UpdatePacketStatus.php#PacketTrackingCron] |
| `GetConsignPassword` | `cron?task=GetConsignPassword`, `max_orders`, `max_order_age_days` | Fetches missing consignment codes for recent orders | [VERIFY: packetery/libs/Cron/Tasks/GetConsignPassword.php#fetchFromApi] |
| `PurgeLogs` | `cron?task=PurgeLogs`, `log_expiration_days` | Deletes module log rows older than the given number of days | [VERIFY: packetery/libs/Cron/Tasks/PurgeLogs.php#DEFAULT_LOG_EXPIRATION_DAYS] |

The controller sets `ajax` to true and writes every message through a Smarty row template, so the
response is a stream of HTML fragments rather than JSON
[VERIFY: packetery/controllers/front/cron.php#renderMessage]. Client aborts are ignored for the
whole run [VERIFY: packetery/controllers/front/cron.php#ignore_user_abort], and every error row is
also written to the PrestaShop log
[VERIFY: packetery/controllers/front/cron.php#PrestaShopLogger].

Inside the repository packetery-packet-tracking offers 40 public methods on 15 types. The ones
other modules use are `PacketStatusFactory::getPacketStatuses`, the status catalogue;
`PacketTrackingRepository::getLastStatusCodeByOrderAndPacketId`, the last stored status of a packet
[VERIFY: packetery/libs/PacketTracking/PacketTrackingRepository.php#getLastStatusCodeByOrderAndPacketId];
and `PacketStatusTrackingFormService`, the configuration form definition keyed by
`submitPacketStatusTrackingSubmit`
[VERIFY: packetery/libs/PacketTracking/PacketStatusTrackingFormService.php#SUBMIT_ACTION_KEY].

## packetery-packet-tracking: status update flow

packetery-packet-tracking performs its main work in `PacketTrackingCron::run`, which stops early
unless `PACKETERY_PACKET_STATUS_TRACKING_ENABLED` is set
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#PACKETERY_PACKET_STATUS_TRACKING_ENABLED].
The run then reads `PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATES`, a JSON map of PrestaShop order
state ids, and keeps the ids whose value is `on`
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATES].
An empty selection ends the run with the message from `getNoOrderStatusesMessage`
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#getNoOrderStatusesMessage].

The candidate orders are selected by those order states, by the final packet statuses that must be
skipped, by `PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS` and by an age limit built from
`PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS`
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#getOrdersByStateAndLastUpdate]. The
set of final statuses comes from the status catalogue itself, from every entry whose `isFinal` flag
is true [VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#getFinalStatusIds].

For each order the run asks the Packeta API for the tracking history of the packet, and records the
outcome in the module log under `ACTION_PACKET_TRACKING`, as a success with the response or as an
error with the fault string
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#ACTION_PACKET_TRACKING]. An order
whose last record is neither a final status nor a status selected in
`PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES` is skipped
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES].

Both the API records and the stored records are converted into `PacketStatusRecord` objects
[VERIFY: packetery/libs/PacketTracking/PacketStatusRecordFactory.php#createFromSoapApi] and compared
by hash; the comparator reports a difference as soon as one API hash is missing from the stored set
[VERIFY: packetery/libs/PacketTracking/PacketStatusComparator.php#isDifferenceBetweenApiAndDatabase].
On a difference the stored rows of that order are deleted and the whole API history is inserted
again. When `PACKETERY_ORDER_STATUS_CHANGE_ENABLED` is set, the order is then moved to the
PrestaShop order state configured under `PACKETERY_ORDER_STATUS_CHANGE_` plus the last status code
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#updateOrderStatus], and the order is
stamped with the current tracking timestamp
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#setLastUpdateTrackingStatus].

## packetery-packet-tracking: data model

packetery-packet-tracking stores one table and holds two in-memory record types. The table
`packetery_packet_status` is created and dropped by the module repository, and its rows are a local
copy of the tracking history that Packeta reports
[VERIFY: packetery/libs/PacketTracking/PacketTrackingRepository.php#getCreateTableSql].

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| `packetery_packet_status` | `id` | int unsigned, auto increment | primary key | [VERIFY: packetery/libs/PacketTracking/PacketTrackingRepository.php#getCreateTableSql] |
| `packetery_packet_status` | `id_order` | int unsigned | indexed; the PrestaShop order the packet belongs to | [VERIFY: packetery/libs/PacketTracking/PacketTrackingRepository.php#getPacketStatusesByOrderId] |
| `packetery_packet_status` | `packet_id` | varchar(15) | indexed; the Packeta tracking number stored on the order | [VERIFY: packetery/libs/PacketTracking/PacketTrackingRepository.php#getLastStatusCodeByOrderAndPacketId] |
| `packetery_packet_status` | `event_datetime` | datetime | the moment the status was reported; used for the newest-first ordering | [VERIFY: packetery/libs/PacketTracking/PacketTrackingRepository.php#getLastStatusCodeByOrderAndPacketId] |
| `packetery_packet_status` | `status_code` | tinyint unsigned | one of the ids of the status catalogue | [VERIFY: packetery/libs/PacketTracking/PacketStatus.php#RECEIVED_DATA] |
| `packetery_packet_status` | `status_text` | text | the text Packeta sent with the record | [VERIFY: packetery/libs/PacketTracking/PacketTrackingRepository.php#insert] |
| `packetery_packet_status` | `created_at` | datetime, defaults to the insert time | written by the database, never by the module | [VERIFY: packetery/libs/PacketTracking/PacketTrackingRepository.php#getCreateTableSql] |

`PacketStatus` is the catalogue entry: an id, a machine code, a translated name and the final flag
[VERIFY: packetery/libs/PacketTracking/PacketStatus.php#isFinal]. Sixteen entries are defined, from
`RECEIVED_DATA` to the fallback `UNKNOWN`, and four of them are final: `DELIVERED`, `RETURNED`,
`CANCELLED` and `UNKNOWN` [VERIFY: packetery/libs/PacketTracking/PacketStatusFactory.php#UNKNOWN].
Only the non-final entries are offered as tracking triggers in the configuration form
[VERIFY: packetery/libs/PacketTracking/PacketStatusTrackingFormService.php#isFinal].

`PacketStatusRecord` is one history line — a date, a status code and a status text — and its only
behaviour is the hash over all three fields that the comparator uses
[VERIFY: packetery/libs/PacketTracking/PacketStatusRecord.php#getHash]. The same record type is
built from an API response and from a database row, which is what makes the two sides comparable
[VERIFY: packetery/libs/PacketTracking/PacketStatusRecordFactory.php#createFromDatabase].

## packetery-packet-tracking: dependencies

packetery-packet-tracking reaches two Packeta systems, both of them through services that live in
other modules of this repository.

calls → packeta-soap-api (sync, SOAP)
calls → packeta-pickup-point-api (sync, HTTP/JSON)

The tracking run sends the packet tracking number and receives the packet history
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#getPacketTracking], and the
consignment-code task sends the same identifier and receives the `consignPassword` of the packet
[VERIFY: packetery/libs/Cron/Tasks/GetConsignPassword.php#fetchFromApi]. The carrier task only
triggers the download of the carrier feed and forwards the failure text
[VERIFY: packetery/libs/Cron/Tasks/DownloadCarriers.php#Downloader]. The endpoint values are not
repeated here; they live with the clients that own them.

Inside the repository packetery-packet-tracking is wired through the module container only. It
reads and stamps orders through `Packetery\Order\OrderRepository`
[VERIFY: packetery/libs/Cron/Tasks/GetConsignPassword.php#getOrdersMissingConsignPassword], writes
API traffic through `Packetery\Log\LogRepository` and purges the same store
[VERIFY: packetery/libs/Cron/Tasks/PurgeLogs.php#LogRepository], reads its settings through
`Packetery\Tools\ConfigHelper` [VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#PACKETERY_PACKET_STATUS_TRACKING_ENABLED]
and reaches the packet-status table through `Packetery\Tools\DbTools`
[VERIFY: packetery/libs/PacketTracking/PacketTrackingRepository.php#DbTools]. The consignment-code
task first asks `Packetery\Order\ConsignPasswordSettings` whether the cron mode is the configured
one and reports a message instead of working when it is not
[VERIFY: packetery/libs/Cron/Tasks/GetConsignPassword.php#isCron].

Other modules read this one in turn: the order detail view renders the last stored status
[VERIFY: packetery/libs/Order/OrderDetailView.php#addPacketStatus], the order grid translates a
stored status code for display
[VERIFY: packetery/controllers/admin/PacketeryOrderGridController.php#getTranslatedPacketStatus],
the order status form builds one select per catalogue entry
[VERIFY: packetery/libs/Order/OrderStatusChangeFormService.php#PACKETERY_ORDER_STATUS_CHANGE_ENABLED],
and the installer creates the table
[VERIFY: packetery/libs/Module/Installer.php#PacketTrackingRepository].

The coupling to the PrestaShop platform is direct and not a dependency edge: the controller extends
`ModuleFrontController` and renders through Smarty, the tasks are resolved from the module
container, configuration goes through `Configuration`, and the order state change uses the
PrestaShop `Order` and `OrderState` objects
[VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#OrderState].

## packetery-packet-tracking: known limitations

packetery-packet-tracking has these limitations evidenced in the code. The comparison of API and
stored history is all or nothing: one unmatched hash deletes every stored row of the order and
re-inserts the full history, and because the hash covers the status text, a changed text alone is
enough to trigger the rewrite [VERIFY: packetery/libs/PacketTracking/PacketStatusRecord.php#getHash].

The order state change fails silently. When the order or the configured target state does not load,
`updateOrderStatus` returns without a message and without a log entry, so a missing
`PACKETERY_ORDER_STATUS_CHANGE_` mapping for the reported status code is indistinguishable from a
successful run [VERIFY: packetery/libs/PacketTracking/PacketTrackingCron.php#updateOrderStatus].
The consignment-code task behaves the same way per order: an API failure and a database failure are
both caught and skipped, and the task returns an empty message list
[VERIFY: packetery/libs/Cron/Tasks/GetConsignPassword.php#ApiClientException].

Task parameters are read in two places. The controller reads `max_orders` and `max_order_age_days`
from the request [VERIFY: packetery/controllers/front/cron.php#max_order_age_days], while
`DeleteLabels` and `PurgeLogs` read their own parameters from the request inside `execute`
[VERIFY: packetery/libs/Cron/Tasks/DeleteLabels.php#DEFAULT_NUMBER_OF_DAYS], so the two task
families cannot be driven the same way from PHP.

`PurgeLogs` is reachable through the controller but the configuration page builds no URL for it,
unlike the four other tasks [VERIFY: packetery/packetery.php#generateCronInfoBlock]. The label
cleanup deletes at most one batch per run, and the batch size falls back to a constant whenever the
request value is missing or not positive
[VERIFY: packetery/libs/Cron/Tasks/DeleteLabels.php#DEFAULT_NUMBER_OF_FILES]; the task also lists
the whole label directory with `glob` on every run, so the cost grows with the number of files kept
[VERIFY: packetery/libs/Cron/Tasks/DeleteLabels.php#PACKETERY_PLUGIN_DIR].
