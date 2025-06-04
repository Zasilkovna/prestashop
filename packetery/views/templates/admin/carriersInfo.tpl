<div class="packetery-panel panel col-lg-12">
	<div class="panel-heading">
		{l s='Packeta carriers update' d='Modules.Packetery.Carriersinfo'}
	</div>

	<div class="clearfix">
		{if isset($messages)}
			<div class="col-lg-12">
				{foreach from=$messages item=message}
					<div class="alert alert-{$message.class}">{$message.text}</div>
				{/foreach}
			</div>
		{/if}
	</div>

	{if isset($updateCarriersLink)}
		{if $totalCarriers}
			<div class="clearfix">
				<label class="control-label col-lg-3">
					{l s='Total carriers' d='Modules.Packetery.Carriersinfo'}:
				</label>
				<div class="packetery-carriers-right-column"><strong>{$totalCarriers|escape:'htmlall':'UTF-8'}</strong></div>
			</div>

			<div class="clearfix">
				<label class="control-label col-lg-3">
					{l s='Last carriers update' d='Modules.Packetery.Carriersinfo'}:
				</label>
				<div class="packetery-carriers-right-column">{$lastCarriersUpdate|escape:'htmlall':'UTF-8'}</div>
			</div>
		{else}
			<div class="clearfix">
				<label class="control-label col-lg-3"></label>
				<div class="packetery-carriers-right-column packetery-button-container">
					{l s='The list of carriers is currently empty.' d='Modules.Packetery.Carriersinfo'}
				</div>
			</div>
		{/if}
		<div class="clearfix">
			<label class="control-label col-lg-3"></label>
			<div class="packetery-carriers-right-column packetery-button-container">
				<img src="{$module_dir|escape:'html':'UTF-8'}/logo.png" alt="Packeta" />
				<a href="{$updateCarriersLink}" class="btn btn-default btn-block"><i class="icon-arrow-down"></i> {l s='Manually update the list of carriers' d='Modules.Packetery.Carriersinfo'}</a>
			</div>
		</div>
	{else}
		<div class="clearfix">
			<div class="packetery-carriers-right-column packetery-button-container">
				{l s='It is not possible to use the update of carriers. First, set an API password.' d='Modules.Packetery.Carriersinfo'}
			</div>
		</div>
	{/if}

</div>
