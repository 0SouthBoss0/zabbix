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


const TABFILTER_EVENT_URLSET = 'urlset.tabfilter';

class CTabFilter extends CBaseComponent {

	constructor(target, options) {
		super(target);
		this._options = options;
		// Array of CTabFilterItem objects.
		this._items = [];
		this._active_item = null;
		this._shared_domnode = null;
		// NodeList of available templates (<script> DOM elements).
		this._templates = {};
		this._fetchpromise = null;
		this._idx_namespace = options.idx;
		this._timeselector = null;

		this.init(options);
		this.registerEvents(options);
	}

	init(options) {
		let item, index = 0;

		if (options.expanded) {
			options.data[options.selected].expanded = true;
		}

		this._shared_domnode = this._target.querySelector('.form-buttons');

		for (const template of this._target.querySelectorAll('[type="text/x-jquery-tmpl"][data-template]')) {
			this._templates[template.getAttribute('data-template')] = template;
		};

		for (const title of this._target.querySelectorAll('nav [data-target]')) {
			item = this.create(title, options.data[index]||{});

			if (item._expanded) {
				this._active_item = item;
			}

			if (item.hasClass('active')) {
				item._target.focus();
			}

			index++;
		}
	}

	/**
	 * Register tab filter events, called once during initialization.
	 *
	 * @param {object} options  Tab filter initialization options.
	 */
	registerEvents(options) {
		this._events = {
			/**
			 * Event handler on tab content expand.
			 */
			expand: (ev) => {
				this._active_item = ev.detail.target;
				this.collapseAllItemsExcept(this._active_item);

				if (!this._active_item || !this._active_item._expanded) {
					if (this._active_item == this._timeselector) {
						this._shared_domnode.classList.add('display-none');
					}
					else {
						this._shared_domnode.classList.remove('display-none');
						this.profileUpdate('selected', {
							value_int: this._active_item._index
						});
					}

					if (this._active_item != this._timeselector) {
						this.updateTimeselector(this._active_item);
					}
				}
			},

			/**
			 * Event handler on tab content collapse.
			 */
			collapse: (ev) => {
				if (ev.detail.target === this._active_item) {
					this._shared_domnode.classList.add('display-none');
					this.profileUpdate('expanded', {
						value_int: 0
					});
				}
			},

			/**
			 * UI sortable update event handler. Updates tab sorting in profile.
			 */
			tabSortChanged: (ev, ui) => {
				// Update order of this._items array.
				var from, to, target = ui.item[0].querySelector('[data-target]');

				this._items.forEach((item, index) => from = (item._target === target) ? index : from);
				this._target.querySelectorAll('nav [data-target]')
					.forEach((elm, index) => to = (elm === target) ? index : to);
				this._items[to] = this._items.splice(from, 1, this._items[to])[0];

				// Tab order changed, update changes via ajax.
				let value_str = this._items.map((item) => item._index).join(',');

				this.profileUpdate('taborder', {
					value_str: value_str
				}).then(() => {
					this._items.forEach((item, index) => {
						item._index = index;
					});
				});
			},

			/**
			 * Delete tab filter item. Removed HTML elements and CTabFilterItem object. Update index of tabfilter items.
			 */
			deleteItem: (ev) => {
				let item = ev.detail.target,
					index = this._items.indexOf(item);

				if (index > -1) {
					this._active_item = this._items[index - 1];
					this._active_item.select();
					item._target.parentNode.remove();
					delete this._items[index];
					this._items.splice(index, 1);
					this._items.forEach((item, index) => {
						item._index = index;
					});
				}
			},

			/**
			 * Item properties updated event handler, is called when tab properties popup were closed pressing 'Update'.
			 */
			updateItem: (ev) => {
				if (this._active_item != this._timeselector) {
					this.updateTimeselector(this._active_item);
				}
			},

			/**
			 * Event handler for 'Save as' button
			 */
			popupUpdateAction: (ev) => {
				var item = this.create(this._active_item._target.cloneNode(), {});

				if (ev.detail.form_action === 'update') {
					item.update(Object.assign(ev.detail, {
						from: ev.detail.tabfilter_from,
						to: ev.detail.tabfilter_to
					}));
					var params = item.getFilterParams();

					this.profileUpdate('properties', {
						'idx2': ev.detail.idx2,
						'value_str': params.toString()
					}).then(() => {
						item.setBrowserLocation(params);
						window.location.reload(true);
					});
				}
			},

			/**
			 * Action on 'chevron left' button press. Select previous active tab filter.
			 */
			selectPrevTab: (ev) => {
				let index = this._items.indexOf(this._active_item);

				if (index > 0) {
					this._items[index - 1].select();
				}
			},

			/**
			 * Action on 'chevron right' button press. Select next active tab filter.
			 */
			selectNextTab: (ev) => {
				let index = this._items.indexOf(this._active_item);

				if (index > -1 && index < this._items.length - 1) {
					this._items[index + 1].select();
				}
			},

			/**
			 * Action on 'chevron down' button press. Creates dropdown with list of existing tabs.
			 */
			toggleTabsList: (ev) => {
				let items = [],
					dropdown = [{
						items: [{
								label: t('Home'),
								clickCallback: () => this._items[0].select()
							}]
					}];

				if (this._items.length > 2) {
					for (const item of this._items.slice(1, -1)) {
						items.push({
							label: item._data.filter_name,
							clickCallback: () => item.select()
						});
					}

					dropdown.push({items: items});
				}

				$(this._target).menuPopup(dropdown, $(ev), {
					position: {
						of: ev.target,
						my: 'left bottom',
						at: 'left top'
					}
				});
			},

			/**
			 * Action on 'Update' button press.
			 */
			buttonUpdateAction: () => {
				var params = this._active_item.getFilterParams();

				this.profileUpdate('properties', {
					'idx2': this._active_item._index,
					'value_str': params.toString()
				}).then(() => {
					this._active_item.setBrowserLocation(params);
				});
			},

			/**
			 * Action on 'Save as' button press, open properties popup.
			 */
			buttonSaveasAction: (ev) => {
				this._active_item.openPropertiesForm(ev.target, {
					'idx': this._active_item._idx_namespace,
					'idx2': this._items.length
				});
			},

			/**
			 * Action on 'Apply' button press.
			 */
			buttonApplyAction: () => {
				this._active_item.setUnsavedState();
				this._active_item.setBrowserLocation(this._active_item.getFilterParams());
			},

			/**
			 * Action on 'Reset' button press.
			 */
			buttonResetAction: () => {
				this._active_item.setBrowserLocation(new URLSearchParams());
				window.location.reload(true);
			},

			/**
			 * Keydown handler for keyboard navigation support.
			 */
			keydown: (ev) => {
				if (ev.key !== 'ArrowLeft' && ev.key !== 'ArrowRight') {
					return;
				}

				if (ev.path.indexOf(this._target.querySelector('nav')) > -1) {
					this._events[(ev.key == 'ArrowRight') ? 'selectNextTab' : 'selectPrevTab']();

					cancelEvent(ev);
				}
			}
		}

		for (const item of this._items) {
			item.on(TABFILTERITEM_EVENT_EXPAND_BEFORE, this._events.expand);
			item.on(TABFILTERITEM_EVENT_COLLAPSE, this._events.collapse);
			item.on(TABFILTERITEM_EVENT_DELETE, this._events.deleteItem);
			item.on(TABFILTERITEM_EVENT_URLSET, () => this.fire(TABFILTER_EVENT_URLSET));
			item.on(TABFILTERITEM_EVENT_UPDATE, this._events.updateItem);
		}

		$('.ui-sortable', this._target).sortable({
			update: this._events.tabSortChanged,
			axis: 'x',
			containment: 'parent'
		});

		for (const action of this._target.querySelectorAll('nav [data-action]')) {
			action.addEventListener('click', this._events[action.getAttribute('data-action')]);
		}

		this._shared_domnode.querySelector('[name="filter_update"]').addEventListener('click', this._events.buttonUpdateAction);
		this._shared_domnode.querySelector('[name="filter_new"]').addEventListener('click', this._events.buttonSaveasAction);
		this._shared_domnode.querySelector('[name="filter_apply"]').addEventListener('click', this._events.buttonApplyAction);
		this._shared_domnode.querySelector('[name="filter_reset"]').addEventListener('click', this._events.buttonResetAction);

		this.on('keydown', this._events.keydown);
		this.on('popup.tabfilter', this._events.popupUpdateAction);
	}

