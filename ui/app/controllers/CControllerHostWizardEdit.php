<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


class CControllerHostWizardEdit extends CController {

	protected function init(): void {
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool {
		$fields = [
			'hostid' => 'db hosts.hostid'
		];

		$ret = $this->validateInput($fields);

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

	protected function checkPermissions(): bool {
		if (!$this->checkAccess(CRoleHelper::UI_CONFIGURATION_HOSTS)) {
			return false;
		}

		if ($this->hasInput('hostid')) {
			$hosts = API::Host()->get([
				'output' => [],
				'hostids' => $this->getInput('hostid'),
				'editable' => true,
				'limit' => 1
			]);

			if (!$hosts) {
				return false;
			}
		}

		return true;
	}

	protected function doAction(): void {
		$vendor_template_count = API::template()->get([
			'output' => [],
			'search' => ['vendor_version' => '-'],
			'countOutput' => true
		]);

		$wizard_ready_templates = API::template()->get([
			'output' => ['templateid', 'name', 'description', 'vendor_version'],
			'filter' => ['wizard_ready' => '1'],
			'selectTemplateGroups' => ['name'],
			'selectTags' => ['tag', 'value'],
			'selectItems' => ['type'],
			'selectDiscoveries' => ['type']
		]);

		$wizard_vendor_template_count = 0;

		foreach ($wizard_ready_templates as &$template) {
			if ($template['vendor_version'] !== '') {
				$wizard_vendor_template_count ++;
			}

			$item_prototypes = API::itemprototype()->get([
				'output' => ['type'],
				'hostids' => $template['templateid']
			]);

			$unique_types = array_keys(array_column(
				array_merge($template['items'], $template['discoveries'], $item_prototypes), null, 'type'
			));

			// Remove unnecessary template data.
			unset($template['items'], $template['discoveries'], $template['vendor_version']);

			$template['data_collection'] = [];
			$template['agent_mode'] = [];

			foreach ($unique_types as $type) {
				$agent_mode = $type == ITEM_TYPE_ZABBIX
					? ZBX_TEMPLATE_AGENT_MODE_PASSIVE
					: ZBX_TEMPLATE_AGENT_MODE_ACTIVE;

				if (in_array($type, [ITEM_TYPE_ZABBIX, ITEM_TYPE_ZABBIX_ACTIVE])) {
					$template['data_collection'][ZBX_TEMPLATE_DATA_COLLECTION_AGENT_BASED] = true;
					$template['agent_mode'][$agent_mode] = true;
				}
				else {
					$template['data_collection'][ZBX_TEMPLATE_DATA_COLLECTION_AGENTLESS] = true;
				}
			}

			$template['data_collection'] = array_keys($template['data_collection']);
			$template['agent_mode'] = array_keys($template['agent_mode']);
		}
		unset($template);

		$linked_templates = [];

		if ($this->hasInput('hostid')) {
			$linked_templates = API::template()->get([
				'output' => ['templateid'],
				'hostids' => $this->getInput('hostid'),
				'preservekeys' => true
			]);
		}

		$data = [
			'form_action' => $linked_templates ? 'host.wizard.update' : 'host.wizard.create',
			'templates' => $wizard_ready_templates,
			'linked_templates' => array_keys($linked_templates),
			'old_template_count' => $vendor_template_count - $wizard_vendor_template_count,
			'wizard_hide_welcome' => CProfile::get('web.host.wizard.hide.welcome', 0)
		];

		$response = new CControllerResponseData($data);
		$this->setResponse($response);
	}
}
