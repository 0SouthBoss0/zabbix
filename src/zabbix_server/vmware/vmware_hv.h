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
#ifndef ZABBIX_VMWARE_HV_H
#define ZABBIX_VMWARE_HV_H

#include "config.h"

#if defined(HAVE_LIBXML2) && defined(HAVE_LIBCURL)

#include "vmware.h"
#include "vmware_internal.h"

void	vmware_hv_clean(zbx_vmware_hv_t *hv);

int	vmware_service_init_hv(zbx_vmware_service_t *service, CURL *easyhandle, const char *id,
		zbx_vector_vmware_datastore_t *dss, zbx_vector_vmware_resourcepool_t *rpools,
		zbx_vector_cq_value_t *cq_values, zbx_vmware_alarms_data_t *alarms_data, zbx_vmware_hv_t *hv,
		char **error);

void	vmware_hv_shared_clean(zbx_vmware_hv_t *hv);

int	vmware_service_get_hv_ds_dc_dvs_list(const zbx_vmware_service_t *service, CURL *easyhandle,
		zbx_vmware_alarms_data_t *alarms_data, zbx_vector_str_t *hvs, zbx_vector_str_t *dss,
		zbx_vector_vmware_datacenter_t *datacenters, zbx_vector_vmware_dvswitch_t *dvswitches,
		zbx_vector_str_t *vc_alarm_ids, char **error);

#endif	/* defined(HAVE_LIBXML2) && defined(HAVE_LIBCURL) */

#endif	/* ZABBIX_VMWARE_HV_H */


