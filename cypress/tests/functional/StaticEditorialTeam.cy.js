/**
 * @file cypress/tests/functional/StaticEditorialTeam.cy.js
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Functional tests: the Editorial Team page a reader sees.
 *
 * Parameters (--env): contextPath, adminUser, adminPassword (captcha on login
 * must be off for the run). The defaults match the data set of PKP's continuous
 * integration. The first test enables the plugin when it is off. The journal's
 * text and the plugin settings are put back as they were after the run.
 */

describe('Static Editorial Team plugin', function() {
	const contextPath = Cypress.env('contextPath') || 'publicknowledge';
	const adminUser = Cypress.env('adminUser') || 'admin';
	const adminPassword = Cypress.env('adminPassword') || 'admin';

	const row = 'staticeditorialteamplugin';
	const form = '#staticEditorialTeamSettingsForm';
	const marker = 'OJSBR static editorial team ' + Date.now();
	let originalText = null;
	let originalSettings = null;

	// ---- OJSBR spec helpers (padrão v2): work on OJS/OMP 3.3, 3.4 and 3.5 and in PKP's CI ----

	const pageUrl = (path) => '/index.php/' + contextPath + (path ? '/' + path : '');

	// Same as PKP's cy.waitJQuery(), which the support files of OJS 3.3 test sites may lack.
	// The Plugins tab can keep requests open for a while (the plugin gallery), hence the timeout.
	const waitJQuery = () => cy.window().its('jQuery.active', {timeout: 60000}).should('eq', 0);

	// Requests carry the browser's User-Agent: OJS 3.3 drops a session whose agent changes.
	const request = (options) => cy.window({log: false}).then((win) => cy.request(Object.assign(
		typeof options === 'string' ? {url: options} : options,
		{headers: Object.assign({'User-Agent': win.navigator.userAgent}, (typeof options === 'string' ? {} : options.headers) || {})}
	)));

	// Signs in through requests (the login page can re-render while it is typed into), then
	// falls back to the form when the session did not stick (OJS 3.3 cookie handling).
	const login = (username, password) => {
		cy.clearCookies();
		request(pageUrl('login')).then((response) => {
			const token = /name="csrfToken" value="([^"]+)"/.exec(response.body)[1];
			// The form posts to the URL with the language: a redirect would turn the POST into a GET.
			const action = /<form[^>]*id="login"[^>]*action="([^"]+)"/.exec(response.body)[1];
			request({method: 'POST', url: action, form: true, body: {csrfToken: token, username: username, password: password}, log: false});
		});
		cy.visit(pageUrl('submissions') + '?reload=' + Date.now());
		cy.get('body').then(($body) => {
			if ($body.find('form#login').length) {
				cy.get('form#login input[name="username"]').type(username, {delay: 0});
				cy.get('form#login input[name="password"]').type(password, {delay: 0, log: false});
				cy.get('form#login').submit();
				cy.get('form#login', {timeout: 30000}).should('not.exist');
			}
		});
	};

	// REST API calls made from the page itself, so they carry the browser's own session.
	const api = (path, options = {}) => cy.window({log: false}).then((win) => cy.wrap(
		win.fetch(path, Object.assign({credentials: 'same-origin'}, options)).then((response) => {
			if (!response.ok) {
				return response.text().then((text) => {
					throw new Error(path + ' answered ' + response.status + ': ' + text.slice(0, 300));
				});
			}
			return response.json();
		}),
		{log: false, timeout: 30000}
	));

	// The website settings page on its Plugins tab (a new query string forces a load). Load it
	// once per test: loading it again while its plugin gallery request is pending stalls the
	// web server of PKP's CI; API calls and settings modals work on the page already open.
	const openPluginsTab = () => {
		cy.visit(pageUrl('management/settings/website') + '?reload=' + Date.now() + '#plugins');
		cy.get('button[id="plugins-button"]', {timeout: 60000}).click();
		cy.get('button[id="plugins-button"]').should('have.attr', 'aria-selected', 'true');
		waitJQuery();
	};

	// Enables the plugin in the grid when it is off (never turns it off).
	const enablePlugin = (rowName) => {
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]', {timeout: 30000}).then(($checkbox) => {
			if (!$checkbox.is(':checked')) {
				cy.wrap($checkbox).click();
				waitJQuery();
			}
		});
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]').should('be.checked');
	};

	// Opens the settings modal from the grid, without reloading the page: a reload right
	// after saving can stall the web server of PKP's CI. The form is fetched each time.
	const openPluginSettings = (rowName, formSelector) => {
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]', {timeout: 30000}).then(($link) => {
			if (!$link.is(':visible')) {
				cy.get('tr[id$="-row-' + rowName + '"] a.show_extras').first().click();
			}
		});
		// The grid may still be animating the extras row: the link is clicked once it exists.
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]').first().click({force: true});
		waitJQuery();
		cy.window().should((win) => {
			expect(win.jQuery(formSelector).data('pkp.handler')).to.exist;
		});
	};

	// ---- end of helpers ----

	// The journal of contextPath with all its settings, and the CSRF token of the page.
	const withJournal = (callback) => {
		cy.window({timeout: 60000}).its('pkp.currentUser.csrfToken').then((token) => {
			api('/index.php/index/api/v1/contexts?count=100').then((list) => {
				const journal = list.items.find((item) => item.urlPath === contextPath);
				api(pageUrl('api/v1/contexts/' + journal.id)).then((details) => callback(details, token));
			});
		});
	};

	const saveText = (journal, token, text) => api(pageUrl('api/v1/contexts/' + journal.id), {
		method: 'PUT',
		headers: {'Content-Type': 'application/json', 'X-Csrf-Token': token},
		body: JSON.stringify({editorialHistory: text}),
	});

	// The settings of the form as it is.
	const readSettings = () => cy.get(form).then(($form) => ({
		mode: $form.find('input[name="mode"]:checked').val(),
		checks: ['showReviewers', 'showHistoryLink', 'hideOnHistoryPage', 'relabelField']
			.reduce((all, name) => Object.assign(all, {[name]: $form.find('input[name="' + name + '"]').is(':checked')}), {}),
	}));

	const saveSettings = (settings) => {
		cy.get(form + ' input[name="mode"][value="' + settings.mode + '"]').check({force: true});
		Object.keys(settings.checks).forEach((name) => {
			cy.get(form + ' input[name="' + name + '"]')[settings.checks[name] ? 'check' : 'uncheck']({force: true});
		});
		cy.get(form + ' button[id^="submitFormButton"]').click();
		waitJQuery();
		cy.get(form).should('not.exist');
	};

	// Saves the settings with a request to the form's own URL and token: the cleanup does not
	// depend on the modal, which a failed test may have left in any state.
	const postSettings = (settings) => cy.window({timeout: 60000}).its('pkp.currentUser.csrfToken').then((token) => {
		const body = {csrfToken: token, mode: settings.mode};
		Object.keys(settings.checks).filter((name) => settings.checks[name]).forEach((name) => {
			body[name] = '1';
		});
		request({
			method: 'POST',
			url: pageUrl('$$$call$$$/grid/settings/plugins/settings-plugin-grid/manage') + '?verb=settings&plugin=' + row + '&category=generic&save=1',
			form: true,
			body: body,
		}).its('body.status').should('eq', true);
	});

	const visitPage = (op) => {
		cy.clearCookies();
		cy.visit(pageUrl('about/' + op) + '?reload=' + Date.now(), {headers: {Cookie: 'OJSSID=cypress' + Date.now()}});
	};

	it('Enables the plugin', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		enablePlugin(row);
	});

	it('Shows the journal text, filtered, instead of the listing, and not on the history page', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		openPluginSettings(row, form);
		readSettings().then((settings) => {
			if (originalSettings === null) {
				originalSettings = settings;
			}
		});
		saveSettings({mode: 'staticOnly', checks: {showReviewers: false, showHistoryLink: false, hideOnHistoryPage: true, relabelField: true}});

		withJournal((journal, token) => {
			if (originalText === null) {
				originalText = journal.editorialHistory || {};
			}
			const text = {};
			journal.supportedFormLocales.forEach((locale) => {
				text[locale] = '<p>' + marker + '</p><script>window.ojsbrStaticTeamXss = true;</script>';
			});
			saveText(journal, token, text);
		});

		visitPage('editorialMasthead');
		cy.get('.editorial_team_content').should('contain', marker);
		cy.get('.editorial_team_content script').should('have.length', 0);
		cy.window().its('ojsbrStaticTeamXss').should('be.undefined');
		cy.get('.page_editorial_team h2').should('have.length', 0);

		visitPage('editorialHistory');
		cy.get('body').should('not.contain', marker);
	});

	it('Puts the text after the listing', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		openPluginSettings(row, form);
		saveSettings({mode: 'staticLast', checks: {showReviewers: false, showHistoryLink: true, hideOnHistoryPage: false, relabelField: true}});

		visitPage('editorialMasthead');
		cy.get('.page_editorial_team > *').then(($children) => {
			const content = $children.index($children.filter('.editorial_team_content'));
			const firstRole = $children.index($children.filter('h2').first());
			expect(content, 'the text is on the page').to.be.greaterThan(-1);
			if (firstRole > -1) {
				expect(content, 'the text comes after the listing').to.be.greaterThan(firstRole);
			}
		});
		cy.get('.page_editorial_team a[href*="editorialHistory"]').should('exist');

		visitPage('editorialHistory');
		cy.get('body').should('contain', marker);
	});

	// Puts the journal text and the plugin settings back, also when a test failed.
	after(function() {
		if (originalText === null && originalSettings === null) {
			return;
		}
		login(adminUser, adminPassword);
		openPluginsTab();
		if (originalSettings !== null) {
			postSettings(originalSettings);
		}
		if (originalText !== null) {
			withJournal((journal, token) => saveText(journal, token, originalText));
		}
	});
});
