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

class CItemTypeSsh implements CItemType {

	/**
	 * @inheritDoc
	 */
	const FIELD_NAMES = ['interfaceid', 'authtype', 'username', 'publickey', 'privatekey', 'password', 'params',
		'delay'
	];

	/**
	 * @inheritDoc
	 */
	public static function getCreateValidationRules(array &$item): array {
		return [
			'interfaceid' =>	['type' => API_MULTIPLE, 'rules' => [
									['if' => ['field' => 'host_status', 'in' => implode(',', [HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED])], 'type' => API_ID, 'flags' => API_REQUIRED],
									['else' => true, 'type' => API_UNEXPECTED]
			]],
			'authtype' =>		['type' => API_INT32, 'in' => implode(',', [ITEM_AUTHTYPE_PASSWORD, ITEM_AUTHTYPE_PUBLICKEY]), 'default' => DB::getDefault('items', 'authtype')],
			'username' =>		['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'username')],
			'publickey' =>		['type' => API_MULTIPLE, 'rules' => [
									['if' => ['field' => 'authtype', 'in' => ITEM_AUTHTYPE_PUBLICKEY], 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'publickey')],
									['else' => true, 'type' => API_UNEXPECTED]
			]],
			'privatekey' =>		['type' => API_MULTIPLE, 'rules' => [
									['if' => ['field' => 'authtype', 'in' => ITEM_AUTHTYPE_PUBLICKEY], 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'privatekey')],
									['else' => true, 'type' => API_UNEXPECTED]
			]],
			'password' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'password')],
			'params' =>			['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'params')],
			'delay' =>			['type' => API_ITEM_DELAY, 'flags' => API_REQUIRED, 'length' => DB::getFieldLength('items', 'delay')]
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRules(array &$item, array $db_item): array {
		$item += array_intersect_key($db_item, array_flip(['authtype']));

		return [
			'interfaceid' =>	['type' => API_MULTIPLE, 'rules' => [
									['if' => static function () use ($db_item): bool {
										return in_array($db_item['host_status'], [HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED])
											&& in_array($db_item['type'], [ITEM_TYPE_TRAPPER, ITEM_TYPE_INTERNAL, ITEM_TYPE_ZABBIX_ACTIVE,
												ITEM_TYPE_DB_MONITOR, ITEM_TYPE_CALCULATED, ITEM_TYPE_DEPENDENT, ITEM_TYPE_SCRIPT
											]);
									}, 'type' => API_ID, 'flags' => API_REQUIRED],
									['if' => ['field' => 'host_status', 'in' => implode(',', [HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED])], 'type' => API_ID],
									['else' => true, 'type' => API_UNEXPECTED]
			]],
			'authtype' =>		['type' => API_INT32, 'in' => implode(',', [ITEM_AUTHTYPE_PASSWORD, ITEM_AUTHTYPE_PUBLICKEY])],
			'username' =>		['type' => API_MULTIPLE, 'rules' => [
									['if' => static function () use ($db_item): bool {
										return in_array($db_item['type'], [ITEM_TYPE_ZABBIX, ITEM_TYPE_TRAPPER, ITEM_TYPE_INTERNAL,
											ITEM_TYPE_ZABBIX_ACTIVE, ITEM_TYPE_EXTERNAL, ITEM_TYPE_IPMI, ITEM_TYPE_CALCULATED,
											ITEM_TYPE_SNMPTRAP, ITEM_TYPE_DEPENDENT, ITEM_TYPE_SNMP, ITEM_TYPE_SCRIPT
										]) || (in_array($db_item['type'], [ITEM_TYPE_SIMPLE, ITEM_TYPE_DB_MONITOR, ITEM_TYPE_JMX, ITEM_TYPE_HTTPAGENT]) && $db_item['username'] === '');
									}, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'username')],
									['else' => true, 'type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'username')]
			]],
			'publickey' =>		['type' => API_MULTIPLE, 'rules' => [
									['if' => static function (array $data) use ($db_item): bool {
										return $data['authtype'] == ITEM_AUTHTYPE_PUBLICKEY
											&& ($db_item['type'] != ITEM_TYPE_SSH || $db_item['authtype'] != ITEM_AUTHTYPE_PUBLICKEY);
									}, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'publickey')],
									['if' => ['field' => 'authtype', 'in' => ITEM_AUTHTYPE_PUBLICKEY], 'type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'publickey')],
									['else' => true, 'type' => API_UNEXPECTED]
			]],
			'privatekey' =>		['type' => API_MULTIPLE, 'rules' => [
									['if' => static function (array $data) use ($db_item): bool {
										return $data['authtype'] == ITEM_AUTHTYPE_PUBLICKEY
											&& ($db_item['type'] != ITEM_TYPE_SSH || $db_item['authtype'] != ITEM_AUTHTYPE_PUBLICKEY);
									}, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'privatekey')],
									['if' => ['field' => 'authtype', 'in' => ITEM_AUTHTYPE_PUBLICKEY], 'type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'privatekey')],
									['else' => true, 'type' => API_UNEXPECTED]
			]],
			'password' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'password')],
			'params' =>			['type' => API_MULTIPLE, 'rules' => [
									['if' => static function () use ($db_item): bool {
										return in_array($db_item['type'], [
											ITEM_TYPE_ZABBIX, ITEM_TYPE_TRAPPER, ITEM_TYPE_SIMPLE, ITEM_TYPE_INTERNAL, ITEM_TYPE_ZABBIX_ACTIVE,
											ITEM_TYPE_EXTERNAL, ITEM_TYPE_IPMI, ITEM_TYPE_JMX, ITEM_TYPE_SNMPTRAP, ITEM_TYPE_DEPENDENT,
											ITEM_TYPE_HTTPAGENT, ITEM_TYPE_SNMP
										]);
									}, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'params')],
									['else' => true, 'type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'params')]
			]],
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
		$item += array_intersect_key($db_item, array_flip(['authtype']));

		return [
			'interfaceid' =>	['type' => API_MULTIPLE, 'rules' => [
									['if' => static function () use ($db_item): bool {
										return in_array($db_item['host_status'], [HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED]);
									}, 'type' => API_ID],
									['else' => true, 'type' => API_UNEXPECTED]
			]],
			'authtype' =>		['type' => API_INT32, 'in' => implode(',', [ITEM_AUTHTYPE_PASSWORD, ITEM_AUTHTYPE_PUBLICKEY])],
			'username' =>		['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'username')],
			'publickey' =>		['type' => API_MULTIPLE, 'rules' => [
									['if' => static function (array $data) use ($db_item): bool {
										return $data['authtype'] == ITEM_AUTHTYPE_PUBLICKEY && $db_item['authtype'] != ITEM_AUTHTYPE_PUBLICKEY;
									}, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'publickey')],
									['if' => ['field' => 'authtype', 'in' => ITEM_AUTHTYPE_PUBLICKEY], 'type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'publickey')],
									['else' => true, 'type' => API_UNEXPECTED]
			]],
			'privatekey' =>		['type' => API_MULTIPLE, 'rules' => [
									['if' => static function (array $data) use ($db_item): bool {
										return $data['authtype'] == ITEM_AUTHTYPE_PUBLICKEY && $db_item['authtype'] != ITEM_AUTHTYPE_PUBLICKEY;
									}, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'privatekey')],
									['if' => ['field' => 'authtype', 'in' => ITEM_AUTHTYPE_PUBLICKEY], 'type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'privatekey')],
									['else' => true, 'type' => API_UNEXPECTED]
			]],
			'password' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'password')],
			'params' =>			['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'params')],
			'delay' =>			['type' => API_ITEM_DELAY, 'length' => DB::getFieldLength('items', 'delay')]
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRulesDiscovered(array &$item, array $db_item): array {
		return [
			'interfaceid' =>	['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'authtype' =>		['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'username' =>		['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'publickey' =>		['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'privatekey' =>		['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'password' =>		['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'params' =>			['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'delay' =>			['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED]
		];
	}
}
