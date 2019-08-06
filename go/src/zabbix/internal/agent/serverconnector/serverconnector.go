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

package serverconnector

import (
	"encoding/json"
	"errors"
	"fmt"
	"net"
	"strings"
	"time"
	"unicode/utf8"
	"zabbix/internal/agent"
	"zabbix/internal/agent/resultcache"
	"zabbix/internal/agent/scheduler"
	"zabbix/internal/monitor"
	"zabbix/internal/plugin"
	"zabbix/pkg/log"
	"zabbix/pkg/zbxcomms"
)

const hostMetadataLen = 255
const defaultAgentPort = 10050

type Connector struct {
	clientID    uint64
	input       chan interface{}
	address     string
	lastError   error
	resultCache *resultcache.ResultCache
	taskManager scheduler.Scheduler
}

type activeChecksRequest struct {
	Request      string `json:"request"`
	Host         string `json:"host"`
	Version      string `json:"version"`
	HostMetadata string `json:"host_metadata,omitempty"`
	ListenIP     string `json:"ip,omitempty"`
	ListenPort   int    `json:"port,omitempty"`
}

type activeChecksResponse struct {
	Response string            `json:"response"`
	Info     string            `json:"info"`
	Data     []*plugin.Request `json:"data"`
}

type agentDataResponse struct {
	Response string `json:"response"`
	Info     string `json:"info"`
}

func ParseServerActive() ([]string, error) {
	addresses := strings.Split(agent.Options.ServerActive, ",")

	for i := 0; i < len(addresses); i++ {
		if strings.IndexByte(addresses[i], ':') == -1 {
			if _, _, err := net.SplitHostPort(addresses[i] + ":10051"); err != nil {
				return nil, fmt.Errorf("error parsing the \"ServerActive\" parameter: address \"%s\": %s", addresses[i], err)
			}
			addresses[i] += ":10051"
		} else {
			if _, _, err := net.SplitHostPort(addresses[i]); err != nil {
				return nil, fmt.Errorf("error parsing the \"ServerActive\" parameter: address \"%s\": %s", addresses[i], err)
			}
		}

		for j := 0; j < i; j++ {
			if addresses[j] == addresses[i] {
				return nil, fmt.Errorf("error parsing the \"ServerActive\" parameter: address \"%s\" specified more than once", addresses[i])
			}
		}
	}

	return addresses, nil
}

type hostMetadataWriter struct {
	input chan *plugin.Result
}

func (h hostMetadataWriter) Write(result *plugin.Result) {
	h.input <- result
}

// Write function is used by ResultCache to print diagnostic information. It will be callled from
// different goroutine.
func (c *Connector) Addr() (s string) {
	return c.address
}

