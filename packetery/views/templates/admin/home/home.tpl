{**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<div id="packetery-home">
    {if $setupGuide}
        <div class="alert alert-info">
            {l s='Follow these steps to get the most out of the Packeta module. Completed steps are checked automatically based on your saved settings.' mod='packetery'}
        </div>
        <div class="panel" id="packetery-setup-guide">
            <div class="panel-heading packetery-setup-guide-heading">
                <span role="heading" aria-level="2">{l s='Let\'s set up the module' mod='packetery'}</span>
                <span class="badge" id="packetery-setup-guide-count">{$setupGuide.doneCount|intval} / {$setupGuide.stepCount|intval}</span>
                <span class="packetery-setup-guide-progress">
                    <span class="progress">
                        <span class="progress-bar progress-bar-success" id="packetery-setup-guide-progress" role="progressbar"
                              aria-label="{l s='Setup progress' mod='packetery'}"
                              aria-valuemin="0" aria-valuemax="{$setupGuide.stepCount|intval}" aria-valuenow="{$setupGuide.doneCount|intval}"
                              style="width: {$setupGuide.progressPercent|intval}%;"></span>
                    </span>
                    <span class="packetery-setup-guide-percent" id="packetery-setup-guide-percent">{$setupGuide.progressPercent|intval}%</span>
                </span>
            </div>
            <ol class="list-group packetery-setup-steps">
                {foreach from=$setupGuide.steps item=step}
                    <li class="list-group-item packetery-setup-step{if $step.isDone} packetery-setup-step-done{/if}"
                        id="packetery-setup-step-{$step.id|escape:'html':'UTF-8'}" data-step-done="{if $step.isDone}1{else}0{/if}">
                        {if $step.isDone}
                            <span class="label label-success packetery-setup-marker"><i class="material-icons" aria-hidden="true">check</i></span>
                        {elseif $step.isInformative}
                            <span class="packetery-setup-marker packetery-setup-marker-info text-muted"><i class="material-icons" aria-hidden="true">store</i></span>
                        {else}
                            <span class="packetery-setup-marker packetery-setup-marker-todo">{$step.number|intval}</span>
                        {/if}
                        <div class="packetery-setup-step-body">
                            <div class="packetery-setup-step-heading">
                                <span class="packetery-setup-step-title{if $step.isDone} text-muted{/if}" role="heading" aria-level="3">{$step.title|escape:'html':'UTF-8'}</span>
                                {if $step.isDone}
                                    <span class="label label-success packetery-setup-step-label packetery-setup-step-done-label">{l s='Done' mod='packetery'}</span>
                                {/if}
                                {if $step.showOptionalLabel}
                                    <span class="label label-default packetery-setup-step-label packetery-setup-step-optional-label">{l s='Optional' mod='packetery'}</span>
                                {/if}
                                {if $step.isInformative}
                                    <span class="label label-default packetery-setup-step-label packetery-setup-step-informative-label">{l s='For your information' mod='packetery'}</span>
                                {/if}
                            </div>
                            <p class="help-block packetery-setup-step-description">{$step.description|escape:'html':'UTF-8'}</p>
                            <div class="help-block packetery-setup-step-location">{$step.location|escape:'html':'UTF-8'}</div>
                            {if $step.cronTasks}
                                <ul class="list-group packetery-setup-cron-tasks">
                                    {foreach from=$step.cronTasks item=cronTask}
                                        <li class="list-group-item packetery-setup-cron-task"
                                            id="packetery-setup-cron-{$cronTask.id|escape:'html':'UTF-8'}" data-task-run="{if $cronTask.lastRun}1{else}0{/if}">
                                            {if $cronTask.lastRun}
                                                <span class="label label-success packetery-setup-cron-marker"><i class="material-icons" aria-hidden="true">check</i></span>
                                            {else}
                                                <span class="packetery-setup-cron-marker packetery-setup-marker-todo text-muted"></span>
                                            {/if}
                                            <span class="packetery-setup-cron-name">{$cronTask.name|escape:'html':'UTF-8'}</span>
                                            <span class="packetery-setup-cron-status text-muted">
                                                {if $cronTask.lastRun}
                                                    {capture assign=cronLastRunDate}{dateFormat date=$cronTask.lastRun full=1}{/capture}
                                                    {l s='Last run: %s' sprintf=[$cronLastRunDate] mod='packetery'}
                                                {else}
                                                    {l s='Not run yet' mod='packetery'}
                                                {/if}
                                            </span>
                                        </li>
                                    {/foreach}
                                </ul>
                            {/if}
                        </div>
                        <div class="packetery-setup-step-actions">
                            {foreach from=$step.buttons item=button}
                                <a class="btn {if $button.isPrimary}btn-primary{else}btn-default{/if}" href="{$button.url|escape:'htmlall':'UTF-8'}">{$button.label|escape:'html':'UTF-8'}</a>
                            {/foreach}
                        </div>
                    </li>
                {/foreach}
            </ol>
        </div>
    {/if}
</div>
