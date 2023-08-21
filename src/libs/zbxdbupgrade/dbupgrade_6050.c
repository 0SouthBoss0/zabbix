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

#include "dbupgrade.h"

#include "zbxdbschema.h"
#include "zbxdbhigh.h"
#include "zbxalgo.h"
#include "zbxtypes.h"

/*
 * 7.0 development database patches
 */

#ifndef HAVE_SQLITE3

static int	DBpatch_6050000(void)
{
	const zbx_db_field_t	field = {"url", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("config", &field, NULL);
}

static int	DBpatch_6050001(void)
{
	const zbx_db_field_t	field = {"geomaps_tile_url", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("config", &field, NULL);
}

static int	DBpatch_6050002(void)
{
	const zbx_db_field_t	field = {"url", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("sysmap_url", &field, NULL);
}

static int	DBpatch_6050003(void)
{
	const zbx_db_field_t	field = {"url", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("sysmap_element_url", &field, NULL);
}

static int	DBpatch_6050004(void)
{
	const zbx_db_field_t	field = {"url_a", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("host_inventory", &field, NULL);
}

static int	DBpatch_6050005(void)
{
	const zbx_db_field_t	field = {"url_b", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("host_inventory", &field, NULL);
}

static int	DBpatch_6050006(void)
{
	const zbx_db_field_t	field = {"url_c", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("host_inventory", &field, NULL);
}

static int	DBpatch_6050007(void)
{
	const zbx_db_field_t	field = {"value_str", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("widget_field", &field, NULL);
}

static int	DBpatch_6050008(void)
{
	const zbx_db_field_t	field = {"value", "0.0000", NULL, NULL, 0, ZBX_TYPE_FLOAT, ZBX_NOTNULL, 0};

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

#if defined(HAVE_ORACLE)
	if (SUCCEED == zbx_db_check_oracle_colum_type("history", "value", ZBX_TYPE_FLOAT))
		return SUCCEED;
#endif /* defined(HAVE_ORACLE) */

	return DBmodify_field_type("history", &field, &field);
}

static int	DBpatch_6050009(void)
{
	const zbx_db_field_t	field = {"value_min", "0.0000", NULL, NULL, 0, ZBX_TYPE_FLOAT, ZBX_NOTNULL, 0};

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

#if defined(HAVE_ORACLE)
	if (SUCCEED == zbx_db_check_oracle_colum_type("trends", "value_min", ZBX_TYPE_FLOAT))
		return SUCCEED;
#endif /* defined(HAVE_ORACLE) */

	return DBmodify_field_type("trends", &field, &field);
}

static int	DBpatch_6050010(void)
{
	const zbx_db_field_t	field = {"value_avg", "0.0000", NULL, NULL, 0, ZBX_TYPE_FLOAT, ZBX_NOTNULL, 0};

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

#if defined(HAVE_ORACLE)
	if (SUCCEED == zbx_db_check_oracle_colum_type("trends", "value_avg", ZBX_TYPE_FLOAT))
		return SUCCEED;
#endif /* defined(HAVE_ORACLE) */

	return DBmodify_field_type("trends", &field, &field);
}

static int	DBpatch_6050011(void)
{
	const zbx_db_field_t	field = {"value_max", "0.0000", NULL, NULL, 0, ZBX_TYPE_FLOAT, ZBX_NOTNULL, 0};

#if defined(HAVE_ORACLE)
	if (SUCCEED == zbx_db_check_oracle_colum_type("trends", "value_max", ZBX_TYPE_FLOAT))
		return SUCCEED;
#endif /* defined(HAVE_ORACLE) */

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	return DBmodify_field_type("trends", &field, &field);
}

static int	DBpatch_6050012(void)
{
	const zbx_db_field_t	field = {"allow_redirect", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0};

	return DBadd_field("dchecks", &field);
}

static int	DBpatch_6050013(void)
{
	const zbx_db_table_t	table =
			{"history_bin", "itemid,clock,ns", 0,
				{
					{"itemid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{"clock", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
					{"ns", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
					{"value", "", NULL, NULL, 0, ZBX_TYPE_BLOB, ZBX_NOTNULL, 0},
					{NULL}
				},
				NULL
			};

	return DBcreate_table(&table);
}

static int	DBpatch_6050014(void)
{
	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > zbx_db_execute(
			"delete from widget_field"
			" where name='adv_conf' and widgetid in ("
				"select widgetid"
				" from widget"
				" where type in ('clock', 'item')"
			")"))
	{
		return FAIL;
	}

	return SUCCEED;
}

static int	DBpatch_6050015(void)
{
	const zbx_db_field_t	field = {"http_user", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL | ZBX_PROXY, 0};

	return DBmodify_field_type("httptest", &field, NULL);
}

static int	DBpatch_6050016(void)
{
	const zbx_db_field_t	field = {"http_password", "", NULL, NULL, 255, ZBX_TYPE_CHAR,
			ZBX_NOTNULL | ZBX_PROXY, 0};

	return DBmodify_field_type("httptest", &field, NULL);
}

static int	DBpatch_6050017(void)
{
	const zbx_db_field_t	field = {"username", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL | ZBX_PROXY, 0};

	return DBmodify_field_type("items", &field, NULL);
}

static int	DBpatch_6050018(void)
{
	const zbx_db_field_t	field = {"password", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL | ZBX_PROXY, 0};

	return DBmodify_field_type("items", &field, NULL);
}

static int	DBpatch_6050019(void)
{
	const zbx_db_field_t	field = {"username", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("connector", &field, NULL);
}

static int	DBpatch_6050020(void)
{
	const zbx_db_field_t	field = {"password", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("connector", &field, NULL);
}

static int	DBpatch_6050021(void)
{
	const zbx_db_field_t	field = {"concurrency_max", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0};

	return DBadd_field("drules", &field);
}

static int	DBpatch_6050022(void)
{
	if (ZBX_DB_OK > zbx_db_execute("update drules set concurrency_max=1"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_6050023(void)
{
	const char	*sql =
			"update widget_field"
			" set name='acknowledgement_status'"
			" where name='unacknowledged'"
				" and exists ("
					"select null"
					" from widget w"
					" where widget_field.widgetid=w.widgetid"
						" and w.type='problems'"
				")";

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK <= zbx_db_execute("%s", sql))
		return SUCCEED;

	return FAIL;
}

static int	DBpatch_6050024(void)
{
	const char	*sql =
			"update widget_field"
			" set name='show_lines'"
			" where name='count'"
				" and exists ("
					"select null"
					" from widget w"
					" where widget_field.widgetid=w.widgetid"
						" and w.type='tophosts'"
				")";

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK <= zbx_db_execute("%s", sql))
		return SUCCEED;

	return FAIL;
}

static int	DBpatch_6050025(void)
{
	if (FAIL == zbx_db_index_exists("problem", "problem_4"))
		return DBcreate_index("problem", "problem_4", "cause_eventid", 0);

	return SUCCEED;
}

static int	DBpatch_6050026(void)
{
	const zbx_db_field_t	field = {"id", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0};

	return DBdrop_field_autoincrement("proxy_history", &field);

	return SUCCEED;
}

static int	DBpatch_6050027(void)
{
	const zbx_db_field_t	field = {"id", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0};

	return DBdrop_field_autoincrement("proxy_dhistory", &field);

	return SUCCEED;
}

static int	DBpatch_6050028(void)
{
	const zbx_db_field_t	field = {"id", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0};

	return DBdrop_field_autoincrement("proxy_autoreg_host", &field);

	return SUCCEED;
}

static int	DBpatch_6050029(void)
{
	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > zbx_db_execute("insert into module (moduleid,id,relative_path,status,config) values"
			" (" ZBX_FS_UI64 ",'gauge','widgets/gauge',%d,'[]')", zbx_db_get_maxid("module"), 1))
	{
		return FAIL;
	}

	return SUCCEED;
}

static int	DBpatch_6050030(void)
{
	const zbx_db_table_t table =
			{"optag", "optagid", 0,
				{
					{"optagid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{"operationid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
					{"tag", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
					{"value", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
					{0}
				},
				NULL
			};

	return DBcreate_table(&table);
}

static int  DBpatch_6050031(void)
{
	return DBcreate_index("optag", "optag_1", "operationid", 0);
}

static int	DBpatch_6050032(void)
{
	const zbx_db_field_t	field = {"operationid", NULL, "operations", "operationid", 0, 0, 0,
			ZBX_FK_CASCADE_DELETE};

	return DBadd_foreign_key("optag", 1, &field);
}

static int	DBpatch_6050033(void)
{
	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > zbx_db_execute("insert into module (moduleid,id,relative_path,status,config) values"
			" (" ZBX_FS_UI64 ",'toptriggers','widgets/toptriggers',%d,'[]')", zbx_db_get_maxid("module"), 1))
	{
		return FAIL;
	}

	return SUCCEED;
}

static int	DBpatch_6050034(void)
{
	const zbx_db_table_t	table = {"proxy", "proxyid", 0,
			{
				{"proxyid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
				{"name", "", NULL, NULL, 128, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
				{"mode", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
				{"description", "", NULL, NULL, 0, ZBX_TYPE_SHORTTEXT, ZBX_NOTNULL, 0},
				{"tls_connect", "1", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
				{"tls_accept", "1", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
				{"tls_issuer", "", NULL, NULL, 1024, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
				{"tls_subject", "", NULL, NULL, 1024, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
				{"tls_psk_identity", "", NULL, NULL, 128, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
				{"tls_psk", "", NULL, NULL, 512, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
				{"allowed_addresses", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
				{"address", "127.0.0.1", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
				{"port", "10051", NULL, NULL, 64, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
				{0}
			},
			NULL
		};

	return DBcreate_table(&table);
}

static int	DBpatch_6050035(void)
{
	return DBcreate_index("proxy", "proxy_1", "name", 1);
}

static int	DBpatch_6050036(void)
{
	return DBcreate_changelog_insert_trigger("proxy", "proxyid");
}

static int	DBpatch_6050037(void)
{
	return DBcreate_changelog_update_trigger("proxy", "proxyid");
}

static int	DBpatch_6050038(void)
{
	return DBcreate_changelog_delete_trigger("proxy", "proxyid");
}

#define DEPRECATED_STATUS_PROXY_ACTIVE	5
#define DEPRECATED_STATUS_PROXY_PASSIVE	6

static int	DBpatch_6050039(void)
{
	zbx_db_row_t		row;
	zbx_db_result_t		result;
	zbx_db_insert_t		db_insert_proxies;
	int			ret;

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	result = zbx_db_select(
			"select h.hostid,h.host,h.status,h.description,h.tls_connect,h.tls_accept,h.tls_issuer,"
				"h.tls_subject,h.tls_psk_identity,h.tls_psk,h.proxy_address,i.useip,i.ip,i.dns,i.port"
			" from hosts h"
			" left join interface i"
				" on h.hostid=i.hostid"
			" where h.status in (%i,%i)",
			DEPRECATED_STATUS_PROXY_PASSIVE, DEPRECATED_STATUS_PROXY_ACTIVE);

	zbx_db_insert_prepare(&db_insert_proxies, "proxy", "proxyid", "name", "mode", "description", "tls_connect",
			"tls_accept", "tls_issuer", "tls_subject", "tls_psk_identity", "tls_psk", "allowed_addresses",
			"address", "port", NULL);

	while (NULL != (row = zbx_db_fetch(result)))
	{
		zbx_uint64_t	proxyid;
		int		status, tls_connect, tls_accept;

		ZBX_STR2UINT64(proxyid, row[0]);
		status = atoi(row[2]);
		tls_connect = atoi(row[4]);
		tls_accept = atoi(row[5]);

		if (DEPRECATED_STATUS_PROXY_ACTIVE == status)
		{
			zbx_db_insert_add_values(&db_insert_proxies, proxyid, row[1], PROXY_MODE_ACTIVE, row[3],
					tls_connect, tls_accept, row[6], row[7], row[8], row[9], row[10],
					"127.0.0.1", "10051");
		}
		else if (DEPRECATED_STATUS_PROXY_PASSIVE == status)
		{
			const char	*address;
			const char	*port;

			if (SUCCEED != zbx_db_is_null(row[11]))
			{
				address = (1 == atoi(row[11]) ? row[12] : row[13]);
				port = row[14];
			}
			else
			{
				address = "127.0.0.1";
				port = "10051";
				zabbix_log(LOG_LEVEL_WARNING, "cannot select interface for proxy '%s'",  row[1]);
			}

			zbx_db_insert_add_values(&db_insert_proxies, proxyid, row[1], PROXY_MODE_PASSIVE, row[3],
					tls_connect, tls_accept, row[6], row[7], row[8], row[9], "", address, port);
		}
	}
	zbx_db_free_result(result);

	ret = zbx_db_insert_execute(&db_insert_proxies);
	zbx_db_insert_clean(&db_insert_proxies);

	return ret;
}

static int	DBpatch_6050040(void)
{
	return DBdrop_foreign_key("hosts", 1);
}

static int	DBpatch_6050041(void)
{
	const zbx_db_field_t	field = {"proxyid", NULL, "hosts", "hostid", 0, ZBX_TYPE_ID, 0, 0};

	return DBrename_field("hosts", "proxy_hostid", &field);
}

static int	DBpatch_6050042(void)
{
	const zbx_db_field_t	field = {"proxyid", NULL, "proxy", "proxyid", 0, 0, 0, 0};

	return DBadd_foreign_key("hosts", 1, &field);
}

static int	DBpatch_6050043(void)
{
	return DBdrop_foreign_key("drules", 1);
}

static int	DBpatch_6050044(void)
{
	const zbx_db_field_t	field = {"proxyid", NULL, "hosts", "hostid", 0, ZBX_TYPE_ID, 0, 0};

	return DBrename_field("drules", "proxy_hostid", &field);
}

static int	DBpatch_6050045(void)
{
	const zbx_db_field_t	field = {"proxyid", NULL, "proxy", "proxyid", 0, 0, 0, 0};

	return DBadd_foreign_key("drules", 1, &field);
}

static int	DBpatch_6050046(void)
{
	return DBdrop_foreign_key("autoreg_host", 1);
}

static int	DBpatch_6050047(void)
{
	const zbx_db_field_t	field = {"proxyid", NULL, "hosts", "hostid", 0, ZBX_TYPE_ID, 0, ZBX_FK_CASCADE_DELETE};

	return DBrename_field("autoreg_host", "proxy_hostid", &field);
}

static int	DBpatch_6050048(void)
{
	const zbx_db_field_t	field = {"proxyid", NULL, "proxy", "proxyid", 0, 0, 0, ZBX_FK_CASCADE_DELETE};

	return DBadd_foreign_key("autoreg_host", 1, &field);
}

static int	DBpatch_6050049(void)
{
	return DBdrop_foreign_key("task", 1);
}

static int	DBpatch_6050050(void)
{
	const zbx_db_field_t	field = {"proxyid", NULL, "hosts", "hostid", 0, ZBX_TYPE_ID, 0, 0};

	return DBrename_field("task", "proxy_hostid", &field);
}

static int	DBpatch_6050051(void)
{
	const zbx_db_field_t	field = {"proxyid", NULL, "proxy", "proxyid", 0, 0, 0, ZBX_FK_CASCADE_DELETE};

	return DBadd_foreign_key("task", 1, &field);
}

static int	DBpatch_6050052(void)
{
	const zbx_db_table_t	table = {"proxy_rtdata", "proxyid", 0,
			{
				{"proxyid", NULL, "proxy", "proxyid", 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
				{"lastaccess", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
				{"version", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
				{"compatibility", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
				{0}
			},
			NULL
		};

	return DBcreate_table(&table);
}

static int	DBpatch_6050053(void)
{
	const zbx_db_field_t	field = {"proxyid", NULL, "proxy", "proxyid", 0, 0, 0, ZBX_FK_CASCADE_DELETE};

	return DBadd_foreign_key("proxy_rtdata", 1, &field);
}

static int	DBpatch_6050054(void)
{
	zbx_db_row_t		row;
	zbx_db_result_t		result;
	zbx_db_insert_t		db_insert_rtdata;
	int			ret;

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	result = zbx_db_select(
		"select hr.hostid,hr.lastaccess,hr.version,hr.compatibility"
		" from host_rtdata hr"
		" join hosts h"
			" on hr.hostid=h.hostid"
		" where h.status in (%i,%i)",
		DEPRECATED_STATUS_PROXY_ACTIVE, DEPRECATED_STATUS_PROXY_PASSIVE);

	zbx_db_insert_prepare(&db_insert_rtdata, "proxy_rtdata", "proxyid", "lastaccess", "version", "compatibility",
			NULL);

	while (NULL != (row = zbx_db_fetch(result)))
	{
		int		lastaccess, version, compatibility;
		zbx_uint64_t	hostid;

		ZBX_STR2UINT64(hostid, row[0]);
		lastaccess = atoi(row[1]);
		version = atoi(row[2]);
		compatibility = atoi(row[3]);

		zbx_db_insert_add_values(&db_insert_rtdata, hostid, lastaccess, version, compatibility);
	}
	zbx_db_free_result(result);

	ret = zbx_db_insert_execute(&db_insert_rtdata);
	zbx_db_insert_clean(&db_insert_rtdata);

	return ret;
}

#undef DEPRECATED_STATUS_PROXY_ACTIVE
#undef DEPRECATED_STATUS_PROXY_PASSIVE

static int	DBpatch_6050055(void)
{
	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > zbx_db_execute("delete from hosts where status in (5,6)"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_6050056(void)
{
	return DBdrop_field("host_rtdata", "lastaccess");
}

static int	DBpatch_6050057(void)
{
	return DBdrop_field("host_rtdata", "version");
}

static int	DBpatch_6050058(void)
{
	return DBdrop_field("host_rtdata", "compatibility");
}

static int	DBpatch_6050059(void)
{
	return DBdrop_field("hosts", "proxy_address");
}

static int	DBpatch_6050060(void)
{
	return DBdrop_field("hosts", "auto_compress");
}

static int	DBpatch_6050061(void)
{
	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > zbx_db_execute("delete from profiles where idx='web.proxies.filter_status'"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_6050062(void)
{
	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > zbx_db_execute(
			"update profiles"
			" set value_str='name'"
			" where value_str='host'"
				" and idx='web.proxies.php.sort'"))
	{
		return FAIL;
	}

	return SUCCEED;
}

static int	DBpatch_6050063(void)
{
#define TM_DATA_TYPE_TEST_ITEM	0
#define TM_DATA_TYPE_PROXYIDS	2

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > zbx_db_execute("delete"
			" from task"
			" where exists ("
				"select null"
				" from task_data td"
				" where td.taskid=task.taskid and td.type in (%i,%i)"
			")",
			TM_DATA_TYPE_TEST_ITEM, TM_DATA_TYPE_PROXYIDS))
	{
		return FAIL;
	}
#undef TM_DATA_TYPE_TEST_ITEM
#undef TM_DATA_TYPE_PROXYIDS

	return SUCCEED;
}

typedef struct
{
	char		*name;
	zbx_uint64_t	wid;
	zbx_uint64_t	wfid;
	char		*value_str;
	int		value_int;
}
zbx_wiget_field_t;

ZBX_PTR_VECTOR_DECL(wiget_field, zbx_wiget_field_t *)
ZBX_PTR_VECTOR_IMPL(wiget_field, zbx_wiget_field_t *)

static void	zbx_wiget_field_free(zbx_wiget_field_t *wf)
{
	zbx_free(wf->name);
	zbx_free(wf->value_str);
	zbx_free(wf);
}

static int	zbx_wiget_field_compare(const void *d1, const void *d2)
{
	const zbx_wiget_field_t	*f1 = *(const zbx_wiget_field_t * const *)d1;
	const zbx_wiget_field_t	*f2 = *(const zbx_wiget_field_t * const *)d2;

	ZBX_RETURN_IF_NOT_EQUAL(f1->wid, f2->wid);

	return strcmp(f1->name, f2->name);
}

static void	DBpatch_6050064_transform(zbx_vector_wiget_field_t *timeshift, zbx_vector_wiget_field_t *interval,
		zbx_vector_wiget_field_t *aggr_func, zbx_vector_wiget_field_t *time_from,
		zbx_vector_wiget_field_t *time_to, zbx_vector_uint64_t *nofunc_ids)
{
	int	i;

	zbx_vector_wiget_field_sort(interval, zbx_wiget_field_compare);
	zbx_vector_wiget_field_sort(timeshift, zbx_wiget_field_compare);

	for (i = 0; i < aggr_func->values_num; i++)	/* remove fields */
	{
		int			n;
		zbx_wiget_field_t	*val = aggr_func->values[i];

		if (0 != val->value_int)
			continue;

		if (FAIL != (n = zbx_vector_wiget_field_bsearch(interval, val, zbx_wiget_field_compare)))
		{
			zbx_vector_uint64_append(nofunc_ids, interval->values[n]->wfid);
			zbx_wiget_field_free(interval->values[n]);
			zbx_vector_wiget_field_remove_noorder(interval, n);
		}

		if (FAIL != (n = zbx_vector_wiget_field_bsearch(timeshift, val, zbx_wiget_field_compare)))
		{
			zbx_vector_uint64_append(nofunc_ids, timeshift->values[n]->wfid);
			zbx_wiget_field_free(timeshift->values[n]);
			zbx_vector_wiget_field_remove(timeshift, n);
		}
	}

	while (0 < interval->values_num)	/* columns.time_from.N */
	{
		int			n;
		const char		*shift, *sign_shift = "+", *sign_interv = "-";
		zbx_wiget_field_t	*val = interval->values[interval->values_num - 1];

		if (FAIL == (n = zbx_vector_wiget_field_bsearch(timeshift, val, zbx_wiget_field_compare)))
			shift = "";
		else
			shift = timeshift->values[n]->value_str;

		if ('\0' == *shift || '-' == *shift)
			sign_shift = "";

		if ('\0' == *val->value_str)
			sign_interv = "";

		val->value_str = zbx_dsprintf(val->value_str, "now%s%s%s%s", sign_shift, shift, sign_interv,
				val->value_str);
		zbx_vector_wiget_field_append(time_from, val);
		zbx_vector_wiget_field_remove_noorder(interval, interval->values_num - 1);
	}

	while (0 < timeshift->values_num)	/* columns.time_to.N */
	{
		const char		*sign_shift = "+";
		zbx_wiget_field_t	*val = timeshift->values[timeshift->values_num - 1];

		if ('\0' == *val->value_str || '-' == *val->value_str)
			sign_shift = "";

		val->value_str = zbx_dsprintf(val->value_str, "now%s%s", sign_shift, val->value_str);
		zbx_vector_wiget_field_append(time_to, val);
		zbx_vector_wiget_field_remove_noorder(timeshift, timeshift->values_num - 1);
	}
}

static int	DBpatch_6050064_load(zbx_vector_wiget_field_t *time_from, zbx_vector_wiget_field_t *time_to,
		zbx_vector_uint64_t *nofunc_ids)
{
	zbx_db_result_t			result;
	zbx_db_row_t			row;
	zbx_vector_wiget_field_t	timeshift, interval, aggr_func;

	if (NULL == (result = zbx_db_select("select widget_fieldid,widgetid,name,value_str,value_int from widget_field"
				" where name like 'columns.timeshift.%%'"
					" or name like 'columns.aggregate_interval.%%'"
					" or name like 'columns.aggregate_function.%%'")))
	{
		return FAIL;
	}

	zbx_vector_wiget_field_create(&timeshift);
	zbx_vector_wiget_field_create(&interval);
	zbx_vector_wiget_field_create(&aggr_func);

	while (NULL != (row = zbx_db_fetch(result)))
	{
		zbx_wiget_field_t	*val;
		const char		*name;

		val = (zbx_wiget_field_t *) zbx_malloc(NULL, sizeof(zbx_wiget_field_t));

		ZBX_STR2UINT64(val->wfid, row[0]);
		ZBX_STR2UINT64(val->wid, row[1]);
		name = row[2];
		val->value_str = zbx_strdup(NULL, row[3]);
		val->value_int = atoi(row[4]);

		if ('t' == name[ZBX_CONST_STRLEN("columns.")])
		{
			val->name = zbx_strdup(NULL, &name[ZBX_CONST_STRLEN("columns.timeshift")]);
			zbx_vector_wiget_field_append(&timeshift, val);
		}
		else if  ('i' == name[ZBX_CONST_STRLEN("columns.aggregate_")])
		{
			val->name = zbx_strdup(NULL, &name[ZBX_CONST_STRLEN("columns.aggregate_interval")]);
			zbx_vector_wiget_field_append(&interval, val);
		}
		else
		{
			val->name = zbx_strdup(NULL, &name[ZBX_CONST_STRLEN("columns.aggregate_function")]);
			zbx_vector_wiget_field_append(&aggr_func, val);
		}
	}
	zbx_db_free_result(result);

	DBpatch_6050064_transform(&timeshift, &interval, &aggr_func, time_from, time_to, nofunc_ids);

	zbx_vector_wiget_field_clear_ext(&timeshift, zbx_wiget_field_free);
	zbx_vector_wiget_field_clear_ext(&interval, zbx_wiget_field_free);
	zbx_vector_wiget_field_clear_ext(&aggr_func, zbx_wiget_field_free);
	zbx_vector_wiget_field_destroy(&timeshift);
	zbx_vector_wiget_field_destroy(&interval);
	zbx_vector_wiget_field_destroy(&aggr_func);

	return SUCCEED;
}

static int	DBpatch_6050064_remove(zbx_vector_uint64_t *nofuncs)
{
	if (0 == nofuncs->values_num)
		return SUCCEED;

	zbx_vector_uint64_sort(nofuncs,ZBX_DEFAULT_UINT64_COMPARE_FUNC);

	return zbx_db_execute_multiple_query("delete from widget_field where", "widget_fieldid", nofuncs);
}

static int	DBpatch_6050064_update(zbx_vector_wiget_field_t *time_from, zbx_vector_wiget_field_t *time_to)
{
	char	*sql = NULL;
	size_t	sql_alloc = 0, sql_offset = 0;
	int	i, ret = SUCCEED;

	zbx_db_begin_multiple_update(&sql, &sql_alloc, &sql_offset);

	for (i = 0; i < time_from->values_num; i++)
	{
		zbx_wiget_field_t	*val = time_from->values[i];
		char			name[255 * ZBX_MAX_BYTES_IN_UTF8_CHAR + 1];

		zbx_snprintf(name, sizeof(name), "columns.time_from%s", val->name);
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"update widget_field"
				" set value_str='%s',name='%s'"
				" where widget_fieldid=" ZBX_FS_UI64 ";\n",
				val->value_str, name, val->wfid);
		zbx_db_execute_overflowed_sql(&sql, &sql_alloc, &sql_offset);
	}

	for (i = 0; i < time_to->values_num; i++)
	{
		zbx_wiget_field_t	*val = time_to->values[i];
		char			name[255 * ZBX_MAX_BYTES_IN_UTF8_CHAR + 1];

		zbx_snprintf(name, sizeof(name), "columns.time_to%s", val->name);
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"update widget_field"
				" set value_str='%s',name='%s'"
				" where widget_fieldid=" ZBX_FS_UI64 ";\n",
				val->value_str, name, val->wfid);
		zbx_db_execute_overflowed_sql(&sql, &sql_alloc, &sql_offset);
	}

	if (16 < sql_offset)	/* in ORACLE always present begin..end; */
	{
		zbx_db_end_multiple_update(&sql, &sql_alloc, &sql_offset);

		if (ZBX_DB_OK > zbx_db_execute("%s", sql))
			ret = FAIL;
	}

	zbx_free(sql);

	return ret;
}

static int	DBpatch_6050064_insert(zbx_vector_wiget_field_t *time_from)
{
	zbx_db_insert_t	db_insert;
	int		i, ret = SUCCEED;

	if (0 == time_from->values_num)
		return ret;

	zbx_db_insert_prepare(&db_insert, "widget_field", "widget_fieldid", "widgetid", "type", "name", "value_int",
			NULL);

	for (i = 0; i < time_from->values_num; i++)
	{
		zbx_wiget_field_t	*val = time_from->values[i];
		char			name[255 * ZBX_MAX_BYTES_IN_UTF8_CHAR + 1];

		zbx_snprintf(name, sizeof(name), "columns.item_time%s", val->name);
		zbx_db_insert_add_values(&db_insert, __UINT64_C(0), val->wid, 0, name, 1);
	}

	zbx_db_insert_autoincrement(&db_insert, "widget_fieldid");
	ret = zbx_db_insert_execute(&db_insert);
	zbx_db_insert_clean(&db_insert);

	return ret;
}

static int	DBpatch_6050064(void)
{
	zbx_vector_wiget_field_t	time_from, time_to;
	zbx_vector_uint64_t		nofuncs_ids;
	int				ret = FAIL;

	if (0 == (DBget_program_type() & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	zbx_vector_wiget_field_create(&time_from);
	zbx_vector_wiget_field_create(&time_to);
	zbx_vector_uint64_create(&nofuncs_ids);

	if (SUCCEED == DBpatch_6050064_load(&time_from, &time_to, &nofuncs_ids)
			&& SUCCEED == DBpatch_6050064_remove(&nofuncs_ids)
			&& SUCCEED == DBpatch_6050064_update(&time_from, &time_to)
			&& SUCCEED == DBpatch_6050064_insert(&time_from))
	{
		ret = SUCCEED;
	}

	zbx_vector_wiget_field_clear_ext(&time_from, zbx_wiget_field_free);
	zbx_vector_wiget_field_clear_ext(&time_to, zbx_wiget_field_free);
	zbx_vector_wiget_field_destroy(&time_from);
	zbx_vector_wiget_field_destroy(&time_to);
	zbx_vector_uint64_destroy(&nofuncs_ids);

	return ret;
}

#endif

DBPATCH_START(6050)

/* version, duplicates flag, mandatory flag */

DBPATCH_ADD(6050000, 0, 1)
DBPATCH_ADD(6050001, 0, 1)
DBPATCH_ADD(6050002, 0, 1)
DBPATCH_ADD(6050003, 0, 1)
DBPATCH_ADD(6050004, 0, 1)
DBPATCH_ADD(6050005, 0, 1)
DBPATCH_ADD(6050006, 0, 1)
DBPATCH_ADD(6050007, 0, 1)
DBPATCH_ADD(6050008, 0, 1)
DBPATCH_ADD(6050009, 0, 1)
DBPATCH_ADD(6050010, 0, 1)
DBPATCH_ADD(6050011, 0, 1)
DBPATCH_ADD(6050012, 0, 1)
DBPATCH_ADD(6050013, 0, 1)
DBPATCH_ADD(6050014, 0, 1)
DBPATCH_ADD(6050015, 0, 1)
DBPATCH_ADD(6050016, 0, 1)
DBPATCH_ADD(6050017, 0, 1)
DBPATCH_ADD(6050018, 0, 1)
DBPATCH_ADD(6050019, 0, 1)
DBPATCH_ADD(6050020, 0, 1)
DBPATCH_ADD(6050021, 0, 1)
DBPATCH_ADD(6050022, 0, 1)
DBPATCH_ADD(6050023, 0, 1)
DBPATCH_ADD(6050024, 0, 1)
DBPATCH_ADD(6050025, 0, 1)
DBPATCH_ADD(6050026, 0, 1)
DBPATCH_ADD(6050027, 0, 1)
DBPATCH_ADD(6050028, 0, 1)
DBPATCH_ADD(6050029, 0, 1)
DBPATCH_ADD(6050030, 0, 1)
DBPATCH_ADD(6050031, 0, 1)
DBPATCH_ADD(6050032, 0, 1)
DBPATCH_ADD(6050033, 0, 1)
DBPATCH_ADD(6050034, 0, 1)
DBPATCH_ADD(6050035, 0, 1)
DBPATCH_ADD(6050036, 0, 1)
DBPATCH_ADD(6050037, 0, 1)
DBPATCH_ADD(6050038, 0, 1)
DBPATCH_ADD(6050039, 0, 1)
DBPATCH_ADD(6050040, 0, 1)
DBPATCH_ADD(6050041, 0, 1)
DBPATCH_ADD(6050042, 0, 1)
DBPATCH_ADD(6050043, 0, 1)
DBPATCH_ADD(6050044, 0, 1)
DBPATCH_ADD(6050045, 0, 1)
DBPATCH_ADD(6050046, 0, 1)
DBPATCH_ADD(6050047, 0, 1)
DBPATCH_ADD(6050048, 0, 1)
DBPATCH_ADD(6050049, 0, 1)
DBPATCH_ADD(6050050, 0, 1)
DBPATCH_ADD(6050051, 0, 1)
DBPATCH_ADD(6050052, 0, 1)
DBPATCH_ADD(6050053, 0, 1)
DBPATCH_ADD(6050054, 0, 1)
DBPATCH_ADD(6050055, 0, 1)
DBPATCH_ADD(6050056, 0, 1)
DBPATCH_ADD(6050057, 0, 1)
DBPATCH_ADD(6050058, 0, 1)
DBPATCH_ADD(6050059, 0, 1)
DBPATCH_ADD(6050060, 0, 1)
DBPATCH_ADD(6050061, 0, 1)
DBPATCH_ADD(6050062, 0, 1)
DBPATCH_ADD(6050063, 0, 1)
DBPATCH_ADD(6050064, 0, 1)

DBPATCH_END()
