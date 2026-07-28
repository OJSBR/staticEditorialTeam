<?php

/**
 * @file plugins/generic/staticEditorialTeam/StaticEditorialTeamSettingsForm.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StaticEditorialTeamSettingsForm
 *
 * @brief Settings form for the static Editorial Team page.
 */

namespace APP\plugins\generic\staticEditorialTeam;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class StaticEditorialTeamSettingsForm extends Form
{
    public function __construct(private StaticEditorialTeamPlugin $plugin, private int $contextId)
    {
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @copydoc Form::initData()
     */
    public function initData(): void
    {
        $this->setData('mode', $this->plugin->getMode($this->contextId));
        $this->setData('showReviewers', $this->plugin->getShowReviewers($this->contextId));
        $this->setData('showHistoryLink', $this->plugin->getShowHistoryLink($this->contextId));
        $this->setData('hideOnHistoryPage', $this->plugin->getHideOnHistoryPage($this->contextId));
        $this->setData('relabelField', $this->plugin->getRelabelField($this->contextId));
        parent::initData();
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData(): void
    {
        $this->readUserVars(['mode', 'showReviewers', 'showHistoryLink', 'hideOnHistoryPage', 'relabelField']);
        parent::readInputData();
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false): string
    {
        $templateMgr = TemplateManager::getManager($request);
        $context = $request->getContext();

        // Warn when the journal's text field is still empty.
        $content = (array) $context->getData(StaticEditorialTeamPlugin::CONTENT_FIELD);
        $hasContent = (bool) array_filter(array_map(fn ($value) => trim(strip_tags((string) $value)), $content));

        $dispatcher = $request->getDispatcher();
        $settingsUrl = $dispatcher->url(
            $request,
            Application::ROUTE_PAGE,
            $context->getPath(),
            'management',
            'settings',
            ['context'],
            null,
            'masthead'
        );

        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'hasContent' => $hasContent,
            'settingsUrl' => $settingsUrl,
            'modeStaticOnly' => StaticEditorialTeamPlugin::MODE_STATIC_ONLY,
            'modeStaticFirst' => StaticEditorialTeamPlugin::MODE_STATIC_FIRST,
            'modeStaticLast' => StaticEditorialTeamPlugin::MODE_STATIC_LAST,
        ]);
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $mode = $this->getData('mode');
        if (!in_array($mode, [
            StaticEditorialTeamPlugin::MODE_STATIC_ONLY,
            StaticEditorialTeamPlugin::MODE_STATIC_FIRST,
            StaticEditorialTeamPlugin::MODE_STATIC_LAST,
        ], true)) {
            $mode = StaticEditorialTeamPlugin::MODE_STATIC_ONLY;
        }

        $this->plugin->updateSetting($this->contextId, 'mode', $mode, 'string');
        $this->plugin->updateSetting($this->contextId, 'showReviewers', (bool) $this->getData('showReviewers'), 'bool');
        $this->plugin->updateSetting($this->contextId, 'showHistoryLink', (bool) $this->getData('showHistoryLink'), 'bool');
        $this->plugin->updateSetting($this->contextId, 'hideOnHistoryPage', (bool) $this->getData('hideOnHistoryPage'), 'bool');
        $this->plugin->updateSetting($this->contextId, 'relabelField', (bool) $this->getData('relabelField'), 'bool');

        parent::execute(...$functionArgs);
    }
}
