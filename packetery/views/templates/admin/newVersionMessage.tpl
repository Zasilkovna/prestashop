{l s='A new version of the Packeta module is available: %newVersion% (current version: %currentVersion%).' sprintf=['%newVersion%'=>$newVersion,'%currentVersion%'=>$currentVersion] mod='packetery'}
{if $downloadUrl}
    {l s='Download it' mod='packetery'} <a href="{$downloadUrl}" target="_blank">{l s='here' mod='packetery'}</a>.
{/if}
