<?php declare(strict_types = 1);
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

class CItemTypeDbMonitor extends CItemType {

	/**
	 * @inheritDoc
	 */
	public static function getCreateValidationRules(string $class_name): array {
		return [
			'username' =>	['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'username')],
			'password' =>	['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'password')],
			'params' =>		['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'params')],
			'delay' =>		['type' => API_ITEM_DELAY, 'flags' => API_REQUIRED, 'length' => DB::getFieldLength('items', 'delay')]
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRules(string $class_name, array $db_item): array {
		return [
			'username' =>	['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'username')],
			'password' =>	['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'password')],
			'params' =>		['type' => API_MULTIPLE, 'rules' => [
								['if' => static function (array $data) use ($db_item): bool {
									return $data['type'] != $db_item['type'];
								}, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'params')],
								['else' => true, 'type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'params')]
			]],
			'delay' =>		['type' => API_MULTIPLE, 'rules' => [
								['if' => static function (array $data) use ($db_item): bool {
									return in_array($db_item['type'], [ITEM_TYPE_TRAPPER, ITEM_TYPE_SNMPTRAP, ITEM_TYPE_DEPENDENT])
										|| ($db_item['type'] == ITEM_TYPE_ZABBIX_ACTIVE && strncmp($db_item['key_'], 'mqtt.get', 8) === 0);
								}, 'type' => API_ITEM_DELAY, 'flags' => API_REQUIRED, 'length' => DB::getFieldLength('items', 'delay')],
								['else' => true, 'type' => API_ITEM_DELAY, 'length' => DB::getFieldLength('items', 'delay')]
			]]
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRulesInherited(string $class_name, array $db_item): array {
		return [
			'username' =>	['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'username')],
			'password' =>	['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'password')],
			'params' =>		['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'params')],
			'delay' =>		['type' => API_ITEM_DELAY, 'length' => DB::getFieldLength('items', 'delay')]
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRulesDiscovered(string $class_name, array $db_item): array {
		return [
			'username' =>	['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'password' =>	['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'params' =>		['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'delay' =>		['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED]
		];
	}
}
