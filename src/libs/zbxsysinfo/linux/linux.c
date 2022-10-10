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

#include "zbxsysinfo.h"

ZBX_METRIC parameters_specific[] =
/*	KEY			FLAG		FUNCTION		TEST PARAMETERS */
{
	{"kernel.maxfiles",	0,		kernel_maxfiles,	NULL},
	{"kernel.maxproc",	0,		kernel_maxproc,		NULL},
	{"kernel.openfiles",	0,		kernel_openfiles,	NULL},

	{"vfs.fs.size",		CF_HAVEPARAMS,	VFS_FS_SIZE,		"/,free"},
	{"vfs.fs.inode",	CF_HAVEPARAMS,	VFS_FS_INODE,		"/,free"},
	{"vfs.fs.discovery",	0,		VFS_FS_DISCOVERY,	NULL},
	{"vfs.fs.get",		0,		VFS_FS_GET,		NULL},

	{"vfs.dev.read",	CF_HAVEPARAMS,	VFS_DEV_READ,		"sda,operations"},
	{"vfs.dev.write",	CF_HAVEPARAMS,	VFS_DEV_WRITE,		"sda,operations"},
	{"vfs.dev.discovery",	0,		VFS_DEV_DISCOVERY,	NULL},

	{"net.tcp.listen",	CF_HAVEPARAMS,	net_tcp_listen,		"80"},
	{"net.udp.listen",	CF_HAVEPARAMS,	net_udp_listen,		"68"},

	{"net.tcp.socket.count",CF_HAVEPARAMS,	net_tcp_socket_count,	",80"},
	{"net.udp.socket.count",CF_HAVEPARAMS,	NET_UDP_SOCKET_COUNT,	",68"},

	{"net.if.in",		CF_HAVEPARAMS,	net_if_in,		"lo,bytes"},
	{"net.if.out",		CF_HAVEPARAMS,	net_if_out,		"lo,bytes"},
	{"net.if.total",	CF_HAVEPARAMS,	net_if_total,		"lo,bytes"},
	{"net.if.collisions",	CF_HAVEPARAMS,	net_if_collisions,	"lo"},
	{"net.if.discovery",	0,		net_if_discovery,	NULL},

	{"vm.memory.size",	CF_HAVEPARAMS,	VM_MEMORY_SIZE,		"total"},

	{"proc.cpu.util",	CF_HAVEPARAMS,	proc_cpu_util,		"inetd"},
	{"proc.get",		CF_HAVEPARAMS,	proc_get,		"inetd"},
	{"proc.num",		CF_HAVEPARAMS,	proc_num,		"inetd"},
	{"proc.mem",		CF_HAVEPARAMS,	proc_mem,		"inetd"},

	{"system.cpu.switches", 0,		SYSTEM_CPU_SWITCHES,	NULL},
	{"system.cpu.intr",	0,		SYSTEM_CPU_INTR,	NULL},
	{"system.cpu.util",	CF_HAVEPARAMS,	SYSTEM_CPU_UTIL,	"all,user,avg1"},
	{"system.cpu.load",	CF_HAVEPARAMS,	SYSTEM_CPU_LOAD,	"all,avg1"},
	{"system.cpu.num",	CF_HAVEPARAMS,	SYSTEM_CPU_NUM,		"online"},
	{"system.cpu.discovery",0,		SYSTEM_CPU_DISCOVERY,	NULL},

	{"system.uname",	0,		SYSTEM_UNAME,		NULL},

	{"system.hw.chassis",	CF_HAVEPARAMS,	SYSTEM_HW_CHASSIS,	NULL},
	{"system.hw.cpu",	CF_HAVEPARAMS,	SYSTEM_HW_CPU,		NULL},
	{"system.hw.devices",	CF_HAVEPARAMS,	SYSTEM_HW_DEVICES,	NULL},
	{"system.hw.macaddr",	CF_HAVEPARAMS,	SYSTEM_HW_MACADDR,	NULL},

	{"system.sw.arch",	0,		SYSTEM_SW_ARCH,		NULL},
	{"system.sw.os",	CF_HAVEPARAMS,	SYSTEM_SW_OS,		NULL},
	{"system.sw.packages",	CF_HAVEPARAMS,	SYSTEM_SW_PACKAGES,	NULL},

	{"system.swap.size",	CF_HAVEPARAMS,	SYSTEM_SWAP_SIZE,	"all,free"},
	{"system.swap.in",	CF_HAVEPARAMS,	SYSTEM_SWAP_IN,		"all"},
	{"system.swap.out",	CF_HAVEPARAMS,	SYSTEM_SWAP_OUT,	"all"},

	{"system.uptime",	0,		SYSTEM_UPTIME,		NULL},
	{"system.boottime",	0,		SYSTEM_BOOTTIME,	NULL},

	{"sensor",		CF_HAVEPARAMS,	get_sensor,		"w83781d-i2c-0-2d,temp1"},

	{NULL}
};
