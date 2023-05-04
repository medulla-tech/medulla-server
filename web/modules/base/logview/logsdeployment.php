<?php
/**
 *
 * (c) 2015-2017 Siveo, http://http://www.siveo.net
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
 * File : logsdeployment.php
 */

 /*
 this page show logs table
+-------------+------------------+------+-----+-------------------+----------------+
| Field       | Type             | Null | Key | Default           | Extra          |
+-------------+------------------+------+-----+-------------------+----------------+
| date        | timestamp        | NO   |     | CURRENT_TIMESTAMP |                |
| fromuser    | varchar(45)      | YES  |     | NULL              |                |
| touser      | varchar(45)      | YES  |     | NULL              |                |
| action      | varchar(45)      | YES  |     | NULL              |                |
| type        | varchar(6)       | NO   |     | noset             |                |
| module      | varchar(45)      | YES  |     |                   |                |
| text        | varchar(255)     | NO   |     | NULL              |                |
| sessionname | varchar(45)      | YES  |     |                   |                |
| how         | varchar(255)     | YES  |     | ""                |                |
| who         | varchar(45)      | YES  |     | ""                |                |
| why         | varchar(255)     | YES  |     | ""                |                |
| priority    | int(11)          | YES  |     | 0                 |                |
+-------------+------------------+------+-----+-------------------+----------------+
key criterium for search
Inventory
Inventory requested
Deployment
User
Deployment planning
Deployment execution
Manual
Convergence
Planned
ARS
WOL sent
Master
*/
?>
<script src="jsframework/lib/plugin.jquery-ui/datatable/js/dataTables.buttons.min.js"></script>
<script src="jsframework/lib/plugin.jquery-ui/datatable/js/buttons.flash.min.js"></script>
<script src="jsframework/lib/jszip.min.js"></script>
<script src="jsframework/lib/pdfmake.min.js"></script>
<script src="jsframework/lib/vfs_fonts.js"></script>
<script src="jsframework/lib/plugin.jquery-ui/datatable/js/buttons.html5.min.js"></script>
<script src="jsframework/lib/plugin.jquery-ui/datatable/js/buttons.print.min.js"></script>

<?php
    require("graph/navbar.inc.php");
    require("localSidebar.inc.php");

    global $conf;
    $maxperpage = $conf["global"]["maxperpage"];
    if ($maxperpage >= 100){
        $maxperpage = 100;
    }
    elseif($maxperpage >= 75){
        $maxperpage = 75;
    }
    elseif($maxperpage >= 50){
        $maxperpage = 50;
    }

    class DateTimeTplnew extends DateTimeTpl{

        function __construct($name, $label = null){
            $this->label = $label;
            parent::__construct($name);
        }

        function display($arrParam = array()) {
            print "<label for=\"".$this->name."\">".$this->label."</label>\n";
            parent::display($arrParam);
        }
    }

class SelectItemlabeltitle extends SelectItem {
    var $title;
    /**
     * constructor
     */
    function __construct($idElt, $label = null, $title = null, $jsFunc = null, $style = null) {
        $this->title = $title;
        $this->label = $label;
        parent::__construct($idElt, $jsFunc, $style);
    }

    function to_string($paramArray = null) {
        $ret = "";
        if ($this->label){
            $ret = "<label for=\"".$this->id."\">".$this->label."</label>\n";
        }

        $ret .= "<select";
        if ($this->title){
            $ret .= " title=\"" . $this->title . "\"";
        }
        if ($this->style) {
            $ret .= " class=\"" . $this->style . "\"";
        }
        if ($this->jsFunc) {
            $ret .= " onchange=\"" . $this->jsFunc . "(";
            if ($this->jsFuncParams) {
                $ret .= implode(", ", $this->jsFuncParams);
            }
            $ret .= "); return false;\"";
        }
        $ret .= isset($paramArray["required"]) ? ' rel="required"' : '';
        $ret .= " name=\"" . $this->name . "\" id=\"" . $this->id . "\">\n";
        $ret .= $this->content_to_string($paramArray);
        $ret .= "</select>";
        return $ret;
    }
}

// ------------------------------------------------------------------------------------------------
    $p = new PageGenerator(_("Deployment Logs"));
    $p->setSideMenu($sidemenu);
    $p->display();

    $filterlogs = "Deployment";
    $headercolumn= "date@fromuser@who@sessionname@text";

?>

<script type="text/javascript">

var filterlogs = <?php echo "'$filterlogs'";?>;

