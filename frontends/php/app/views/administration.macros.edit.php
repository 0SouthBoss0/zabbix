<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
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


$this->addJsFile('buttondropdown.js');
$this->addJsFile('inputsecret.js');
$this->addJsFile('textareaflexible.js');
$this->includeJSfile('app/views/administration.macros.edit.js.php');

$widget = (new CWidget())
	->setTitle(_('Macros'))
	->setTitleSubmenu(getAdministrationGeneralSubmenu());

$table = (new CTable())
	->setId('tbl_macros')
	->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_CONTAINER)
	->setHeader([_('Macro'), _('Value'), _('Description'), '']);

foreach ($data['macros'] as $i => $macro) {
	$macro_input = (new CTextAreaFlexible('macros['.$i.'][macro]', $macro['macro']))
		->addClass('macro')
		->setWidth(ZBX_TEXTAREA_MACRO_WIDTH)
		->setAttribute('placeholder', '{$MACRO}');

	if ($i == 0) {
		$macro_input->setAttribute('autofocus', 'autofocus');
	}

	// Macro value input group.
	$value_input_group = (new CDiv())
		->addClass(ZBX_STYLE_INPUT_GROUP)
		->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH);

	$value_input = ($macro['type'] == ZBX_MACRO_TYPE_TEXT)
		? (new CTextAreaFlexible('macros['.$i.'][value]', CMacrosResolverGeneral::getMacroValue($macro)))
			->setAttribute('placeholder', _('value'))
		: (new CInputSecret('macros['.$i.'][value]', ZBX_MACRO_SECRET_MASK, _('value')));

	$dropdown_options = [
		'title' => _('Change type'),
		'active_class' => ($macro['type'] == ZBX_MACRO_TYPE_TEXT) ? ZBX_STYLE_ICON_TEXT : ZBX_STYLE_ICON_SECRET_TEXT,
		'items' => [
			['label' => _('Text'), 'value' => ZBX_MACRO_TYPE_TEXT, 'class' => ZBX_STYLE_ICON_TEXT],
			['label' => _('Secret text'), 'value' => ZBX_MACRO_TYPE_SECRET, 'class' => ZBX_STYLE_ICON_SECRET_TEXT]
		]
	];

	$value_input_group->addItem([
		$value_input,
		($macro['type'] == ZBX_MACRO_TYPE_SECRET)
			? (new CButton(null))
				->setAttribute('title', _('Revert changes'))
				->addClass(ZBX_STYLE_BTN_ALT.' '.ZBX_STYLE_BTN_UNDO)
			: null,
		new CButtonDropdown('macros['.$i.'][type]', $macro['type'], $dropdown_options)
	]);

	$description_input = (new CTextAreaFlexible('macros['.$i.'][description]', $macro['description']))
		->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
		->setMaxlength(DB::getFieldLength('globalmacro', 'description'))
		->setAttribute('placeholder', _('description'));

	$button_cell = [
		(new CButton('macros['.$i.'][remove]', _('Remove')))
			->addClass(ZBX_STYLE_BTN_LINK)
			->addClass('element-table-remove')
	];
	if (array_key_exists('globalmacroid', $macro)) {
		$button_cell[] = new CVar('macros['.$i.'][globalmacroid]', $macro['globalmacroid']);
	}

	$table->addRow([
		(new CCol($macro_input))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
		(new CCol($value_input_group))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
		(new CCol($description_input))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
		(new CCol($button_cell))->addClass(ZBX_STYLE_NOWRAP)
	], 'form_row');
}

$table->setFooter(new CCol(
	(new CButton('macro_add', _('Add')))
		->addClass(ZBX_STYLE_BTN_LINK)
		->addClass('element-table-add')
));

$macros_form_list = (new CFormList('macrosFormList'))->addRow($table);

$tab_view = (new CTabView())->addTab('macros', _('Macros'), $macros_form_list);

$save_button = (new CSubmit('update', _('Update')))->setAttribute('data-removed-count', 0);

$tab_view->setFooter(makeFormFooter($save_button));

$form = (new CForm())
	->setName('macrosForm')
	->setAction((new CUrl('zabbix.php'))->setArgument('action', 'macros.update')->getUrl())
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addItem($tab_view);

$widget->addItem($form)->show();
