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

#ifndef ZABBIX_RTC_SERVER_H
#define ZABBIX_RTC_SERVER_H

#include "zbxrtc.h"
#include "zbxtypes.h"

int	rtc_parse_options_ex(const char *opt, zbx_uint32_t *code, char **data, char **error);
int	rtc_process_request_ex(zbx_rtc_t *rtc, int code, const unsigned char *data, char **result);
int	zbx_rtc_process(const char *option, char **error);
void	zbx_rtc_reset(zbx_rtc_t *rtc);
int	zbx_rtc_open(zbx_ipc_async_socket_t *asocket, int timeout, char **error);
#endif