func (c *Connector) refreshActiveChecks() {
	var err error

	a := activeChecksRequest{Request: "active checks", Host: agent.Options.Hostname, Version: "4.4"}

	log.Debugf("[%d] In refreshActiveChecks() from [%s]", c.clientID, c.address)
	defer log.Debugf("[%d] End of refreshActiveChecks() from [%s]", c.clientID, c.address)

	if len(agent.Options.HostMetadata) > 0 {
		a.HostMetadata = agent.Options.HostMetadata
	} else if len(agent.Options.HostMetadataItem) > 0 {
		log.Debugf("[%d] retrieving HostMetadataItem [%s]", c.clientID, agent.Options.HostMetadataItem)

		h := hostMetadataWriter{input: make(chan *plugin.Result)}

		c.taskManager.UpdateTasks(0, h, []*plugin.Request{{Key: agent.Options.HostMetadataItem}})
		select {
		case r := <-h.input:
			a.HostMetadata = *r.Value
		case <-time.After(time.Duration(agent.Options.Timeout) * time.Second):
			log.Errf("[%d] timeout while getting host metadata using \"%s\" item specified by \"HostMetadataItem\" configuration parameter", c.clientID, agent.Options.HostMetadataItem)
			return
		}

		if !utf8.ValidString(a.HostMetadata) {
			log.Warningf("cannot get host metadata using \"%s\" item specified by \"HostMetadataItem\" configuration parameter: returned value is not an UTF-8 string", agent.Options.HostMetadataItem)
			return
		}

		var l int

		for i := range a.HostMetadata {
			if i > hostMetadataLen {
				log.Warningf("the returned value of \"%s\" item specified by \"HostMetadataItem\" configuration parameter is too long, using first %d characters", agent.Options.HostMetadataItem, l)
				a.HostMetadata = a.HostMetadata[:l]
				break
			}
			l = i
		}

		log.Debugf("[%d] retrieved HostMetadataItem [%s] value [%s]", c.clientID, agent.Options.HostMetadataItem, a.HostMetadata)
	}

	if len(agent.Options.ListenIP) > 0 {
		if i := strings.IndexByte(agent.Options.ListenIP, ','); i != -1 {
			a.ListenIP = agent.Options.ListenIP[:i]
		} else {
			a.ListenIP = agent.Options.ListenIP
		}
	}

	if agent.Options.ListenPort != defaultAgentPort {
		a.ListenPort = agent.Options.ListenPort
	}

	request, err := json.Marshal(&a)
	if err != nil {
		log.Errf("[%d] cannot create active checks request to [%s]: %s", c.clientID, c.address, err)
		return
	}

	data, err := zbxcomms.Exchange(c.address, time.Second*time.Duration(agent.Options.Timeout), request)

	if err != nil {
		if c.lastError == nil || err.Error() != c.lastError.Error() {
			log.Warningf("[%d] active check configuration update from [%s] started to fail (%s)", c.clientID,
				c.address, err)
			c.lastError = err
		}
		return
	}

	if c.lastError != nil {
		log.Warningf("[%d] active check configuration update from [%s] is working again", c.clientID, c.address)
		c.lastError = nil
	}

	var response activeChecksResponse

	err = json.Unmarshal(data, &response)
	if err != nil {
		log.Errf("[%d] cannot parse list of active checks from [%s]: %s", c.clientID, c.address, err)
		return
	}

	if response.Response != "success" {
		if len(response.Info) != 0 {
			log.Errf("[%d] no active checks on server [%s]: %s", c.clientID, c.address, response.Info)
		} else {
			log.Errf("[%d] no active checks on server [%s]", c.clientID, c.address)
		}

		return
	}

	if response.Data == nil {
		log.Errf("[%d] cannot parse list of active checks from [%s]: data array is missing", c.clientID,
			c.address)
		return
	}

	for i := 0; i < len(response.Data); i++ {
		if len(response.Data[i].Key) == 0 {
			if response.Data[i].Itemid == 0 {
				log.Errf("[%d] cannot parse list of active checks from [%s]: key is missing",
					c.clientID, c.address)
				return
			}

			log.Errf("[%d] cannot parse list of active checks from [%s]: key is missing for itemid '%d'",
				c.clientID, c.address, response.Data[i].Itemid)
			return
		}

		if response.Data[i].Itemid == 0 {
			log.Errf("[%d] cannot parse list of active checks from [%s]: itemid is missing for key '%s'",
				c.clientID, c.address, response.Data[i].Key)
			return
		}

		if len(response.Data[i].Delay) == 0 {
			log.Errf("[%d] cannot parse list of active checks from [%s]: delay is missing for itemid '%d'",
				c.clientID, c.address, response.Data[i].Itemid)
			return
		}

		if response.Data[i].LastLogsize == nil {
			log.Errf("[%d] cannot parse list of active checks from [%s]: lastlogsize is missing for itemid '%d'",
				c.clientID, c.address, response.Data[i].Itemid)
			return
		}

		if response.Data[i].Mtime == nil {
			log.Errf("[%d] cannot parse list of active checks from [%s]: mtime is missing for itemid '%d'",
				c.clientID, c.address, response.Data[i].Itemid)
			return
		}
	}

	c.taskManager.UpdateTasks(c.clientID, c.resultCache, response.Data)
}

// Write function is used by ResultCache to upload cached history. It will be callled from
// different goroutine.
func (c *Connector) Write(data []byte) (n int, err error) {
	b, err := zbxcomms.Exchange(c.address, time.Second*time.Duration(agent.Options.Timeout), data)
	if err != nil {
		return 0, err
	}

	var response agentDataResponse

	err = json.Unmarshal(b, &response)
	if err != nil {
		return 0, err
	}

	if response.Response != "success" {
		if len(response.Info) != 0 {
			return 0, fmt.Errorf("%s", response.Info)
		}
		return 0, errors.New("unsuccessful response")
	}

	return len(data), nil
}

func (c *Connector) run() {
	var start time.Time

	defer log.PanicHook()
	log.Debugf("[%d] starting server connector for '%s'", c.clientID, c.address)

	ticker := time.NewTicker(time.Second)
run:
	for {
		select {
		case <-ticker.C:
			c.resultCache.Flush()
			if time.Since(start) > time.Duration(agent.Options.RefreshActiveChecks)*time.Second {
				c.refreshActiveChecks()
				start = time.Now()
			}
		case <-c.input:
			break run
		}
	}
	close(c.input)
	log.Debugf("[%d] server connector has been stopped", c.clientID)
	monitor.Unregister()
}

func New(taskManager *scheduler.Manager, address string) *Connector {
	c := &Connector{
		taskManager: taskManager,
		address:     address,
		input:       make(chan interface{}, 10),
		clientID:    agent.NewClientID(),
	}
	c.resultCache = resultcache.NewActive(c.clientID, c)

	return c
}

func (c *Connector) Start() {
	c.resultCache.Start()
	monitor.Register()
	go c.run()
}

func (c *Connector) Stop() {
	c.input <- nil
	c.resultCache.Stop()
}
