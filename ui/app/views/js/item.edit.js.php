<?php
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * @var CView $this
 */

?><script>
(() => {
const CONFIRM_FORM_NAVIGATION = <?= json_encode(_('Any changes made in the current form will be lost.')) ?>;
const ERROR_XHR_SERVER = <?= json_encode(_('Unexpected server error.')) ?>;
const HOST_STATUS_MONITORED = <?= HOST_STATUS_MONITORED ?>;
const INTERFACE_TYPE_OPT = <?= INTERFACE_TYPE_OPT ?>;
const ITEM_DELAY_FLEXIBLE = <?= ITEM_DELAY_FLEXIBLE ?>;
const ITEM_DELAY_SCHEDULING = <?= ITEM_DELAY_SCHEDULING ?>;
const ITEM_STORAGE_OFF = <?= ITEM_STORAGE_OFF ?>;
const ITEM_TYPE_DEPENDENT = <?= ITEM_TYPE_DEPENDENT ?>;
const ITEM_TYPE_IPMI = <?= ITEM_TYPE_IPMI ?>;
const ITEM_TYPE_SIMPLE = <?= ITEM_TYPE_SIMPLE ?>;
const ITEM_TYPE_SSH = <?= ITEM_TYPE_SSH ?>;
const ITEM_TYPE_TELNET = <?= ITEM_TYPE_TELNET ?>;
const ITEM_TYPE_ZABBIX_ACTIVE = <?= ITEM_TYPE_ZABBIX_ACTIVE ?>;
const ITEM_VALUE_TYPE_BINARY = <?= ITEM_VALUE_TYPE_BINARY ?>;
const ZBX_STYLE_DISPLAY_NONE = <?= json_encode(ZBX_STYLE_DISPLAY_NONE) ?>;
const ZBX_STYLE_FIELD_LABEL_ASTERISK = <?= json_encode(ZBX_STYLE_FIELD_LABEL_ASTERISK) ?>;

window.item_edit_form = new class {

	init({
		field_switches, form_data, host, interface_types, testable_item_types, type_with_key_select,
		value_type_keys
	}) {
		this.form_data = form_data;
		this.host = host;
		this.testable_item_types = testable_item_types;
		this.interface_types = interface_types;
		this.optional_interfaces = [];
		this.type_interfaceids = {};
		this.value_type_keys = value_type_keys;
		this.type_with_key_select = type_with_key_select;

		for (const type in interface_types) {
			if (interface_types[type] == INTERFACE_TYPE_OPT) {
				this.optional_interfaces.push(parseInt(type, 10));
			}
		}

		for (const host_interface of Object.values(host.interfaces)) {
			if (host_interface.type in this.type_interfaceids) {
				this.type_interfaceids[host_interface.type].push(host_interface.interfaceid);
			}
			else {
				this.type_interfaceids[host_interface.type] = [host_interface.interfaceid];
			}
		}

		this.overlay = overlays_stack.end();
		this.dialogue = this.overlay.$dialogue[0];
		this.form = this.overlay.$dialogue.$body[0].querySelector('form');
		this.footer = this.overlay.$dialogue.$footer[0];

		this.initForm(field_switches);
		this.initEvents();
		this.updateFieldsVisibility();

		this.initial_form_fields = getFormFields(this.form);
	}

	initForm(field_switches) {
		new CViewSwitcher('allow_traps', 'change', field_switches.for_traps);
		new CViewSwitcher('authtype', 'change', field_switches.for_authtype);
		new CViewSwitcher('http_authtype', 'change', field_switches.for_http_auth_type);
		new CViewSwitcher('type', 'change', field_switches.for_type);
		new CViewSwitcher('value_type', 'change', field_switches.for_value_type);

		this.field = {
			history: this.form.querySelector('[name="history"]'),
			history_mode: this.form.querySelectorAll('[name="history_mode"]'),
			interfaceid: this.form.querySelector('[name="interfaceid"]'),
			key: this.form.querySelector('[name="key"]'),
			key_button: this.form.querySelector('[name="key"] ~ .js-select-key'),
			trends: this.form.querySelector('[name="trends"]'),
			trends_mode: this.form.querySelectorAll('[name="trends_mode"]'),
			type: this.form.querySelector('[name="type"]'),
			url: this.form.querySelector('[name="url"]'),
			username: this.form.querySelector('[name=username]'),
			value_type: this.form.querySelector('[name="value_type"]'),
			value_type_steps: this.form.querySelector('[name="value_type_steps"]'),
			ipmi_sensor: this.form.querySelector('[name="ipmi_sensor"]')
		};
		this.label = {
			interfaceid: this.form.querySelector('[for=interfaceid]'),
			value_type_hint: this.form.querySelector('#js-item-type-hint'),// remove id
			username: this.form.querySelector('[for=username]'),
			ipmi_sensor: this.form.querySelector('[for="ipmi_sensor"]'),
			history_hint: this.form.querySelector('#history_mode_hint'),// remove id [for="history"] > :has([data-hintbox])
			trends_hint: this.form.querySelector('#trends_mode_hint') // remove id
		};

		// TODO: move this code to tags.tab javascript file.
		if (jQuery('#tabs').tabs('option', 'active') == 1) {
			// Force dynamicRows event handlers initialization when 'Tags' tab is already active.
			jQuery(() => jQuery('#tabs').trigger('tabscreate.tags-tab', {panel: $('#tags-tab')}));
		}

		jQuery('#parameters-table').dynamicRows({
			template: '#parameter-row-tmpl',
			rows: this.form_data.parameters
		});
		jQuery('#query-fields-table').dynamicRows({
			sortable: true,
			template: '#query-field-row-tmpl',
			rows: this.form_data.query_fields
		});
		jQuery('#headers-table').dynamicRows({
			sortable: true,
			template: '#header-row-tmpl',
			rows: this.form_data.headers
		});
		jQuery('#delay-flex-table').dynamicRows({
			template: '#delay-flex-row-tmpl',
			rows: this.form_data.delay_flex
		});
		this.form.querySelectorAll('#delay-flex-table .form_row')?.forEach(row => {
			const flexible = row.querySelector('[name$="[type]"]:checked').value == ITEM_DELAY_FLEXIBLE;

			row.querySelector('[name$="[delay]"]').classList.toggle(ZBX_STYLE_DISPLAY_NONE, !flexible);
			row.querySelector('[name$="[period]"]').classList.toggle(ZBX_STYLE_DISPLAY_NONE, !flexible);
			row.querySelector('[name$="[schedule]"]').classList.toggle(ZBX_STYLE_DISPLAY_NONE, flexible);
		});
	}

	initEvents() {
		// Item tab events.
		this.field.key.addEventListener('help_items.paste', e => this.#keyChangeHandler(e));
		this.field.key.addEventListener('keyup', e => this.#keyChangeHandler(e));
		this.field.key_button?.addEventListener('click', e => this.#keySelectClickHandler(e));
		this.field.type.addEventListener('click', e => this.#typeChangeHandler(e));
		this.field.value_type.addEventListener('change', e => this.#valueTypeChangeHandler(e));
		this.form.addEventListener('click', e => {
			const target = e.target;

			switch (target.getAttribute('name')) {
				case 'history_mode':
				case 'trends_mode':
					this.updateFieldsVisibility();

					break;

				case 'parseurl':
					const url = parseUrlString(this.field.url.value);

					if (url === false) {
						return this.#showErrorDialog(target.getAttribute('error-message'), target);
					}

					if (url.pairs.length) {
						const dynamic_rows = jQuery('#query-fields-table').dynamicRows();

						dynamic_rows.addRows(url.pairs);
						dynamic_rows.removeRows(row => [].filter.call(
								row.querySelectorAll('[type="text"]'),
								input => input.value === ''
							).length == 2
						);
					}

					this.field.url.value = url.url;

					break;
			}

			if (target.matches('a') && target.closest('.js-parent-items')) {
				e.preventDefault();

				if (!this.#isFormModified() || window.confirm(CONFIRM_FORM_NAVIGATION)) {
					const data = new URLSearchParams(target.getAttribute('href').replace(/^.*\?/g, ''));
					this.#openRelatedItem(Object.fromEntries(data));
				}
			}
		});
		this.form.querySelector('#delay-flex-table').addEventListener('click', e => this.#intervalTypeChangeHandler(e));

		// Tags tab events.
		this.form.querySelectorAll('[name="show_inherited_tags"]')
			.forEach(o => o.addEventListener('click', e => this.#inheritedTagsChangeHandler(e)));

		// Preprocessing tab events.
		this.field.value_type_steps.addEventListener('change', e => this.#valueTypeChangeHandler(e));
	}

	clone({title, buttons}) {
		// TODO: bug, navigate to item prototype and click clone, title is set to "New item", should be "New item prototype".
		// Remove itemid to correctly render form when inherited tags changed.
		this.form.querySelector('[name="itemid"]').remove();
		this.overlay.setProperties({title, buttons});
		this.overlay.unsetLoading();
		this.overlay.recoverFocus();
	}

	create() {
		const fields = this.#getFormFields();
		const curl = new Curl('zabbix.php');

		curl.setArgument('action', 'item.create');
		this.#post(curl.getUrl(), fields);
	}

	update() {
		const fields = this.#getFormFields();
		const curl = new Curl('zabbix.php');

		curl.setArgument('action', 'item.update');
		this.#post(curl.getUrl(), fields);
	}

	test() {
		const indexes = [].map.call(
			this.form.querySelectorAll('z-select[name^="preprocessing"][name$="[type]"]'),
			type => type.getAttribute('name').match(/preprocessing\[(?<step>[\d]+)\]/).groups.step
		);

		this.overlay.unsetLoading();
		this.#updateActionButtons();
		// Method requires form name to be set to itemForm.
		openItemTestDialog(indexes, true, true, this.footer.querySelector('.js-test-item'), -2);
	}

	delete() {
		const fields = this.#getFormFields();
		const curl = new Curl('zabbix.php');

		curl.setArgument('action', 'item.delete');
		this.#post(curl.getUrl(), {...fields, itemids: [fields.itemid]});
	}

	clear() {
		const fields = this.#getFormFields();
		const curl = new Curl('zabbix.php');

		curl.setArgument('action', 'item.clear');
		this.#post(curl.getUrl(), {...fields, itemids: [fields.itemid]});
	}

	execute() {
		const fields = this.#getFormFields();
		const curl = new Curl('zabbix.php');

		curl.setArgument('action', 'item.execute');
		this.#post(curl.getUrl(), {...fields, itemids: [fields.itemid]});
	}

	updateFieldsVisibility() {
		const type = parseInt(this.field.type.value, 10);
		const key = this.field.key.value;
		const username_required = type == ITEM_TYPE_SSH || type == ITEM_TYPE_TELNET;
		const ipmi_sensor_required = type == ITEM_TYPE_IPMI && key !== 'ipmi.get';
		const interface_optional = this.optional_interfaces.indexOf(type) != -1;

		this.#updateActionButtons();
		this.#updateCustomIntervalVisibility();
		this.#updateHistoryModeVisibility();
		this.#updateTrendsModeVisibility();
		this.#updateValueTypeHintVisibility();
		this.#updateValueTypeOptionVisibility();
		this.field.key_button?.toggleAttribute('disabled', this.type_with_key_select.indexOf(type) == -1);
		this.field.username[username_required ? 'setAttribute' : 'removeAttribute']('aria-required', 'true');
		this.label.username.classList.toggle(ZBX_STYLE_FIELD_LABEL_ASTERISK, username_required);
		this.field.interfaceid?.toggleAttribute('aria-required', !interface_optional);
		this.label.interfaceid?.classList.toggle(ZBX_STYLE_FIELD_LABEL_ASTERISK, !interface_optional);
		this.field.ipmi_sensor[ipmi_sensor_required ? 'setAttribute' : 'removeAttribute']('aria-required', 'true');
		this.label.ipmi_sensor.classList.toggle(ZBX_STYLE_FIELD_LABEL_ASTERISK, ipmi_sensor_required);
		organizeInterfaces(this.type_interfaceids, this.interface_types, parseInt(this.field.type.value, 10));
	}

	#showErrorDialog(body, trigger_element) {
		overlayDialogue({
			title: t('Error'),
			class: 'modal-popup position-middle',
			content: jQuery('<span>').html(body),
			buttons: [{
				title: t('Ok'),
				class: 'btn-alt',
				focused: true,
				action: function() {}
			}]
		}, jQuery(trigger_element));
	}

	#getFormFields() {
		const fields = getFormFields(this.form);

		for (let key in fields) {
			if (typeof fields[key] === 'string' && key !== 'confirmation') {
				fields[key] = fields[key].trim();
			}
		}

		return fields;
	}

	#post(url, data) {
		fetch(url, {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(data)
		})
			.then((response) => response.json())
			.then((response) => {
				if ('error' in response) {
					throw {error: response.error};
				}
				overlayDialogueDestroy(this.overlay.dialogueid);

				this.dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response.success}));
			})
			.catch((exception) => {
				for (const element of this.form.parentNode.children) {
					if (element.matches('.msg-good, .msg-bad, .msg-warning')) {
						element.parentNode.removeChild(element);
					}
				}

				let title, messages;

				if (typeof exception === 'object' && 'error' in exception) {
					title = exception.error.title;
					messages = exception.error.messages;
				}
				else {
					messages = [ERROR_XHR_SERVER];
				}

				const message_box = makeMessageBox('bad', messages, title)[0];

				this.form.parentNode.insertBefore(message_box, this.form);
			})
			.finally(() => {
				this.overlay.unsetLoading();
			});
	}

	#isTestableItem() {
		const key = this.field.key.value;
		const type = parseInt(this.field.type.value, 10);

		return type == ITEM_TYPE_SIMPLE
			? key.substr(0, 7) !== 'vmware.' && key.substr(0, 8) !== 'icmpping'
			: this.testable_item_types.indexOf(type) != -1;
	}

	#isFormModified() {
		return JSON.stringify(this.initial_form_fields) !== JSON.stringify(getFormFields(this.form));
	}

	#updateActionButtons() {
		const is_testable = this.#isTestableItem();
		const is_executable = this.host.status == HOST_STATUS_MONITORED && is_testable;

		this.footer.querySelector('.js-test-item')?.toggleAttribute('disabled', !is_testable);
		this.footer.querySelector('.js-execute-item')?.toggleAttribute('disabled', !is_executable);
	}

	#updateCustomIntervalVisibility() {
		if (parseInt(this.field.type.value, 10) != ITEM_TYPE_ZABBIX_ACTIVE) {
			return;
		}

		const fields = ['delay', 'js-item-delay-label', 'js-item-delay-field', 'js-item-flex-intervals-label',
			'js-item-flex-intervals-field'
		];

		const action = (this.field.key.value.substr(0, 8) === 'mqtt.get') ? 'hideObj' : 'showObj';
		const switcher = globalAllObjForViewSwitcher['type'];

		fields.forEach(id => switcher[action]({id}));
	}

	#updateValueTypeHintVisibility() {
		const key = this.field.key.value;
		const value_type = this.field.value_type.value;
		const inferred_type = this.#getInferredValueType(key);

		this.label.value_type_hint
			.classList.toggle(ZBX_STYLE_DISPLAY_NONE, inferred_type === null || value_type == inferred_type);
	}

	#getInferredValueType(key) {
		const type = this.field.type.value;
		const search = key.split('[')[0].trim().toLowerCase();

		if (!(type in this.value_type_keys) || search === '') {
			return null;
		}

		if (search in this.value_type_keys[type]) {
			return this.value_type_keys[type][search];
		}

		const matches = Object.entries(this.value_type_keys[type])
							.filter(([key_name, value_type]) => key_name.startsWith(search));

		return (matches.length && matches.every(([_, value_type]) => value_type == matches[0][1]))
			? matches[0][1] : null;
	}

	#typeChangeHandler(e) {
		this.updateFieldsVisibility();
	}

	#intervalTypeChangeHandler(e) {
		const target = e.target;

		if (!target.matches('[name$="[type]"]')) {
			return;
		}

		const row = target.closest('.form_row');
		const flexible = target.value == ITEM_DELAY_FLEXIBLE;

		row.querySelector('[name$="[delay]"]').classList.toggle(ZBX_STYLE_DISPLAY_NONE, !flexible);
		row.querySelector('[name$="[period]"]').classList.toggle(ZBX_STYLE_DISPLAY_NONE, !flexible);
		row.querySelector('[name$="[schedule]"]').classList.toggle(ZBX_STYLE_DISPLAY_NONE, flexible);
	}

	#valueTypeChangeHandler(e) {
		this.field.value_type.value = e.target.value;
		this.field.value_type_steps.value = e.target.value;
		this.updateFieldsVisibility();
	}

	#keyChangeHandler(e) {
		const inferred_type = this.#getInferredValueType(this.field.key.value);

		if (inferred_type !== null) {
			this.field.value_type.value = inferred_type;
		}

		this.updateFieldsVisibility();
	}

	#keySelectClickHandler(e) {
		PopUp('popup.generic', {
			srctbl: 'help_items',
			srcfld1: 'key',
			dstfrm: this.form.getAttribute('name'),
			dstfld1: 'key',
			itemtype: this.field.type.value
		}, {dialogue_class: 'modal-popup-generic'});
	}

	#inheritedTagsChangeHandler(e) {
		const form_refresh = document.createElement('input');

		form_refresh.setAttribute('type', 'hidden');
		form_refresh.setAttribute('name', 'form_refresh');
		form_refresh.setAttribute('value', 1);
		this.form.append(form_refresh);

		reloadPopup(this.form, 'item.edit');
	}

	#updateHistoryModeVisibility() {
		const mode_field = [].filter.call(this.field.history_mode, e => e.matches(':checked')).pop();
		const disabled = mode_field.value == ITEM_STORAGE_OFF;

		this.field.history.toggleAttribute('disabled', disabled);
		this.field.history.classList.toggle(ZBX_STYLE_DISPLAY_NONE, disabled);
		this.label.history_hint?.classList.toggle(ZBX_STYLE_DISPLAY_NONE, disabled);
	}

	#updateTrendsModeVisibility() {
		const mode_field = [].filter.call(this.field.trends_mode, e => e.matches(':checked')).pop();
		const disabled = mode_field.value == ITEM_STORAGE_OFF;

		this.field.trends.toggleAttribute('disabled', disabled);
		this.field.trends.classList.toggle(ZBX_STYLE_DISPLAY_NONE, disabled);
		this.label.trends_hint?.classList.toggle(ZBX_STYLE_DISPLAY_NONE, disabled);
	}

	#updateValueTypeOptionVisibility() {
		const disable_binary = this.field.type.value != ITEM_TYPE_DEPENDENT;

		if (disable_binary && this.field.value_type.value == ITEM_VALUE_TYPE_BINARY) {
			const value = this.field.value_type.getOptions().find(o => o.value != ITEM_VALUE_TYPE_BINARY).value;

			this.field.value.value = value;
			this.field.value_type.value = value;
		}

		this.field.value_type.getOptionByValue(ITEM_VALUE_TYPE_BINARY).hidden = disable_binary;
		this.field.value_type_steps.getOptionByValue(ITEM_VALUE_TYPE_BINARY).hidden = disable_binary;
	}

	#openRelatedItem(parameters) {
		const dialogueid = this.dialogue.dataset.dialogueid;
		const dialogue_class = this.dialogue.getAttribute('class');

		PopUp(parameters.action, parameters, {dialogueid, dialogue_class});
	}
}
})();
