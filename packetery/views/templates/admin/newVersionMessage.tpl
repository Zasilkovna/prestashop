{l s='A new version of the Packeta module is available: %newVersion% (current version: %currentVersion%).' sprintf=['%newVersion%'=>$newVersion,'%currentVersion%'=>$currentVersion] d='Modules.Packetery.Newversionmessage'}
{if $downloadUrl}
    {l s='Download it' d='Modules.Packetery.Newversionmessage'} <a href="{$downloadUrl}" target="_blank">{l s='here' d='Modules.Packetery.Newversionmessage'}</a>.
{/if}
<br>
{if $releaseNotes}
    {l s='Change log:' d='Modules.Packetery.Newversionmessage'}<br>
    {foreach $releaseNotes as $releaseNote}
        {$releaseNotes|nl2br|truncate:400:"… <a target='_blank href='https://github.com/Zasilkovna/prestashop/releases'>{l s='Read more' d='Modules.Packetery.Newversionmessage'}</a>" nofilter}
    {/foreach}
{/if}
