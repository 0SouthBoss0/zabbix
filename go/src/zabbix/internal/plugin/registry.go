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

package plugin

import (
	"errors"
	"fmt"
)

var Metrics map[string]Accessor = make(map[string]Accessor)

func RegisterMetric(impl Accessor, name string, key string, description string) {
	if _, ok := Metrics[key]; ok {
		panic(fmt.Sprintf(`cannot register duplicate metric "%s"`, key))
	}

	switch impl.(type) {
	case Exporter, Collector, Runner, Watcher, Configurator:
	default:
		panic(fmt.Sprintf(`plugin "%s" does not implement any plugin interfaces`, name))
	}

	impl.Init(name, key, description)
	Metrics[key] = impl
}

func RegisterMetrics(impl Accessor, name string, params ...string) {
	if len(params) < 2 {
		panic("expected at least one metric and its description")
	}
	if len(params)&1 != 0 {
		panic("expected even number of metric and description parameters")
	}
	for i := 0; i < len(params); i += 2 {
		RegisterMetric(impl, name, params[i], params[i+1])
	}
}

func Get(key string) (acc Accessor, err error) {
	var ok bool
	if acc, ok = Metrics[key]; ok {
		return
	}
	return nil, errors.New("no plugin found")
}

func ClearRegistry() {
	Metrics = make(map[string]Accessor)
}
