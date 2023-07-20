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


class CControllerTriggerPrototypeUpdate extends CController {

	/**
	 * @var array
	 */
	private $db_trigger_prototype;

	protected function init(): void {
		$this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
	}

	protected function checkInput(): bool {
		$fields = [
			'comments' =>							'db triggers.comments',
			'correlation_mode' =>					'db triggers.correlation_mode|in '.implode(',', [ZBX_TRIGGER_CORRELATION_NONE, ZBX_TRIGGER_CORRELATION_TAG]),
			'correlation_tag' =>					'db triggers.correlation_tag',
			'dependencies' =>						'array',
			'description' =>						'required|db triggers.description|not_empty',
			'discover' =>							'db triggers.discover',
			'event_name' =>							'db triggers.event_name',
			'expression' =>							'required|db triggers.expression|not_empty',
			'manual_close' =>						'db triggers.manual_close|in '.implode(',',[ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED, ZBX_TRIGGER_MANUAL_CLOSE_ALLOWED]),
			'opdata' =>								'db triggers.opdata',
			'parent_discoveryid' => 				'required|db triggers.triggerid',
			'priority' =>							'db triggers.priority|in 0,1,2,3,4,5',
			'recovery_expression' =>				'db triggers.recovery_expression',
			'recovery_mode' =>						'db triggers.recovery_mode|in '.implode(',', [ZBX_RECOVERY_MODE_EXPRESSION, ZBX_RECOVERY_MODE_RECOVERY_EXPRESSION, ZBX_RECOVERY_MODE_NONE]),
			'status' =>								'db triggers.status|in '.implode(',', [TRIGGER_STATUS_ENABLED, TRIGGER_STATUS_DISABLED]),
			'tags' =>								'array',
			'triggerid' =>							'fatal|required|db triggers.triggerid',
			'type' =>								'db triggers.type|in 0,1',
			'url' =>								'db triggers.url',
			'url_name' =>							'db triggers.url_name'
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
		$discovery_rule = API::DiscoveryRule()->get([
			'output' => ['name', 'itemid', 'hostid'],
			'itemids' => $this->getInput('parent_discoveryid'),
			'editable' => true
		]);

		if (!$discovery_rule) {
			return false;
		}

		$db_trigger_prototypes = API::TriggerPrototype()->get([
			'output' => ['expression', 'description', 'url_name', 'url', 'status', 'priority', 'comments', 'templateid',
				'type', 'recovery_mode', 'recovery_expression', 'correlation_mode', 'correlation_tag', 'manual_close',
				'opdata', 'discover', 'event_name'
			],
			'selectDependencies' => ['triggerid'],
			'selectTags' => ['tag', 'value'],
			'triggerids' => $this->getInput('triggerid'),
			'editable' => true
		]);

		if (!$db_trigger_prototypes) {
			return false;
		}

		$db_trigger_prototypes = CMacrosResolverHelper::resolveTriggerExpressions($db_trigger_prototypes,
			['sources' => ['expression', 'recovery_expression']]
		);

		$this->db_trigger_prototype = reset($db_trigger_prototypes);

		return $this->checkAccess(CRoleHelper::UI_CONFIGURATION_HOSTS);
	}

	protected function doAction(): void {
		$trigger_prototype = [];

		if ($this->db_trigger_prototype['templateid'] == 0) {
			$trigger_prototype += [
				'description' => $this->getInput('description'),
				'event_name' => $this->getInput('event_name', ''),
				'opdata' => $this->getInput('opdata', ''),
				'expression' => $this->getInput('expression'),
				'recovery_mode' => $this->getInput('recovery_mode', ZBX_RECOVERY_MODE_EXPRESSION),
				'manual_close' => $this->getInput('manual_close', ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED)
			];

			switch ($trigger_prototype['recovery_mode']) {
				case ZBX_RECOVERY_MODE_RECOVERY_EXPRESSION:
					$trigger_prototype['recovery_expression'] = $this->getInput('recovery_expression', '');
				// break; is not missing here.

				case ZBX_RECOVERY_MODE_EXPRESSION:
					$trigger_prototype['correlation_mode'] = $this->getInput('correlation_mode', ZBX_TRIGGER_CORRELATION_NONE);

					if ($trigger_prototype['correlation_mode'] == ZBX_TRIGGER_CORRELATION_TAG) {
						$trigger_prototype['correlation_tag'] = $this->getInput('correlation_tag', '');
					}
					break;
			}
		}

		$tags = $this->getInput('tags', []);

		// Unset empty and inherited tags.
		foreach ($tags as $key => $tag) {
			if ($tag['tag'] === '' && $tag['value'] === '') {
				unset($tags[$key]);
			}
			elseif (array_key_exists('type', $tag) && !($tag['type'] & ZBX_PROPERTY_OWN)) {
				unset($tags[$key]);
			}
			else {
				unset($tags[$key]['type']);
			}
		}

		CArrayHelper::sort($tags, ['tag', 'value']);

		$dependencies = zbx_toObject(getRequest('dependencies', []), 'triggerid');
		CArrayHelper::sort($dependencies, ['triggerid']);

		$trigger_prototype += [
			'type' => $this->getInput('type', 0),
			'url_name' => $this->getInput('url_name', ''),
			'url' => $this->getInput('url', ''),
			'priority' => $this->getInput('priority', TRIGGER_SEVERITY_NOT_CLASSIFIED),
			'comments' => $this->getInput('comments', ''),
			'dependencies' => $dependencies,
			'tags' => $tags,
			'status' => $this->hasInput('status') ? TRIGGER_STATUS_ENABLED : TRIGGER_STATUS_DISABLED,
			'triggerid' => $this->getInput('triggerid'),
			'discover' => $this->getInput('discover', ZBX_PROTOTYPE_NO_DISCOVER)
		];

		$result = (bool) API::TriggerPrototype()->update($trigger_prototype);

		if ($result) {
			$output['success']['title'] = _('Trigger prototype updated');

			if ($messages = get_and_clear_messages()) {
				$output['success']['messages'] = array_column($messages, 'message');
			}
		}
		else {
			$output['error'] = [
				'title' => _('Cannot update trigger prototype'),
				'messages' => array_column(get_and_clear_messages(), 'message')
			];
		}

		$this->setResponse(new CControllerResponseData(['main_block' => json_encode($output)]));
	}
}
