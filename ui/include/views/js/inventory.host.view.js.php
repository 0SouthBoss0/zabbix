<?php declare(strict_types = 1);
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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


/**
 * @var CView $this
 */
?>

<script>
	const view = {
		init() {
			host_popup.init();
		},

		hostEdit({hostid}) {
			const overlay = PopUp('popup.host.edit', {hostid}, 'host_edit', document.activeElement);

			overlay.$dialogue[0].addEventListener('dialogue.delete', (e) => {
				const detail = e.detail;
				debugger;
				alert('DELETE!');
				// const data = [];

				// for (const service of e.detail) {
				// 	data.push({id: service.serviceid, name: service.name});
				// }

				// jQuery('#parent_serviceids_').multiSelect('addData', data);
			});
		}
	}
</script>
