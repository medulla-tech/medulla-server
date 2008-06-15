#!/usr/bin/python
# -*- coding: utf-8; -*-
#
# (c) 2007-2008 Mandriva, http://www.mandriva.com/
#
# $Id: __init__.py 30 2008-02-08 16:40:54Z nrueff $
#
# This file is part of Pulse 2, http://pulse2.mandriva.org
#
# Pulse 2 is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Pulse 2 is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Pulse 2; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
# MA 02110-1301, USA.

"""
    Pulse2 Package server types
"""

from pulse2.package_server.utilities import md5file, md5sum

class Mirror:
    def __init__(self, protocol = None, server = None, port = None, mountpoint = None):
        self.protocol = protocol
        self.server = server
        self.port = port
        self.mountpoint = mountpoint
        self.uuid = "UUID%s"%(mountpoint)

    def toH(self):
        return { 'protocol' : self.protocol, 'server' : self.server, 'port' : self.port, 'mountpoint' : self.mountpoint, 'uuid' : self.uuid }

    def fromH(self, h):
        self.protocol = h['protocol']
        self.server = h['server']
        self.port = h['port']
        self.mountpoint = h['mountpoint']
        self.uuid = "UUID%s" % (self.mountpoint)

    def equal(self, a):
        if self.protocol == a.protocol and self.server == a.server and self.port == a.port and self.mountpoint == a.mountpoint:
            return True
        return False


class Command:
    def __init__(self, name = '', command = ''):
        self.name = name
        self.command = command

    def toH(self):
        return { 'name':self.name, 'command':self.command }

    def fromH(self, h):
        if type(h) == dict:
            self.name = h['name']
            self.command = h['command']
        elif type(h) == Command:
            self.name = h.name
            self.command = h.command
        elif type(h) == str:
            self.command = h
            self.name = ''

    def to_s(self):
        return self.command

    def equal(self, c):
        if self.name != c.name or self.command != c.command:
            return False
        return True

def getCommandFromH(h):
    cmd = Command()
    cmd.fromH(h)
    return cmd

class A_Packages:
    def __init__(self, a):
        self.packages = a

class Package:
    def __init__(self):
        self.files = AFiles()
        self.specifiedFiles = []

    def init(self, id, label, version, size, description, cmd, initcmd = '', precmd = '', postcmd_ok = '', postcmd_ko = ''):
        self.label = label
        self.version = version
        self.size = size
        self.description = description
        self.initcmd = getCommandFromH(initcmd)
        self.precmd = getCommandFromH(precmd)
        self.cmd = getCommandFromH(cmd)
        self.postcmd_ok = getCommandFromH(postcmd_ok)
        self.postcmd_ko = getCommandFromH(postcmd_ko)
        self.id = id

    def addFile(self, file):
        self.files.append(file)

    def toH(self):
        return {
            'label':self.label,
            'version':self.version,
            'size':self.size,
            'id':self.id,
            'description':self.description,
            'installInit':self.initcmd.toH(),
            'preCommand':self.precmd.toH(),
            'command':self.cmd.toH(),
            'postCommandSuccess':self.postcmd_ok.toH(),
            'postCommandFailure':self.postcmd_ko.toH(),
            'files':self.files.toH()
        }

    def to_h(self):
        return self.toH()

    def fromH(self, h):
        self.label = h['label']
        self.version = h['version']
        self.id = h['id']
        self.size = h['size']
        self.description = h['description']

        self.initcmd = getCommandFromH(h['installInit'])
        self.precmd = getCommandFromH(h['preCommand'])
        self.cmd = getCommandFromH(h['command'])
        self.postcmd_ok = getCommandFromH(h['postCommandSuccess'])
        self.postcmd_ko = getCommandFromH(h['postCommandFailure'])

    def equal(self, p):
        if self.label != p.label or self.version != p.version or self.size != p.size or self.id != p.id or not(self.initcmd.equal(p.initcmd)) or not(self.precmd.equal(p.precmd)) or not(self.cmd.equal(p.cmd)) or not(self.postcmd_ok.equal(p.postcmd_ok)) or not(self.postcmd_ko.equal(p.postcmd_ko)) or self.description != p.description:
            return False
        # TODO check files
        return True

class AFiles:
    internals = []
    def append(self, elt):
        self.internals.append(elt)

    def toH(self):
        return map(lambda x: x.toH(), self.internals)

    def to_h(self):
        return self.toH()

class File:
    def __init__(self, name = None, path = '/', checksum = None, size = 0, proto = 'file', server_uri = '127.0.0.1', server_port = '80', server_mp = '', id = None):
        self.name = name
        self.path = path
        self.proto = proto
        self.checksum = checksum
        self.server_uri = server_uri
        self.server_port = server_port
        self.server_mp = server_mp
        self.size = size
        if id == None:
            self.id = md5sum(self.toS())
        else:
            self.id = id

    def toURI(self):
        return "%s://%s:%s%s%s/%s" % (self.proto, self.server_uri, str(self.server_port), self.server_mp, self.path, self.name)

    def toS(self):
        return "%s/%s" % (self.path, self.name)

    def to_s(self):
        return self.toS()

    def toH(self):
        return { 'name':self.name, 'path':self.path, 'proto':self.proto, 'id':self.id }

    def to_h(self):
        return self.toH()

class Machine:
    def __init__(self, name = None):
        self.name = name

    def uuid(self):
        return self.name

    def to_h(self):
        return { 'name' : self.name }

    def from_h(self, h):
        self.name = h['name']
        return self

    def equal(self, a):
        return self.name == a.name

class User:
    def __init__(self, name = None, uuid = None):
        self.name = name
        self.uuid = name

    def to_h(self):
        return { 'name' : self.name, 'uuid' : self.uuid }

    def from_h(self, h):
        self.name = h['name']
        self.uuid = h['uuid']

    def equal(self, a):
        return (self.name == a.name and self.uuid == a.uuid)


