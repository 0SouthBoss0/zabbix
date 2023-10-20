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


class CWidgetHoneycomb extends CWidget {

	static ZBX_STYLE_DASHBOARD_WIDGET_PADDING_V = 8;
	static ZBX_STYLE_DASHBOARD_WIDGET_PADDING_H = 10;

	/**
	 * @type {CSVGHoneycomb|null}
	 */
	#honeycomb = null;

	onResize() {
		if (this.getState() === WIDGET_STATE_ACTIVE && this.#honeycomb !== null) {
			this.#honeycomb.setSize(super._getContentsSize());
		}
	}

	getUpdateRequestData() {
		return {
			...super.getUpdateRequestData(),
			with_config: this.#honeycomb === null ? 1 : undefined
		};
	}

	updateProperties({name, view_mode, fields}) {
		if (this.#honeycomb !== null) {
			this.#honeycomb.destroy();
			this.#honeycomb = null;
		}

		super.updateProperties({name, view_mode, fields});
	}

	setContents(response) {
		if (this.#honeycomb === null) {
			const padding = {
				vertical: CWidgetHoneycomb.ZBX_STYLE_DASHBOARD_WIDGET_PADDING_V,
				horizontal: CWidgetHoneycomb.ZBX_STYLE_DASHBOARD_WIDGET_PADDING_H,
			};

			this.#honeycomb = new CSVGHoneycomb(padding, response.config);
			this._body.prepend(this.#honeycomb.getSVGElement());

			this.#honeycomb.setSize(super._getContentsSize());
		}

		this.#honeycomb.setValue({
			cells: response.cells
		});
	}

	getActionsContextMenu({can_copy_widget, can_paste_widget}) {
		const menu = super.getActionsContextMenu({can_copy_widget, can_paste_widget});

		if (this.isEditMode()) {
			return menu;
		}

		let menu_actions = null;

		for (const search_menu_actions of menu) {
			if ('label' in search_menu_actions && search_menu_actions.label === t('Actions')) {
				menu_actions = search_menu_actions;

				break;
			}
		}

		if (menu_actions === null) {
			menu_actions = {
				label: t('Actions'),
				items: []
			};

			menu.unshift(menu_actions);
		}

		menu_actions.items.push({
			label: t('Download image'),
			disabled: this.#honeycomb === null,
			clickCallback: () => {
				downloadSvgImage(this.#honeycomb.getSVGElement(), 'image.png');
			}
		});

		return menu;
	}

	hasPadding() {
		return false;
	}
}
