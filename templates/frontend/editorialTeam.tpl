{**
 * plugins/generic/staticEditorialTeam/templates/frontend/editorialTeam.tpl
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Static Editorial Team page: displays the journal's free text instead
 *        of (or alongside) the dynamic listing introduced in OJS 3.5.
 *
 * @uses $staticEditorialTeamContent string Free text configured in the journal
 * @uses $staticEditorialTeamShowRoles bool Display the dynamic role listing
 * @uses $staticEditorialTeamStaticFirst bool Free text above the dynamic listing
 * @uses $staticEditorialTeamShowReviewers bool Display the peer reviewers
 * @uses $staticEditorialTeamShowHistoryLink bool Display the editorial history link
 *}
{include file="frontend/components/header.tpl" pageTitle="common.editorialMasthead"}

<div class="page page_masthead page_editorial_team">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="common.editorialMasthead"}

	<h1>{translate key="common.editorialMasthead"}</h1>

	{if $staticEditorialTeamStaticFirst}
		<div class="editorial_team_content">
			{$staticEditorialTeamContent}
			{include file="frontend/components/editLink.tpl" page="management" op="settings" path="context" anchor="masthead" sectionTitleKey="common.editorialMasthead"}
		</div>
	{/if}

	{if $staticEditorialTeamShowRoles}
		{foreach from=$mastheadRoles item="mastheadRole"}
			{if array_key_exists($mastheadRole->id, $mastheadUsers)}
				<h2>{$mastheadRole->getLocalizedData('name')|escape}</h2>
				<ul class="user_listing" role="list">
				{foreach from=$mastheadUsers[$mastheadRole->id] item="mastheadUser"}
					<li>
						{strip}
							{if !empty($mastheadUser['dateStart'])}
								<span class="date_start">{translate key="common.fromUntil" from=$mastheadUser['dateStart'] until=""}</span>
							{/if}
							<span class="name">
								{$mastheadUser['user']->getFullName()|escape}
								{if $mastheadUser['user']->getData('orcid') && $mastheadUser['user']->hasVerifiedOrcid()}
									<span class="orcid">
										<a href="{$mastheadUser['user']->getData('orcid')|escape}" target="_blank" aria-label="{translate key="common.editorialHistory.page.orcidLink" name=$mastheadUser['user']->getFullName()|escape}">
											{$orcidIcon}
										</a>
									</span>
								{/if}
							</span>
							{if !empty($mastheadUser['user']->getLocalizedData('affiliation'))}
								<span class="affiliation">{$mastheadUser['user']->getLocalizedData('affiliation')|escape}</span>
							{/if}
						{/strip}
					</li>
				{/foreach}
				</ul>
			{/if}
		{/foreach}
	{/if}

	{if !$staticEditorialTeamStaticFirst}
		<div class="editorial_team_content">
			{$staticEditorialTeamContent}
			{include file="frontend/components/editLink.tpl" page="management" op="settings" path="context" anchor="masthead" sectionTitleKey="common.editorialMasthead"}
		</div>
	{/if}

	{if $staticEditorialTeamShowHistoryLink}
		<p>
			{capture assign=editorialHistoryUrl}{url page="about" op="editorialHistory" router=\PKP\core\PKPApplication::ROUTE_PAGE}{/capture}
			{translate key="about.editorialMasthead.linkToEditorialHistory" url=$editorialHistoryUrl}
		</p>
	{/if}

	{if $staticEditorialTeamShowReviewers && $reviewers->count()}
		<hr>
		<h2>{translate key="common.editorialMasthead.peerReviewers"}</h2>
		<p>{translate key="common.editorialMasthead.peerReviewers.description" year=$previousYear}</p>
		<ul class="user_listing" role="list">
		{foreach from=$reviewers item="reviewer"}
			<li>
				{strip}
					<span class="name">
						{$reviewer->getFullName()|escape}
						{if $reviewer->getData('orcid') && $reviewer->getData('orcidAccessToken')}
							<span class="orcid">
								<a href="{$reviewer->getData('orcid')|escape}" target="_blank" aria-label="{translate key="common.editorialHistory.page.orcidLink" name=$reviewer->getFullName()|escape}">
									{$orcidIcon}
								</a>
							</span>
						{/if}
					</span>
					{if !empty($reviewer->getLocalizedData('affiliation'))}
						<span class="affiliation">{$reviewer->getLocalizedData('affiliation')|escape}</span>
					{/if}
				{/strip}
			</li>
		{/foreach}
		</ul>
	{/if}
</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
