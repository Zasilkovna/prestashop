---
title: "prestashop — packetery-tools module"
repo: prestashop
module: packetery-tools
generated-by: skill:generate-docs@0.3.5
source-commit: b85243bbeb5f9f91c0bde739f5195b2285b867f9
last-generated: 2026-09-22
covers: [packetery/libs/Tools, packetery/libs/Log, packetery/libs/LogWrapper, packetery/libs/Exceptions]
confidence: reviewed
tags: [ai-generated, repo-prestashop, module-packetery-tools, type-reference]
---

Repo: prestashop · Module: packetery-tools · Type: reference · Status: current

## packetery-tools: purpose

packetery-tools holds the shared infrastructure that the other modules of the Packeta PrestaShop
module build on. It declares 26 types with 52 public methods in five namespaces: `Packetery\Tools`
(configuration, database, HTTP, messages, controller and JSON helpers), `Packetery\Tools\Exception`
[VERIFY: packetery/libs/Tools/Exception/InvalidApiKeyException.php#InvalidApiKeyException],
`Packetery\Log` (the module's own log table), `Packetery\LogWrapper` (a wrapper over the PrestaShop
log) and `Packetery\Exceptions` (the exception hierarchy). Almost every class is a thin adapter over
a PrestaShop global class, written so that the rest of the code reaches `Db` only through `DbTools`
and so that version differences between supported PrestaShop releases stay in one place
[VERIFY: packetery/libs/Tools/ControllerWrapper.php#registerStylesheet]. A few call sites still
reach `Configuration` and `Tools` directly
[VERIFY: packetery/libs/Weight/Converter.php#PS_WEIGHT_UNIT].

Two adapters add behaviour of their own. `ConfigHelper` decides, per key, whether a value is
global or per shop, and it falls back to values written by module versions before 3.0
[VERIFY: packetery/libs/Tools/ConfigHelper.php#getConfigBehavior]. `DbTools` turns a silent
PrestaShop database error into a thrown `DatabaseException` and writes the failing query to the
module's own file log first [VERIFY: packetery/libs/Tools/DbTools.php#executeQueries].

The classes carry no PrestaShop hook handler, no controller and no route; they are called from the
module's other code only. All files except `CheckoutControllerUrlProvider` and
`CheckoutControllerUrlException` start with the `_PS_VERSION_` guard that PrestaShop modules use to
stop direct HTTP access [VERIFY: packetery/libs/Tools/Logger.php#logToFile].

> ⚠ add business context (elicitation)

## packetery-tools: configuration keys

packetery-tools declares the module's configuration keys as constants on `ConfigHelper`, and every
read and write of them goes through `ConfigHelper::get` and `ConfigHelper::update` rather than
through PrestaShop `Configuration` [VERIFY: packetery/libs/Tools/ConfigHelper.php#getMultiple].
The values live in the shop database, written by the installer and by the module's settings form;
this page names the keys only.

| Constant | Configuration key | Read by | Anchor |
|---|---|---|---|
| `KEY_APIPASS` | `PACKETERY_APIPASS` | `ConfigHelper::getApiPass`, and `getValidApiKey`, which cuts the first 16 characters off as the API key | [VERIFY: packetery/libs/Tools/ConfigHelper.php#getApiKeyFromApiPass] |
| `KEY_ESHOP_ID` | `PACKETERY_ESHOP_ID` | the order export, as the sender label; the only key marked `BEHAVIOR_SEPARATE`, so it is resolved per shop group and shop | [VERIFY: packetery/libs/Order/OrderExporter.php#senderLabel] |
| `KEY_ID_PREFERENCE` | `PACKETERY_ID_PREFERENCE` | `ConfigHelper::isOrderNumberByReference`, compared against `Packetery::ID_PREF_REF` | [VERIFY: packetery/libs/Tools/ConfigHelper.php#isOrderNumberByReference] |
| `KEY_WIDGET_VALIDATION_MODE` | `PACKETERY_WIDGET_VALIDATION_MODE` | `ConfigHelper::isApiWidgetValidationModeEnabled` | [VERIFY: packetery/libs/Tools/ConfigHelper.php#isApiWidgetValidationModeEnabled] |
| `KEY_USE_PS_CURRENCY_CONVERSION` | `PACKETERY_USE_PS_CURRENCY_CONVERSION` | the order export, when it converts the cash-on-delivery amount | [VERIFY: packetery/libs/Order/OrderExporter.php#KEY_USE_PS_CURRENCY_CONVERSION] |
| `KEY_SHOW_CONSIGN_PASSWORD` | `PACKETERY_SHOW_CONSIGN_PASSWORD` | the consign-password settings of the order module | [VERIFY: packetery/libs/Order/ConsignPasswordSettings.php#KEY_SHOW_CONSIGN_PASSWORD] |
| `KEY_CONSIGN_PASSWORD_RETRIEVAL` | `PACKETERY_CONSIGN_PASSWORD_RETRIEVAL` | the same settings class, to choose between the immediate and the cron retrieval mode | [VERIFY: packetery/libs/Order/ConsignPasswordSettings.php#KEY_CONSIGN_PASSWORD_RETRIEVAL] |

Four further keys cache what the version check found on the releases endpoint. `ConfigHelper`
declares them, the version checker writes and reads them.

| Constant | Configuration key | Read by | Anchor |
|---|---|---|---|
| `KEY_LAST_VERSION_CHECK_TIMESTAMP` | `PACKETERY_LAST_VERSION_CHECK_TIMESTAMP` | the version checker, to decide whether the next check is due | [VERIFY: packetery/libs/Module/VersionChecker.php#KEY_LAST_VERSION_CHECK_TIMESTAMP] |
| `KEY_LAST_VERSION` | `PACKETERY_LAST_VERSION` | the version checker, to compare the cached release against the installed version | [VERIFY: packetery/libs/Module/VersionChecker.php#isNewVersionAvailable] |
| `KEY_LAST_VERSION_URL` | `PACKETERY_LAST_VERSION_URL` | the version checker, assigned to the admin template as the download link | [VERIFY: packetery/libs/Module/VersionChecker.php#KEY_LAST_VERSION_URL] |
| `KEY_LAST_RELEASE_NOTES` | `PACKETERY_LAST_RELEASE_NOTES` | the version checker, assigned to the admin template | [VERIFY: packetery/libs/Module/VersionChecker.php#KEY_LAST_RELEASE_NOTES] |

`ConfigHelper::get` has two behaviours. A key that is not listed in `configBehavior` is read with
all scope arguments set to `null`, so one value serves the whole installation. A key marked
`BEHAVIOR_SEPARATE` is read for the current shop group and shop, and when nothing is stored there
the method retries for the group alone and then without scope, which is how values written before
the multistore-aware version are still found [VERIFY: packetery/libs/Tools/ConfigHelper.php#getConfigBehavior].
Other configuration keys of the module are used as string literals outside `ConfigHelper` and are
not declared here.

## packetery-tools: public interface

packetery-tools exposes its 52 public methods as plain PHP classes; the module has no route, no
console command and no public HTTP surface of its own. The table groups the methods by class.

| Class | Public methods | Behaviour | Anchor |
|---|---|---|---|
| `ConfigHelper` | 10 | reads and writes the configuration keys, resolves the API key from the stored API credential, and answers the two boolean settings questions | [VERIFY: packetery/libs/Tools/ConfigHelper.php#getMultiple] |
| `DbTools` | 10 | `getRows`, `getRow`, `getValue`, `execute`, `insert`, `update`, `delete`, `executeQueries` and `getPairs` over PrestaShop `Db`; all of them except `getPairs`, which only reshapes an array, raise `DatabaseException` on failure | [VERIFY: packetery/libs/Tools/DbTools.php#getPairs] |
| `Tools` | 2 | `getValue` re-implements the request reader of PrestaShop 1.7.6 for older releases, where stripslashes breaks JSON in POST; `sanitizeFloatValue` normalises decimal commas and spaces | [VERIFY: packetery/libs/Tools/Tools.php#sanitizeFloatValue] |
| `Logger` | 2 | appends one timestamped line to the module's own error log file, and returns `false` instead of throwing when the file or its directory is not writable | [VERIFY: packetery/libs/Tools/Logger.php#logToFile] |
| `ControllerWrapper` | 3 | `registerJavascript` and `registerStylesheet` fall back to `addJS` and `addCSS` below PrestaShop 1.7.0.0 | [VERIFY: packetery/libs/Tools/ControllerWrapper.php#registerJavascript] |
| `HttpClientWrapper` | 2 | `get` and `post` create a Symfony HTTP client per call and return the response body as a string | [VERIFY: packetery/libs/Tools/HttpClientWrapper.php#GET_METHOD] |
| `MessageManager` | 3 | stores one message per error level in the PrestaShop cookie under the `packetery_` prefix; `getMessageClean` reads and unsets it | [VERIFY: packetery/libs/Tools/MessageManager.php#getContextAndKey] |
| `JsonStructureValidator` | 2 | checks decoded JSON against a nested array of expected scalar type names, recursing into sub-arrays | [VERIFY: packetery/libs/Tools/JsonStructureValidator.php#isStructureValid] |
| `CheckoutControllerUrlProvider` | 2 | builds the front checkout controller link and appends the `action=` query parameter, honouring friendly URLs and multistore | [VERIFY: packetery/libs/Tools/CheckoutControllerUrlProvider.php#getPath] |
| `PrestashopLogWrapper` | 2 | static `addLog` and `logException` over `PrestaShopLogger`, with the severity constants `LEVEL_INFO`, `LEVEL_WARNING` and `LEVEL_ERROR` | [VERIFY: packetery/libs/LogWrapper/PrestashopLogWrapper.php#logException] |

The eleventh helper class, `LogRepository`, contributes 9 more public methods and is described
together with the table it owns; the remaining 5 belong to the exception classes
[VERIFY: packetery/libs/Exceptions/AggregatedException.php#getExceptions]. The classes are resolved from the module's container by
class name, so a consumer asks for `ControllerWrapper` and gets it wired with the current
PrestaShop controller [VERIFY: packetery/packetery.php#ControllerWrapper].

## packetery-tools: error handling

packetery-tools declares the module's whole exception hierarchy: 14 classes in
`Packetery\Exceptions` and `InvalidApiKeyException` in `Packetery\Tools\Exception`. All of them
extend `\Exception` except `InvalidApiKeyException`, which extends `\RuntimeException`
[VERIFY: packetery/libs/Tools/Exception/InvalidApiKeyException.php#createFromMissingKey]. The set
names one failing operation each: `ApiClientException`, `DatabaseException`, `DownloadException`,
`ExportException`, `LabelPrintException`, `CollectionPrintException`, `VersionCheckerException`,
`FormDataPersistException`, `FailedToConvertJsonException`, `EmptyArrayToJsonConvertException`,
`ConsignPasswordCleanupException`, `TrackingNumberClearingException`,
`CheckoutControllerUrlException` and `AggregatedException`.

Two of them carry more than a name. `AggregatedException` wraps a list of exceptions raised while
processing several orders and hands it back through `getExceptions`
[VERIFY: packetery/libs/Exceptions/AggregatedException.php#getExceptions]. `VersionCheckerException`
offers a named constructor for an unusable response from the releases endpoint
[VERIFY: packetery/libs/Exceptions/VersionCheckerException.php#createForInvalidLatestReleaseResponse].

Three log sinks exist side by side. `Logger::logToFile` appends to a file in the module root,
and it is the only place that receives the failing SQL statement
[VERIFY: packetery/libs/Tools/Logger.php#errorLogFilePath]. `PrestashopLogWrapper` writes into the
PrestaShop log with a severity, and `logException` folds the message, file and line of an exception
into one line [VERIFY: packetery/libs/LogWrapper/PrestashopLogWrapper.php#LEVEL_ERROR]. The
`packetery_log` table records business actions. `DbTools` uses the first two together: it writes
the query to the file log, then throws a `DatabaseException` whose message points the reader at
that log rather than repeating the statement [VERIFY: packetery/libs/Tools/DbTools.php#getRows].
`executeQueries` is the one method that swallows the exception: it copies the message into the
PrestaShop log and continues with the next statement, or stops when the caller asked for it
[VERIFY: packetery/libs/Tools/DbTools.php#executeQueries].

## packetery-tools: data model

packetery-tools owns one table, `packetery_log`, created and dropped by `LogRepository` from SQL it
builds with the shop table prefix [VERIFY: packetery/libs/Log/LogRepository.php#getCreateTableSql].
The repository is the only writer; the admin log grid reads the same table directly.

| Entity | Column | Type | Note | Anchor |
|---|---|---|---|---|
| `packetery_log` | `id` | int(11), auto increment | primary key | [VERIFY: packetery/libs/Log/LogRepository.php#getDropTableSql] |
| `packetery_log` | `order_id` | int(10), nullable | `insertRow` stores `null` when the caller passes `0` or `'0'` | [VERIFY: packetery/libs/Log/LogRepository.php#hasAnyByOrderId] |
| `packetery_log` | `params` | text, not null | the caller's parameter array as JSON with unescaped unicode, escaped for SQL | [VERIFY: packetery/libs/Log/LogRepository.php#insertRow] |
| `packetery_log` | `status` | varchar(20), default empty | `STATUS_SUCCESS` or `STATUS_ERROR` | [VERIFY: packetery/libs/Log/LogRepository.php#STATUS_ERROR] |
| `packetery_log` | `action` | varchar(45), default empty | one of the nine `ACTION_` constants | [VERIFY: packetery/libs/Log/LogRepository.php#ACTION_PACKET_SENDING] |
| `packetery_log` | `date` | datetime, not null | written from `DateTimeImmutable` in the PHP default timezone | [VERIFY: packetery/libs/Log/LogRepository.php#insert] |

The nine action constants are `ACTION_PACKET_SENDING`, `ACTION_LABEL_PRINT`,
`ACTION_SENDER_VALIDATION`, `ACTION_PACKET_TRACKING`, `ACTION_CARRIER_TRACKING_NUMBER`,
`ACTION_PACKET_CANCELLING`, `ACTION_PICKUP_POINT_VALIDATE`, `ACTION_COLLECTION_PRINT` and
`ACTION_PACKET_INFO`; `getActionTranslations` maps each of them to a translated label for the admin
grid, and `getTranslatedAction` returns the raw value when a label is missing
[VERIFY: packetery/libs/Log/LogRepository.php#getActionTranslations]. `purge` deletes rows older
than a number of days that the caller supplies, and `hasAnyByOrderId` answers whether an order has
any log row at all [VERIFY: packetery/libs/Log/LogRepository.php#purge].

## packetery-tools: dependencies and known limitations

packetery-tools names one outbound counterparty of its own, the shop database it reaches through
`DbTools`.

calls → mysql (sync, SQL)

Every statement `DbTools` runs goes to the shop's own MySQL database over the PrestaShop `Db`
instance the container hands it; the module opens no connection of its own
[VERIFY: packetery/libs/Tools/DbTools.php#execute]. `HttpClientWrapper` makes outbound HTTP calls,
but the URL and the options are arguments, so the counterparty is chosen by the caller and not by
packetery-tools [VERIFY: packetery/libs/Tools/HttpClientWrapper.php#POST_METHOD]. Beyond that the
coupling is to the platform and to two libraries: `Tools` extends `\ToolsCore`, `ControllerWrapper`
wraps `\ControllerCore`, `MessageManager` writes into the PrestaShop cookie, `PrestashopLogWrapper`
calls `PrestaShopLogger`, and `HttpClientWrapper` builds on the Symfony HTTP client
[VERIFY: packetery/libs/Tools/MessageManager.php#setMessage].

Limitations the code shows:

- `ConfigHelper::getValidApiKey` is declared to return `string` but returns `false` when no API
  credential is stored, so the declared type and the code disagree on that path
  [VERIFY: packetery/libs/Tools/ConfigHelper.php#getValidApiKey].
- `ConfigHelper::getApiKey` is marked deprecated in favour of `getValidApiKey` and is still present
  [VERIFY: packetery/libs/Tools/ConfigHelper.php#getApiKey].
- `LogRepository::insertRow` defaults its status argument to a string literal instead of
  `STATUS_SUCCESS` [VERIFY: packetery/libs/Log/LogRepository.php#STATUS_SUCCESS].
- `HttpClientWrapper` catches the typed Symfony transport failures and rethrows a plain
  `\Exception`, so a caller cannot tell a transport error from any other failure
  [VERIFY: packetery/libs/Tools/HttpClientWrapper.php#get].
- `MessageManager` keeps one message per error level because the cookie cannot hold arrays
  [VERIFY: packetery/libs/Tools/MessageManager.php#PREFIX].
- The file log has no rotation and no size limit in this module; `Logger` only appends
  [VERIFY: packetery/libs/Tools/Logger.php#fopen].
