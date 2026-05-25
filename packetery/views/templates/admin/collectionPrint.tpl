{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{l s='Bill of delivery' mod='packetery'} – {l s='Packeta' mod='packetery'}</title>
    <link rel="stylesheet" href="{$collectionPrintCssUrl|escape:'html':'UTF-8'}">
    <script src="{$collectionPrintJsUrl|escape:'html':'UTF-8'}" defer></script>
</head>
<body>
<table>
    <thead>
        <tr>
            <td colspan="{if $showConsignPassword}7{else}6{/if}">
                <table>
                    <tbody>
                    <tr>
                        <td class="cell-border">
                            <strong>{l s='Bill of delivery' mod='packetery'}</strong><br>
                            {$barcodeText|escape:'htmlall':'UTF-8'}<br>
                            {l s='Packages:' mod='packetery'} {$orderCount|intval}<br>
                            {l s='Printed:' mod='packetery'} {$generatedAt|escape:'htmlall':'UTF-8'}
                        </td>
                        <td class="cell-border cell-center">
                            <img src="data:image/png;base64,{$barcodeImage|escape:'htmlall':'UTF-8'}" alt="Barcode" class="header-barcode">
                        </td>
                        <td class="cell-border">
                            <strong>{l s='Sender' mod='packetery'}</strong><br>
                            {if $sender.name}{$sender.name|escape:'htmlall':'UTF-8'}<br>{/if}
                            {if $sender.street}{$sender.street|escape:'htmlall':'UTF-8'}<br>{/if}
                            {if $sender.zip || $sender.city}{$sender.zip|escape:'htmlall':'UTF-8'} {$sender.city|escape:'htmlall':'UTF-8'}{/if}
                        </td>
                        <td class="cell-border">
                            <strong>{l s='Recipient' mod='packetery'}</strong><br>
                            {$recipient.company|escape:'htmlall':'UTF-8'}<br>
                            {$recipient.street|escape:'htmlall':'UTF-8'}<br>
                            {$recipient.zip|escape:'htmlall':'UTF-8'} {$recipient.city|escape:'htmlall':'UTF-8'}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <th>{l s='Order No.' mod='packetery'}</th>
            <th>{l s='Barcode' mod='packetery'}</th>
            {if $showConsignPassword}<th>{l s='Z-BOX consign password' mod='packetery'}</th>{/if}
            <th>{l s='Created' mod='packetery'}</th>
            <th>{l s='Recipient full name' mod='packetery'}</th>
            <th>{l s='C.O.D.' mod='packetery'}</th>
            <th>{l s='Pickup point/Service' mod='packetery'}</th>
        </tr>
    </thead>
    <tbody>
        {foreach $orders as $row}
        <tr class="{if $row.index % 2 == 0}even{else}odd{/if}">
            <td class="cell-nowrap">{$row.orderNumber|escape:'htmlall':'UTF-8'}</td>
            <td class="cell-nowrap cell-center">{$row.trackingNumber|escape:'htmlall':'UTF-8'}</td>
            {if $showConsignPassword}<td class="cell-nowrap cell-center">{$row.consignPassword|default:''|escape:'htmlall':'UTF-8'}</td>{/if}
            <td class="cell-nowrap cell-center">{$row.created|escape:'htmlall':'UTF-8'}</td>
            <td class="cell-nowrap cell-center">{$row.customerName|escape:'htmlall':'UTF-8'}</td>
            <td class="cell-nowrap cell-right"><strong>{$row.cod|escape:'htmlall':'UTF-8'}&nbsp;{$row.codCurrency|escape:'htmlall':'UTF-8'}</strong></td>
            <td class="cell-center">{$row.pickupPoint|escape:'htmlall':'UTF-8'}</td>
        </tr>
        {/foreach}
        <tr>
            <td>
                <div class="end-marker">
                    {l s='THE END' mod='packetery'}
                </div>
            </td>
        </tr>
    </tbody>
</table>
</body>
</html>
