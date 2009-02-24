#
# (c) 2008 Mandriva, http://www.mandriva.com/
#
# $Id$
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

from pulse2.managers.location import ComputerLocationManager
import logging

def complete_ctx(ctx):
    """
    Set GLPI user locations and profile in current security context.
    """
    from mmc.plugins.glpi.database import Glpi
    if not hasattr(ctx, "locations") or ctx.locations == None:
        logging.getLogger().debug("adding locations in context for user %s" % (ctx.userid))
        ctx.locations = Glpi().getUserLocations(ctx.userid)
        ctx.locationsid = map(lambda e: e.ID, ctx.locations)
    if not hasattr(ctx, "profile"):
        logging.getLogger().debug("adding profiles in context for user %s" % (ctx.userid))
        ctx.profile = ComputerLocationManager().getUserProfile(ctx.userid)

