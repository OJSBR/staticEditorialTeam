<?php

/**
 * @file plugins/generic/staticEditorialTeam/StaticEditorialTeamPlugin.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StaticEditorialTeamPlugin
 *
 * @brief Restores the static Editorial Team page: the page shows the journal's
 *        free-text setting again (renamed to "Editorial History" in OJS 3.5)
 *        instead of the dynamic listing built from user roles.
 */

namespace APP\plugins\generic\staticEditorialTeam;

use APP\core\Application;
use APP\notification\NotificationManager;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class StaticEditorialTeamPlugin extends GenericPlugin
{
    /** Core template holding the dynamic editorial masthead listing. */
    public const MASTHEAD_TEMPLATE = 'frontend/pages/editorialMasthead.tpl';

    /** Core template of the editorial history page. */
    public const HISTORY_TEMPLATE = 'frontend/pages/editorialHistory.tpl';

    /** Identifier of the Settings > Journal > Editorial Team form. */
    public const MASTHEAD_FORM_ID = 'masthead';

    /** Context field holding the free text (named `editorialTeam` up to 3.4). */
    public const CONTENT_FIELD = 'editorialHistory';

    /** Static content only (the behaviour of earlier versions). */
    public const MODE_STATIC_ONLY = 'staticOnly';

    /** Static content above the dynamic listing. */
    public const MODE_STATIC_FIRST = 'staticFirst';

    /** Static content below the dynamic listing. */
    public const MODE_STATIC_LAST = 'staticLast';

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }
        if (Application::isUnderMaintenance() || !$this->getEnabled($mainContextId)) {
            return true;
        }

        // Swap the front-end page templates before they are rendered.
        Hook::add('TemplateManager::display', $this->handleTemplateDisplay(...));

        // Relabel the settings field so it states where the text is published.
        Hook::add('Form::config::after', $this->relabelMastheadField(...));

        return true;
    }

    /**
     * Hook TemplateManager::display — replaces the core template with the
     * plugin's own. Only the template name is changed (`$args[1]` is passed by
     * reference), so headers, the session cookie and the compile id are still
     * handled by the TemplateManager as usual.
     *
     * @param array $args [$templateMgr, &$template, &$output]
     */
    public function handleTemplateDisplay(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if (!is_string($template)) {
            return Hook::CONTINUE;
        }
        if ($template !== self::MASTHEAD_TEMPLATE && $template !== self::HISTORY_TEMPLATE) {
            return Hook::CONTINUE;
        }

        $context = Application::get()->getRequest()->getContext();
        if (!$context) {
            return Hook::CONTINUE;
        }
        $contextId = $context->getId();

        if ($template === self::HISTORY_TEMPLATE) {
            // The text moved to the Editorial Team page; do not repeat it here.
            if ($this->getHideOnHistoryPage($contextId)) {
                $args[1] = $this->getTemplateResource('frontend/editorialHistory.tpl');
            }
            return Hook::CONTINUE;
        }

        $mode = $this->getMode($contextId);
        $templateMgr->assign([
            'staticEditorialTeamContent' => $context->getLocalizedData(self::CONTENT_FIELD),
            'staticEditorialTeamMode' => $mode,
            'staticEditorialTeamShowRoles' => $mode !== self::MODE_STATIC_ONLY,
            'staticEditorialTeamStaticFirst' => $mode !== self::MODE_STATIC_LAST,
            'staticEditorialTeamShowReviewers' => $this->getShowReviewers($contextId),
            'staticEditorialTeamShowHistoryLink' => $this->getShowHistoryLink($contextId),
        ]);
        $args[1] = $this->getTemplateResource('frontend/editorialTeam.tpl');

        return Hook::CONTINUE;
    }

    /**
     * Hook Form::config::after — relabels the `editorialHistory` field in the
     * Settings > Journal > Editorial Team form, making it clear that the
     * content is published on the Editorial Team page again.
     *
     * @param array $args [&$config, $form]
     */
    public function relabelMastheadField(string $hookName, array $args): bool
    {
        $config = &$args[0];
        $form = $args[1];

        if (!isset($form->id) || $form->id !== self::MASTHEAD_FORM_ID) {
            return Hook::CONTINUE;
        }

        $context = Application::get()->getRequest()->getContext();
        if (!$context || !$this->getRelabelField($context->getId())) {
            return Hook::CONTINUE;
        }
        if (empty($config['fields']) || !is_array($config['fields'])) {
            return Hook::CONTINUE;
        }

        foreach ($config['fields'] as $index => $field) {
            $name = is_array($field) ? ($field['name'] ?? null) : ($field->name ?? null);
            if ($name !== self::CONTENT_FIELD) {
                continue;
            }
            $label = __('plugins.generic.staticEditorialTeam.fieldLabel');
            $description = __('plugins.generic.staticEditorialTeam.fieldDescription');
            if (is_array($field)) {
                $config['fields'][$index]['label'] = $label;
                $config['fields'][$index]['description'] = $description;
            } else {
                $config['fields'][$index]->label = $label;
                $config['fields'][$index]->description = $description;
            }
            break;
        }

        return Hook::CONTINUE;
    }

    /**
     * Display mode configured for the context.
     */
    public function getMode(int $contextId): string
    {
        $mode = $this->getSetting($contextId, 'mode');
        return in_array($mode, [self::MODE_STATIC_ONLY, self::MODE_STATIC_FIRST, self::MODE_STATIC_LAST], true)
            ? $mode
            : self::MODE_STATIC_ONLY;
    }

    /**
     * Whether to list last year's peer reviewers on the Editorial Team page.
     */
    public function getShowReviewers(int $contextId): bool
    {
        return (bool) $this->getSetting($contextId, 'showReviewers');
    }

    /**
     * Whether to display the link to the Editorial History page.
     */
    public function getShowHistoryLink(int $contextId): bool
    {
        return (bool) $this->getSetting($contextId, 'showHistoryLink');
    }

    /**
     * Whether to hide the static text on the Editorial History page (default: yes).
     */
    public function getHideOnHistoryPage(int $contextId): bool
    {
        $value = $this->getSetting($contextId, 'hideOnHistoryPage');
        return $value === null ? true : (bool) $value;
    }

    /**
     * Whether to relabel the field in the journal settings form (default: yes).
     */
    public function getRelabelField(int $contextId): bool
    {
        $value = $this->getSetting($contextId, 'relabelField');
        return $value === null ? true : (bool) $value;
    }

    /**
     * @copydoc Plugin::getContextSpecificPluginSettingsFile()
     */
    public function getContextSpecificPluginSettingsFile(): string
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $verb): array
    {
        $actions = parent::getActions($request, $verb);
        if (!$this->getEnabled()) {
            return $actions;
        }
        $router = $request->getRouter();
        $url = $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']);
        array_unshift($actions, new LinkAction('settings', new AjaxModal($url, $this->getDisplayName()), __('manager.plugins.settings')));
        return $actions;
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request): JSONMessage
    {
        if ($request->getUserVar('verb') !== 'settings') {
            return parent::manage($args, $request);
        }

        $form = new StaticEditorialTeamSettingsForm($this, $request->getContext()->getId());
        if (!$request->getUserVar('save')) {
            $form->initData();
            return new JSONMessage(true, $form->fetch($request));
        }

        $form->readInputData();
        if (!$form->validate()) {
            return new JSONMessage(true, $form->fetch($request));
        }

        $form->execute();
        $notificationManager = new NotificationManager();
        $notificationManager->createTrivialNotification($request->getUser()->getId());
        return new JSONMessage(true);
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.staticEditorialTeam.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.staticEditorialTeam.description');
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\staticEditorialTeam\StaticEditorialTeamPlugin', '\StaticEditorialTeamPlugin');
}
