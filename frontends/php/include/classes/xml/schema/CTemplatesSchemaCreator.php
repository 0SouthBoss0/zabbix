<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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


class CTemplatesSchemaCreator implements CSchemaCreator {

	public function create() {
		return (new CIndexedArrayXmlTag('templates'))
			->setSchema(
				(new CArrayXmlTag('template'))
					->setSchema(
						(new CIndexedArrayXmlTag('groups'))
							->setRequired()
							->setSchema(
								(new CArrayXmlTag('group'))
									->setRequired()
									->setSchema(
										(new CStringXmlTag('name'))->setRequired()
									)
							),
						(new CStringXmlTag('template'))->setRequired(),
						new CStringXmlTag('description'),
						new CStringXmlTag('name'),
						(new CIndexedArrayXmlTag('applications'))
							->setSchema(
								(new CArrayXmlTag('application'))
									->setSchema(
										(new CStringXmlTag('name'))->setRequired()
									)
							),
						(new CIndexedArrayXmlTag('discovery_rules'))
							->setSchema(
								(new CArrayXmlTag('discovery_rule'))
									->setSchema(
										(new CStringXmlTag('key'))->setRequired(),
										(new CStringXmlTag('name'))->setRequired(),
										(new CStringXmlTag('allow_traps'))
											->setDefaultValue(DB::getDefault('items', 'allow_traps'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
										new CStringXmlTag('allowed_hosts'),
										(new CStringXmlTag('authtype'))
											->setDefaultValue(DB::getDefault('items', 'authtype'))
											->addConstant(CXmlConstantName::NONE, CXmlConstantValue::NONE, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT)
											->addConstant(CXmlConstantName::BASIC, CXmlConstantValue::BASIC, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT)
											->addConstant(CXmlConstantName::NTLM, CXmlConstantValue::NTLM, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT)
											->addConstant(CXmlConstantName::PASSWORD, CXmlConstantValue::PASSWORD, CXmlConstantValue::ITEM_TYPE_SSH)
											->addConstant(CXmlConstantName::PUBLIC_KEY, CXmlConstantValue::PUBLIC_KEY, CXmlConstantValue::ITEM_TYPE_SSH)
											->setExportHandler(function(array $data, CXmlTagInterface $class) {
												if ($data['type'] != CXmlConstantValue::ITEM_TYPE_HTTP_AGENT
													&& $data['type'] != CXmlConstantValue::ITEM_TYPE_SSH) {
													return $data[$class->getTag()];
												}

												return $class->getConstantByValue($data['authtype'], $data['type']);
											})
											->setImportHandler(function(array $data, CXmlTagInterface $class) {
												if (!array_key_exists('authtype', $data)) {
													return (string) CXmlConstantValue::NONE;
												}

												$type = ($data['type'] === CXmlConstantName::HTTP_AGENT
													? CXmlConstantValue::ITEM_TYPE_HTTP_AGENT
													: CXmlConstantValue::ITEM_TYPE_SSH);
												return (string) $class->getConstantValueByName($data['authtype'], $type);
											}),
										// Default value is different from DB default value.
										(new CStringXmlTag('delay'))->setDefaultValue('1m'),
										new CStringXmlTag('description'),
										(new CArrayXmlTag('filter'))
											->setSchema(
												(new CIndexedArrayXmlTag('conditions'))
													->setSchema(
														(new CArrayXmlTag('condition'))
															->setSchema(
																(new CStringXmlTag('formulaid'))->setRequired(),
																(new CStringXmlTag('macro'))->setRequired(),
																(new CStringXmlTag('operator'))
																	->setDefaultValue(DB::getDefault('item_condition', 'operator'))
																	->addConstant(CXmlConstantName::MATCHES_REGEX, CXmlConstantValue::CONDITION_MATCHES_REGEX)
																	->addConstant(CXmlConstantName::NOT_MATCHES_REGEX, CXmlConstantValue::CONDITION_NOT_MATCHES_REGEX),
																new CStringXmlTag('value')
															)
													),
												(new CStringXmlTag('evaltype'))
													->setDefaultValue(DB::getDefault('items', 'evaltype'))
													->addConstant(CXmlConstantName::AND_OR, CXmlConstantValue::AND_OR)
													->addConstant(CXmlConstantName::XML_AND, CXmlConstantValue::XML_AND)
													->addConstant(CXmlConstantName::XML_OR, CXmlConstantValue::XML_OR)
													->addConstant(CXmlConstantName::FORMULA, CXmlConstantValue::FORMULA),
												new CStringXmlTag('formula')
											)->setImportHandler(function(array $data, CXmlTagInterface $class) {
												if (!array_key_exists('filter', $data)) {
													return [
														'conditions' => '',
														'evaltype' => DB::getDefault('items', 'evaltype'),
														'formula' => ''
													];
												}

												return $data['filter'];
											}),
										(new CStringXmlTag('follow_redirects'))
											->setDefaultValue(DB::getDefault('items', 'follow_redirects'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
										(new CIndexedArrayXmlTag('graph_prototypes'))
											->setSchema(
												(new CArrayXmlTag('graph_prototype'))
													->setSchema(
														(new CStringXmlTag('name'))->setRequired(),
														(new CIndexedArrayXmlTag('graph_items'))
															->setRequired()
															->setSchema(
																(new CArrayXmlTag('graph_item'))
																	->setSchema(
																		(new CArrayXmlTag('item'))
																			->setRequired()
																			->setSchema(
																				(new CStringXmlTag('host'))->setRequired(),
																				(new CStringXmlTag('key'))->setRequired()
																			),
																		(new CStringXmlTag('calc_fnc'))
																			->setDefaultValue(DB::getDefault('graphs_items', 'calc_fnc'))
																			->addConstant(CXmlConstantName::MIN, CXmlConstantValue::MIN)
																			->addConstant(CXmlConstantName::AVG, CXmlConstantValue::AVG)
																			->addConstant(CXmlConstantName::MAX, CXmlConstantValue::MAX)
																			->addConstant(CXmlConstantName::ALL, CXmlConstantValue::ALL)
																			->addConstant(CXmlConstantName::LAST, CXmlConstantValue::LAST),
																		new CStringXmlTag('color'),
																		(new CStringXmlTag('drawtype'))
																			->setDefaultValue(DB::getDefault('graphs_items', 'drawtype'))
																			->addConstant(CXmlConstantName::SINGLE_LINE, CXmlConstantValue::SINGLE_LINE)
																			->addConstant(CXmlConstantName::FILLED_REGION, CXmlConstantValue::FILLED_REGION)
																			->addConstant(CXmlConstantName::BOLD_LINE, CXmlConstantValue::BOLD_LINE)
																			->addConstant(CXmlConstantName::DOTTED_LINE, CXmlConstantValue::DOTTED_LINE)
																			->addConstant(CXmlConstantName::DASHED_LINE, CXmlConstantValue::DASHED_LINE)
																			->addConstant(CXmlConstantName::GRADIENT_LINE, CXmlConstantValue::GRADIENT_LINE),
																		(new CStringXmlTag('sortorder'))
																			->setDefaultValue(DB::getDefault('graphs_items', 'sortorder')),
																		(new CStringXmlTag('type'))
																			->setDefaultValue(DB::getDefault('graphs_items', 'type'))
																			->addConstant(CXmlConstantName::SIMPLE, CXmlConstantValue::SIMPLE)
																			->addConstant(CXmlConstantName::GRAPH_SUM, CXmlConstantValue::GRAPH_SUM),
																		(new CStringXmlTag('yaxisside'))
																			->setDefaultValue(DB::getDefault('graphs_items', 'yaxisside'))
																			->addConstant(CXmlConstantName::LEFT, CXmlConstantValue::LEFT)
																			->addConstant(CXmlConstantName::RIGHT, CXmlConstantValue::RIGHT)
																	)
															),
														(new CStringXmlTag('height'))->setDefaultValue(DB::getDefault('graphs', 'height')),
														(new CStringXmlTag('percent_left'))->setDefaultValue(DB::getDefault('graphs', 'percent_left')),
														(new CStringXmlTag('percent_right'))->setDefaultValue(DB::getDefault('graphs', 'percent_right')),
														(new CStringXmlTag('show_3d'))
															->setDefaultValue(DB::getDefault('graphs', 'show_3d'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														(new CStringXmlTag('show_legend'))
															->setDefaultValue(DB::getDefault('graphs', 'show_legend'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														(new CStringXmlTag('show_triggers'))
															->setDefaultValue(DB::getDefault('graphs', 'show_triggers'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														(new CStringXmlTag('show_work_period'))
															->setDefaultValue(DB::getDefault('graphs', 'show_work_period'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														(new CStringXmlTag('type'))
															->setDefaultValue(DB::getDefault('graphs', 'graphtype'))
															->addConstant(CXmlConstantName::NORMAL, CXmlConstantValue::NORMAL)
															->addConstant(CXmlConstantName::STACKED, CXmlConstantValue::STACKED)
															->addConstant(CXmlConstantName::PIE, CXmlConstantValue::PIE)
															->addConstant(CXmlConstantName::EXPLODED, CXmlConstantValue::EXPLODED),
														(new CStringXmlTag('width'))->setDefaultValue(DB::getDefault('graphs', 'width')),
														(new CStringXmlTag('yaxismax'))->setDefaultValue(DB::getDefault('graphs', 'yaxismax')),
														(new CStringXmlTag('yaxismin'))->setDefaultValue(DB::getDefault('graphs', 'yaxismin')),
														(new CStringXmlTag('ymax_item_1'))
															->setDefaultValue('0')
															->setExportHandler(function(array $data, CXmlTagInterface $class) {
																if ($data['ymax_type_1'] == 2) {
																	if (array_key_exists('ymax_item_1', $data)) {
																		if (!array_key_exists('host', $data['ymax_item_1']) &&
																			!array_key_exists('key', $data['ymax_item_1'])) {
																			throw new Exception(
																				_s('Invalid tag "%1$s": %2$s.',
																					'/zabbix_export/templates/template/discovery_rules/discovery_rule/graph_prototypes/graph_prototype/ymax_item_1',
																					_('an array is expected'))
																			);
																		}
																	}
																}

																return $data['ymax_item_1'];
															}),
														(new CStringXmlTag('ymax_type_1'))
															->setDefaultValue(DB::getDefault('graphs', 'ymax_type'))
															->addConstant(CXmlConstantName::CALCULATED, CXmlConstantValue::CALCULATED)
															->addConstant(CXmlConstantName::FIXED, CXmlConstantValue::FIXED)
															->addConstant(CXmlConstantName::ITEM, CXmlConstantValue::ITEM),
														(new CStringXmlTag('ymin_item_1'))
															->setDefaultValue('0')
															->setExportHandler(function(array $data, CXmlTagInterface $class) {
																if ($data['ymin_type_1'] == 2) {
																	if (array_key_exists('ymin_item_1', $data)) {
																		if (!array_key_exists('host', $data['ymin_item_1']) &&
																			!array_key_exists('key', $data['ymin_item_1'])) {
																			throw new Exception(
																				_s('Invalid tag "%1$s": %2$s.',
																					'/zabbix_export/templates/template/discovery_rules/discovery_rule/graph_prototypes/graph_prototype/ymin_item_1',
																					_('an array is expected'))
																			);
																		}
																	}
																}

																return $data['ymax_item_1'];
															}),
														(new CStringXmlTag('ymin_type_1'))
															->setDefaultValue(DB::getDefault('graphs', 'ymin_type'))
															->addConstant(CXmlConstantName::CALCULATED, CXmlConstantValue::CALCULATED)
															->addConstant(CXmlConstantName::FIXED, CXmlConstantValue::FIXED)
															->addConstant(CXmlConstantName::ITEM, CXmlConstantValue::ITEM)
													)
											),
										(new CIndexedArrayXmlTag('headers'))
											->setSchema(
												(new CArrayXmlTag('header'))
													->setSchema(
														(new CStringXmlTag('name'))->setRequired(),
														(new CStringXmlTag('value'))->setRequired()
													)
											),
										(new CIndexedArrayXmlTag('host_prototypes'))
											->setSchema(
												(new CArrayXmlTag('host_prototype'))
													->setSchema(
														(new CIndexedArrayXmlTag('group_links'))
															->setSchema(
																(new CArrayXmlTag('group_link'))
																	->setSchema(
																		(new CArrayXmlTag('group'))
																			->setSchema(
																				(new CStringXmlTag('name'))->setRequired()
																			)
																	)
															),
														(new CIndexedArrayXmlTag('group_prototypes'))
															->setSchema(
																(new CArrayXmlTag('group_prototype'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired()
																	)
															),
														(new CStringXmlTag('host'))->setRequired(),
														new CStringXmlTag('name'),
														(new CStringXmlTag('status'))
															->setDefaultValue(DB::getDefault('hosts', 'status'))
															->addConstant(CXmlConstantName::ENABLED, CXmlConstantValue::ENABLED)
															->addConstant(CXmlConstantName::DISABLED, CXmlConstantValue::DISABLED),
														(new CIndexedArrayXmlTag('templates'))
															->setSchema(
																(new CArrayXmlTag('template'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired()
																	)
															)
													)
											),
										new CStringXmlTag('http_proxy'),
										new CStringXmlTag('ipmi_sensor'),
										(new CIndexedArrayXmlTag('item_prototypes'))
											->setSchema(
												(new CArrayXmlTag('item_prototype'))
													->setSchema(
														(new CStringXmlTag('key'))->setRequired(),
														(new CStringXmlTag('name'))->setRequired(),
														(new CStringXmlTag('allow_traps'))
															->setDefaultValue(DB::getDefault('items', 'allow_traps'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														new CStringXmlTag('allowed_hosts'),
														(new CIndexedArrayXmlTag('applications'))
															->setSchema(
																(new CArrayXmlTag('application'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired()
																	)
															),
														(new CStringXmlTag('authtype'))
															->setDefaultValue(DB::getDefault('items', 'authtype'))
															->addConstant(CXmlConstantName::NONE, CXmlConstantValue::NONE, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT)
															->addConstant(CXmlConstantName::BASIC, CXmlConstantValue::BASIC, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT)
															->addConstant(CXmlConstantName::NTLM, CXmlConstantValue::NTLM, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT)
															->addConstant(CXmlConstantName::PASSWORD, CXmlConstantValue::PASSWORD, CXmlConstantValue::ITEM_TYPE_SSH)
															->addConstant(CXmlConstantName::PUBLIC_KEY, CXmlConstantValue::PUBLIC_KEY, CXmlConstantValue::ITEM_TYPE_SSH)
															->setExportHandler(function(array $data, CXmlTagInterface $class) {
																if ($data['type'] != CXmlConstantValue::ITEM_TYPE_HTTP_AGENT
																	&& $data['type'] != CXmlConstantValue::ITEM_TYPE_SSH) {
																	return $data[$class->getTag()];
																}

																return $class->getConstantByValue($data['authtype'], $data['type']);
															})
															->setImportHandler(function(array $data, CXmlTagInterface $class) {
																if (!array_key_exists('authtype', $data)) {
																	return (string) CXmlConstantValue::NONE;
																}

																$type = ($data['type'] === CXmlConstantName::HTTP_AGENT
																	? CXmlConstantValue::ITEM_TYPE_HTTP_AGENT
																	: CXmlConstantValue::ITEM_TYPE_SSH);
																return (string) $class->getConstantValueByName($data['authtype'], $type);
															}),
														// Default value is different from DB default value.
														(new CStringXmlTag('delay'))->setDefaultValue('1m'),
														new CStringXmlTag('description'),
														(new CStringXmlTag('follow_redirects'))
															->setDefaultValue(DB::getDefault('items', 'follow_redirects'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														(new CIndexedArrayXmlTag('headers'))
															->setSchema(
																(new CArrayXmlTag('header'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired(),
																		(new CStringXmlTag('value'))->setRequired()
																	)
															),
														(new CStringXmlTag('history'))->setDefaultValue(DB::getDefault('items', 'history')),
														new CStringXmlTag('http_proxy'),
														(new CStringXmlTag('inventory_link'))
															->setDefaultValue(DB::getDefault('items', 'inventory_link'))
															->addConstant(CXmlConstantName::NONE, CXmlConstantValue::NONE)
															->addConstant(CXmlConstantName::ALIAS, CXmlConstantValue::ALIAS)
															->addConstant(CXmlConstantName::ASSET_TAG, CXmlConstantValue::ASSET_TAG)
															->addConstant(CXmlConstantName::CHASSIS, CXmlConstantValue::CHASSIS)
															->addConstant(CXmlConstantName::CONTACT, CXmlConstantValue::CONTACT)
															->addConstant(CXmlConstantName::CONTRACT_NUMBER, CXmlConstantValue::CONTRACT_NUMBER)
															->addConstant(CXmlConstantName::DATE_HW_DECOMM, CXmlConstantValue::DATE_HW_DECOMM)
															->addConstant(CXmlConstantName::DATE_HW_EXPIRY, CXmlConstantValue::DATE_HW_EXPIRY)
															->addConstant(CXmlConstantName::DATE_HW_INSTALL, CXmlConstantValue::DATE_HW_INSTALL)
															->addConstant(CXmlConstantName::DATE_HW_PURCHASE, CXmlConstantValue::DATE_HW_PURCHASE)
															->addConstant(CXmlConstantName::DEPLOYMENT_STATUS, CXmlConstantValue::DEPLOYMENT_STATUS)
															->addConstant(CXmlConstantName::HARDWARE, CXmlConstantValue::HARDWARE)
															->addConstant(CXmlConstantName::HARDWARE_FULL, CXmlConstantValue::HARDWARE_FULL)
															->addConstant(CXmlConstantName::HOST_NETMASK, CXmlConstantValue::HOST_NETMASK)
															->addConstant(CXmlConstantName::HOST_NETWORKS, CXmlConstantValue::HOST_NETWORKS)
															->addConstant(CXmlConstantName::HOST_ROUTER, CXmlConstantValue::HOST_ROUTER)
															->addConstant(CXmlConstantName::HW_ARCH, CXmlConstantValue::HW_ARCH)
															->addConstant(CXmlConstantName::INSTALLER_NAME, CXmlConstantValue::INSTALLER_NAME)
															->addConstant(CXmlConstantName::LOCATION, CXmlConstantValue::LOCATION)
															->addConstant(CXmlConstantName::LOCATION_LAT, CXmlConstantValue::LOCATION_LAT)
															->addConstant(CXmlConstantName::LOCATION_LON, CXmlConstantValue::LOCATION_LON)
															->addConstant(CXmlConstantName::MACADDRESS_A, CXmlConstantValue::MACADDRESS_A)
															->addConstant(CXmlConstantName::MACADDRESS_B, CXmlConstantValue::MACADDRESS_B)
															->addConstant(CXmlConstantName::MODEL, CXmlConstantValue::MODEL)
															->addConstant(CXmlConstantName::NAME, CXmlConstantValue::NAME)
															->addConstant(CXmlConstantName::NOTES, CXmlConstantValue::NOTES)
															->addConstant(CXmlConstantName::OOB_IP, CXmlConstantValue::OOB_IP)
															->addConstant(CXmlConstantName::OOB_NETMASK, CXmlConstantValue::OOB_NETMASK)
															->addConstant(CXmlConstantName::OOB_ROUTER, CXmlConstantValue::OOB_ROUTER)
															->addConstant(CXmlConstantName::OS, CXmlConstantValue::OS)
															->addConstant(CXmlConstantName::OS_FULL, CXmlConstantValue::OS_FULL)
															->addConstant(CXmlConstantName::OS_SHORT, CXmlConstantValue::OS_SHORT)
															->addConstant(CXmlConstantName::POC_1_CELL, CXmlConstantValue::POC_1_CELL)
															->addConstant(CXmlConstantName::POC_1_EMAIL, CXmlConstantValue::POC_1_EMAIL)
															->addConstant(CXmlConstantName::POC_1_NAME, CXmlConstantValue::POC_1_NAME)
															->addConstant(CXmlConstantName::POC_1_NOTES, CXmlConstantValue::POC_1_NOTES)
															->addConstant(CXmlConstantName::POC_1_PHONE_A, CXmlConstantValue::POC_1_PHONE_A)
															->addConstant(CXmlConstantName::POC_1_PHONE_B, CXmlConstantValue::POC_1_PHONE_B)
															->addConstant(CXmlConstantName::POC_1_SCREEN, CXmlConstantValue::POC_1_SCREEN)
															->addConstant(CXmlConstantName::POC_2_CELL, CXmlConstantValue::POC_2_CELL)
															->addConstant(CXmlConstantName::POC_2_EMAIL, CXmlConstantValue::POC_2_EMAIL)
															->addConstant(CXmlConstantName::POC_2_NAME, CXmlConstantValue::POC_2_NAME)
															->addConstant(CXmlConstantName::POC_2_NOTES, CXmlConstantValue::POC_2_NOTES)
															->addConstant(CXmlConstantName::POC_2_PHONE_A, CXmlConstantValue::POC_2_PHONE_A)
															->addConstant(CXmlConstantName::POC_2_PHONE_B, CXmlConstantValue::POC_2_PHONE_B)
															->addConstant(CXmlConstantName::POC_2_SCREEN, CXmlConstantValue::POC_2_SCREEN)
															->addConstant(CXmlConstantName::SERIALNO_A, CXmlConstantValue::SERIALNO_A)
															->addConstant(CXmlConstantName::SERIALNO_B, CXmlConstantValue::SERIALNO_B)
															->addConstant(CXmlConstantName::SITE_ADDRESS_A, CXmlConstantValue::SITE_ADDRESS_A)
															->addConstant(CXmlConstantName::SITE_ADDRESS_B, CXmlConstantValue::SITE_ADDRESS_B)
															->addConstant(CXmlConstantName::SITE_ADDRESS_C, CXmlConstantValue::SITE_ADDRESS_C)
															->addConstant(CXmlConstantName::SITE_CITY, CXmlConstantValue::SITE_CITY)
															->addConstant(CXmlConstantName::SITE_COUNTRY, CXmlConstantValue::SITE_COUNTRY)
															->addConstant(CXmlConstantName::SITE_NOTES, CXmlConstantValue::SITE_NOTES)
															->addConstant(CXmlConstantName::SITE_RACK, CXmlConstantValue::SITE_RACK)
															->addConstant(CXmlConstantName::SITE_STATE, CXmlConstantValue::SITE_STATE)
															->addConstant(CXmlConstantName::SITE_ZIP, CXmlConstantValue::SITE_ZIP)
															->addConstant(CXmlConstantName::SOFTWARE, CXmlConstantValue::SOFTWARE)
															->addConstant(CXmlConstantName::SOFTWARE_APP_A, CXmlConstantValue::SOFTWARE_APP_A)
															->addConstant(CXmlConstantName::SOFTWARE_APP_B, CXmlConstantValue::SOFTWARE_APP_B)
															->addConstant(CXmlConstantName::SOFTWARE_APP_C, CXmlConstantValue::SOFTWARE_APP_C)
															->addConstant(CXmlConstantName::SOFTWARE_APP_D, CXmlConstantValue::SOFTWARE_APP_D)
															->addConstant(CXmlConstantName::SOFTWARE_APP_E, CXmlConstantValue::SOFTWARE_APP_E)
															->addConstant(CXmlConstantName::SOFTWARE_FULL, CXmlConstantValue::SOFTWARE_FULL)
															->addConstant(CXmlConstantName::TAG, CXmlConstantValue::TAG)
															->addConstant(CXmlConstantName::TYPE, CXmlConstantValue::TYPE)
															->addConstant(CXmlConstantName::TYPE_FULL, CXmlConstantValue::TYPE_FULL)
															->addConstant(CXmlConstantName::URL_A, CXmlConstantValue::URL_A)
															->addConstant(CXmlConstantName::URL_B, CXmlConstantValue::URL_B)
															->addConstant(CXmlConstantName::URL_C, CXmlConstantValue::URL_C)
															->addConstant(CXmlConstantName::VENDOR, CXmlConstantValue::VENDOR),
														new CStringXmlTag('ipmi_sensor'),
														new CStringXmlTag('jmx_endpoint'),
														new CStringXmlTag('logtimefmt'),
														(new CArrayXmlTag('master_item'))
															->setSchema(
																(new CStringXmlTag('key'))->setRequired()
															),
														(new CStringXmlTag('output_format'))
															->setDefaultValue(DB::getDefault('items', 'output_format'))
															->addConstant(CXmlConstantName::RAW, CXmlConstantValue::RAW)
															->addConstant(CXmlConstantName::JSON, CXmlConstantValue::JSON),
														new CStringXmlTag('params'),
														new CStringXmlTag('password'),
														new CStringXmlTag('port'),
														(new CStringXmlTag('post_type'))
															->setDefaultValue(DB::getDefault('items', 'post_type'))
															->addConstant(CXmlConstantName::RAW, CXmlConstantValue::RAW)
															->addConstant(CXmlConstantName::JSON, CXmlConstantValue::JSON)
															->addConstant(CXmlConstantName::XML, CXmlConstantValue::XML),
														new CStringXmlTag('posts'),
														(new CIndexedArrayXmlTag('preprocessing'))
															->setSchema(
																(new CArrayXmlTag('step'))
																	->setRequired()
																	->setSchema(
																		(new CStringXmlTag('params'))->setRequired(),
																		(new CStringXmlTag('type'))
																			->setRequired()
																			->addConstant(CXmlConstantName::MULTIPLIER, CXmlConstantValue::MULTIPLIER)
																			->addConstant(CXmlConstantName::RTRIM, CXmlConstantValue::RTRIM)
																			->addConstant(CXmlConstantName::LTRIM, CXmlConstantValue::LTRIM)
																			->addConstant(CXmlConstantName::TRIM, CXmlConstantValue::TRIM)
																			->addConstant(CXmlConstantName::REGEX, CXmlConstantValue::REGEX)
																			->addConstant(CXmlConstantName::BOOL_TO_DECIMAL, CXmlConstantValue::BOOL_TO_DECIMAL)
																			->addConstant(CXmlConstantName::OCTAL_TO_DECIMAL, CXmlConstantValue::OCTAL_TO_DECIMAL)
																			->addConstant(CXmlConstantName::HEX_TO_DECIMAL, CXmlConstantValue::HEX_TO_DECIMAL)
																			->addConstant(CXmlConstantName::SIMPLE_CHANGE, CXmlConstantValue::SIMPLE_CHANGE)
																			->addConstant(CXmlConstantName::CHANGE_PER_SECOND, CXmlConstantValue::CHANGE_PER_SECOND)
																			->addConstant(CXmlConstantName::XMLPATH, CXmlConstantValue::XMLPATH)
																			->addConstant(CXmlConstantName::JSONPATH, CXmlConstantValue::JSONPATH)
																			->addConstant(CXmlConstantName::IN_RANGE, CXmlConstantValue::IN_RANGE)
																			->addConstant(CXmlConstantName::MATCHES_REGEX, CXmlConstantValue::MATCHES_REGEX)
																			->addConstant(CXmlConstantName::NOT_MATCHES_REGEX, CXmlConstantValue::NOT_MATCHES_REGEX)
																			->addConstant(CXmlConstantName::CHECK_JSON_ERROR, CXmlConstantValue::CHECK_JSON_ERROR)
																			->addConstant(CXmlConstantName::CHECK_XML_ERROR, CXmlConstantValue::CHECK_XML_ERROR)
																			->addConstant(CXmlConstantName::CHECK_REGEX_ERROR, CXmlConstantValue::CHECK_REGEX_ERROR)
																			->addConstant(CXmlConstantName::DISCARD_UNCHANGED, CXmlConstantValue::DISCARD_UNCHANGED)
																			->addConstant(CXmlConstantName::DISCARD_UNCHANGED_HEARTBEAT, CXmlConstantValue::DISCARD_UNCHANGED_HEARTBEAT)
																			->addConstant(CXmlConstantName::JAVASCRIPT, CXmlConstantValue::JAVASCRIPT)
																			->addConstant(CXmlConstantName::PROMETHEUS_PATTERN, CXmlConstantValue::PROMETHEUS_PATTERN)
																			->addConstant(CXmlConstantName::PROMETHEUS_TO_JSON, CXmlConstantValue::PROMETHEUS_TO_JSON),
																		(new CStringXmlTag('error_handler'))
																			->setDefaultValue(DB::getDefault('item_preproc', 'error_handler'))
																			->addConstant(CXmlConstantName::ORIGINAL_ERROR, CXmlConstantValue::ORIGINAL_ERROR)
																			->addConstant(CXmlConstantName::DISCARD_VALUE, CXmlConstantValue::DISCARD_VALUE)
																			->addConstant(CXmlConstantName::CUSTOM_VALUE, CXmlConstantValue::CUSTOM_VALUE)
																			->addConstant(CXmlConstantName::CUSTOM_ERROR, CXmlConstantValue::CUSTOM_ERROR),
																		new CStringXmlTag('error_handler_params')
																	)
															),
														new CStringXmlTag('privatekey'),
														new CStringXmlTag('publickey'),
														(new CIndexedArrayXmlTag('query_fields'))
															->setSchema(
																(new CArrayXmlTag('query_field'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired(),
																		new CStringXmlTag('value')
																	)
															),
														(new CStringXmlTag('request_method'))
															->setDefaultValue(DB::getDefault('items', 'request_method'))
															->addConstant(CXmlConstantName::GET, CXmlConstantValue::GET)
															->addConstant(CXmlConstantName::POST, CXmlConstantValue::POST)
															->addConstant(CXmlConstantName::PUT, CXmlConstantValue::PUT)
															->addConstant(CXmlConstantName::HEAD, CXmlConstantValue::HEAD),
														(new CStringXmlTag('retrieve_mode'))
															->setDefaultValue(DB::getDefault('items', 'retrieve_mode'))
															->addConstant(CXmlConstantName::BODY, CXmlConstantValue::BODY)
															->addConstant(CXmlConstantName::HEADERS, CXmlConstantValue::HEADERS)
															->addConstant(CXmlConstantName::BOTH, CXmlConstantValue::BOTH),
														new CStringXmlTag('snmp_community'),
														new CStringXmlTag('snmp_oid'),
														new CStringXmlTag('snmpv3_authpassphrase'),
														(new CStringXmlTag('snmpv3_authprotocol'))
															->setDefaultValue(DB::getDefault('items', 'snmpv3_authprotocol'))
															->addConstant(CXmlConstantName::MD5, CXmlConstantValue::SNMPV3_MD5)
															->addConstant(CXmlConstantName::SHA, CXmlConstantValue::SNMPV3_SHA),
														new CStringXmlTag('snmpv3_contextname'),
														new CStringXmlTag('snmpv3_privpassphrase'),
														(new CStringXmlTag('snmpv3_privprotocol'))
															->setDefaultValue(DB::getDefault('items', 'snmpv3_privprotocol'))
															->addConstant(CXmlConstantName::DES, CXmlConstantValue::DES)
															->addConstant(CXmlConstantName::AES, CXmlConstantValue::AES),
														(new CStringXmlTag('snmpv3_securitylevel'))
															->setDefaultValue(DB::getDefault('items', 'snmpv3_securitylevel'))
															->addConstant(CXmlConstantName::NOAUTHNOPRIV, CXmlConstantValue::NOAUTHNOPRIV)
															->addConstant(CXmlConstantName::AUTHNOPRIV, CXmlConstantValue::AUTHNOPRIV)
															->addConstant(CXmlConstantName::AUTHPRIV, CXmlConstantValue::AUTHPRIV),
														new CStringXmlTag('snmpv3_securityname'),
														new CStringXmlTag('ssl_cert_file'),
														new CStringXmlTag('ssl_key_file'),
														new CStringXmlTag('ssl_key_password'),
														(new CStringXmlTag('status'))
															->setDefaultValue(DB::getDefault('items', 'status'))
															->addConstant(CXmlConstantName::ENABLED, CXmlConstantValue::ENABLED)
															->addConstant(CXmlConstantName::DISABLED, CXmlConstantValue::DISABLED),
														new CStringXmlTag('status_codes'),
														new CStringXmlTag('timeout'),
														(new CStringXmlTag('trends'))->setDefaultValue(DB::getDefault('items', 'trends')),
														(new CStringXmlTag('type'))
															->setDefaultValue(DB::getDefault('items', 'type'))
															->addConstant(CXmlConstantName::ZABBIX_PASSIVE, CXmlConstantValue::ITEM_TYPE_ZABBIX_PASSIVE)
															->addConstant(CXmlConstantName::SNMPV1, CXmlConstantValue::ITEM_TYPE_SNMPV1)
															->addConstant(CXmlConstantName::TRAP, CXmlConstantValue::ITEM_TYPE_TRAP)
															->addConstant(CXmlConstantName::SIMPLE, CXmlConstantValue::ITEM_TYPE_SIMPLE)
															->addConstant(CXmlConstantName::SNMPV2, CXmlConstantValue::ITEM_TYPE_SNMPV2)
															->addConstant(CXmlConstantName::INTERNAL, CXmlConstantValue::ITEM_TYPE_INTERNAL)
															->addConstant(CXmlConstantName::SNMPV3, CXmlConstantValue::ITEM_TYPE_SNMPV3)
															->addConstant(CXmlConstantName::ZABBIX_ACTIVE, CXmlConstantValue::ITEM_TYPE_ZABBIX_ACTIVE)
															->addConstant(CXmlConstantName::AGGREGATE, CXmlConstantValue::ITEM_TYPE_AGGREGATE)
															->addConstant(CXmlConstantName::EXTERNAL, CXmlConstantValue::ITEM_TYPE_EXTERNAL)
															->addConstant(CXmlConstantName::ODBC, CXmlConstantValue::ITEM_TYPE_ODBC)
															->addConstant(CXmlConstantName::IPMI, CXmlConstantValue::ITEM_TYPE_IPMI)
															->addConstant(CXmlConstantName::SSH, CXmlConstantValue::ITEM_TYPE_SSH)
															->addConstant(CXmlConstantName::TELNET, CXmlConstantValue::ITEM_TYPE_TELNET)
															->addConstant(CXmlConstantName::CALCULATED, CXmlConstantValue::ITEM_TYPE_CALCULATED)
															->addConstant(CXmlConstantName::JMX, CXmlConstantValue::ITEM_TYPE_JMX)
															->addConstant(CXmlConstantName::SNMP_TRAP, CXmlConstantValue::ITEM_TYPE_SNMP_TRAP)
															->addConstant(CXmlConstantName::DEPENDENT, CXmlConstantValue::ITEM_TYPE_DEPENDENT)
															->addConstant(CXmlConstantName::HTTP_AGENT, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT),
														new CStringXmlTag('units'),
														new CStringXmlTag('url'),
														new CStringXmlTag('username'),
														(new CStringXmlTag('value_type'))
															// Default value is different from DB default value.
															->setDefaultValue(CXmlConstantValue::UNSIGNED)
															->addConstant(CXmlConstantName::FLOAT, CXmlConstantValue::FLOAT)
															->addConstant(CXmlConstantName::CHAR, CXmlConstantValue::CHAR)
															->addConstant(CXmlConstantName::LOG, CXmlConstantValue::LOG)
															->addConstant(CXmlConstantName::UNSIGNED, CXmlConstantValue::UNSIGNED)
															->addConstant(CXmlConstantName::TEXT, CXmlConstantValue::TEXT),
														(new CArrayXmlTag('valuemap'))
															->setSchema(
																new CStringXmlTag('name')
															),
														(new CStringXmlTag('verify_host'))
															->setDefaultValue(DB::getDefault('items', 'verify_host'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														(new CStringXmlTag('verify_peer'))
															->setDefaultValue(DB::getDefault('items', 'verify_peer'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														(new CIndexedArrayXmlTag('application_prototypes'))
															->setSchema(
																(new CArrayXmlTag('application_prototype'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired()
																	)
															)
													)
											),
										new CStringXmlTag('jmx_endpoint'),
										(new CStringXmlTag('lifetime'))->setDefaultValue(DB::getDefault('items', 'lifetime')),
										(new CIndexedArrayXmlTag('lld_macro_paths'))
											->setSchema(
												(new CArrayXmlTag('lld_macro_path'))
													->setSchema(
														(new CStringXmlTag('lld_macro'))->setRequired(),
														(new CStringXmlTag('path'))->setRequired()
													)
											),
										(new CArrayXmlTag('master_item'))
											->setSchema(
												(new CStringXmlTag('key'))->setRequired()
											),
										new CStringXmlTag('params'),
										new CStringXmlTag('password'),
										new CStringXmlTag('port'),
										(new CStringXmlTag('post_type'))
											->setDefaultValue(DB::getDefault('items', 'post_type'))
											->addConstant(CXmlConstantName::RAW, CXmlConstantValue::RAW)
											->addConstant(CXmlConstantName::JSON, CXmlConstantValue::JSON)
											->addConstant(CXmlConstantName::XML, CXmlConstantValue::XML),
										new CStringXmlTag('posts'),
										(new CIndexedArrayXmlTag('preprocessing'))
											->setSchema(
												(new CArrayXmlTag('step'))
													->setRequired()
													->setSchema(
														(new CStringXmlTag('params'))->setRequired(),
														(new CStringXmlTag('type'))
															->setRequired()
															->addConstant(CXmlConstantName::REGEX, CXmlConstantValue::REGEX)
															->addConstant(CXmlConstantName::JSONPATH, CXmlConstantValue::JSONPATH)
															->addConstant(CXmlConstantName::NOT_MATCHES_REGEX, CXmlConstantValue::NOT_MATCHES_REGEX)
															->addConstant(CXmlConstantName::CHECK_JSON_ERROR, CXmlConstantValue::CHECK_JSON_ERROR)
															->addConstant(CXmlConstantName::DISCARD_UNCHANGED_HEARTBEAT, CXmlConstantValue::DISCARD_UNCHANGED_HEARTBEAT)
															->addConstant(CXmlConstantName::JAVASCRIPT, CXmlConstantValue::JAVASCRIPT)
															->addConstant(CXmlConstantName::PROMETHEUS_TO_JSON, CXmlConstantValue::PROMETHEUS_TO_JSON),
														(new CStringXmlTag('error_handler'))
															->setDefaultValue(DB::getDefault('item_preproc', 'error_handler'))
															->addConstant(CXmlConstantName::ORIGINAL_ERROR, CXmlConstantValue::ORIGINAL_ERROR)
															->addConstant(CXmlConstantName::DISCARD_VALUE, CXmlConstantValue::DISCARD_VALUE)
															->addConstant(CXmlConstantName::CUSTOM_VALUE, CXmlConstantValue::CUSTOM_VALUE)
															->addConstant(CXmlConstantName::CUSTOM_ERROR, CXmlConstantValue::CUSTOM_ERROR),
														new CStringXmlTag('error_handler_params')
													)
											),
										new CStringXmlTag('privatekey'),
										new CStringXmlTag('publickey'),
										(new CIndexedArrayXmlTag('query_fields'))
											->setSchema(
												(new CArrayXmlTag('query_field'))
													->setSchema(
														(new CStringXmlTag('name'))->setRequired(),
														new CStringXmlTag('value')
													)
											),
										(new CStringXmlTag('request_method'))
											->setDefaultValue(DB::getDefault('items', 'request_method'))
											->addConstant(CXmlConstantName::GET, CXmlConstantValue::GET)
											->addConstant(CXmlConstantName::POST, CXmlConstantValue::POST)
											->addConstant(CXmlConstantName::PUT, CXmlConstantValue::PUT)
											->addConstant(CXmlConstantName::HEAD, CXmlConstantValue::HEAD),
										(new CStringXmlTag('retrieve_mode'))
											->setDefaultValue(DB::getDefault('items', 'retrieve_mode'))
											->addConstant(CXmlConstantName::BODY, CXmlConstantValue::BODY)
											->addConstant(CXmlConstantName::HEADERS, CXmlConstantValue::HEADERS)
											->addConstant(CXmlConstantName::BOTH, CXmlConstantValue::BOTH),
										new CStringXmlTag('snmp_community'),
										new CStringXmlTag('snmp_oid'),
										new CStringXmlTag('snmpv3_authpassphrase'),
										(new CStringXmlTag('snmpv3_authprotocol'))
											->setDefaultValue(DB::getDefault('items', 'snmpv3_authprotocol'))
											->addConstant(CXmlConstantName::MD5, CXmlConstantValue::SNMPV3_MD5)
											->addConstant(CXmlConstantName::SHA, CXmlConstantValue::SNMPV3_SHA),
										new CStringXmlTag('snmpv3_contextname'),
										new CStringXmlTag('snmpv3_privpassphrase'),
										(new CStringXmlTag('snmpv3_privprotocol'))
											->setDefaultValue(DB::getDefault('items', 'snmpv3_privprotocol'))
											->addConstant(CXmlConstantName::DES, CXmlConstantValue::DES)
											->addConstant(CXmlConstantName::AES, CXmlConstantValue::AES),
										(new CStringXmlTag('snmpv3_securitylevel'))
											->setDefaultValue(DB::getDefault('items', 'snmpv3_securitylevel'))
											->addConstant(CXmlConstantName::NOAUTHNOPRIV, CXmlConstantValue::NOAUTHNOPRIV)
											->addConstant(CXmlConstantName::AUTHNOPRIV, CXmlConstantValue::AUTHNOPRIV)
											->addConstant(CXmlConstantName::AUTHPRIV, CXmlConstantValue::AUTHPRIV),
										new CStringXmlTag('snmpv3_securityname'),
										new CStringXmlTag('ssl_cert_file'),
										new CStringXmlTag('ssl_key_file'),
										new CStringXmlTag('ssl_key_password'),
										(new CStringXmlTag('status'))
											->setDefaultValue(DB::getDefault('items', 'status'))
											->addConstant(CXmlConstantName::ENABLED, CXmlConstantValue::ENABLED)
											->addConstant(CXmlConstantName::DISABLED, CXmlConstantValue::DISABLED),
										new CStringXmlTag('status_codes'),
										new CStringXmlTag('timeout'),
										(new CIndexedArrayXmlTag('trigger_prototypes'))
											->setSchema(
												(new CArrayXmlTag('trigger_prototype'))
													->setSchema(
														(new CStringXmlTag('expression'))->setRequired(),
														(new CStringXmlTag('name'))->setRequired(),
														(new CStringXmlTag('correlation_mode'))
															->setDefaultValue(DB::getDefault('triggers', 'correlation_mode'))
															->addConstant(CXmlConstantName::DISABLED, CXmlConstantValue::TRIGGER_DISABLED)
															->addConstant(CXmlConstantName::TAG_VALUE, CXmlConstantValue::TRIGGER_TAG_VALUE),
														new CStringXmlTag('correlation_tag'),
														(new CIndexedArrayXmlTag('dependencies'))
															->setSchema(
																(new CArrayXmlTag('dependency'))
																	->setSchema(
																		(new CStringXmlTag('expression'))->setRequired(),
																		(new CStringXmlTag('name'))->setRequired(),
																		new CStringXmlTag('recovery_expression')
																	)
															),
														new CStringXmlTag('description'),
														(new CStringXmlTag('manual_close'))
															->setDefaultValue(DB::getDefault('triggers', 'manual_close'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														(new CStringXmlTag('priority'))
															->setDefaultValue(DB::getDefault('triggers', 'priority'))
															->addConstant(CXmlConstantName::NOT_CLASSIFIED, CXmlConstantValue::NOT_CLASSIFIED)
															->addConstant(CXmlConstantName::INFO, CXmlConstantValue::INFO)
															->addConstant(CXmlConstantName::WARNING, CXmlConstantValue::WARNING)
															->addConstant(CXmlConstantName::AVERAGE, CXmlConstantValue::AVERAGE)
															->addConstant(CXmlConstantName::HIGH, CXmlConstantValue::HIGH)
															->addConstant(CXmlConstantName::DISASTER, CXmlConstantValue::DISASTER),
														new CStringXmlTag('recovery_expression'),
														(new CStringXmlTag('recovery_mode'))
															->setDefaultValue(DB::getDefault('triggers', 'recovery_mode'))
															->addConstant(CXmlConstantName::EXPRESSION, CXmlConstantValue::TRIGGER_EXPRESSION)
															->addConstant(CXmlConstantName::RECOVERY_EXPRESSION, CXmlConstantValue::TRIGGER_RECOVERY_EXPRESSION)
															->addConstant(CXmlConstantName::NONE, CXmlConstantValue::TRIGGER_NONE),
														(new CStringXmlTag('status'))
															->setDefaultValue(DB::getDefault('triggers', 'status'))
															->addConstant(CXmlConstantName::ENABLED, CXmlConstantValue::ENABLED)
															->addConstant(CXmlConstantName::DISABLED, CXmlConstantValue::DISABLED),
														(new CIndexedArrayXmlTag('tags'))
															->setSchema(
																(new CArrayXmlTag('tag'))
																	->setSchema(
																		(new CStringXmlTag('tag'))->setRequired(),
																		new CStringXmlTag('value')
																	)
															),
														(new CStringXmlTag('type'))
															->setDefaultValue(DB::getDefault('triggers', 'type'))
															->addConstant(CXmlConstantName::SINGLE, CXmlConstantValue::SINGLE)
															->addConstant(CXmlConstantName::MULTIPLE, CXmlConstantValue::MULTIPLE),
														new CStringXmlTag('url')
													)
											),
										(new CStringXmlTag('type'))
											->setDefaultValue(DB::getDefault('items', 'type'))
											->addConstant(CXmlConstantName::ZABBIX_PASSIVE, CXmlConstantValue::ITEM_TYPE_ZABBIX_PASSIVE)
											->addConstant(CXmlConstantName::SNMPV1, CXmlConstantValue::ITEM_TYPE_SNMPV1)
											->addConstant(CXmlConstantName::TRAP, CXmlConstantValue::ITEM_TYPE_TRAP)
											->addConstant(CXmlConstantName::SIMPLE, CXmlConstantValue::ITEM_TYPE_SIMPLE)
											->addConstant(CXmlConstantName::SNMPV2, CXmlConstantValue::ITEM_TYPE_SNMPV2)
											->addConstant(CXmlConstantName::INTERNAL, CXmlConstantValue::ITEM_TYPE_INTERNAL)
											->addConstant(CXmlConstantName::SNMPV3, CXmlConstantValue::ITEM_TYPE_SNMPV3)
											->addConstant(CXmlConstantName::ZABBIX_ACTIVE, CXmlConstantValue::ITEM_TYPE_ZABBIX_ACTIVE)
											->addConstant(CXmlConstantName::EXTERNAL, CXmlConstantValue::ITEM_TYPE_EXTERNAL)
											->addConstant(CXmlConstantName::ODBC, CXmlConstantValue::ITEM_TYPE_ODBC)
											->addConstant(CXmlConstantName::IPMI, CXmlConstantValue::ITEM_TYPE_IPMI)
											->addConstant(CXmlConstantName::SSH, CXmlConstantValue::ITEM_TYPE_SSH)
											->addConstant(CXmlConstantName::TELNET, CXmlConstantValue::ITEM_TYPE_TELNET)
											->addConstant(CXmlConstantName::JMX, CXmlConstantValue::ITEM_TYPE_JMX)
											->addConstant(CXmlConstantName::DEPENDENT, CXmlConstantValue::ITEM_TYPE_DEPENDENT)
											->addConstant(CXmlConstantName::HTTP_AGENT, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT),
										new CStringXmlTag('url'),
										new CStringXmlTag('username'),
										(new CStringXmlTag('verify_host'))
											->setDefaultValue(DB::getDefault('items', 'verify_host'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
										(new CStringXmlTag('verify_peer'))
											->setDefaultValue(DB::getDefault('items', 'verify_peer'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES)
									)
							),
						(new CIndexedArrayXmlTag('httptests'))
							->setSchema(
								(new CArrayXmlTag('httptest'))
									->setSchema(
										(new CStringXmlTag('name'))->setRequired(),
										(new CIndexedArrayXmlTag('steps'))
											->setRequired()
											->setSchema(
												(new CArrayXmlTag('step'))
													->setRequired()
													->setSchema(
														(new CStringXmlTag('name'))->setRequired(),
														(new CStringXmlTag('url'))->setRequired(),
														(new CStringXmlTag('follow_redirects'))
															->setDefaultValue(DB::getDefault('items', 'follow_redirects'))
															->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
															->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
														(new CIndexedArrayXmlTag('headers'))
															->setSchema(
																(new CArrayXmlTag('header'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired(),
																		(new CStringXmlTag('value'))->setRequired()
																	)
															),
														(new CIndexedArrayXmlTag('posts'))
															->setSchema(
																(new CArrayXmlTag('post_field'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired(),
																		(new CStringXmlTag('value'))->setRequired()
																	)
															),
														(new CIndexedArrayXmlTag('query_fields'))
															->setSchema(
																(new CArrayXmlTag('query_field'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired(),
																		new CStringXmlTag('value')
																	)
															),
														new CStringXmlTag('required'),
														(new CStringXmlTag('retrieve_mode'))
															->setDefaultValue(DB::getDefault('items', 'retrieve_mode'))
															->addConstant(CXmlConstantName::BODY, CXmlConstantValue::BODY)
															->addConstant(CXmlConstantName::HEADERS, CXmlConstantValue::HEADERS)
															->addConstant(CXmlConstantName::BOTH, CXmlConstantValue::BOTH),
														new CStringXmlTag('status_codes'),
														// Default value is different from DB default value.
														(new CStringXmlTag('timeout'))->setDefaultValue('15s'),
														(new CIndexedArrayXmlTag('variables'))
															->setSchema(
																(new CArrayXmlTag('variable'))
																	->setSchema(
																		(new CStringXmlTag('name'))->setRequired(),
																		(new CStringXmlTag('value'))->setRequired()
																	)
															)
													)
											),
										(new CStringXmlTag('agent'))->setDefaultValue(DB::getDefault('httptest', 'agent')),
										(new CArrayXmlTag('application'))
											->setSchema(
												(new CStringXmlTag('name'))->setRequired()
											),
										(new CStringXmlTag('attempts'))->setDefaultValue(DB::getDefault('httptest', 'retries')),
										(new CStringXmlTag('authentication'))
											->setDefaultValue(DB::getDefault('httptest', 'authentication'))
											->addConstant(CXmlConstantName::NONE, CXmlConstantValue::NONE)
											->addConstant(CXmlConstantName::BASIC, CXmlConstantValue::BASIC)
											->addConstant(CXmlConstantName::NTLM, CXmlConstantValue::NTLM),
										(new CStringXmlTag('delay'))->setDefaultValue(DB::getDefault('httptest', 'delay')),
										(new CIndexedArrayXmlTag('headers'))
											->setSchema(
												(new CArrayXmlTag('header'))
													->setSchema(
														(new CStringXmlTag('name'))->setRequired(),
														(new CStringXmlTag('value'))->setRequired()
													)
											),
										new CStringXmlTag('http_password'),
										new CStringXmlTag('http_proxy'),
										new CStringXmlTag('http_user'),
										new CStringXmlTag('ssl_cert_file'),
										new CStringXmlTag('ssl_key_file'),
										new CStringXmlTag('ssl_key_password'),
										(new CStringXmlTag('status'))
											->setDefaultValue(DB::getDefault('httptest', 'status'))
											->addConstant(CXmlConstantName::ENABLED, CXmlConstantValue::ENABLED)
											->addConstant(CXmlConstantName::DISABLED, CXmlConstantValue::DISABLED),
										(new CIndexedArrayXmlTag('variables'))
											->setSchema(
												(new CArrayXmlTag('variable'))
													->setSchema(
														(new CStringXmlTag('name'))->setRequired(),
														(new CStringXmlTag('value'))->setRequired()
													)
											),
										(new CStringXmlTag('verify_host'))
											->setDefaultValue(DB::getDefault('httptest', 'verify_host'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
										(new CStringXmlTag('verify_peer'))
											->setDefaultValue(DB::getDefault('httptest', 'verify_peer'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES)
									)
							),
						(new CIndexedArrayXmlTag('items'))
							->setSchema(
								(new CArrayXmlTag('item'))
									->setSchema(
										(new CStringXmlTag('key'))->setRequired(),
										(new CStringXmlTag('name'))->setRequired(),
										(new CStringXmlTag('allow_traps'))
											->setDefaultValue(DB::getDefault('items', 'allow_traps'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
										new CStringXmlTag('allowed_hosts'),
										(new CIndexedArrayXmlTag('applications'))
											->setSchema(
												(new CArrayXmlTag('application'))
													->setSchema(
														(new CStringXmlTag('name'))->setRequired()
													)
											),
										(new CStringXmlTag('authtype'))
											->setDefaultValue(DB::getDefault('items', 'authtype'))
											->addConstant(CXmlConstantName::NONE, CXmlConstantValue::NONE, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT)
											->addConstant(CXmlConstantName::BASIC, CXmlConstantValue::BASIC, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT)
											->addConstant(CXmlConstantName::NTLM, CXmlConstantValue::NTLM, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT)
											->addConstant(CXmlConstantName::PASSWORD, CXmlConstantValue::PASSWORD, CXmlConstantValue::ITEM_TYPE_SSH)
											->addConstant(CXmlConstantName::PUBLIC_KEY, CXmlConstantValue::PUBLIC_KEY, CXmlConstantValue::ITEM_TYPE_SSH)
											->setExportHandler(function(array $data, CXmlTagInterface $class) {
												if ($data['type'] != CXmlConstantValue::ITEM_TYPE_HTTP_AGENT
													&& $data['type'] != CXmlConstantValue::ITEM_TYPE_SSH) {
													return CXmlConstantName::NONE;
												}

												return $class->getConstantByValue($data['authtype'], $data['type']);
											})
											->setImportHandler(function(array $data, CXmlTagInterface $class) {
												if (!array_key_exists('authtype', $data)) {
													return (string) CXmlConstantValue::NONE;
												}

												$type = ($data['type'] === CXmlConstantName::HTTP_AGENT
													? CXmlConstantValue::ITEM_TYPE_HTTP_AGENT
													: CXmlConstantValue::ITEM_TYPE_SSH);
												return (string) $class->getConstantValueByName($data['authtype'], $type);
											}),
										// Default value is different from DB default value.
										(new CStringXmlTag('delay'))->setDefaultValue('1m'),
										new CStringXmlTag('description'),
										(new CStringXmlTag('follow_redirects'))
											->setDefaultValue(DB::getDefault('items', 'follow_redirects'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
										(new CIndexedArrayXmlTag('headers'))
											->setSchema(
												(new CArrayXmlTag('header'))
													->setSchema(
														(new CStringXmlTag('name'))->setRequired(),
														(new CStringXmlTag('value'))->setRequired()
													)
											),
										(new CStringXmlTag('history'))->setDefaultValue(DB::getDefault('items', 'history')),
										new CStringXmlTag('http_proxy'),
										(new CStringXmlTag('inventory_link'))
											->setDefaultValue(DB::getDefault('items', 'inventory_link'))
											->addConstant(CXmlConstantName::NONE, CXmlConstantValue::NONE)
											->addConstant(CXmlConstantName::ALIAS, CXmlConstantValue::ALIAS)
											->addConstant(CXmlConstantName::ASSET_TAG, CXmlConstantValue::ASSET_TAG)
											->addConstant(CXmlConstantName::CHASSIS, CXmlConstantValue::CHASSIS)
											->addConstant(CXmlConstantName::CONTACT, CXmlConstantValue::CONTACT)
											->addConstant(CXmlConstantName::CONTRACT_NUMBER, CXmlConstantValue::CONTRACT_NUMBER)
											->addConstant(CXmlConstantName::DATE_HW_DECOMM, CXmlConstantValue::DATE_HW_DECOMM)
											->addConstant(CXmlConstantName::DATE_HW_EXPIRY, CXmlConstantValue::DATE_HW_EXPIRY)
											->addConstant(CXmlConstantName::DATE_HW_INSTALL, CXmlConstantValue::DATE_HW_INSTALL)
											->addConstant(CXmlConstantName::DATE_HW_PURCHASE, CXmlConstantValue::DATE_HW_PURCHASE)
											->addConstant(CXmlConstantName::DEPLOYMENT_STATUS, CXmlConstantValue::DEPLOYMENT_STATUS)
											->addConstant(CXmlConstantName::HARDWARE, CXmlConstantValue::HARDWARE)
											->addConstant(CXmlConstantName::HARDWARE_FULL, CXmlConstantValue::HARDWARE_FULL)
											->addConstant(CXmlConstantName::HOST_NETMASK, CXmlConstantValue::HOST_NETMASK)
											->addConstant(CXmlConstantName::HOST_NETWORKS, CXmlConstantValue::HOST_NETWORKS)
											->addConstant(CXmlConstantName::HOST_ROUTER, CXmlConstantValue::HOST_ROUTER)
											->addConstant(CXmlConstantName::HW_ARCH, CXmlConstantValue::HW_ARCH)
											->addConstant(CXmlConstantName::INSTALLER_NAME, CXmlConstantValue::INSTALLER_NAME)
											->addConstant(CXmlConstantName::LOCATION, CXmlConstantValue::LOCATION)
											->addConstant(CXmlConstantName::LOCATION_LAT, CXmlConstantValue::LOCATION_LAT)
											->addConstant(CXmlConstantName::LOCATION_LON, CXmlConstantValue::LOCATION_LON)
											->addConstant(CXmlConstantName::MACADDRESS_A, CXmlConstantValue::MACADDRESS_A)
											->addConstant(CXmlConstantName::MACADDRESS_B, CXmlConstantValue::MACADDRESS_B)
											->addConstant(CXmlConstantName::MODEL, CXmlConstantValue::MODEL)
											->addConstant(CXmlConstantName::NAME, CXmlConstantValue::NAME)
											->addConstant(CXmlConstantName::NOTES, CXmlConstantValue::NOTES)
											->addConstant(CXmlConstantName::OOB_IP, CXmlConstantValue::OOB_IP)
											->addConstant(CXmlConstantName::OOB_NETMASK, CXmlConstantValue::OOB_NETMASK)
											->addConstant(CXmlConstantName::OOB_ROUTER, CXmlConstantValue::OOB_ROUTER)
											->addConstant(CXmlConstantName::OS, CXmlConstantValue::OS)
											->addConstant(CXmlConstantName::OS_FULL, CXmlConstantValue::OS_FULL)
											->addConstant(CXmlConstantName::OS_SHORT, CXmlConstantValue::OS_SHORT)
											->addConstant(CXmlConstantName::POC_1_CELL, CXmlConstantValue::POC_1_CELL)
											->addConstant(CXmlConstantName::POC_1_EMAIL, CXmlConstantValue::POC_1_EMAIL)
											->addConstant(CXmlConstantName::POC_1_NAME, CXmlConstantValue::POC_1_NAME)
											->addConstant(CXmlConstantName::POC_1_NOTES, CXmlConstantValue::POC_1_NOTES)
											->addConstant(CXmlConstantName::POC_1_PHONE_A, CXmlConstantValue::POC_1_PHONE_A)
											->addConstant(CXmlConstantName::POC_1_PHONE_B, CXmlConstantValue::POC_1_PHONE_B)
											->addConstant(CXmlConstantName::POC_1_SCREEN, CXmlConstantValue::POC_1_SCREEN)
											->addConstant(CXmlConstantName::POC_2_CELL, CXmlConstantValue::POC_2_CELL)
											->addConstant(CXmlConstantName::POC_2_EMAIL, CXmlConstantValue::POC_2_EMAIL)
											->addConstant(CXmlConstantName::POC_2_NAME, CXmlConstantValue::POC_2_NAME)
											->addConstant(CXmlConstantName::POC_2_NOTES, CXmlConstantValue::POC_2_NOTES)
											->addConstant(CXmlConstantName::POC_2_PHONE_A, CXmlConstantValue::POC_2_PHONE_A)
											->addConstant(CXmlConstantName::POC_2_PHONE_B, CXmlConstantValue::POC_2_PHONE_B)
											->addConstant(CXmlConstantName::POC_2_SCREEN, CXmlConstantValue::POC_2_SCREEN)
											->addConstant(CXmlConstantName::SERIALNO_A, CXmlConstantValue::SERIALNO_A)
											->addConstant(CXmlConstantName::SERIALNO_B, CXmlConstantValue::SERIALNO_B)
											->addConstant(CXmlConstantName::SITE_ADDRESS_A, CXmlConstantValue::SITE_ADDRESS_A)
											->addConstant(CXmlConstantName::SITE_ADDRESS_B, CXmlConstantValue::SITE_ADDRESS_B)
											->addConstant(CXmlConstantName::SITE_ADDRESS_C, CXmlConstantValue::SITE_ADDRESS_C)
											->addConstant(CXmlConstantName::SITE_CITY, CXmlConstantValue::SITE_CITY)
											->addConstant(CXmlConstantName::SITE_COUNTRY, CXmlConstantValue::SITE_COUNTRY)
											->addConstant(CXmlConstantName::SITE_NOTES, CXmlConstantValue::SITE_NOTES)
											->addConstant(CXmlConstantName::SITE_RACK, CXmlConstantValue::SITE_RACK)
											->addConstant(CXmlConstantName::SITE_STATE, CXmlConstantValue::SITE_STATE)
											->addConstant(CXmlConstantName::SITE_ZIP, CXmlConstantValue::SITE_ZIP)
											->addConstant(CXmlConstantName::SOFTWARE, CXmlConstantValue::SOFTWARE)
											->addConstant(CXmlConstantName::SOFTWARE_APP_A, CXmlConstantValue::SOFTWARE_APP_A)
											->addConstant(CXmlConstantName::SOFTWARE_APP_B, CXmlConstantValue::SOFTWARE_APP_B)
											->addConstant(CXmlConstantName::SOFTWARE_APP_C, CXmlConstantValue::SOFTWARE_APP_C)
											->addConstant(CXmlConstantName::SOFTWARE_APP_D, CXmlConstantValue::SOFTWARE_APP_D)
											->addConstant(CXmlConstantName::SOFTWARE_APP_E, CXmlConstantValue::SOFTWARE_APP_E)
											->addConstant(CXmlConstantName::SOFTWARE_FULL, CXmlConstantValue::SOFTWARE_FULL)
											->addConstant(CXmlConstantName::TAG, CXmlConstantValue::TAG)
											->addConstant(CXmlConstantName::TYPE, CXmlConstantValue::TYPE)
											->addConstant(CXmlConstantName::TYPE_FULL, CXmlConstantValue::TYPE_FULL)
											->addConstant(CXmlConstantName::URL_A, CXmlConstantValue::URL_A)
											->addConstant(CXmlConstantName::URL_B, CXmlConstantValue::URL_B)
											->addConstant(CXmlConstantName::URL_C, CXmlConstantValue::URL_C)
											->addConstant(CXmlConstantName::VENDOR, CXmlConstantValue::VENDOR),
										new CStringXmlTag('ipmi_sensor'),
										new CStringXmlTag('jmx_endpoint'),
										new CStringXmlTag('logtimefmt'),
										(new CArrayXmlTag('master_item'))
											->setSchema(
												(new CStringXmlTag('key'))->setRequired()
											),
										(new CStringXmlTag('output_format'))
											->setDefaultValue(DB::getDefault('items', 'output_format'))
											->addConstant(CXmlConstantName::RAW, CXmlConstantValue::RAW)
											->addConstant(CXmlConstantName::JSON, CXmlConstantValue::JSON),
										new CStringXmlTag('params'),
										new CStringXmlTag('password'),
										new CStringXmlTag('port'),
										(new CStringXmlTag('post_type'))
											->setDefaultValue(DB::getDefault('items', 'post_type'))
											->addConstant(CXmlConstantName::RAW, CXmlConstantValue::RAW)
											->addConstant(CXmlConstantName::JSON, CXmlConstantValue::JSON)
											->addConstant(CXmlConstantName::XML, CXmlConstantValue::XML),
										new CStringXmlTag('posts'),
										(new CIndexedArrayXmlTag('preprocessing'))
											->setSchema(
												(new CArrayXmlTag('step'))
													->setRequired()
													->setSchema(
														(new CStringXmlTag('params'))->setRequired(),
														(new CStringXmlTag('type'))
															->setRequired()
															->addConstant(CXmlConstantName::MULTIPLIER, CXmlConstantValue::MULTIPLIER)
															->addConstant(CXmlConstantName::RTRIM, CXmlConstantValue::RTRIM)
															->addConstant(CXmlConstantName::LTRIM, CXmlConstantValue::LTRIM)
															->addConstant(CXmlConstantName::TRIM, CXmlConstantValue::TRIM)
															->addConstant(CXmlConstantName::REGEX, CXmlConstantValue::REGEX)
															->addConstant(CXmlConstantName::BOOL_TO_DECIMAL, CXmlConstantValue::BOOL_TO_DECIMAL)
															->addConstant(CXmlConstantName::OCTAL_TO_DECIMAL, CXmlConstantValue::OCTAL_TO_DECIMAL)
															->addConstant(CXmlConstantName::HEX_TO_DECIMAL, CXmlConstantValue::HEX_TO_DECIMAL)
															->addConstant(CXmlConstantName::SIMPLE_CHANGE, CXmlConstantValue::SIMPLE_CHANGE)
															->addConstant(CXmlConstantName::CHANGE_PER_SECOND, CXmlConstantValue::CHANGE_PER_SECOND)
															->addConstant(CXmlConstantName::XMLPATH, CXmlConstantValue::XMLPATH)
															->addConstant(CXmlConstantName::JSONPATH, CXmlConstantValue::JSONPATH)
															->addConstant(CXmlConstantName::IN_RANGE, CXmlConstantValue::IN_RANGE)
															->addConstant(CXmlConstantName::MATCHES_REGEX, CXmlConstantValue::MATCHES_REGEX)
															->addConstant(CXmlConstantName::NOT_MATCHES_REGEX, CXmlConstantValue::NOT_MATCHES_REGEX)
															->addConstant(CXmlConstantName::CHECK_JSON_ERROR, CXmlConstantValue::CHECK_JSON_ERROR)
															->addConstant(CXmlConstantName::CHECK_XML_ERROR, CXmlConstantValue::CHECK_XML_ERROR)
															->addConstant(CXmlConstantName::CHECK_REGEX_ERROR, CXmlConstantValue::CHECK_REGEX_ERROR)
															->addConstant(CXmlConstantName::DISCARD_UNCHANGED, CXmlConstantValue::DISCARD_UNCHANGED)
															->addConstant(CXmlConstantName::DISCARD_UNCHANGED_HEARTBEAT, CXmlConstantValue::DISCARD_UNCHANGED_HEARTBEAT)
															->addConstant(CXmlConstantName::JAVASCRIPT, CXmlConstantValue::JAVASCRIPT)
															->addConstant(CXmlConstantName::PROMETHEUS_PATTERN, CXmlConstantValue::PROMETHEUS_PATTERN)
															->addConstant(CXmlConstantName::PROMETHEUS_TO_JSON, CXmlConstantValue::PROMETHEUS_TO_JSON),
														(new CStringXmlTag('error_handler'))
															->setDefaultValue(DB::getDefault('item_preproc', 'error_handler'))
															->addConstant(CXmlConstantName::ORIGINAL_ERROR, CXmlConstantValue::ORIGINAL_ERROR)
															->addConstant(CXmlConstantName::DISCARD_VALUE, CXmlConstantValue::DISCARD_VALUE)
															->addConstant(CXmlConstantName::CUSTOM_VALUE, CXmlConstantValue::CUSTOM_VALUE)
															->addConstant(CXmlConstantName::CUSTOM_ERROR, CXmlConstantValue::CUSTOM_ERROR),
														new CStringXmlTag('error_handler_params')
													)
											),
										new CStringXmlTag('privatekey'),
										new CStringXmlTag('publickey'),
										(new CIndexedArrayXmlTag('query_fields'))
											->setSchema(
												(new CArrayXmlTag('query_field'))
													->setSchema(
														(new CStringXmlTag('name'))->setRequired(),
														new CStringXmlTag('value')
													)
											),
										(new CStringXmlTag('request_method'))
											->setDefaultValue(DB::getDefault('items', 'request_method'))
											->addConstant(CXmlConstantName::GET, CXmlConstantValue::GET)
											->addConstant(CXmlConstantName::POST, CXmlConstantValue::POST)
											->addConstant(CXmlConstantName::PUT, CXmlConstantValue::PUT)
											->addConstant(CXmlConstantName::HEAD, CXmlConstantValue::HEAD),
										(new CStringXmlTag('retrieve_mode'))
											->setDefaultValue(DB::getDefault('items', 'retrieve_mode'))
											->addConstant(CXmlConstantName::BODY, CXmlConstantValue::BODY)
											->addConstant(CXmlConstantName::HEADERS, CXmlConstantValue::HEADERS)
											->addConstant(CXmlConstantName::BOTH, CXmlConstantValue::BOTH),
										new CStringXmlTag('snmp_community'),
										new CStringXmlTag('snmp_oid'),
										new CStringXmlTag('snmpv3_authpassphrase'),
										(new CStringXmlTag('snmpv3_authprotocol'))
											->setDefaultValue(DB::getDefault('items', 'snmpv3_authprotocol'))
											->addConstant(CXmlConstantName::MD5, CXmlConstantValue::SNMPV3_MD5)
											->addConstant(CXmlConstantName::SHA, CXmlConstantValue::SNMPV3_SHA),
										new CStringXmlTag('snmpv3_contextname'),
										new CStringXmlTag('snmpv3_privpassphrase'),
										(new CStringXmlTag('snmpv3_privprotocol'))
											->setDefaultValue(DB::getDefault('items', 'snmpv3_privprotocol'))
											->addConstant(CXmlConstantName::DES, CXmlConstantValue::DES)
											->addConstant(CXmlConstantName::AES, CXmlConstantValue::AES),
										(new CStringXmlTag('snmpv3_securitylevel'))
											->setDefaultValue(DB::getDefault('items', 'snmpv3_securitylevel'))
											->addConstant(CXmlConstantName::NOAUTHNOPRIV, CXmlConstantValue::NOAUTHNOPRIV)
											->addConstant(CXmlConstantName::AUTHNOPRIV, CXmlConstantValue::AUTHNOPRIV)
											->addConstant(CXmlConstantName::AUTHPRIV, CXmlConstantValue::AUTHPRIV),
										new CStringXmlTag('snmpv3_securityname'),
										new CStringXmlTag('ssl_cert_file'),
										new CStringXmlTag('ssl_key_file'),
										new CStringXmlTag('ssl_key_password'),
										(new CStringXmlTag('status'))
											->setDefaultValue(CXmlConstantValue::ENABLED)
											->addConstant(CXmlConstantName::ENABLED, CXmlConstantValue::ENABLED)
											->addConstant(CXmlConstantName::DISABLED, CXmlConstantValue::DISABLED),
										new CStringXmlTag('status_codes'),
										new CStringXmlTag('timeout'),
										(new CStringXmlTag('trends'))->setDefaultValue(DB::getDefault('items', 'trends')),
										(new CStringXmlTag('type'))
											->setDefaultValue(DB::getDefault('items', 'type'))
											->addConstant(CXmlConstantName::ZABBIX_PASSIVE, CXmlConstantValue::ITEM_TYPE_ZABBIX_PASSIVE)
											->addConstant(CXmlConstantName::SNMPV1, CXmlConstantValue::ITEM_TYPE_SNMPV1)
											->addConstant(CXmlConstantName::TRAP, CXmlConstantValue::ITEM_TYPE_TRAP)
											->addConstant(CXmlConstantName::SIMPLE, CXmlConstantValue::ITEM_TYPE_SIMPLE)
											->addConstant(CXmlConstantName::SNMPV2, CXmlConstantValue::ITEM_TYPE_SNMPV2)
											->addConstant(CXmlConstantName::INTERNAL, CXmlConstantValue::ITEM_TYPE_INTERNAL)
											->addConstant(CXmlConstantName::SNMPV3, CXmlConstantValue::ITEM_TYPE_SNMPV3)
											->addConstant(CXmlConstantName::ZABBIX_ACTIVE, CXmlConstantValue::ITEM_TYPE_ZABBIX_ACTIVE)
											->addConstant(CXmlConstantName::AGGREGATE, CXmlConstantValue::ITEM_TYPE_AGGREGATE)
											->addConstant(CXmlConstantName::EXTERNAL, CXmlConstantValue::ITEM_TYPE_EXTERNAL)
											->addConstant(CXmlConstantName::ODBC, CXmlConstantValue::ITEM_TYPE_ODBC)
											->addConstant(CXmlConstantName::IPMI, CXmlConstantValue::ITEM_TYPE_IPMI)
											->addConstant(CXmlConstantName::SSH, CXmlConstantValue::ITEM_TYPE_SSH)
											->addConstant(CXmlConstantName::TELNET, CXmlConstantValue::ITEM_TYPE_TELNET)
											->addConstant(CXmlConstantName::CALCULATED, CXmlConstantValue::ITEM_TYPE_CALCULATED)
											->addConstant(CXmlConstantName::JMX, CXmlConstantValue::ITEM_TYPE_JMX)
											->addConstant(CXmlConstantName::SNMP_TRAP, CXmlConstantValue::ITEM_TYPE_SNMP_TRAP)
											->addConstant(CXmlConstantName::DEPENDENT, CXmlConstantValue::ITEM_TYPE_DEPENDENT)
											->addConstant(CXmlConstantName::HTTP_AGENT, CXmlConstantValue::ITEM_TYPE_HTTP_AGENT),
										new CStringXmlTag('units'),
										new CStringXmlTag('url'),
										new CStringXmlTag('username'),
										(new CStringXmlTag('value_type'))
											// Default value is different from DB default value.
											->setDefaultValue(CXmlConstantValue::UNSIGNED)
											->addConstant(CXmlConstantName::FLOAT, CXmlConstantValue::FLOAT)
											->addConstant(CXmlConstantName::CHAR, CXmlConstantValue::CHAR)
											->addConstant(CXmlConstantName::LOG, CXmlConstantValue::LOG)
											->addConstant(CXmlConstantName::UNSIGNED, CXmlConstantValue::UNSIGNED)
											->addConstant(CXmlConstantName::TEXT, CXmlConstantValue::TEXT),
										(new CArrayXmlTag('valuemap'))
											->setSchema(
												(new CStringXmlTag('name'))
											),
										(new CStringXmlTag('verify_host'))
											->setDefaultValue(DB::getDefault('items', 'verify_host'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES),
										(new CStringXmlTag('verify_peer'))
											->setDefaultValue(DB::getDefault('items', 'verify_peer'))
											->addConstant(CXmlConstantName::NO, CXmlConstantValue::NO)
											->addConstant(CXmlConstantName::YES, CXmlConstantValue::YES)
									)
							),
						(new CIndexedArrayXmlTag('macros'))
							->setSchema(
								(new CArrayXmlTag('macro'))
									->setSchema(
										(new CStringXmlTag('macro'))->setRequired(),
										new CStringXmlTag('value')
									)
							),
						(new CIndexedArrayXmlTag('screens'))
							->setSchema(
								(new CArrayXmlTag('screen'))
									->setSchema(
										new CStringXmlTag('name'),
										new CStringXmlTag('hsize'),
										(new CIndexedArrayXmlTag('screen_items'))
											->setSchema(
												(new CArrayXmlTag('screen_item'))
													->setSchema(
														new CStringXmlTag('x'),
														new CStringXmlTag('y'),
														new CStringXmlTag('application'),
														new CStringXmlTag('colspan'),
														new CStringXmlTag('dynamic'),
														new CStringXmlTag('elements'),
														new CStringXmlTag('halign'),
														new CStringXmlTag('height'),
														new CStringXmlTag('max_columns'),
														new CStringXmlTag('resource'),
														new CStringXmlTag('resourcetype'),
														new CStringXmlTag('rowspan'),
														new CStringXmlTag('sort_triggers'),
														new CStringXmlTag('style'),
														new CStringXmlTag('url'),
														new CStringXmlTag('valign'),
														new CStringXmlTag('width')
													)
											),
										new CStringXmlTag('vsize')
									)
							),
						(new CIndexedArrayXmlTag('tags'))
							->setSchema(
								(new CArrayXmlTag('tag'))
									->setSchema(
										(new CStringXmlTag('tag'))->setRequired(),
										new CStringXmlTag('value')
									)
							),
						(new CIndexedArrayXmlTag('templates'))
							->setSchema(
								(new CArrayXmlTag('template'))
									->setSchema(
										(new CStringXmlTag('name'))->setRequired()
									)
							)
					)
			);
	}
}
