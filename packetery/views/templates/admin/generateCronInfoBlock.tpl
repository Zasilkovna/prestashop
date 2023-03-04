<div id="packetery-form" class="panel col-lg-12">
    <div class="panel-heading">
        {l s='CRON jobs' mod='packetery'}
    </div>
    <div class="panel col-lg-12">
        <div class="panel-heading">
            {l s='Automatic PDF label deletion via CRON' mod='packetery'}
        </div>
        <div class="clearfix">
            <div class="col-lg-12">
                <p>
                    {l s='This URL below provides basic functionality for deleting old PDF labels.' mod='packetery'}
                    {l s='It deletes all PDF labels older than %s days.' sprintf=[$numberOfDays] mod='packetery'}
                    {l s='To delete the PDF labels automatically, you need to call this URL via CRON jobs.' mod='packetery'}
                </p>
                <p>
                    <a href="{$deleteLabelsUrl}" target="_blank">{$deleteLabelsUrl}</a>
                </p>
                <p>
                    {l s='You have also added options to adjust the functionality by adding some extra parameters.' mod='packetery'}
                </p>
                <table class="table">
                    <thead>
                    <tr>
                        <th>{l s='Parameter' mod='packetery'}</th>
                        <th>{l s='Explanation' mod='packetery'}</th>
                    </tr>
                    <tr>
                        <td>{l s='number_of_days' mod='packetery'}</td>
                        <td>
                            {l s='The parameter tells how old files need to be to be deleted.' mod='packetery'}
                            {l s='This example will delete all PDF labels that are older than %s days.' sprintf=[$numberOfDays] mod='packetery'}
                        </td>
                    </tr>
                    <tr>
                        <td>{l s='number_of_files' mod='packetery'}</td>
                        <td>
                            {l s='The parameter tells how many PDF labels can be deleted in one CRON call.' mod='packetery'}
                            {l s='This example will delete max %s PDF labels in one batch.' sprintf=[$numberOfFiles] mod='packetery'}
                            {l s='Can be used to ease up your server/hosting resources.' mod='packetery'}
                        </td>
                    </tr>
                    </thead>
                </table>
                <p>{l s='* You can also use these two parameters separatelly and also change their values.' mod='packetery'}</p>
            </div>
        </div>
    </div>

    <div class="panel col-lg-12">
        <div class="panel-heading">
            {l s='Automatic updating of Packeta carriers' mod='packetery'}
        </div>
        <div class="clearfix">
            <div class="col-lg-12">
                <p>
                    {l s='Link to update Packeta carriers using CRON' mod='packetery'}:
                </p>
                <p>
                    <a href="{$updateCarriersUrl}" target="_blank">{$updateCarriersUrl}</a>
                </p>
            </div>
        </div>
    </div>
</div>