	/**
	 * Create new CTabFilterItem object with it container if it does not exists and append to _items array.
	 *
	 * @param {HTMLElement} title  HTML node element of tab label.
	 * @param {object}      data   Filter item dynamic data for template.
	 *
	 * @return {CTabFilterItem}
	 */
	create(title, data) {
		let item,
			containers = this._target.querySelector('.tabfilter-tabs-container'),
			container = containers.querySelector('#' + title.getAttribute('data-target'));

		if (!container) {
			container = document.createElement('div');
			container.setAttribute('id', title.getAttribute('data-target'));
			container.classList.add('display-none');
			containers.appendChild(container);
		}

		item = new CTabFilterItem(title, {
			parent: this,
			idx_namespace: this._idx_namespace,
			index: this._items.length,
			expanded: data.expanded||false,
			can_toggle: this._options.can_toggle,
			container: container,
			data: data,
			template: this._templates[data.tab_view]||null,
			support_custom_time: this._options.support_custom_time
		});

		this._items.push(item);

		if (title.getAttribute('data-target') === 'tabfilter_timeselector') {
			this._timeselector = item;
		}

		return item;
	}

	/**
	 * Fire event TABFILTERITEM_EVENT_COLLAPSE on every expanded tab except passed one.
	 *
	 * @param {CTabFilterItem} except  Tab item object.
	 */
	collapseAllItemsExcept(except) {
		for (const item of this._items) {
			if (item !== except && item._expanded) {
				item.fire(TABFILTERITEM_EVENT_COLLAPSE)
			}
		}
	}

