{**
 * plugins/generic/staticEditorialTeam/templates/settingsForm.tpl
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings form for the static Editorial Team page.
 *}
<script>
	$(function() {ldelim}
		$('#staticEditorialTeamSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="staticEditorialTeamSettingsForm" method="post" action="{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="staticEditorialTeamSettingsFormNotification"}

	<p>{translate key="plugins.generic.staticEditorialTeam.settings.description"}</p>

	{capture assign="staticEditorialTeamWhere"}{"plugins.generic.staticEditorialTeam.settings.where.body"|translate|escape} <a href="{$settingsUrl|escape}" target="_blank" rel="noopener">{"plugins.generic.staticEditorialTeam.settings.where.link"|translate|escape}</a>{/capture}
	<div class="pkp_notification">
		{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId="staticEditorialTeamWhere" notificationTitle="plugins.generic.staticEditorialTeam.settings.where.title"|translate|escape notificationContents=$staticEditorialTeamWhere}
	</div>

	{if !$hasContent}
		<div class="pkp_notification">
			{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId="staticEditorialTeamEmpty" notificationStyleClass="notifyWarning" notificationTitle="plugins.generic.staticEditorialTeam.settings.empty.title"|translate|escape notificationContents="plugins.generic.staticEditorialTeam.settings.empty.body"|translate|escape}
		</div>
	{/if}

	{fbvFormArea id="staticEditorialTeamMode"}
		{fbvFormSection title="plugins.generic.staticEditorialTeam.settings.mode" list=true}
			{fbvElement type="radio" id="modeStaticOnly" name="mode" value=$modeStaticOnly checked=($mode == $modeStaticOnly) label="plugins.generic.staticEditorialTeam.settings.mode.staticOnly"}
			{fbvElement type="radio" id="modeStaticFirst" name="mode" value=$modeStaticFirst checked=($mode == $modeStaticFirst) label="plugins.generic.staticEditorialTeam.settings.mode.staticFirst"}
			{fbvElement type="radio" id="modeStaticLast" name="mode" value=$modeStaticLast checked=($mode == $modeStaticLast) label="plugins.generic.staticEditorialTeam.settings.mode.staticLast"}
		{/fbvFormSection}

		{fbvFormSection title="plugins.generic.staticEditorialTeam.settings.options" list=true}
			{fbvElement type="checkbox" id="showReviewers" name="showReviewers" value="1" checked=$showReviewers label="plugins.generic.staticEditorialTeam.settings.showReviewers"}
			{fbvElement type="checkbox" id="showHistoryLink" name="showHistoryLink" value="1" checked=$showHistoryLink label="plugins.generic.staticEditorialTeam.settings.showHistoryLink"}
			{fbvElement type="checkbox" id="hideOnHistoryPage" name="hideOnHistoryPage" value="1" checked=$hideOnHistoryPage label="plugins.generic.staticEditorialTeam.settings.hideOnHistoryPage"}
			{fbvElement type="checkbox" id="relabelField" name="relabelField" value="1" checked=$relabelField label="plugins.generic.staticEditorialTeam.settings.relabelField"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>
