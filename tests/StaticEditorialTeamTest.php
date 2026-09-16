<?php

/**
 * @file plugins/generic/staticEditorialTeam/tests/StaticEditorialTeamTest.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StaticEditorialTeamTest
 *
 * @brief Settings defaults, the template swap, the relabelled field, and the
 *        copies of the core templates against the installed PKP.
 */

namespace APP\plugins\generic\staticEditorialTeam\tests;

use APP\core\Application;
use PKP\context\Context;
use APP\plugins\generic\staticEditorialTeam\StaticEditorialTeamPlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\tests\PKPTestCase;

#[CoversClass(StaticEditorialTeamPlugin::class)]
class StaticEditorialTeamTest extends PKPTestCase
{
    /**
     * A plugin whose settings and journal are the given ones.
     */
    protected function plugin(array $settings, ?Context $journal = null): StaticEditorialTeamPlugin
    {
        return new class ($settings, $journal) extends StaticEditorialTeamPlugin {
            public function __construct(private array $settings, private ?Context $journal)
            {
                parent::__construct();
            }

            public function getSetting($contextId, $name)
            {
                return $this->settings[$name] ?? null;
            }

            protected function currentContext(): ?\PKP\context\Context
            {
                return $this->journal;
            }

            // Localized data reads the request's locale, which the command line does not have.
            protected function contentFor(\PKP\context\Context $context): string
            {
                return (string) $context->getData(StaticEditorialTeamPlugin::CONTENT_FIELD, 'en');
            }

            public function getTemplateResource($template = null, $inCore = false)
            {
                return 'plugin:' . $template;
            }
        };
    }

    protected function journal(): Context
    {
        // The journal or press of the installation the suite runs on.
        $journal = Application::getContextDAO()->newDataObject();
        $journal->setId(3);
        $journal->setData(StaticEditorialTeamPlugin::CONTENT_FIELD, '<p>Board</p>', 'en');
        return $journal;
    }

    /**
     * A template manager that keeps what is assigned.
     */
    protected function templateManager(): object
    {
        return new class () {
            public array $vars = [];

            public function assign($vars)
            {
                $this->vars = array_merge($this->vars, $vars);
            }
        };
    }

    public function testUnknownOrMissingSettingsFallBackToTheSafeDefaults(): void
    {
        $plugin = $this->plugin(['mode' => 'somethingElse']);
        $this->assertSame(StaticEditorialTeamPlugin::MODE_STATIC_ONLY, $plugin->getMode(1));
        $this->assertTrue($plugin->getHideOnHistoryPage(1));
        $this->assertTrue($plugin->getRelabelField(1));
        $this->assertFalse($plugin->getShowReviewers(1));
        $this->assertFalse($plugin->getShowHistoryLink(1));

        $plugin = $this->plugin(['mode' => StaticEditorialTeamPlugin::MODE_STATIC_LAST, 'hideOnHistoryPage' => false, 'relabelField' => false]);
        $this->assertSame(StaticEditorialTeamPlugin::MODE_STATIC_LAST, $plugin->getMode(1));
        $this->assertFalse($plugin->getHideOnHistoryPage(1));
        $this->assertFalse($plugin->getRelabelField(1));
    }

    public function testTheEditorialTeamPageGetsThePluginTemplateAndItsOptions(): void
    {
        $plugin = $this->plugin(['mode' => StaticEditorialTeamPlugin::MODE_STATIC_LAST, 'showReviewers' => true], $this->journal());
        $templateMgr = $this->templateManager();
        $template = StaticEditorialTeamPlugin::MASTHEAD_TEMPLATE;
        $output = null;

        $plugin->handleTemplateDisplay('TemplateManager::display', [$templateMgr, &$template, &$output]);

        $this->assertSame('plugin:frontend/editorialTeam.tpl', $template);
        $this->assertTrue($templateMgr->vars['staticEditorialTeamShowRoles']);
        $this->assertFalse($templateMgr->vars['staticEditorialTeamStaticFirst']);
        $this->assertTrue($templateMgr->vars['staticEditorialTeamShowReviewers']);
        $this->assertFalse($templateMgr->vars['staticEditorialTeamShowHistoryLink']);
    }

