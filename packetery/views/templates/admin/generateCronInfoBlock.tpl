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
                <a href="{$DeleteLabelsUrl}&number_of_files=20&number_of_days=10"
                   target="_blank">{$deleteLabelsUrl}<strong>&number_of_files=20</strong><strong>&number_of_days=10</strong>
                </a>

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
                            {l s='This example will delete all PDF labels that are older than 10 days.' mod='packetery'}
                        </td>
                    </tr>
                    <tr>
                        <td>{l s='number_of_files' mod='packetery'}</td>
                        <td>
                            {l s='The parameter tells how many PDF labels can be deleted in one CRON call.' mod='packetery'}
                            {l s='This example will delete max 20 PDF labels in one batch.' mod='packetery'}
                            {l s='Can be used to ease up your server/hosting resources.' mod='packetery'}
                        </td>
                    </tr>
                </thead>
            </table>
            <p>{l s='* You can also use these two parameters separatelly.' mod='packetery'}</p>
        </div>
    </div>
</div>