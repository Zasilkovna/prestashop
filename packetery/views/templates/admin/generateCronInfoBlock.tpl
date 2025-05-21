<div class="panel">
    <div class="panel-heading">
        {l s='CRON jobs' d='Modules.Packetery.Generatecroninfoblock'}
    </div>
    <div class="panel">
        <div class="panel-heading">
            {l s='Automatic PDF label deletion via CRON' d='Modules.Packetery.Generatecroninfoblock'}
        </div>
        <div class="clearfix">
            <p>
                {l s='This URL below provides basic functionality for deleting old PDF labels.' d='Modules.Packetery.Generatecroninfoblock'}
                {l s='It deletes all PDF labels older than %s days.' sprintf=[$numberOfDays] d='Modules.Packetery.Generatecroninfoblock'}
                {l s='To delete the PDF labels automatically, you need to call this URL via CRON jobs.' d='Modules.Packetery.Generatecroninfoblock'}
            </p>
            <p>
                <a href="{$deleteLabelsUrl}" target="_blank">{$deleteLabelsUrl}</a>
            </p>
            <p>
                {l s='Extended options are also available to modify the functionality using parameters.' d='Modules.Packetery.Generatecroninfoblock'}
            </p>
            <table class="table">
                <thead>
                <tr>
                    <th>{l s='Parameter' d='Modules.Packetery.Generatecroninfoblock'}</th>
                    <th>{l s='Explanation' d='Modules.Packetery.Generatecroninfoblock'}</th>
                </tr>
                <tr>
                    <td>number_of_days</td>
                    <td>
                        {l s='The parameter tells how old files need to be to be deleted.' d='Modules.Packetery.Generatecroninfoblock'}
                        {l s='This example will delete all PDF labels that are older than %s days.' sprintf=[$numberOfDays] d='Modules.Packetery.Generatecroninfoblock'}
                    </td>
                </tr>
                <tr>
                    <td>number_of_files</td>
                    <td>
                        {l s='The parameter tells how many PDF labels can be deleted in one CRON call.' d='Modules.Packetery.Generatecroninfoblock'}
                        {l s='This example will delete max %s PDF labels in one batch.' sprintf=[$numberOfFiles] d='Modules.Packetery.Generatecroninfoblock'}
                        {l s='Can be used to ease up your server/hosting resources.' d='Modules.Packetery.Generatecroninfoblock'}
                    </td>
                </tr>
                </thead>
            </table>
            <p>{l s='* You can also use these two parameters separately and change their values.' d='Modules.Packetery.Generatecroninfoblock'}</p>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            {l s='Packeta carriers update' d='Modules.Packetery.Generatecroninfoblock'}
        </div>
        <div class="clearfix">
            <div class="col-lg-12">
                {if isset($updateCarriersUrl)}
                    <p>
                        {l s='Link to update Packeta carriers using CRON' d='Modules.Packetery.Generatecroninfoblock'}:
                    </p>
                    <p>
                        <a href="{$updateCarriersUrl}" target="_blank">{$updateCarriersUrl}</a>
                    </p>
                {else}
                    <p>
                       {l s='It is not possible to use the update of carriers. First, set an API password.' d='Modules.Packetery.Generatecroninfoblock'}
                    </p>
               {/if}
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            {l s='Packet status tracking' d='Modules.Packetery.Generatecroninfoblock'}
        </div>
        <div class="clearfix">
            <div class="col-lg-12">
                {if isset($updatePacketStatusesUrl)}
                    <p>
                        {l s='To automatically update packet statuses regularly, you need to call this URL via CRON jobs.' d='Modules.Packetery.Generatecroninfoblock'}
                    </p>
                    <p>
                        <a href="{$updatePacketStatusesUrl}" target="_blank">{$updatePacketStatusesUrl}</a>
                    </p>
                {else}
                    <p>
                        {l s='It is not possible to use the task for updating packet statuses. First, set an API password.' d='Modules.Packetery.Generatecroninfoblock'}
                    </p>
                {/if}
            </div>
        </div>
    </div>

</div>
