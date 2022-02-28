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

class CItemTypeJmx implements CItemType {

	/**
	 * @inheritDoc
	 */
	public static function getCreateValidationRules(array &$item): array {
		return [
			'interfaceid' =>	['type' => API_ID],
			'jmx_endpoint' =>	['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'jmx_endpoint'), 'default' => ZBX_DEFAULT_JMX_ENDPOINT],
			'username' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'username')],
			'password' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'password')],
			'delay' =>			['type' => API_ITEM_DELAY, 'flags' => API_REQUIRED, 'length' => DB::getFieldLength('items', 'delay')]
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRules(array &$item, array $db_item): array {
		return [
			'interfaceid' =>	['type' => API_ID],
			'jmx_endpoint' =>	['type' => API_MULTIPLE, 'rules' => [
									['if' => static function () use ($db_item): bool {
										return $db_item['type'] != ITEM_TYPE_JMX;
									}, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'jmx_endpoint')],
									['else' => true, 'type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'jmx_endpoint')]
			]],
			'username' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'username')],
			'password' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'password')],
			'delay' =>			['type' => API_MULTIPLE, 'rules' => [
									['if' => static function () use ($db_item): bool {
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
	public static function getUpdateValidationRulesInherited(array &$item, array $db_item): array {
		return [
			'interfaceid' =>	['type' => API_ID],
			'jmx_endpoint' =>	['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'jmx_endpoint')],
			'username' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'username')],
			'password' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'password')],
			'delay' =>			['type' => API_ITEM_DELAY, 'length' => DB::getFieldLength('items', 'delay')]
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRulesDiscovered(array &$item, array $db_item): array {
		return [
			'interfaceid' =>	['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'jmx_endpoint' =>	['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'username' =>		['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'password' =>		['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'delay' =>			['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED]
		];
	}
}
