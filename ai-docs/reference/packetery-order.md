---
title: "prestashop — packetery-order module"
repo: prestashop
module: packetery-order
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers: [packetery/libs/Order, packetery/libs/Request, packetery/libs/Response, packetery/libs/Payment]
confidence: reviewed
tags: [ai-generated, repo-prestashop, module-packetery-order, type-reference]
---

Repo: prestashop · Module: packetery-order · Type: reference · Status: current

## packetery-order: purpose

packetery-order keeps the shipping data of a PrestaShop order and turns that data into a packet at
Packeta. The module writes one `packetery_order` row per cart and order, fills it with the chosen
pickup point or the validated home-delivery address, and later reads that row to build the packet
attributes that go out to the carrier [VERIFY: packetery/libs/Order/OrderExporter.php#prepareData].
After a successful submission the module stores the returned packet identifier in
`tracking_number`, marks the row `exported`, and copies the same number into the PrestaShop
`OrderCarrier` record [VERIFY: packetery/libs/Order/PacketSubmitter.php#ordersExport].

packetery-order also covers everything the merchant does with a submitted packet: cancellation
[VERIFY: packetery/libs/Order/PacketCanceller.php#cancelPacket], label and carrier-label PDF
printing [VERIFY: packetery/libs/Order/Labels.php#packetsLabelsPdf], the bill-of-delivery
collection print with its barcode [VERIFY: packetery/libs/Order/CollectionPrintHandler.php#renderPrint],
a CSV export in the Packeta import format [VERIFY: packetery/libs/Order/CsvExporter.php#outputCsvExport],
and retrieval of the consignment code that the recipient needs at the pickup point
[VERIFY: packetery/libs/Order/ConsignPasswordProvider.php#fetchFromApi].

The `Packetery\Request` and `Packetery\Response` namespaces hold the plain data objects for those
API calls. `BaseResponse` carries the fault identifier and fault string that every SOAP response
may hold, and the concrete responses add the payload — the label PDF, the barcode image, the
packet info [VERIFY: packetery/libs/Response/BaseResponse.php#hasFault]. `Packetery\Payment` holds
the mapping from a PrestaShop payment module name to the cash-on-delivery flag, plus the currency
conversion between the order currency and the pickup-point currency
[VERIFY: packetery/libs/Payment/PaymentRepository.php#getRateTotal].

> ⚠ add business context (elicitation)

## packetery-order: public interface

packetery-order is a library module with no route of its own; the PrestaShop controllers and hooks
of the other modules call into the 36 classes and 140 public methods that the module holds. The
entry points that carry the module's work are these.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| `PacketSubmitter::ordersExport` | `ordersExport(array $orderIds)` | Skips orders that already have a `tracking_number`, submits the rest one by one, writes the returned number to `packetery_order` and to `OrderCarrier`, and throws an `AggregatedException` holding every per-order failure | [VERIFY: packetery/libs/Order/PacketSubmitter.php#ordersExport] |
| `PacketCanceller::cancelPacket` | `cancelPacket(int $orderId, string $packetId)` | Cancels the packet, then clears `tracking_number` and `consign_password`; returns a success flag and a merchant-facing message | [VERIFY: packetery/libs/Order/PacketCanceller.php#cancelPacket] |
| `Labels::packetsLabelsPdf` | `packetsLabelsPdf(array $packets, $type, $packetsEnhanced, $offset, $fallbackToPacketaLabel)` | Picks the Packeta or the carrier label call by `$type`, falls back to the Packeta label when asked to, logs one row per packet, returns the PDF bytes | [VERIFY: packetery/libs/Order/Labels.php#packetsLabelsPdf] |
| `CollectionPrintHandler::handleBulkAction`, `CollectionPrintHandler::renderPrint` | `handleBulkAction(array $orderIds)`, `renderPrint(string $rawIds)` | Builds the auto-submit form variables for the grid bulk action, then renders the bill of delivery and ends the request with `exit` | [VERIFY: packetery/libs/Order/CollectionPrintHandler.php#handleBulkAction] |
| `CollectionPrintService::buildOrdersForPrint` | `buildOrdersForPrint(array $orderIds)` | Drops orders without a `tracking_number` and returns the print rows plus the packet id list for the barcode | [VERIFY: packetery/libs/Order/CollectionPrintService.php#buildOrdersForPrint] |
| `BarcodeProvider::getBarcodeData` | `getBarcodeData(array $trackingNumbers)` | Creates a shipment, then fetches its barcode PNG; returns `null` on a fault or an empty payload, and logs the fault paths only | [VERIFY: packetery/libs/Order/BarcodeProvider.php#getBarcodeData] |
| `CsvExporter::outputCsvExport` | `outputCsvExport(array $orders)` | Streams the Packeta import CSV, orders that fail to export are skipped, every exported order is marked `exported` | [VERIFY: packetery/libs/Order/CsvExporter.php#outputCsvExport] |
| `OrderSaver::saveNewOrder`, `OrderSaver::savePickupPointInCartGetJson` | `saveNewOrder(Cart $cart, PrestaShopOrder $order)`, `savePickupPointInCartGetJson()` | Writes the `packetery_order` row at order creation, or stores the widget's pickup point against the cart and answers the checkout AJAX call in JSON | [VERIFY: packetery/libs/Order/OrderSaver.php#saveNewOrder] |
| `OrderDetailsUpdater::orderUpdate` | `orderUpdate(&$messages, $packeteryOrder, $orderId)` | Applies the merchant's edits on the order detail — pickup point, address, dimensions, weight, prices, age verification — and refuses to touch an order that is already `exported` | [VERIFY: packetery/libs/Order/OrderDetailsUpdater.php#orderUpdate] |
| `OrderExporter::prepareData` | `prepareData(\Order $order)` | Assembles every packet attribute from the order, the address, the customer and the configuration; throws `ExportException` when `id_branch` is missing or the carrier cannot verify age | [VERIFY: packetery/libs/Order/OrderExporter.php#prepareData] |

`OrderRepository` holds the rest of the surface as plain reads and writes over `packetery_order`
[VERIFY: packetery/libs/Order/OrderRepository.php#getWithShopById]. Around those entry points sit
small collaborators: `CodResolver` resolves and rounds the cash-on-delivery amount
[VERIFY: packetery/libs/Order/CodResolver.php#roundCodByCurrency], `OrderNumberResolver` chooses
between the order id and the order reference [VERIFY: packetery/libs/Order/OrderNumberResolver.php#getPreferredOrderNumber],
`ConsignPasswordSettings` reads the two consignment-code configuration keys into a value object
[VERIFY: packetery/libs/Order/ConsignPasswordSettings.php#fromConfig], `Ajax` stores a validated
home-delivery address against the cart [VERIFY: packetery/libs/Order/Ajax.php#saveAddressInCart],
and `OrderStatusChangeFormService` builds the `PACKETERY_ORDER_STATUS_CHANGE_ENABLED` form together
with one select per packet status [VERIFY: packetery/libs/Order/OrderStatusChangeFormService.php#getConfigurationFormFields].

## packetery-order: data model

packetery-order owns two tables, `packetery_order` and `packetery_payment`, both created by the
module installer in the shop's own MySQL database [VERIFY: packetery/libs/Module/Installer.php#packetery_order].

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| `packetery_order` | `id_order`, `id_cart` | `int`, both `UNIQUE` | The row starts as a cart-only row at pickup-point selection and gets `id_order` when the order is created | [VERIFY: packetery/libs/Order/OrderSaver.php#saveNewOrder] |
| `packetery_order` | `id_branch`, `name_branch`, `currency_branch` | `int`, `varchar(255)`, `char(3)` | The chosen pickup point; an empty `id_branch` makes the export throw | [VERIFY: packetery/libs/Order/OrderExporter.php#prepareData] |
| `packetery_order` | `is_ad`, `is_carrier`, `carrier_pickup_point` | `int`, `tinyint(1)`, `varchar(40)` | Delivery mode — home delivery, external carrier point, or internal Packeta point | [VERIFY: packetery/libs/Order/OrderSaver.php#savePickupPointInCart] |
| `packetery_order` | `country`, `county`, `zip`, `city`, `street`, `house_number`, `latitude`, `longitude` | `varchar` | The widget-validated home-delivery address; written only when the new country matches the order's country | [VERIFY: packetery/libs/Order/OrderDetailsUpdater.php#processAddressChange] |
| `packetery_order` | `point_place`, `point_street`, `point_city`, `point_zip` | `varchar` | The pickup point's own address, shown on the order detail when the point is an internal one | [VERIFY: packetery/libs/Order/OrderDetailView.php#getPickupPointOrDeliveryAddress] |
| `packetery_order` | `tracking_number`, `exported` | `varchar(15)`, `tinyint(1)` | The packet id returned by the API; both are cleared together, while `exported` is also set on its own by the CSV export | [VERIFY: packetery/libs/Order/OrderRepository.php#clearTrackingNumber] |
| `packetery_order` | `carrier_number` | `varchar(255)` | The external carrier's own number, used to ask for a carrier label | [VERIFY: packetery/libs/Order/OrderRepository.php#setCarrierNumber] |
| `packetery_order` | `is_cod`, `price_cod`, `price_total` | `tinyint(1)`, `decimal(20,6)` | `price_cod` overrides the order total as the collected amount; a `null` `price_total` falls back to the order's paid total | [VERIFY: packetery/libs/Order/CodResolver.php#resolveCodValue] |
| `packetery_order` | `weight`, `length`, `width`, `height` | `decimal(20,6)`, `int` | Merchant-editable packet size; the three dimensions must be whole numbers greater than zero, the weight a number greater than zero | [VERIFY: packetery/libs/Order/OrderDetailsUpdater.php#processDimensionsAndPricesChange] |
| `packetery_payment` | `module_name`, `is_cod` | `varchar(255)` primary key, `tinyint(1)` | One row per PrestaShop payment module, telling the order saver whether that payment means cash on delivery | [VERIFY: packetery/libs/Payment/PaymentRepository.php#setOrInsert] |

Three further `packetery_order` columns sit outside the table above: `age_verification_required`
overrides the per-product adult flag, `last_update_tracking_status` records when the packet status
was last polled, and the pair `consign_password` plus `consign_password_processed` holds the
consignment code with the timestamp of the last retrieval attempt
[VERIFY: packetery/libs/Order/OrderRepository.php#getOrdersMissingConsignPassword]. The retrieval
order is untried rows first and then the oldest attempt first, so a failing row does not block the
queue [VERIFY: packetery/libs/Order/OrderRepository.php#markConsignPasswordAttempts].

## packetery-order: dependencies

packetery-order reaches one system outside the repository and two data owners inside it.

calls → packeta-soap-api (sync, SOAP over HTTP)
references → packetery-module
references → packetery-packet-tracking

Over the SOAP link packetery-order sends packet attributes and receives a packet id
[VERIFY: packetery/libs/Order/PacketSubmitter.php#createPacketSoap], sends a packet id and
receives a cancellation result [VERIFY: packetery/libs/Request/CancelPacketRequest.php#getPacketId],
asks for label and carrier-label PDFs [VERIFY: packetery/libs/Order/Labels.php#packetsLabelsPdf],
asks for a shipment barcode image [VERIFY: packetery/libs/Order/BarcodeProvider.php#getBarcodeData],
and asks for the packet info that carries the consignment code
[VERIFY: packetery/libs/Order/ConsignPasswordProvider.php#fetchFromApi]. Every call but the packet
creation goes through the shared SOAP client of packetery-module; `PacketSubmitter` builds its own
`\SoapClient` from the same resolved WSDL location. The API password is read from the
configuration and never stored in this module.

Inside the repository packetery-order reads two tables it does not own. `packetery_product_attribute`
belongs to packetery-module and answers whether an order holds an adults-only product
[VERIFY: packetery/libs/Order/OrderRepository.php#isOrderForAdults], and `packetery_packet_status`
belongs to packetery-packet-tracking and supplies the last packet status when the tracking cron
picks the orders to poll [VERIFY: packetery/libs/Order/OrderRepository.php#getOrdersByStateAndLastUpdate].

The callers are the admin and front controllers of the other modules: `PacketeryOrderGridController`
drives submission, cancellation, labels, collection print and CSV export, the `checkout` front
controller calls the cart-side savers, the `packetery.php` hook handlers call the order saver and
the order-detail updater, and the consignment-code cron task calls `ConsignPasswordProvider`. The
module is coupled to the PrestaShop platform directly rather than through an abstraction — it
constructs `\Order`, `\Address`, `\Currency` and `\OrderCarrier`, reads request values through
`\Tools`, and renders through the Smarty instance of the module context.

## packetery-order: known limitations

packetery-order carries these limitations that follow from the code itself. The affiliate
identifier sent with every created packet is a compile-time constant of `PacketSubmitter`, so it
cannot be changed per shop [VERIFY: packetery/libs/Order/PacketSubmitter.php#AFFILIATE_ID]. The
collection print and the CSV export both write headers straight to the output and end the request
themselves instead of returning a response object, which keeps them out of any response pipeline
[VERIFY: packetery/libs/Order/CsvExporter.php#outputCsvExport].

Two open notes sit in the source. `OrderDetailView` cannot translate a packet status code that the
status factory does not know and leaves the template variable unset, with a note that a `code_text`
column would fix it [VERIFY: packetery/libs/Order/OrderDetailView.php:61]. `OrderSaver` carries a
note that it should inherit from a common base class [VERIFY: packetery/libs/Order/OrderSaver.php:43].

`OrderRepository` holds two methods that delete by cart id with the same body, `deleteByCart` and
`deleteByCartId` [VERIFY: packetery/libs/Order/OrderRepository.php#deleteByCartId]. Two response members are never read: `CancelPacketResponse::hasCancelNotAllowedFault` has no call
site anywhere in the repository
[VERIFY: packetery/libs/Response/CancelPacketResponse.php#hasCancelNotAllowedFault], and
`PacketInfo::getTrackingLink` is never called although the value is written
[VERIFY: packetery/libs/Response/PacketInfo.php#getTrackingLink].

Failure handling is uneven by design in one place: when a cancellation succeeds but the tracking
number cannot be cleared the merchant sees a message, while a failure to clear the consignment code
is only written to the PrestaShop log [VERIFY: packetery/libs/Order/PacketCanceller.php#cancelPacket].
The consignment-code refresh that runs right after a packet is created swallows both the API and
the database failure and leaves the code to the cron task
[VERIFY: packetery/libs/Order/PacketSubmitter.php#refreshConsignPasswordIfImmediate].
