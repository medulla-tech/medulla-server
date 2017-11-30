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
/*
 * Get all available reports
 */

function get_updates($params) {
    return xmlCall("pulseupdate.get_updates", array($params));
}

function get_os_classes($params = array()) {
    return xmlCall("pulseupdate.get_os_classes", array($params));
}

function get_update_types() {
    // Caching update types as session var
    if (!isset($_SESSION['update_types'])) {
        $updtypes = xmlCall("pulseupdate.get_update_types", array(array()));
        $_SESSION['update_types'] = $updtypes['data'];
    }
    return $_SESSION['update_types'];
}

function set_update_status($update_id, $status) {
    return xmlCall("pulseupdate.set_update_status", array($update_id, $status));
}

function enable_only_os_classes($os_classes_id){
    return xmlCall("pulseupdate.enable_only_os_classes", array($os_classes_id));
}

function create_update_commands(){
    return xmlCall("pulseupdate.create_update_commands", array());
}

function getProductUpdates(){
    return xmlCall("pulseupdate.getProductUpdates", array());
}

function installProductUpdates(){
    return xmlCall("pulseupdate.installProductUpdates", array());
}
function xmlrpc_setfromupdatelogxmpp(   $text,
                                            $type = "infouser",
                                            $sessionname = '' ,
                                            $priority = 0,
                                            $who = '',
                                            $how = '',
                                            $why = '',
                                            $action = '',
                                            $touser =  '',
                                            $fromuser = "",
                                            $module = 'update'){
    return xmlCall("xmppmaster.setlogxmpp", array(  $text,
                                                    $type ,
                                                    $sessionname,
                                                    $priority,
                                                    $who,
                                                    $how,
                                                    $why,
                                                    $module,
                                                    $action,
                                                    $touser,
                                                    $fromuser));
}                                                    
?>
