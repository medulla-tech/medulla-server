<?

/**
 * (c) 2012 Mandriva, http://www.mandriva.com
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
 * along with MMC.  If not, see <http://www.gnu.org/licenses/>.
 */

include("modules/msc/msc/vnc_client.php");

if ($_GET['cn']) $_SESSION['cn'] = $_GET['cn'];
if ($_GET['objectUUID']) $_SESSION['objectUUID'] = $_GET['objectUUID'];
if ($_GET['action']) $_SESSION['action'] = $_GET['action'];

$paramArray = array('vnc' => '', 'cn' => $_SESSION['cn'], 'objectUUID' => $_SESSION['objectUUID']);


$inventAction = new ActionItem(_("Inventory"),"invtabs","inventory","inventory", "base", "computers");
$vncClientAction = new ActionItem(_("Remote control"), $_SESSION['action'], "vncclient", "computer", "base", "computers");
$logAction = new ActionItem(_("Read log"),"msctabs","logfile","computer", "base", "computers", "tablogs");
$mscAction = new ActionItem(_("Software deployment"),"msctabs","install","computer", "base", "computers");
$imgAction = new ActionItem(_("Imaging management"),"imgtabs","imaging","computer", "base", "computers");


echo '<div style="float: right; width: 200px;">';
echo '<table border="0" style="border: none;" cellpadding="0" cellspacing="0"><tr>';
    
$actions = array($inventAction, $vncClientAction, $logAction, $mscAction, $imgAction);
foreach ($actions as $action){
	echo '<td style="border: none">';    
        if (is_array($paramArray)) {
	   $paramArray['mod'] = $action->mod;
        }    
        echo "<li class=\"".$action->classCss."\" style=\"list-style-type: none; border: none; float:left; \" >";
        if (is_array($paramArray) & !empty($paramArray)) $urlChunk = $action->buildUrlChunk($paramArray);
        else $urlChunk = "&amp;" . $action->paramString."=" . rawurlencode($paramArray);
	if ($action->action == "vnc_client"){
            echo "<a title=\"".$action->desc."\" href=\"main.php?module=".$action->module."&amp;submod=".$action->submod."&amp;action=" . $action->action . $urlChunk . "\"";
            echo " onclick=\"PopupWindow(event,'main.php?module=".$action->module."&amp;submod=".$action->submod."&amp;action=" .$action->action . $urlChunk . "', " . $action->width . "); return false;\">&nbsp;</a>"; 
	}
	else {
	    echo "<a title=\"".$action->desc."\" href=\"" . urlStr($action->path) . $urlChunk . "\">&nbsp;</a>";
	}
        echo "</li>";
	echo '</td>';
}
echo '</tr></table>';	    
echo '</div>';
?>
