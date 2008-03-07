<?php
/**
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007-2008 Mandriva, http://www.mandriva.com
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

global $conf;
require("modules/base/includes/users.inc.php");
require("includes/template.inc.php");
require("localSidebar.php");
require("graph/navbar.inc.php");

$aclString = getAcl($_GET["user"]);
$aclArr = createAclArray($aclString);
$acl = $aclArr["acl"];
$aclattr = $aclArr["aclattr"];
$acltab = $aclArr["acltab"];

if ($_POST["buser"]) {
    /* FIXME: Why is the unset needed ? */
    unset($acl[0]);
    unset($aclattr[0]);
    unset($acltab[0]);
    foreach ($_SESSION['supportModList'] as $mod) {
        unset($acl[$mod]);
    }
    /* Set POST arrays as empty when not set */
    foreach(array("acl", "aclattr", "acltab") as $postvar) {
        if (!isset($_POST[$postvar])) {
            $_POST[$postvar] = array();
        }
    }
    foreach ($_POST["acl"] as $key => $value) {
        $acl[$key] = $value;
    }
    foreach ($_POST["acltab"] as $key => $value) {
        $acltab[$key] = $value;
    }
    foreach ($_POST["aclattr"] as $key => $value) {
        $aclattr[$key] = $value;
    }
    setAcl($_GET["user"], createAclString($acl, $acltab, $aclattr));
    if (!isXMLRPCError()) {
        new NotifyWidgetSuccess(_("User ACLs successfully modified."));
    }
}

function createAclAttrTemplate($module_name, $aclattr) {
    global $aclArray;
    $rowNum=1;

    //if not acl array
    if (!$aclArray[$module_name]) { return ''; }

    $tpl = new Template('.', 'keep');
    $tpl->set_file("main", "modules/base/templates/editacl_attr.html");
    $tpl->set_block("main", "formacl", "formacls");

    $tpl->set_var("hide",_("hide"));
    $tpl->set_var("description_dsc",_("description"));
    $tpl->set_var("read_only",_("read only"));
    $tpl->set_var("read_write",_("read/write"));

    //foreach $aclArray definition in infopackage
    foreach ($aclArray[$module_name] as $key => $value) {

        //alternate class for display
        if ($rowNum%2) {
            $tr_class='class="alternate"';
        }
        else {
            $tr_class="";
        }
        $rowNum++;

        $tpl->set_var("tr_class",$tr_class);
        $tpl->set_var("nom",$key);
        $tpl->set_var("description",$value);
        $tpl->set_var("checked_hide","");
        $tpl->set_var("checked_ro","");
        $tpl->set_var("checked_rw","");

        if ($aclattr[$key]=="ro") {
            $tpl->set_var("checked_ro","checked");
        } else if ($aclattr[$key]=="rw"){
            $tpl->set_var("checked_rw","checked");
        } else {
            $tpl->set_var("checked_hide","checked");
        }

        $tpl->parse('formacls', 'formacl', true);
    }

    $tmp = $tpl->parse("tmp", "main");
    return $tmp;
}

function createRedirectAclTemplate($module_name, $acl, $acltab) {
    global $descArray;
    global $redirArray;
    global $tabDescArray;
    global $tabAclArray;
    $key = $module_name;
    $rowNum = 1;

    $tpl = new Template('.', 'keep');

    $tpl->set_file("ptable", "modules/base/templates/editacl_page.html");
    $tpl->set_block("ptable", "actiontab", "actiontabs");
    $tpl->set_block("ptable", "action", "actions");
    $tpl->set_block("ptable", "submodule", "submodules");
    $tpl->set_var("module_name",$module_name);
    $tpl->set_var("select_all",_("Select all"));
    $tpl->set_var("unselect_all",_("Deselect all"));
    $tpl->set_var("function_name_desc",_("Function name"));
    $tpl->set_var("authorize",_("Authorize"));

    $value = $redirArray[$module_name];
    foreach ($value as $subkey => $subvalue) {
        $tpl->set_var("submodule_name",$subkey);

        foreach ($subvalue as $actionkey => $actionvalue) {
            if ($rowNum % 2) {
                $tr_class = 'class="alternate"';
            } else {
                $tr_class = "";
            }
            $rowNum++;

            $tpl->set_var("tr_class",$tr_class);
            //check if checked action
            if ($acl[$key][$subkey][$actionkey]["right"]) {
                $tpl->set_var("checked","checked");
            } else {
                $tpl->set_var("checked","");
            }


            if ($descArray[$key][$subkey][$actionkey]) {
                $tpl->set_var("desc_action",$descArray[$key][$subkey][$actionkey]);
            } else {
                $tpl->set_var("desc_action",_("Warning no desc found in infoPackage.inc.php :").$actionkey);
            }

            $tpl->set_var("action_name", $actionkey);
            
            if (isset($tabAclArray[$key][$subkey][$actionkey])) {
                foreach($tabAclArray[$key][$subkey][$actionkey] as $tabkey => $tabvalue) {
                    if ($rowNum % 2) {
                        $tr_class = 'class="alternate"';
                    } else {
                        $tr_class = "";
                    }
                    $rowNum++;
                    if ($acltab[$key][$subkey][$actionkey][$tabkey]["right"]) {
                        $tpl->set_var("checkedtab","checked");
                    } else {
                        $tpl->set_var("checkedtab","");
                    }
                    $tpl->set_var("tr_classtab",$tr_class);
                    $tpl->set_var("tab_name", $tabkey);
                    $tpl->set_var("desc_actiontab", $descArray[$key][$subkey][$actionkey] . " : " . $tabDescArray[$key][$subkey][$actionkey][$tabkey]);
                    $tpl->parse('actiontabs', 'actiontab', true);
                }
            } else $tpl->set_var("actiontabs", "");
            $tpl->parse('actions', 'action', true);
        }
        $tpl->parse('submodules', 'submodule', true);
        $tpl->set_var("actions","");
    }
    $tmp = $tpl->parse("tmp", "ptable");
    return $tmp;
}

$p = new PageGenerator(sprintf(_("Edit ACL of user %s"), $_GET["user"]));
$sidemenu->forceActiveItem("index");
$p->setSideMenu($sidemenu);
$p->display();

$tpl = new Template('.', 'keep');
global $descArray;

$tpl->set_var("edit_acl",_("Edit ACL of user") . "&nbsp;" . $_GET["user"]);
$tpl->set_file("main", "modules/base/templates/editacl.html");
$tpl->set_block("main", "module", "modules");

foreach ($_SESSION["modulesList"] as $key) {
    $MMCApp =&MMCApp::getInstance();
    $mod = $MMCApp->getModule($key);
    if ($mod != null) {
        $mod_name = $mod->getDescription();
        $tpl->set_var("module_name",$mod_name);
        //check if plugin have information in redirArray
        if ($redirArray[$key]) {
            $tpl->set_var("aclpage_template",createRedirectAclTemplate($key,$acl, $acltab));
        } else {
            $tpl->set_var("aclpage_template",'');
        }
        
        $tpl->set_var("aclattr_template",createAclAttrTemplate($key,$aclattr));
        $tpl->parse('modules', 'module', true);
    }

}
$tpl->set_var("button", _("Confirm"));
$tpl->pparse("out", "main");
?>
