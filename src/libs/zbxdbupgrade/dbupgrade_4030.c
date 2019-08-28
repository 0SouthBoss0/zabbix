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

#include "common.h"
#include "db.h"
#include "dbupgrade.h"

extern unsigned char	program_type;

/*
 * 4.4 development database patches
 */

#ifndef HAVE_SQLITE3

extern unsigned char	program_type;

static int	DBpatch_4030000(void)
{
	const ZBX_FIELD	field = {"host", "", NULL, NULL, 128, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("autoreg_host", &field, NULL);
}

static int	DBpatch_4030001(void)
{
	const ZBX_FIELD	field = {"host", "", NULL, NULL, 128, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("proxy_autoreg_host", &field, NULL);
}

static int	DBpatch_4030002(void)
{
	const ZBX_FIELD	field = {"host", "", NULL, NULL, 128, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("host_discovery", &field, NULL);
}

static int	DBpatch_4030003(void)
{
	const ZBX_TABLE table =
		{"item_rtdata", "itemid", 0,
			{
				{"itemid", NULL, NULL, NULL, 0, ZBX_TYPE_ID, ZBX_NOTNULL, 0},
				{"lastlogsize", "0", NULL, NULL, 0, ZBX_TYPE_UINT, ZBX_NOTNULL, 0},
				{"state", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
				{"mtime", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0},
				{"error", "", NULL, NULL, 2048, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0},
				{0}
			},
			NULL
		};

	return DBcreate_table(&table);
}

static int	DBpatch_4030004(void)
{
	const ZBX_FIELD	field = {"itemid", NULL, "items", "itemid", 0, 0, 0, ZBX_FK_CASCADE_DELETE};

	return DBadd_foreign_key("item_rtdata", 1, &field);
}

static int	DBpatch_4030005(void)
{
	if (ZBX_DB_OK <= DBexecute("insert into item_rtdata (itemid,lastlogsize,state,mtime,error)"
			" select i.itemid,i.lastlogsize,i.state,i.mtime,i.error"
			" from items i"
			" join hosts h on i.hostid=h.hostid"
			" where h.status in (%d,%d) and i.flags<>%d",
			HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED, ZBX_FLAG_DISCOVERY_PROTOTYPE))
	{
		return SUCCEED;
	}

	return FAIL;
}

static int	DBpatch_4030006(void)
{
	return DBdrop_field("items", "lastlogsize");
}

static int	DBpatch_4030007(void)
{
	return DBdrop_field("items", "state");
}

static int	DBpatch_4030008(void)
{
	return DBdrop_field("items", "mtime");
}

static int	DBpatch_4030009(void)
{
	return DBdrop_field("items", "error");
}

static int	DBpatch_4030010(void)
{
	if (0 == (program_type & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	/* 8 - SCREEN_RESOURCE_SCREEN */
	if (ZBX_DB_OK > DBexecute("delete from screens_items where resourcetype=8"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_4030011(void)
{
	if (0 == (program_type & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > DBexecute("update profiles set idx='web.user.filter.usrgrpid'"
				" where idx='web.users.filter.usrgrpid'"))
		return FAIL;

	if (ZBX_DB_OK > DBexecute("update profiles set idx='web.user.sort'"
				" where idx='web.users.php.sort'"))
		return FAIL;

	if (ZBX_DB_OK > DBexecute("update profiles set idx='web.user.sortorder'"
				" where idx='web.users.php.sortorder'"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_4030012(void)
{
	const ZBX_FIELD	field = {"flags", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0};

	return DBadd_field("autoreg_host", &field);
}

static int	DBpatch_4030013(void)
{
	const ZBX_FIELD	field = {"flags", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0};

	return DBadd_field("proxy_autoreg_host", &field);
}

static int	DBpatch_4030014(void)
{
	const ZBX_FIELD	field = {"view_mode", "0", NULL, NULL, 0, ZBX_TYPE_INT, ZBX_NOTNULL, 0};

	return DBadd_field("widget", &field);
}

static int	DBpatch_4030015(void)
{
	if (ZBX_DB_OK > DBexecute("update widget set x=x*2, width=width*2"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_4030016(void)
{
	int		i;
	const char      *values[] = {
			"alarm_ok",
			"no_sound",
			"alarm_information",
			"alarm_warning",
			"alarm_average",
			"alarm_high",
			"alarm_disaster"
		};

	if (0 == (program_type & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	for (i = 0; i < ARRSIZE(values); i++)
	{
		if (ZBX_DB_OK > DBexecute(
				"update profiles"
				" set value_str='%s.mp3'"
				" where value_str='%s.wav'"
					" and idx='web.messages'", values[i], values[i]))
		{
			return FAIL;
		}
	}

	return SUCCEED;
}

static int	DBpatch_4030017(void)
{
	const ZBX_FIELD	field = {"opdata", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBrename_field("triggers", "details", &field);
}

static int	DBpatch_4030018(void)
{
	if (0 == (program_type & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > DBexecute("update profiles set idx='web.problem.filter.show_opdata'"
				" where idx='web.problem.filter.show_latest_values'"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_4030019(void)
{
	if (0 == (program_type & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > DBexecute("update widget_field"
				" set name='show_opdata'"
				" where name='show_latest_values'"
					" and exists (select null"
						" from widget"
						" where widget.widgetid=widget_field.widgetid"
							" and widget.type in ('problems','problemsbysv'))"))
		return FAIL;

	return SUCCEED;
}

#endif

DBPATCH_START(4030)

/* version, duplicates flag, mandatory flag */

DBPATCH_ADD(4030000, 0, 1)
DBPATCH_ADD(4030001, 0, 1)
DBPATCH_ADD(4030002, 0, 1)
DBPATCH_ADD(4030003, 0, 1)
DBPATCH_ADD(4030004, 0, 1)
DBPATCH_ADD(4030005, 0, 1)
DBPATCH_ADD(4030006, 0, 1)
DBPATCH_ADD(4030007, 0, 1)
DBPATCH_ADD(4030008, 0, 1)
DBPATCH_ADD(4030009, 0, 1)
DBPATCH_ADD(4030010, 0, 1)
DBPATCH_ADD(4030011, 0, 1)
DBPATCH_ADD(4030012, 0, 1)
DBPATCH_ADD(4030013, 0, 1)
DBPATCH_ADD(4030014, 0, 1)
DBPATCH_ADD(4030015, 0, 1)
DBPATCH_ADD(4030016, 0, 1)
DBPATCH_ADD(4030017, 0, 1)
DBPATCH_ADD(4030018, 0, 1)
DBPATCH_ADD(4030019, 0, 1)

DBPATCH_END()
