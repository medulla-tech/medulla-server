# -*- coding: utf-8; -*-
# SPDX-FileCopyrightText: 2018-2023 Siveo <support@siveo.net>
# SPDX-License-Identifier: GPL-3.0-or-later

from pulse2.version import getVersion, getRevision  # pyflakes.ignore

# Au cas où on souhaite appeler des configs d'autres modules
from mmc.support.config import PluginConfig, PluginConfigFactory
from mmc.plugins.updates.config import UpdatesConfig

# import pour la database
from pulse2.database.updates import UpdatesDatabase

from pulse2.database.xmppmaster import XmppMasterDatabase
from mmc.plugins.glpi.database import Glpi
import logging

VERSION = "1.0.0"
APIVERSION = "1:0:0"


logger = logging.getLogger()


# #############################################################
# PLUGIN GENERAL FUNCTIONS
# #############################################################


def getApiVersion():
    return APIVERSION


def activate():
    logger = logging.getLogger()
    config = UpdatesConfig("updates")

    if config.disable:
        logger.warning("Plugin updates: disabled by configuration.")
        return False

    if not UpdatesDatabase().activate(config):
        logger.warning(
            "Plugin updates: an error occurred during the database initialization"
        )
        return False
    return True


def tests():
    return UpdatesDatabase().tests()


def test_xmppmaster():
    return UpdatesDatabase().test_xmppmaster()


def get_grey_list(start, end, filter=""):
    return UpdatesDatabase().get_grey_list(start, end, filter)


def get_white_list(start, end, filter=""):
    return UpdatesDatabase().get_white_list(start, end, filter)


def get_black_list(start, end, filter=""):
    return UpdatesDatabase().get_black_list(start, end, filter)


def get_enabled_updates_list(entity, upd_list="gray", start=0, end=-1, filter=""):
    if upd_list not in ["gray", "white"]:
        upd_list = "gray"
    # The glpi config is sent to updatedatabase to get the filter_on param
    datas = UpdatesDatabase().get_enabled_updates_list(
        entity, upd_list, start, end, filter, Glpi().config
    )
    count_glpi = Glpi().get_machines_list1(0, 0, {"location": entity})
    datas["total"] = count_glpi["count"]
    return datas


def get_family_list(start, end, filter=""):
    return UpdatesDatabase().get_family_list(start, end, filter)


def approve_update(updateid):
    return UpdatesDatabase().approve_update(updateid)


def grey_update(updateid, enabled=0):
    return UpdatesDatabase().grey_update(updateid, enabled)


def exclude_update(updateid):
    return UpdatesDatabase().exclude_update(updateid)


def get_count_machine_as_not_upd(updateid):
    return UpdatesDatabase().get_count_machine_as_not_upd(updateid)


def delete_rule(id):
    return UpdatesDatabase().delete_rule(id)


def white_unlist_update(updateid):
    return UpdatesDatabase().white_unlist_update(updateid)


def get_machine_with_update(kb, updateid, uuid, start=0, limit=-1, filter=""):
    result = XmppMasterDatabase().get_machine_with_update(
        kb, updateid, uuid, start, limit, filter, Glpi().config
    )
    return result


def get_count_machine_with_update(kb, uuid, list):
    return Glpi().get_count_machine_with_update(kb, uuid, list)


def get_machines_needing_update(updateid, entity, start=0, limit=-1, filter=""):
    return UpdatesDatabase().get_machines_needing_update(
        updateid, entity, Glpi().config, start, limit, filter
    )


def get_conformity_update_by_entity(entities=[]):
    """Get the conformity for specified entities"""

    # init resultarray with default datas
    # init entitiesarray with entities ids, this will be used in the "in" sql clause
    resultarray = {}
    entitieslist = []
    for entity in entities:
        eid = entity["uuid"].replace("UUID", "")
        entitieslist.append(eid)
        total = Glpi().get_machines_list1(0, 0, {"location": entity["uuid"]})

        rtmp = {
            "entity": eid,
            "nbmachines": 0,
            "nbupdate": 0,
            "totalmach": total["count"],
            "conformite": 100,
        }
        resultarray[entity["uuid"]] = rtmp
    result = XmppMasterDatabase().get_conformity_update_by_entity(
        entitieslist, Glpi().config
    )

    for counters in result:
        euid = "UUID%s" % counters["entity"]
        resultarray[euid]["nbmachines"] = counters[
            "nbmachines"
        ]  # count machines with missing updates
        resultarray[euid]["nbupdate"] = counters[
            "nbupdates"
        ]  # count updates for this entity
        if resultarray[euid]["totalmach"] > 0 and int(counters["nbmachines"]) > 0:
            resultarray[euid]["conformite"] = int(
                (
                    (resultarray[euid]["totalmach"] - counters["nbmachines"])
                    / resultarray[euid]["totalmach"]
                )
                * 100
            )
    return resultarray


def get_conformity_update_by_machines(ids=[]):
    """ids is formated as :
    {
        "uuids": ["UUID4", "UUID3"], // glpi inventory uuids
        "ids": [4,3]
    }
    """

    result = {}
    for uuid in ids["uuids"]:
        result[uuid] = {
            "uuid": "",
            "id": "",
            "missing": 0,
            "hostname": "",
            "installed": 0,
            "total": 0,
            "compliance": 100.0,
        }
    range = len(ids["uuids"])
    count = 0
    while count < range:
        result[ids["uuids"][count]]["id"] = ids["ids"][count]
        count += 1

    if ids["ids"] == "" or ids["ids"] == []:
        history = {}
    else:
        history = XmppMasterDatabase().get_update_history_by_machines(ids["ids"])
    # History :
    # {'UUID3': [{'updateid': 'fd509dc0-2dfb-463b-9af9-34dc55cc5c47', 'id_machine': 14, 'kb': '890830'}]}

    if ids["uuids"] == "" or ids["uuids"] == []:
        installed = {}
    else:
        installed = Glpi().get_count_installed_updates_by_machines(ids["uuids"])

    # Installed :
    # {'UUID3': {'id': 3, 'cn': 'qa-win-9', 'installed': 5}}

    if ids["ids"] == "" or ids["ids"] == []:
        missing = {}
    else:
        missing = XmppMasterDatabase().get_count_missing_updates_by_machines(ids["ids"])

    # Missing :
    # {'UUID5': {'id': 16, 'uuid': 'UUID5', 'hostname': 'qa-win-6', 'missing': 2}}
    for uuid in history:
        result[uuid]["installed"] = len(history[uuid])

    for uuid in installed:
        result[uuid]["installed"] += installed[uuid]["installed"]

    for uuid in missing:
        result[uuid]["missing"] = missing[uuid]["missing"]

    for uuid in result:
        result[uuid]["total"] = result[uuid]["installed"] + result[uuid]["missing"]
        result[uuid]["compliance"] = (
            (result[uuid]["installed"] / result[uuid]["total"]) * 100
            if result[uuid]["total"] > 0
            else 100
        )

    return result
