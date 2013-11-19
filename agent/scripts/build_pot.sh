#!/bin/bash
#
# (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
# (c) 2007-2013 Mandriva, http://www.mandriva.com
#
# This file is part of Mandriva Management Console (MMC).
#
# MMC is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# MMC is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MMC.  If not, see <http://www.gnu.org/licenses/>.

[ ! -d mmc/plugins ] && echo "Run this script from the agent directory." && exit 1

# Generate POT for report templates
[ ! -x /usr/bin/pybabel ] && echo "You need to install python-babel to generate reports templates POT file." && exit 1
PYTHONPATH=${PYTHONPATH}:scripts pybabel extract -F scripts/babel.ini conf/plugins/report/templates/ > mmc/plugins/report/locale/templates.pot

exit 0
