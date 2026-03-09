{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<div class="panel">
    <div class="panel-heading">
        {l s='CRON jobs' mod='packetery'}
    </div>
    <div class="panel">
        <div class="panel-heading">
            {l s='Automatic PDF label deletion via CRON' mod='packetery'}
        </div>
        <div class="clearfix">
            <p>
                {l s='This URL below provides basic functionality for deleting old PDF labels.' mod='packetery'}
                {l s='It deletes all PDF labels older than %s days.' sprintf=[$numberOfDays] mod='packetery'}
                {l s='To delete the PDF labels automatically, you need to call this URL via CRON jobs.' mod='packetery'}
            </p>
            <p>
                <a href="{$deleteLabelsUrl}" target="_blank">{$deleteLabelsUrl}</a>
            </p>
            <p>
                {l s='Extended options are also available to modify the functionality using parameters.' mod='packetery'}
            </p>
            <table class="table">
                <thead>
                <tr>
                    <th>{l s='Parameter' mod='packetery'}</th>
                    <th>{l s='Explanation' mod='packetery'}</th>
                </tr>
                <tr>
                    <td>number_of_days</td>
                    <td>
                        {l s='The parameter tells how old files need to be to be deleted.' mod='packetery'}
                        {l s='This example will delete all PDF labels that are older than %s days.' sprintf=[$numberOfDays] mod='packetery'}
                    </td>
                </tr>
                <tr>
                    <td>number_of_files</td>
                    <td>
                        {l s='The parameter tells how many PDF labels can be deleted in one CRON call.' mod='packetery'}
                        {l s='This example will delete max %s PDF labels in one batch.' sprintf=[$numberOfFiles] mod='packetery'}
                        {l s='Can be used to ease up your server/hosting resources.' mod='packetery'}
                    </td>
                </tr>
                </thead>
            </table>
            <p>{l s='* You can also use these two parameters separately and change their values.' mod='packetery'}</p>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            {l s='Packeta carriers update' mod='packetery'}
        </div>
        <div class="clearfix">
            <div class="col-lg-12">
                {if isset($updateCarriersUrl)}
                    <p>
                        {l s='Link to update Packeta carriers using CRON' mod='packetery'}:
                    </p>
                    <p>
                        <a href="{$updateCarriersUrl}" target="_blank">{$updateCarriersUrl}</a>
                    </p>
                {else}
                    <p>
                       {l s='It is not possible to use the update of carriers. First, set an API password.' mod='packetery'}
                    </p>
               {/if}
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            {l s='Packet status tracking' mod='packetery'}
        </div>
        <div class="clearfix">
            <div class="col-lg-12">
                {if isset($updatePacketStatusesUrl)}
                    <p>
                        {l s='To automatically update packet statuses regularly, you need to call this URL via CRON jobs.' mod='packetery'}
                    </p>
                    <p>
                        <a href="{$updatePacketStatusesUrl}" target="_blank">{$updatePacketStatusesUrl}</a>
                    </p>
                {else}
                    <p>
                        {l s='It is not possible to use the task for updating packet statuses. First, set an API password.' mod='packetery'}
                    </p>
                {/if}
            </div>
        </div>
    </div>

</div>