    public function testTheHistoryPageDropsTheTextOnlyWhenAsked(): void
    {
        $template = StaticEditorialTeamPlugin::HISTORY_TEMPLATE;
        $output = null;
        $this->plugin([], $this->journal())->handleTemplateDisplay('TemplateManager::display', [$this->templateManager(), &$template, &$output]);
        $this->assertSame('plugin:frontend/editorialHistory.tpl', $template);

        $template = StaticEditorialTeamPlugin::HISTORY_TEMPLATE;
        $this->plugin(['hideOnHistoryPage' => false], $this->journal())->handleTemplateDisplay('TemplateManager::display', [$this->templateManager(), &$template, &$output]);
        $this->assertSame(StaticEditorialTeamPlugin::HISTORY_TEMPLATE, $template);
    }

    public function testOtherTemplatesAndPagesWithoutAJournalAreLeftAlone(): void
    {
        $output = null;
        $template = 'frontend/pages/about.tpl';
        $this->plugin([], $this->journal())->handleTemplateDisplay('TemplateManager::display', [$this->templateManager(), &$template, &$output]);
        $this->assertSame('frontend/pages/about.tpl', $template);

        $template = StaticEditorialTeamPlugin::MASTHEAD_TEMPLATE;
        $this->plugin([])->handleTemplateDisplay('TemplateManager::display', [$this->templateManager(), &$template, &$output]);
        $this->assertSame(StaticEditorialTeamPlugin::MASTHEAD_TEMPLATE, $template);
    }

    public function testOnlyTheTextFieldOfTheMastheadFormIsRelabelled(): void
    {
        $config = ['fields' => [['name' => 'editorialTeam', 'label' => 'x'], ['name' => StaticEditorialTeamPlugin::CONTENT_FIELD, 'label' => 'History']]];
        $form = (object) ['id' => StaticEditorialTeamPlugin::MASTHEAD_FORM_ID];

        $this->plugin([], $this->journal())->relabelMastheadField('Form::config::after', [&$config, $form]);
        $this->assertSame('x', $config['fields'][0]['label']);
        $this->assertSame(__('plugins.generic.staticEditorialTeam.fieldLabel'), $config['fields'][1]['label']);

        $other = ['fields' => [['name' => StaticEditorialTeamPlugin::CONTENT_FIELD, 'label' => 'History']]];
        $this->plugin([], $this->journal())->relabelMastheadField('Form::config::after', [&$other, (object) ['id' => 'contact']]);
        $this->assertSame('History', $other['fields'][0]['label']);
    }

    public function testTheCopiesKeepTheListingsOfTheInstalledCoreTemplates(): void
    {
        // The pages are copies of the core ones: after an OJS update they must still print the
        // same listing, or the copy has to be brought up to date.
        $root = dirname(__DIR__, 4);
        $normalize = fn (string $tpl) => preg_replace('/\s+/', ' ', $tpl);
        $core = $normalize((string) file_get_contents($root . '/lib/pkp/templates/' . StaticEditorialTeamPlugin::MASTHEAD_TEMPLATE));
        $copy = $normalize((string) file_get_contents(dirname(__DIR__) . '/templates/frontend/editorialTeam.tpl'));
        preg_match('#\{foreach from=\$mastheadRoles.*?</ul> \{/if\} \{/foreach\}#s', $core, $roles);
        preg_match('#<ul class="user_listing" role="list"> \{foreach from=\$reviewers.*?</ul>#s', $core, $reviewers);
        $this->assertNotEmpty($roles, 'The core masthead template changed its role listing.');
        $this->assertNotEmpty($reviewers, 'The core masthead template changed its reviewer listing.');
        $this->assertStringContainsString($roles[0], $copy);
        $this->assertStringContainsString($reviewers[0], $copy);

        $coreHistory = $normalize((string) file_get_contents($root . '/lib/pkp/templates/' . StaticEditorialTeamPlugin::HISTORY_TEMPLATE));
        $copyHistory = $normalize((string) file_get_contents(dirname(__DIR__) . '/templates/frontend/editorialHistory.tpl'));
        $this->assertSame(
            trim($normalize(str_replace('{$currentContext->getLocalizedData(\'editorialHistory\')}', '', preg_replace('#\{include file="frontend/components/editLink\.tpl"[^}]*\}#', '', substr($coreHistory, strpos($coreHistory, '<div class="page')))))),
            trim(substr($copyHistory, strpos($copyHistory, '<div class="page')))
        );
    }

    public function testTheJournalTextIsFilteredAsHtml(): void
    {
        $copy = (string) file_get_contents(dirname(__DIR__) . '/templates/frontend/editorialTeam.tpl');
        $this->assertSame(2, substr_count($copy, '{$staticEditorialTeamContent|strip_unsafe_html}'));
        $this->assertStringNotContainsString('{$staticEditorialTeamContent}', $copy);
    }
}
