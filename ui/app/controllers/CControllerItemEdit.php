<?php declare(strict_types=0);
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


require 'include/forms.inc.php';

class CControllerItemEdit extends CControllerItem {

	protected function init() {
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool {
		$ret = $this->validateFormInput([]);

		if (!$ret) {
			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode([
					'error' => [
						'messages' => array_column(get_and_clear_messages(), 'message')
					]
				])]))->disableView()
			);
		}

		return $ret;
	}

	public function doAction() {
		$form_refresh = $this->hasInput('form_refresh');
		$host = $this->getInput('context') === 'host' ? $this->getHost() : $this->getTemplate();
		$hostid = $host['hostid'];
		$data = [
			'action' => $this->getAction(),
			'readonly' => false,
			'host' => $host,
			'valuemap' => [],
			'inventory_fields' => [],
			'form' => $form_refresh || !$this->hasInput('itemid') ? $this->getInputForForm() : $this->getItem(),
			'form_refresh' => $form_refresh,
			'parent_items' => [],
			'flags' => ZBX_FLAG_DISCOVERY_NORMAL,
			'discovery_rule' => [],
			'discovery_itemid' => 0,
			'master_item' => [],
			'types' => item_type2str(),
			'testable_item_types' => CControllerPopupItemTest::getTestableItemTypes($hostid),
			'interface_types' => itemTypeInterface(),
			'preprocessing_test_type' => CControllerPopupItemTestEdit::ZBX_TEST_TYPE_ITEM,
			'preprocessing_types' => CItem::SUPPORTED_PREPROCESSING_TYPES,
			'config' => [
				'compression_status' => CHousekeepingHelper::get(CHousekeepingHelper::COMPRESSION_STATUS),
				'hk_history_global' => CHousekeepingHelper::get(CHousekeepingHelper::HK_HISTORY_GLOBAL),
				'hk_history' => CHousekeepingHelper::get(CHousekeepingHelper::HK_HISTORY),
				'hk_trends_global' => CHousekeepingHelper::get(CHousekeepingHelper::HK_TRENDS_GLOBAL),
				'hk_trends' => CHousekeepingHelper::get(CHousekeepingHelper::HK_TRENDS)
			],
			'user' => ['debug_mode' => $this->getDebugMode()]
		];
		unset($data['types'][ITEM_TYPE_HTTPTEST]);

		if ($data['form']['valuemapid']) {
			$valuemaps = CArrayHelper::renameObjectsKeys(API::ValueMap()->get([
				'output' => ['valuemapid', 'name'],
				'valuemapids' => $data['form']['valuemapid']
			]), ['valuemapid' => 'id']);
			$data['valuemap'] = $valuemaps ? reset($valuemaps) : [];
		}

		if ($data['form']['master_itemid']) {
			$master_items = API::Item()->get([
				'output' => ['itemid', 'name'],
				'itemids' => $data['form']['master_itemid'],
				'webitems' => true
			]);
			$data['master_item'] = $master_items ? reset($master_items) : [];
		}

		if ($data['form']['itemid']) {
			$item = [
				'itemid' => $data['form']['itemid'],
				'templateid' => $data['form']['templateid']
			];
			$data['parent_items'] = makeItemTemplatesHtml(
				$item['itemid'],
				getItemParentTemplates([$item], ZBX_FLAG_DISCOVERY_NORMAL),
				ZBX_FLAG_DISCOVERY_NORMAL,
				CWebUser::checkAccess(CRoleHelper::UI_CONFIGURATION_TEMPLATES)
			);
			[$db_item] = API::Item()->get([
				'output' => ['flags'],
				'selectDiscoveryRule' => ['name', 'templateid'],
				'selectItemDiscovery' => ['parent_itemid'],
				'itemids' => [$data['form']['itemid']]
			]);

			if ($db_item) {
				$data['flags'] = $db_item['flags'];
				$data['discovery_rule'] = $db_item['discoveryRule'];

				if ($db_item['itemDiscovery']) {
					$data['discovery_itemid'] = $db_item['itemDiscovery']['parent_itemid'];
				}
			}
		}

		if ($this->getInput('show_inherited_tags', 0)) {
			$data['form']['tags'] = CItemHelper::getTagsWithInherited([
				'item' => $data['form'] + ['discoveryRule' => $data['discovery_rule']],
				'itemid' => $data['form']['itemid'],
				'tags' => $data['form']['tags'],
				'hostid' => [$hostid]
			]);
		}

		$set_inventory = array_column(API::Item()->get([
			'output' => ['inventory_link'],
			'hostids' => [$hostid],
			'nopermissions' => true
		]), 'inventory_link', 'inventory_link');

		foreach (getHostInventories() as $inventory_field) {
			$data['inventory_fields'][$inventory_field['nr']] = [
				'label' => $inventory_field['title'],
				'disabled' => array_key_exists($inventory_field['nr'], $set_inventory)
			];
		};

		$data['value_type_keys'] = [];
		$key_value_type = CItemData::getValueTypeByKey();
		foreach (CItemData::getKeysByItemType() as $type => $keys) {
			foreach ($keys as $key) {
				$value_type = $key_value_type[$key];
				$data['value_type_keys'] += [$type => []];
				$data['value_type_keys'][$type][$key] = $value_type;
			}
		}

		if ($data['form']['templateid']) {
			$data['readonly'] = true;
		}

		$response = new CControllerResponseData($data);
		$response->setTitle(_('Item'));
		$this->setResponse($response);
	}

	/**
	 * Get host data.
	 */
	protected function getHost(): array {
		[$host] = API::Host()->get([
			'output' => ['name', 'flags', 'status'],
			'selectInterfaces' => ['interfaceid', 'ip', 'port', 'dns', 'useip', 'details', 'type', 'main'],
			'hostids' => !$this->hasInput('itemid') ? [$this->getInput('hostid')] : null,
			'itemids' => $this->hasInput('itemid') ? [$this->getInput('itemid')] : null
		]);

		$host['interfaces'] = array_column($host['interfaces'], null, 'interfaceid');
		// Sort interfaces to be listed starting with one selected as 'main'.
		CArrayHelper::sort($host['interfaces'], [
			['field' => 'main', 'order' => ZBX_SORT_DOWN],
			['field' => 'interfaceid','order' => ZBX_SORT_UP]
		]);

		return $host;
	}

	/**
	 * Get template data.
	 *
	 * @param string $templateid
	 */
	protected function getTemplate(): array {
		[$template] = API::Template()->get([
			'output' => ['templateid', 'name', 'flags'],
			'templateids' => !$this->hasInput('itemid') ? [$this->getInput('hostid')] : null,
			'itemids' => $this->hasInput('itemid') ? [$this->getInput('itemid')] : null
		]);
		$template += [
			'hostid' => $template['templateid'],
			'status' => HOST_STATUS_TEMPLATE,
			'interfaces' => []
		];

		return $template;
	}

	/**
	 * Get form data for item from database.
	 *
	 * @return array
	 */
	protected function getItem(): array {
		[$item] = API::Item()->get([
			'ouput' => ['itemid', 'type', 'snmp_oid', 'hostid', 'name', 'key_', 'delay', 'history', 'trends', 'status',
				'value_type', 'trapper_hosts', 'units', 'logtimefmt', 'templateid', 'valuemapid', 'params',
				'ipmi_sensor', 'authtype', 'username', 'password', 'publickey', 'privatekey', 'flags', 'interfaceid',
				'description', 'inventory_link', 'lifetime', 'jmx_endpoint', 'master_itemid', 'url', 'query_fields',
				'parameters', 'timeout', 'posts', 'status_codes', 'follow_redirects', 'post_type', 'http_proxy',
				'headers', 'retrieve_mode', 'request_method', 'output_format', 'ssl_cert_file', 'ssl_key_file',
				'ssl_key_password', 'verify_peer', 'verify_host', 'allow_traps'
			],
			'selectInterfaces' => ['interfaceid', 'type', 'ip', 'dns', 'port', 'useip', 'main'],
			'selectPreprocessing' => ['type', 'params', 'error_handler', 'error_handler_params'],
			'selectTags' => ['tag', 'value'],
			'itemids' => $this->getInput('itemid')
		]);

		$i = 0;
		foreach ($item['preprocessing'] as &$step) {
			$step['params'] = $step['type'] == ZBX_PREPROC_SCRIPT
				? [$step['params'], ''] : explode("\n", $step['params']);
			$step['sortorder'] = $i++;
		}
		unset($step);

		$item += [
			'http_authtype' => '',
			'http_username' => '',
			'http_password' => '',
			'history_mode' => $item['history'] == ITEM_NO_STORAGE_VALUE ? ITEM_STORAGE_OFF : ITEM_STORAGE_CUSTOM,
			'trends_mode' => $item['trends'] == ITEM_NO_STORAGE_VALUE ? ITEM_STORAGE_OFF : ITEM_STORAGE_CUSTOM,
			'context' => $this->getInput('context'),
			'show_inherited_tags' => 0,
			'discovered' => $item['flags'] == ZBX_FLAG_DISCOVERY_CREATED ? 1 : 0,
			'key' => $item['key_']
		];
		unset($item['key_']);

		if ($item['type'] == ITEM_TYPE_HTTPAGENT) {
			$item['http_authtype'] = $item['authtype'];
			$item['http_username'] = $item['username'];
			$item['http_password'] = $item['password'];
			$query_fields = [];

			foreach ($item['query_fields'] as $query_field) {
				$query_fields[] = [
					'name' => key($query_field),
					'value' => reset($query_field)
				];
			}

			$item['query_fields'] = $query_fields;
			$headers = [];

			foreach ($item['headers'] as $header => $value) {
				$headers[] = [
					'name' => $header,
					'value' => $value
				];
			}

			$item['headers'] = $headers;
		}

		$item['delay_flex'] = [];
		$update_interval_parser = new CUpdateIntervalParser([
			'usermacros' => true,
			'lldmacros' => false
		]);

		if ($update_interval_parser->parse($item['delay']) == CParser::PARSE_SUCCESS) {
			$item['delay'] = $update_interval_parser->getDelay();

			if ($item['delay'][0] !== '{') {
				$delay = timeUnitToSeconds($item['delay']);

				if ($delay == 0 && ($item['type'] == ITEM_TYPE_TRAPPER || $item['type'] == ITEM_TYPE_SNMPTRAP
						|| $item['type'] == ITEM_TYPE_DEPENDENT || ($item['type'] == ITEM_TYPE_ZABBIX_ACTIVE
							&& strncmp($item['key'], 'mqtt.get', 8) === 0))) {
					$item['delay'] = ZBX_ITEM_DELAY_DEFAULT;
				}
			}

			foreach ($update_interval_parser->getIntervals() as $interval) {
				if ($interval['type'] == ITEM_DELAY_FLEXIBLE) {
					$item['delay_flex'][] = [
						'delay' => $interval['update_interval'],
						'period' => $interval['time_period'],
						'type' => ITEM_DELAY_FLEXIBLE
					];
				}
				else {
					$item['delay_flex'][] = [
						'schedule' => $interval['interval'],
						'type' => ITEM_DELAY_SCHEDULING
					];
				}
			}
		}
		else {
			$item['delay'] = ZBX_ITEM_DELAY_DEFAULT;
		}

		return $item;
	}
}
