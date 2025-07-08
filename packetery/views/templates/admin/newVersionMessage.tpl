{l s='A new version of the Packeta module is available: %newVersion% (current version: %currentVersion%).' sprintf=['%newVersion%'=>$newVersion,'%currentVersion%'=>$currentVersion] mod='packetery'}
{if $downloadUrl}
    {l s='Download it' mod='packetery'} <a href="{$downloadUrl}" target="_blank">{l s='here' mod='packetery'}</a>.
{/if}
<br>
{if $releaseNotes}
    {l s='Change log:' mod='packetery'}<br>
    {foreach $releaseNotes as $releaseNote}
        {$releaseNotes|nl2br|truncate:400:"… <a target='_blank href='https://github.com/Zasilkovna/prestashop/releases'>{l s='Read more' mod='packetery'}</a>" nofilter}
    {/foreach}
{/if}
