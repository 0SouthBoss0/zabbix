<?php declare(strict_types = 0);
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

$formgrid = (new CFormGrid())
	->addItem($data['parent_items']
		? [
			new CLabel(_('Parent items')),
			(new CFormField($data['parent_items']))->addClass('js-parent-items')
		]
		: null
	)
	->addItem($data['discovered'] ? [
		new CLabel(_('Discovered by')),
		(new CFormField(
			(new CLink($data['discovery_rule']['name']))
				->setAttribute('data-action', 'item.prototype.edit')
				->setAttribute('data-parent_discoveryid', $data['discovery_rule']['itemid'])
				->setAttribute('data-itemid', $data['discovery_itemid'])
				->setAttribute('data-context', $data['form']['context'])
		))->addClass('js-parent-items')
	] : null)
	->addItem([
		(new CLabel(_('Name'), 'name'))->setAsteriskMark(),
		new CFormField(
			(new CTextBox('name', $data['form']['name'], $data['readonly'], DB::getFieldLength('items', 'name')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired()
				->setAttribute('autofocus', 'autofocus')
		)
	])
	->addItem([
		new CLabel(_('Type'), 'label-type'),
		new CFormField(
			(new CSelect('type'))
				->setId('type')
				->setFocusableElementId('label-type')
				->setValue($data['form']['type'])
				->addOptions(CSelect::createOptionsFromArray($data['types']))
				->setReadonly($data['readonly'])
		)
	])
	->addItem([
		(new CLabel(_('Key'), 'key'))->setAsteriskMark(),
		(new CFormField([
			(new CTextBox('key', $data['form']['key'], $data['readonly'], DB::getFieldLength('items', 'key_')))
				->setAriaRequired()
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
			$data['readonly']
				? null
				: [
					(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
					(new CSimpleButton(_('Select')))
						->addClass('js-select-key')
						->addClass(ZBX_STYLE_BTN_GREY)
						->setEnabled(in_array($data['form']['type'], $data['type_with_key_select']))
			]
		]))
	])
	->addItem([
		new CLabel([
			_('Type of information'),
			(new CSpan(makeWarningIcon(_('This type of information may not match the key.'))))
				->addClass('js-hint')
				->addClass(ZBX_STYLE_DISPLAY_NONE)
			], 'label-value-type'),
		new CFormField(
			(new CSelect('value_type'))
				->setFocusableElementId('label-value-type')
				->setId('value_type')
				->setValue($data['form']['value_type'])
				->addOptions(CSelect::createOptionsFromArray($data['value_types']))
				->setReadonly($data['readonly'])
		)
	])
	->addItem([
		(new CLabel(_('URL'), 'url'))
			->setAsteriskMark()
			->setId('js-item-url-label'),
		(new CFormField([
			(new CTextBox('url', $data['form']['url'], $data['readonly'], DB::getFieldLength('items', 'url')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired(),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			(new CSimpleButton(_('Parse')))
				->addClass(ZBX_STYLE_BTN_GREY)
				->setAttribute('name', 'parseurl')
				->setAttribute('error-message', _('Failed to parse URL.').BR().BR()._('URL is not properly encoded.'))
				->setEnabled(!$data['readonly'])
		]))->setId('js-item-url-field')
	])
	->addItem([
		(new CLabel(_('Query fields')))->setId('js-item-query-fields-label'),
		(new CFormField(
			(new CDiv([
				(new CTable())
					->setId('query-fields-table')
					->setHeader(['', _('Name'), '', _('Value'), ''])
					->setFooter((new CCol(
						(new CButtonLink(_('Add')))
							->addClass('element-table-add')
							->setEnabled(!$data['readonly'])
						))->setColSpan(5)
					),
				new CTemplateTag('query-field-row-tmpl', (new CRow([
						(new CCol([
							(new CDiv())->addClass(ZBX_STYLE_DRAG_ICON),
							new CVar('query_fields[sortorder][]', '#{rowNum}')
						]))->addClass(ZBX_STYLE_TD_DRAG_ICON),
						(new CTextBox('query_fields[name][#{rowNum}]', '#{name}', $data['readonly']))
							->setAttribute('placeholder', _('name'))
							->setWidth(ZBX_TEXTAREA_HTTP_PAIR_NAME_WIDTH),
						RARR(),
						(new CTextBox('query_fields[value][#{rowNum}]', '#{value}', $data['readonly']))
							->setAttribute('placeholder', _('value'))
							->setWidth(ZBX_TEXTAREA_HTTP_PAIR_VALUE_WIDTH),
						(new CButtonLink(_('Remove')))
							->addClass('element-table-remove')
							->setEnabled(!$data['readonly'])
					]))->addClass('form_row')
				)
			]))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH . 'px;')
		))->setId('js-item-query-fields-field')
	])
	->addItem([
		(new CLabel(_('Parameters'), 'parameters-table'))->setId('js-item-parameters-label'),
		(new CFormField(
			(new CDiv([
				(new CTable())
					->setId('parameters-table')
					->setHeader([
						(new CColHeader(_('Name')))->setWidth('50%'),
						(new CColHeader(_('Value')))->setWidth('50%'),
						_('Action')
					])
					->setFooter((new CCol(
						(new CButtonLink(_('Add')))
							->addClass('element-table-add')
							->setEnabled(!$data['readonly'])
						))->setColSpan(3)
					),
				new CTemplateTag('parameter-row-tmpl', (new CRow([
						(new CTextBox('parameters[name][#{rowNum}]', '#{name}', $data['readonly'],
							DB::getFieldLength('item_parameter', 'name')
						))
							->setAttribute('style', 'width: 100%;')
							->removeId(),
						(new CTextBox('parameters[value][#{rowNum}]', '#{value}', $data['readonly'],
							DB::getFieldLength('item_parameter', 'value')
						))
							->setAttribute('style', 'width: 100%;')
							->removeId(),
						(new CButtonLink(_('Remove')))
							->addClass('element-table-remove')
							->setEnabled(!$data['readonly'])
					]))->addClass('form_row')
				)
			]))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_STANDARD_WIDTH.'px;')
		))->setId('js-item-parameters-field')
	])
	->addItem([
		(new CLabel(_('Script'), 'script'))
			->setAsteriskMark()
			->setId('js-item-script-label'),
		(new CFormField(
			(new CMultilineInput('script', $data['form']['params'], [
				'title' => _('JavaScript'),
				'placeholder' => _('script'),
				'placeholder_textarea' => 'return value',
				'grow' => 'auto',
				'rows' => 0,
				'maxlength' => DB::getFieldLength('items', 'params'),
				'readonly' => $data['readonly']
			]))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired()
		))->setId('js-item-script-field')
	])
	->addItem([
		(new CLabel(_('Request type'), 'label-request-method'))->setId('js-item-request-method-label'),
		(new CFormField(
			(new CSelect('request_method'))
				->setId('request_method')
				->setFocusableElementId('label-request-method')
				->setValue($data['form']['request_method'])
				->addOptions(CSelect::createOptionsFromArray([
					HTTPCHECK_REQUEST_GET => 'GET',
					HTTPCHECK_REQUEST_POST => 'POST',
					HTTPCHECK_REQUEST_PUT => 'PUT',
					HTTPCHECK_REQUEST_HEAD => 'HEAD'
				]))
				->setReadonly($data['readonly'])
		))->setId('js-item-request-method-field')
	])
	->addItem([
		(new CLabel(_('Timeout'), 'timeout'))
			->setAsteriskMark()
			->setId('js-item-timeout-label'),
		(new CFormField(
			(new CTextBox('timeout', $data['form']['timeout'], $data['readonly']))
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
				->setAriaRequired()
		))->setId('js-item-timeout-field')
	])
	->addItem([
		(new CLabel(_('Request body type'), 'post_type'))->setId('js-item-post-type-label'),
		(new CFormField(
			(new CRadioButtonList('post_type', (int) $data['form']['post_type']))
				->addValue(_('Raw data'), ZBX_POSTTYPE_RAW)
				->addValue(_('JSON data'), ZBX_POSTTYPE_JSON)
				->addValue(_('XML data'), ZBX_POSTTYPE_XML)
				->setEnabled(!$data['readonly'])
				->setModern(true)
		))->setId('js-item-post-type-field')
	])
	->addItem([
		(new CLabel(_('Request body'), 'posts'))->setId('js-item-posts-label'),
		(new CFormField(
			(new CTextArea('posts', $data['form']['posts']))
				->setReadonly($data['readonly'])
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->setId('js-item-posts-field')
	])
	->addItem([
		(new CLabel(_('Headers'), 'headers-table'))->setId('js-item-headers-label'),
		(new CFormField((new CDiv([
				(new CTable())
					->setId('headers-table')
					->setAttribute('style', 'width: 100%;')
					->setHeader(['', _('Name'), '', _('Value'), ''])
					->setFooter((new CCol(
							(new CButtonLink(_('Add')))
								->addClass('element-table-add')
								->setEnabled(!$data['readonly'])
						))->setColSpan(5)
					),
				new CTemplateTag('item-header-row-tmpl',
					(new CRow([
						(new CCol([
							(new CDiv())->addClass(ZBX_STYLE_DRAG_ICON),
							new CVar('headers[sortorder][]', '#{rowNum}')
						]))->addClass(ZBX_STYLE_TD_DRAG_ICON),
						(new CTextBox('headers[name][#{rowNum}]', '#{name}', $data['readonly']))
							->setAttribute('placeholder', _('name'))
							->setWidth(ZBX_TEXTAREA_HTTP_PAIR_NAME_WIDTH),
						RARR(),
						(new CTextBox('headers[value][#{rowNum}]', '#{value}', $data['readonly'], 2000))
							->setAttribute('placeholder', _('value'))
							->setWidth(ZBX_TEXTAREA_HTTP_PAIR_VALUE_WIDTH),
						(new CButtonLink(_('Remove')))
							->addClass('element-table-remove')
							->setEnabled(!$data['readonly'])
					]))->addClass('form_row')
				),
			]))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH . 'px;')
		))->setId('js-item-headers-field')
	])
	->addItem([
		(new CLabel(_('Required status codes'), 'status_codes'))->setId('js-item-status-codes-label'),
		(new CFormField(
			(new CTextBox('status_codes', $data['form']['status_codes'], $data['readonly']))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->setId('js-item-status-codes-field')
	])
	->addItem([
		(new CLabel(_('Follow redirects'), 'follow_redirects'))->setId('js-item-follow-redirects-label'),
		(new CFormField(
			(new CCheckBox('follow_redirects', HTTPTEST_STEP_FOLLOW_REDIRECTS_ON))
				->setEnabled(!$data['readonly'])
				->setChecked($data['form']['follow_redirects'] == HTTPTEST_STEP_FOLLOW_REDIRECTS_ON)
		))->setId('js-item-follow-redirects-field')
	])
	->addItem([
		(new CLabel(_('Retrieve mode'), 'retrieve_mode'))->setId('js-item-retrieve-mode-label'),
		(new CFormField(
			(new CRadioButtonList('retrieve_mode', (int) $data['form']['retrieve_mode']))
				->addValue(_('Body'), HTTPTEST_STEP_RETRIEVE_MODE_CONTENT)
				->addValue(_('Headers'), HTTPTEST_STEP_RETRIEVE_MODE_HEADERS)
				->addValue(_('Body and headers'), HTTPTEST_STEP_RETRIEVE_MODE_BOTH)
				->setEnabled(!($data['readonly'] || $data['form']['request_method'] == HTTPCHECK_REQUEST_HEAD))
				->setModern(true)
		))->setId('js-item-retrieve-mode-field')
	])
	->addItem([
		(new CLabel(_('Convert to JSON'), 'output_format'))->setId('js-item-output-format-label'),
		(new CFormField(
			(new CCheckBox('output_format', HTTPCHECK_STORE_JSON))
				->setEnabled(!$data['readonly'])
				->setChecked($data['form']['output_format'] == HTTPCHECK_STORE_JSON)
		))->setId('js-item-output-format-field')
	])
	->addItem([
		(new CLabel(_('HTTP proxy'), 'http_proxy'))->setId('js-item-http-proxy-label'),
		(new CFormField(
			(new CTextBox('http_proxy', $data['form']['http_proxy'], $data['readonly'],
				DB::getFieldLength('items', 'http_proxy')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAttribute('placeholder', _('[protocol://][user[:password]@]proxy.example.com[:port]'))
				->disableAutocomplete()
		))->setId('js-item-http-proxy-field')
	])
	->addItem([
		(new CLabel(_('HTTP authentication'), 'label-http-authtype'))->setId('js-item-http-authtype-label'),
		(new CFormField(
			(new CSelect('http_authtype'))
				->setValue($data['form']['http_authtype'])
				->setId('http_authtype')
				->setFocusableElementId('label-http-authtype')
				->addOptions(CSelect::createOptionsFromArray(httptest_authentications()))
				->setReadonly($data['readonly'])
		))->setId('js-item-http-authtype-field')
	])
	->addItem([
		(new CLabel(_('User name'), 'http_username'))->setId('js-item-http-username-label'),
		(new CFormField(
			(new CTextBox('http_username', $data['form']['http_username'], $data['readonly'],
				DB::getFieldLength('items', 'username')
			))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->disableAutocomplete()
		))->setId('js-item-http-username-field')
	])
	->addItem([
		(new CLabel(_('Password'), 'http_password'))->setId('js-item-http-password-label'),
		(new CFormField(
			(new CTextBox('http_password', $data['form']['http_password'], $data['readonly'],
					DB::getFieldLength('items', 'password')
			))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->disableAutocomplete()
		))->setId('js-item-http-password-field')
	])
	->addItem([
		(new CLabel(_('SSL verify peer'), 'verify_peer'))->setId('js-item-verify-peer-label'),
		(new CFormField(
			(new CCheckBox('verify_peer', ZBX_HTTP_VERIFY_PEER_ON))
				->setEnabled(!$data['readonly'])
				->setChecked($data['form']['verify_peer'] == ZBX_HTTP_VERIFY_PEER_ON)
		))->setId('js-item-verify-peer-field')
	])
	->addItem([
		(new CLabel(_('SSL verify host'), 'verify_host'))->setId('js-item-verify-host-label'),
		(new CFormField(
			(new CCheckBox('verify_host', ZBX_HTTP_VERIFY_HOST_ON))
				->setEnabled(!$data['readonly'])
				->setChecked($data['form']['verify_host'] == ZBX_HTTP_VERIFY_HOST_ON)
		))->setId('js-item-verify-host-field')
	])
	->addItem([
		(new CLabel(_('SSL certificate file'), 'ssl_cert_file'))->setId('js-item-ssl-cert-file-label'),
		(new CFormField(
			(new CTextBox('ssl_cert_file', $data['form']['ssl_cert_file'], $data['readonly'],
				DB::getFieldLength('items', 'ssl_cert_file')
			))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->setId('js-item-ssl-cert-file-field')
	])
	->addItem([
		(new CLabel(_('SSL key file'), 'ssl_key_file'))->setId('js-item-ssl-key-file-label'),
		(new CFormField(
			(new CTextBox('ssl_key_file', $data['form']['ssl_key_file'], $data['readonly'],
				DB::getFieldLength('items', 'ssl_key_file')))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->setId('js-item-ssl-key-file-field')
	])
	->addItem([
		(new CLabel(_('SSL key password'), 'ssl_key_password'))->setId('js-item-ssl-key-password-label'),
		(new CFormField(
			(new CTextBox('ssl_key_password', $data['form']['ssl_key_password'], $data['readonly'],
				DB::getFieldLength('items', 'ssl_key_password')
			))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->disableAutocomplete()
		))->setId('js-item-ssl-key-password-field')
	])
	->addItem([
		(new CLabel(_('Master item'), 'master_itemid_ms'))
			->setAsteriskMark()
			->setId('js-item-master-item-label'),
		(new CFormField(
			(new CMultiSelect([
				'name' => 'master_itemid',
				'object_name' => 'items',
				'multiple' => false,
				'disabled' => $data['readonly'],
				'data' => $data['master_item']
					? [[
							'id' => $data['master_item']['itemid'],
							'prefix' => $data['host']['name'].NAME_DELIMITER,
							'name' => $data['master_item']['name']
					]]
					: [],
				'popup' => [
					'parameters' => [
						'srctbl' => 'items',
						'srcfld1' => 'itemid',
						'dstfrm' => $data['form_name'],
						'dstfld1' => 'master_itemid',
						'hostid' => $data['form']['hostid'],
						'excludeids' => $data['form']['itemid'] != 0 ? [$data['form']['itemid']] : [],
						'normal_only' => true
					]
				]
			]))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired()
		))->setId('js-item-master-item-field')
	]);

if ($data['host']['status'] == HOST_STATUS_MONITORED || $data['host']['status'] == HOST_STATUS_NOT_MONITORED) {
	$interface = $data['host']['interfaces'][$data['form']['interfaceid']] ?? [];

	if ($data['discovered']) {
		$required = $interface && $interface['type'] != INTERFACE_TYPE_OPT;
		$select_interface = (new CTextBox('interface', $interface ? getHostInterface($interface) : _('None'), true))
			->setAttribute('disabled', 'disabled');
		$label_for = $select_interface->getId();
	}
	else {
		$required = true;
		$select_interface = getInterfaceSelect($data['host']['interfaces'])
			->setId('interface-select')
			->setValue($data['form']['interfaceid'])
			->addClass(ZBX_STYLE_ZSELECT_HOST_INTERFACE)
			->setFocusableElementId('interfaceid')
			->setAriaRequired();
		$label_for = $select_interface->getFocusableElementId();
	}

	$formgrid->addItem([
		(new CLabel(_('Host interface'), $label_for))
			->setAsteriskMark($required)
			->setId('js-item-interface-label'),
		(new CFormField([
			$select_interface,
			(new CSpan(_('No interface found')))
				->setId('interface_not_defined')
				->addClass(ZBX_STYLE_RED)
				->addClass(ZBX_STYLE_DISPLAY_NONE)
		]))->setId('js-item-interface-field')
	]);
}

$formgrid
	->addItem([
		(new CLabel(_('SNMP OID'), 'snmp_oid'))
			->setAsteriskMark()
			->setId('js-item-snmp-oid-label'),
		(new CFormField(
			(new CTextBox('snmp_oid', $data['form']['snmp_oid'], $data['readonly'],
				DB::getFieldLength('items', 'snmp_oid')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAttribute('placeholder', '[IF-MIB::]ifInOctets.1')
				->setAriaRequired()
		))->setId('js-item-snmp-oid-field')
	])
	->addItem([
		(new CLabel(_('IPMI sensor'), 'ipmi_sensor'))->setId('js-item-impi-sensor-label'),
		(new CFormField(
			(new CTextBox('ipmi_sensor', $data['form']['ipmi_sensor'], $data['readonly'],
				DB::getFieldLength('items', 'ipmi_sensor')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->setId('js-item-impi-sensor-field')
	])
	->addItem([
		(new CLabel(_('Authentication method'), 'label-authtype'))->setId('js-item-authtype-label'),
		(new CFormField(
			(new CSelect('authtype'))
				->setId('authtype')
				->setFocusableElementId('label-authtype')
				->setValue($data['form']['authtype'])
				->addOptions(CSelect::createOptionsFromArray([
					ITEM_AUTHTYPE_PASSWORD => _('Password'),
					ITEM_AUTHTYPE_PUBLICKEY => _('Public key')
				]))
				->setReadonly($data['discovered'])
		))->setId('js-item-authtype-field')
	])
	->addItem([
		(new CLabel(_('JMX endpoint'), 'jmx_endpoint'))
			->setAsteriskMark()
			->setId('js-item-jmx-endpoint-label'),
		(new CFormField(
			(new CTextBox('jmx_endpoint', $data['form']['jmx_endpoint'], $data['discovered'],
				DB::getFieldLength('items', 'jmx_endpoint')
			))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired()
		))->setId('js-item-jmx-endpoint-field')
	])
	->addItem([
		(new CLabel(_('User name'), 'username'))->setId('js-item-username-label'),
		(new CFormField(
			(new CTextBox('username', $data['form']['username'], $data['discovered'],
				DB::getFieldLength('items', 'username')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->disableAutocomplete()
		))->setId('js-item-username-field')
	])
	->addItem([
		(new CLabel(_('Public key file'), 'publickey'))
			->setAsteriskMark()
			->setId('js-item-public-key-label'),
		(new CFormField(
			(new CTextBox('publickey', $data['form']['publickey'], $data['discovered'],
				DB::getFieldLength('items', 'publickey')))
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
				->setAriaRequired()
		))->setId('js-item-public-key-field')
	])
	->addItem([
		(new CLabel(_('Private key file'), 'privatekey'))
			->setAsteriskMark()
			->setId('js-item-private-key-label'),
		(new CFormField(
			(new CTextBox('privatekey', $data['form']['privatekey'], $data['discovered'],
				DB::getFieldLength('items', 'privatekey')))
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
				->setAriaRequired()
		))->setId('js-item-private-key-field')
	])
	->addItem([
		(new CLabel(_('Password'), 'password'))->setId('js-item-password-label'),
		(new CFormField(
			(new CTextBox('password', $data['form']['password'], $data['discovered'],
				DB::getFieldLength('items', 'password')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->disableAutocomplete()
		))->setId('js-item-password-field')
	])
	->addItem([
		(new CLabel(_('Executed script'), 'params_es'))
			->setAsteriskMark()
			->setId('js-item-executed-script-label'),
		(new CFormField(
			(new CTextArea('params_es', $data['form']['params']))
				->addClass(ZBX_STYLE_MONOSPACE_FONT)
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setReadonly($data['discovered'])
				->setAriaRequired()
		))->setId('js-item-executed-script-field')
	])
	->addItem([
		(new CLabel(_('SQL query'), 'params_ap'))
			->setAsteriskMark()
			->setId('js-item-sql-query-label'),
		(new CFormField(
			(new CTextArea('params_ap', $data['form']['params']))
				->addClass(ZBX_STYLE_MONOSPACE_FONT)
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired()
				->setReadonly($data['discovered'])
		))->setId('js-item-sql-query-field')
	])
	->addItem([
		(new CLabel(_('Formula'), 'params_f'))
			->setAsteriskMark()
			->setId('js-item-formula-label'),
		(new CFormField(
			(new CTextArea('params_f', $data['form']['params']))
				->addClass(ZBX_STYLE_MONOSPACE_FONT)
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired()
				->setReadonly($data['discovered'])
		))->setId('js-item-formula-field')
	])
	->addItem([
		(new CLabel(_('Units'), 'units'))->setId('js-item-units-label'),
		(new CFormField(
			(new CTextBox('units', $data['form']['units'], $data['readonly']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->setId('js-item-units-field')
	])
	->addItem([
		(new CLabel(_('Update interval'), 'delay'))
			->setAsteriskMark()
			->setId('js-item-delay-label'),
		(new CFormField(
			(new CTextBox('delay', $data['form']['delay'], $data['discovered']))
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
				->setAriaRequired()
		))->setId('js-item-delay-field')
	])
	->addItem([
		(new CLabel(_('Custom intervals'), 'delay-flex-table'))->setId('js-item-flex-intervals-label'),
		(new CFormField(
			(new CDiv([
				(new CTable())
					->setId('delay-flex-table')
					->setHeader([
						_('Type'), _('Interval'), _('Period'), $data['discovered'] ? null : _('Action')
					])
					->setFooter((new CCol(
						(new CButtonLink(_('Add')))
							->addClass('element-table-add')
							->setEnabled(!$data['discovered'])
						))->setColSpan($data['discovered'] ? 3 : 4)
					),
				new CTemplateTag('delay-flex-row-tmpl', (new CRow([
						(new CRadioButtonList('delay_flex[#{rowNum}][type]', ITEM_DELAY_FLEXIBLE))
							->addValue(_('Flexible'), ITEM_DELAY_FLEXIBLE)
							->addValue(_('Scheduling'), ITEM_DELAY_SCHEDULING)
							->setReadonly($data['discovered'])
							->setModern(true),
						[
							(new CTextBox('delay_flex[#{rowNum}][delay]', '#{delay}', $data['discovered']))
								->setAttribute('placeholder', ZBX_ITEM_FLEXIBLE_DELAY_DEFAULT),
							(new CTextBox('delay_flex[#{rowNum}][schedule]', '#{schedule}', $data['discovered']))
								->addClass(ZBX_STYLE_DISPLAY_NONE)
								->setAttribute('placeholder', ZBX_ITEM_SCHEDULING_DEFAULT)
						],
						(new CTextBox('delay_flex[#{rowNum}][period]', '#{period}', $data['discovered']))
							->setAttribute('placeholder', ZBX_DEFAULT_INTERVAL),
						(new CButtonLink(_('Remove')))
							->addClass('element-table-remove')
					]))->addClass('form_row')
				)
			]))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_STANDARD_WIDTH.'px;')
		))->setId('js-item-flex-intervals-field')
	]);

$hint = null;
if ($data['source'] === 'item' && $data['config']['hk_history_global']
		&& ($data['host']['status'] == HOST_STATUS_MONITORED || $data['host']['status'] == HOST_STATUS_NOT_MONITORED)) {
	$link = _x('global housekeeping settings', 'item_form');

	if (CWebUser::getType() == USER_TYPE_SUPER_ADMIN) {
		$link = (new CLink($link, (new CUrl())
			->setArgument('action', 'housekeeping.edit')
			->getUrl()
		))->setTarget('_blank');
	}

	$hint = (new CSpan(makeWarningIcon([_x('Overridden by', 'item_form').' ', $link,
		' ('.$data['config']['hk_history'].')'
	])))->addClass('js-hint');
}

$formgrid->addItem([
	(new CLabel([_('History storage period'), $hint], 'history'))->setAsteriskMark(),
	new CFormField([
		(new CRadioButtonList('history_mode', (int) $data['form']['history_mode']))
			->addValue(_('Do not keep history'), ITEM_STORAGE_OFF)
			->addValue(_('Storage period'), ITEM_STORAGE_CUSTOM)
			->setReadonly($data['discovered'])
			->setModern(true),
		(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
		(new CTextBox('history', $data['form']['history'], $data['discovered']))
			->setWidth(ZBX_TEXTAREA_TINY_WIDTH)
			->setAriaRequired()
	])
]);

$hint = null;
if ($data['source'] === 'item' && $data['config']['hk_trends_global']
		&& ($data['host']['status'] == HOST_STATUS_MONITORED || $data['host']['status'] == HOST_STATUS_NOT_MONITORED)) {
	$link = _x('global housekeeping settings', 'item_form');

	if (CWebUser::getType() == USER_TYPE_SUPER_ADMIN) {
		$link = (new CLink($link, (new CUrl())
			->setArgument('action', 'housekeeping.edit')
			->getUrl()
		))->setTarget('_blank');
	}

	$hint = (new CSpan(makeWarningIcon([_x('Overridden by', 'item_form').' ', $link,
		' ('.$data['config']['hk_trends'].')'
	])))->addClass('js-hint');
}

$formgrid
	->addItem([
		(new CLabel([_('Trend storage period'), $hint], 'trends'))
			->setAsteriskMark()
			->setId('js-item-trends-label'),
		(new CFormField([
			(new CRadioButtonList('trends_mode', (int) $data['form']['trends_mode']))
				->addValue(_('Do not keep trends'), ITEM_STORAGE_OFF)
				->addValue(_('Storage period'), ITEM_STORAGE_CUSTOM)
				->setReadonly($data['discovered'])
				->setModern(true),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			(new CTextBox('trends', $data['form']['trends'], $data['discovered']))
				->setWidth(ZBX_TEXTAREA_TINY_WIDTH)
				->setAriaRequired()
		]))->setId('js-item-trends-field')
	])
	->addItem([
		(new CLabel(_('Log time format'), 'logtimefmt'))->setId('js-item-log-time-format-label'),
		(new CFormField(
			(new CTextBox('logtimefmt', $data['form']['logtimefmt'], $data['readonly'],
				DB::getFieldLength('items', 'logtimefmt'))
			)->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->setId('js-item-log-time-format-field')
	])
	->addItem($data['source'] === 'itemprototype' || $data['host']['flags'] != ZBX_FLAG_DISCOVERY_CREATED ? [
		(new CLabel(_('Value mapping'), 'valuemapid_ms'))->setId('js-item-value-map-label'),
		(new CFormField(
			(new CMultiSelect([
				'name' => 'valuemapid',
				'object_name' => $data['form']['context'] === 'host' ? 'valuemaps' : 'template_valuemaps',
				'disabled' => $data['readonly'],
				'multiple' => false,
				'data' => $data['valuemap'] ? [$data['valuemap']] : [],
				'popup' => [
					'parameters' => [
						'srctbl' => $data['form']['context'] === 'host' ? 'valuemaps' : 'template_valuemaps',
						'srcfld1' => 'valuemapid',
						'dstfrm' => $data['form_name'],
						'dstfld1' => 'valuemapid',
						'hostids' => [$data['form']['hostid']],
						'context' => $data['form']['context'],
						'editable' => true
					]
				]
			]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->setId('js-item-value-map-field')
	] : null)
	->addItem([
		(new CLabel(_('Enable trapping'), 'allow_traps'))->setId('js-item-allow-traps-label'),
		(new CFormField(
			(new CCheckBox('allow_traps', HTTPCHECK_ALLOW_TRAPS_ON))
				->setEnabled(!$data['discovered'])
				->setChecked($data['form']['allow_traps'] == HTTPCHECK_ALLOW_TRAPS_ON)
		))->setId('js-item-allow-traps-field')
	])
	->addItem([
		(new CLabel(_('Allowed hosts'), 'trapper_hosts'))->setId('js-item-trapper-hosts-label'),
		(new CFormField(
			(new CTextBox('trapper_hosts', $data['form']['trapper_hosts'], false,
				DB::getFieldLength('items', 'trapper_hosts')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		))->setId('js-item-trapper-hosts-field')
	]);

if ($data['source'] === 'item' && !$data['discovered']) {
	$select = (new CSelect('inventory_link'))
		->setFocusableElementId('label-host-inventory')
		->setValue($data['form']['inventory_link'])
		->addOption(new CSelectOption(0, '-'._('None').'-'))
		->addOptions(CSelect::createOptionsFromArray($data['inventory_fields']));

	$formgrid->addItem([
		(new CLabel(_('Populates host inventory field'), $select->getFocusableElementId()))
			->setId('js-item-inventory-link-label'),
		(new CFormField($select))->setId('js-item-inventory-link-field')
	]);
}

$formgrid
	->addItem([
		new CLabel(_('Description'), 'description'),
		new CFormField((new CTextArea('description', $data['form']['description']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setMaxlength(DB::getFieldLength('items', 'description'))
			->setReadonly($data['discovered'])
		)
	]);

if ($data['source'] === 'item') {
	$formgrid->addItem([
		new CLabel(_('Enabled'), 'status'),
		new CFormField(
			(new CCheckBox('status', ITEM_STATUS_ACTIVE))->setChecked($data['form']['status'] == ITEM_STATUS_ACTIVE))
	]);

	if (CWebUser::checkAccess(CRoleHelper::UI_MONITORING_LATEST_DATA) && $data['form']['itemid'] != 0
			&& $data['form']['context'] === 'host') {
		$formgrid->addItem(
			(new CFormField((new CLink(_('Latest data'),
				(new CUrl())
					->setArgument('action', 'latest.view')
					->setArgument('hostids[]', $data['form']['hostid'])
					->setArgument('name', $data['form']['name'])
					->setArgument('filter_set', '1')
			))->setTarget('_blank')))
		);
	}
}
else {
	$formgrid
		->addItem([
			new CLabel(_('Create enabled'), 'status'),
			new CFormField(
				(new CCheckBox('status', ITEM_STATUS_ACTIVE))
					->setChecked($data['form']['status'] == ITEM_STATUS_ACTIVE))
		])
		->addItem([
			new CLabel(_('Discover'), 'discover'),
			new CFormField(
				(new CCheckBox('discover', ZBX_PROTOTYPE_DISCOVER))
					->setChecked($data['form']['discover'] == ZBX_PROTOTYPE_DISCOVER)
			)
		]);
}

$formgrid->show();
