<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2024 Zabbix SIA
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


namespace Widgets\HostNavigator\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldHostPatternSelect,
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldRadioButtonList,
	CWidgetFieldSeverities,
	CWidgetFieldTags
};

/**
 * Host navigator widget form.
 */
class WidgetForm extends CWidgetForm {

	public const HOST_STATUS_ANY = 0;
	public const HOST_STATUS_ENABLED = 1;
	public const HOST_STATUS_DISABLED = 2;

	public const PROBLEMS_ALL = 0;
	public const PROBLEMS_UNSUPPRESSED = 1;
	public const PROBLEMS_NONE = 2;

	public function addFields(): self {
		return $this
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
			)
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldHostPatternSelect('hosts', _('Hosts'))
			)
			->addField(
				(new CWidgetFieldRadioButtonList('status', _('Host status'), [
					self::HOST_STATUS_ANY => _('Any'),
					self::HOST_STATUS_ENABLED => _('Enabled'),
					self::HOST_STATUS_DISABLED => _('Disabled')
				]))->setDefault(self::HOST_STATUS_ANY)
			)
			->addField($this->isTemplateDashboard()
				? null
				: (new CWidgetFieldRadioButtonList('evaltype', _('Host tags'), [
					TAG_EVAL_TYPE_AND_OR => _('And/Or'),
					TAG_EVAL_TYPE_OR => _('Or')
				]))->setDefault(TAG_EVAL_TYPE_AND_OR)
			)
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldTags('tags')
			)
			->addField(
				new CWidgetFieldSeverities('severities', _('Severity'))
			)
			->addField(
				new CWidgetFieldCheckBox('maintenance',
					$this->isTemplateDashboard() ? _('Show data in maintenance') : _('Show hosts in maintenance')
				)
			)
			->addField(
				(new CWidgetFieldRadioButtonList('problems', _('Show problems'), [
					self::PROBLEMS_ALL => _('All'),
					self::PROBLEMS_UNSUPPRESSED => _('Unsuppressed'),
					self::PROBLEMS_NONE => _('None')
				]))->setDefault(self::PROBLEMS_UNSUPPRESSED)
			)
			->addField(
				(new CWidgetFieldIntegerBox('limit', _('Host limit'), 1, 9999))
					->setDefault(100)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY |CWidgetField::FLAG_LABEL_ASTERISK)
			);
	}
}