function encodeurl(){
    var critere = filterlogs +
                    "|" + jQuery('#criteriasearch option:selected').val() +
                    "|" + jQuery('#criteriasearch1 option:selected').val() +
                    "|" + jQuery('#criteriasearch2 option:selected').val();
    uri = "modules/base/logview/ajax_Data_Logs.php"
    //QuickAction
    var param = {
        "start_date" : jQuery('#start_date').val(),
        "end_date"   : jQuery('#end_date').val(),
        "type" : "",
        "action" : "",
        "module" : critere,
        "user" : "",
        "how" : "",
        "who" : "",
        "why" : "",
        "headercolumn" : "<?php echo $headercolumn; ?>"
    }
    uri = uri +"?"+xwwwfurlenc(param)
    return uri
}

function xwwwfurlenc(srcjson){
    if(typeof srcjson !== "object")
      if(typeof console !== "undefined"){
        console.log("\"srcjson\" is not a JSON object");
        return null;
      }
    u = encodeURIComponent;
    var urljson = "";
    var keys = Object.keys(srcjson);
    for(var i=0; i <keys.length; i++){
        urljson += u(keys[i]) + "=" + u(srcjson[keys[i]]);
        if(i < (keys.length-1))urljson+="&";
    }
    return urljson;
}

    jQuery(function(){
        jQuery("p").click(function(){
            searchlogs( encodeurl());
        });
    });

function searchlogs(url){
        jQuery('#tablelog').DataTable({
        'retrieve': true,
        "lengthMenu" : [[10 ,20 ,30 ,40 ,50 ,75 ,100 ], [10, 20, 30, 40, 50 ,75 ,100 ]],
        "iDisplayLength": <?php echo $maxperpage; ?>,
        "dom": '<"top"lfi>rt<"bottom"Bp><"clear">',
        buttons: [
        { extend: 'copy', className: 'btn btn-primary', text: 'Copy to clipboard' },
        { extend: 'csv', className: 'btn btn-primary',  text: 'Save to csv file' },
        { extend: 'excel', className: 'btn btn-primary',  text: 'Save to Excel file' },
        { extend: 'print', className: 'btn btn-primary',  text: 'Print logs' }
        ]
    } )
                            .ajax.url(
                                url
                            )
                            .load();
    }

    jQuery(function(){
        searchlogs("modules/base/logview/ajax_Data_Logs.php?start_date=&end_date=&type=&action=&module=<?php echo $filterlogs; ?>%7CNone&user=&how=&who=&why=&headercolumn=<?php echo $headercolumn; ?>")
    });
    </script>

<?php

$typecritere  =        array(
                                        _T('Deployment Transfert','logs'),
                                        _T('Deployment Execution','logs'),
                                        _T('Deployment Download','logs'),
                                        _T('Deployment Notify','logs'),
                                        _T('Deployment Error','logs'),
                                        _T('Deployment Terminate','logs'),
                                        _T('WOL sent','logs'),
                                        _T('no criteria selected','logs'));

$typecritereval  =        array(
                                        'Transfert',
                                        'Execution',
                                        'Download',
                                        'Notify',
                                        'Error',
                                        'Terminate',
                                        'WOL',
                                        'None');


$start_date =   new DateTimeTplnew('start_date', "Start Date");
$end_date   =   new DateTimeTplnew('end_date', "End Date");

$modules = new SelectItemlabeltitle("criteriasearch", _T('criteria','logs'), "search criteria");
$modules->setElements($typecritere);
$modules->setSelected("None");
$modules->setElementsVal($typecritereval);


$modules1 = new SelectItemlabeltitle("criteriasearch1", _T('criteria','logs'), "search criteria1");
$modules1->setElements($typecritere);
$modules1->setSelected("None");
$modules1->setElementsVal($typecritereval);



$modules2 = new SelectItemlabeltitle("criteriasearch2", _T('criteria','logs'), "search criteria2");
$modules2->setElements($typecritere);
$modules2->setSelected("None");
$modules2->setElementsVal($typecritereval);

?>

<style>

.inline { display : inline; }

}

</style>
<?php

?>


<div style="overflow-x:auto;">
    <table border="1" cellspacing="0" cellpadding="5" class="listinfos">
        <thead>
            <tr>
                <th><?php echo $start_date->display(); ?></th>
                <th><?php echo $end_date->display(); ?></th>
                <th><?php echo $modules->display(); ?></th>
                <th><?php echo $modules1->display(); ?></th>
                <th><?php echo $modules2->display(); ?></th>
		<th><p class="btnPrimary">Filter logs</p></th>
            </tr>
        </thead>
     </table>
</div>


<br>

<table id="tablelog" width="100%" border="1" cellspacing="0" cellpadding="1" class="listinfos">
        <thead>
            <tr>
                <th style="width: 8%;"><?php echo _('Date'); ?></th>
                <th style="width: 8%;"><?php echo _('User'); ?></th>
                <th style="width: 6%;"><?php echo _('Who'); ?></th>
                <th style="width: 6%;"><?php echo _('Session name'); ?></th>

                <th><?php echo _('message'); ?></th>
            </tr>
        </thead>

    </table>
