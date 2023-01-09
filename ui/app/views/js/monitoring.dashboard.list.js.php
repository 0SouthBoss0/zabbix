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


/**
 * @var CView $this
 */
?>

<script>
	const view = new class {

		init({csrf_tokens}) {
			this.csrf_tokens = csrf_tokens;

			document.addEventListener('click', (e) => {
				if (e.target.classList.contains('js-massdelete-dashboard')) {
					if(!this.massDeleteDashboard(e.target, Object.keys(chkbxRange.getSelectedIds()))) {
						e.preventDefault();
						e.stopPropagation();
						return false;
					}
				}
			});
		}

		massDeleteDashboard(target, dashboardids) {
			const confirmation = dashboardids.length > 1
				? <?= json_encode(_('Delete selected dashboards?')) ?>
				: <?= json_encode(_('Delete selected dashboard?')) ?>;

			if (!window.confirm(confirmation)) {
				return false;
			}

			create_var(target.closest('form'), '<?= CController::CSRF_TOKEN_NAME ?>', this.csrf_tokens['dashboard.delete'],
				false
			);

			return true;
		}
	};
</script>