	/**
	 * Update timeselector tab and timeselector buttons accessibility according passed item.
	 *
	 * @param {CTabFilterItem} item     Tab item object.
	 * @param {bool}           disable  Additional status to determine should the timeselector to be disabled or not.
	 */
	updateTimeselector(item, disable) {
		let disabled = disable || (!this._options.support_custom_time || item.hasCustomTime()),
			buttons = this._target.querySelectorAll('button.btn-time-left,button.btn-time-out,button.btn-time-right');

		if (this._timeselector) {
			this._timeselector.setDisabled(disabled);

			for (const button of buttons) {
				if (disabled) {
					button.setAttribute('disabled', 'disabled');
				}
				else {
					button.removeAttribute('disabled');
				}
			}
		}
	}

	/**
	 * Updates filter values in user profile. Aborts any previous unfinished updates.
	 *
	 * @param {string} property  Filter property to be updated: 'selected', 'expanded', 'properties'.
	 * @param {object} body      Key value pair of data to be passed to profile.update action.
	 *
	 * @return {Promise}
	 */
	profileUpdate(property, body) {
		if (this._fetch && 'abort' in this._fetch && !this._fetch.aborted) {
			this._fetch.abort();
		}

		body.idx = this._idx_namespace + '.' + property;
		this._fetch = new AbortController();

		return fetch('zabbix.php?action=tabfilter.profile.update', {
			method: 'POST',
			signal: this._fetch.signal,
			body: new URLSearchParams(body)
		}).then(() => {
			this._fetch = null;
		}).catch(() => {
			// User aborted a request.
		});
	}

	/**
	 * Update all tab filter counters values.
	 *
	 * @param {array} counters  Array of counters to be set.
	 */
	updateCounters(counters) {
		counters.forEach((value, index) => {
			let item = this._items[index];

			if (!item) {
				return;
			}

			if (item._data.filter_show_counter) {
				item.setCounter(value);
			}
			else {
				item.removeCounter();
			}
		});
	}
}
