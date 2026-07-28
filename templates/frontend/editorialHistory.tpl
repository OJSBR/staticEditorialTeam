{**
 * plugins/generic/staticEditorialTeam/templates/frontend/editorialHistory.tpl
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Copy of the OJS 3.5 Editorial History page WITHOUT the journal's
 *        free-text block — that content is published on the Editorial Team
 *        page instead, and repeating it here would duplicate the information.
 *}
{include file="frontend/components/header.tpl" pageTitle="common.editorialHistory"}

<div class="page page_masthead">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="common.editorialHistory"}

	<h1>{translate key="common.editorialHistory.page"}</h1>
	<p>{translate key="common.editorialHistory.page.description"}</p>
	{foreach from=$mastheadRoles item="mastheadRole"}
		{if array_key_exists($mastheadRole->id, $mastheadUsers)}
			<h2>{$mastheadRole->getLocalizedData('name')|escape}</h2>
			<ul class="user_listing" role="list">
				{foreach from=$mastheadUsers[$mastheadRole->id] item="mastheadUser"}
					<li>
						{strip}
						<span class="date_start">
							{foreach name="services" from=$mastheadUser['services'] item="service"}
								{translate key="common.fromUntil" from=$service['dateStart'] until=$service['dateEnd']}
								{if !$smarty.foreach.services.last}{translate key="common.commaListSeparator"}{/if}
							{/foreach}
						</span>
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

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
