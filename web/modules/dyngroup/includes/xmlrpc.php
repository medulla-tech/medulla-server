<?php
/**
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007 Mandriva, http://www.mandriva.com
 *
 * $Id$
 *
 * This file is part of Mandriva Management Console (MMC).
 *
 * MMC is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * MMC is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MMC; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

function isProfilesEnable() {
    if (!isset($_SESSION["isProfilesEnable"])) {
        $_SESSION["isProfilesEnable"] = xmlCall("dyngroup.isProfilesEnable", null);
    }
    return $_SESSION["isProfilesEnable"];
}

function isDynamicEnable() {
    if (!isset($_SESSION["isDynamicEnable"])) {
        $_SESSION["isDynamicEnable"] = xmlCall("dyngroup.isDynamicEnable", null);
    }
    return $_SESSION["isDynamicEnable"];
}

function getDefaultModule() {
    if (!isset($_SESSION["defaultModule"])) {
        $_SESSION["defaultModule"] = xmlCall("dyngroup.getDefaultModule", null);
    }
    return $_SESSION["defaultModule"];
}

function updateMachineCache() {
    return xmlCall("dyngroup.update_machine_cache", null);
}

function getMaxElementsForStaticList() {
    if (!isset($_SESSION["maxElementsForStaticList"])) {
        $_SESSION["maxElementsForStaticList"] = xmlCall("dyngroup.getMaxElementsForStaticList", null);
    }
    return $_SESSION["maxElementsForStaticList"];
}

?>
