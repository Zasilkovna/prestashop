<div id="packetery-form" class="panel col-lg-12">
    <div class="panel-heading">
        {l s='Automatic PDF label deletion via CRON' mod='packetery'}
    </div>

    <div class="clearfix">
        <div class="col-lg-12">
            <p>
                {l s='This URL below provides basic functionality for deleting old PDF labels.' mod='packetery'}
                {l s='It deletes all PDF labels older than 7 days.' mod='packetery'}
                {l s='To delete the PDF labels automatically, you need to call this URL via CRON jobs.' mod='packetery'}
            </p>
            <p>
                <a href="{$DeleteLabelsUrl}" target="_blank">{$DeleteLabelsUrl}</a>
            </p>
            <p>
                {l s='You have also added options to adjust the functionality by adding some extra parameters.' mod='packetery'}
            </p>
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='Parameter' mod='packetery'}</th>
                        <th>{l s='Explanation' mod='packetery'}</th>
                        <th>{l s='Example URL' mod='packetery'}</th>
                    </tr>
                    <tr>
                        <td>{l s='numberofdays' mod='packetery'}</td>
                        <td>
                            {l s='The parameter tells how old files need to be to be deleted.' mod='packetery'}
                            {l s='This example will delete all PDF labels that are older than 10 days.' mod='packetery'}
                        </td>
                        <td>
                            <a target="_blank" href="{$DeleteLabelsUrl}&numberofdays=10">
                                {$DeleteLabelsUrl}<strong>&numberofdays=10</strong>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>{l s='numberoffiles' mod='packetery'}</td>
                        <td>
                            {l s='The parameter tells how many PDF labels can be deleted in one CRON call.' mod='packetery'}
                            {l s='This example will delete max 20 PDF labels in one batch.' mod='packetery'}
                            {l s='Can be used to ease up your server/hosting resources.' mod='packetery'}
                        </td>
                        <td>
                            <a target="_blank" href="{$DeleteLabelsUrl}&numberoffiles=20">
                                {$DeleteLabelsUrl}<strong>&numberoffiles=20</strong>
                            </a>
                        </td>
                    </tr>
                </thead>
            </table>
            <p>{l s='* You can also combine these two parameters together.' mod='packetery'}</p>
        </div>
    </div>
</div>