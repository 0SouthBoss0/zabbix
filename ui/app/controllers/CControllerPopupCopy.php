<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
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


class CControllerPopupCopy extends CController {

	protected function checkInput() {
		$fields = [
			'itemids' => 'array_id',
			'triggerids' => 'array_id',
			'graphids' => 'array_id'
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

	protected function checkPermissions() {
		if (!$this->checkAccess(CRoleHelper::UI_CONFIGURATION_HOSTS)
				&& !$this->checkAccess(CRoleHelper::UI_CONFIGURATION_TEMPLATES)) {
			return false;
		}

		$action = $this->getAction();

		if ($action === 'popup.copy.items' && $this->hasInput('itemids')) {
			$items_count = API::Item()->get([
				'countOutput' => true,
				'itemids' => $this->getInput('itemids')
			]);

			return $items_count == count($this->getInput('itemids'));
		}
		elseif ($action === 'popup.copy.triggers' && $this->hasInput('triggerids')) {
			$triggers_count = API::Trigger()->get([
				'countOutput' => true,
				'triggerids' => $this->getInput('triggerids')
			]);

			return $triggers_count == count($this->getInput('triggerids'));
		}
		elseif ($action === 'popup.copy.graphs' && $this->hasInput('graphids')) {
			$graphs_count = API::Graph()->get([
				'countOutput' => true,
				'graphids' => $this->getInput('graphids')
			]);

			return $graphs_count == count($this->getInput('graphids'));
		}

		return false;
	}

	protected function doAction() {
		$data = [
			'action' => $this->getAction()
		];

		if ($data['action'] === 'popup.copy.items') {
			$data['itemids'] = $this->getInput('itemids');
		}
		elseif ($data['action'] === 'popup.copy.triggers') {
			$data['triggerids'] = $this->getInput('triggerids');
		}
		elseif ($data['action'] === 'popup.copy.graphs') {
			$data['graphids'] = $this->getInput('graphids');
		}

		$this->setResponse(new CControllerResponseData($data));
	}
}
