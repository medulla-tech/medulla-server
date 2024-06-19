<?php
/**
 * @TODO: Licence
*/

/**
 * Differents possibilities to call this page
 *
 * without any parameter
 * http://server/mmc/bootmenu.php
 *
 * With the machine mac address
 * http://server/mmc/bootmenu.php?mac=aa:aa:aa:aa:aa:aa
 *
 * With the imaging server uuid
 * http://server/mmc/bootmenu.php?uuid=b7fdbd98-b20e-5430-91f1-8d3b647a925f
 *
 * With the machine mac address and the imaging server uuid
 * http://server/mmc/bootmenu.php?mac=aa:aa:aa:aa:aa:a&uuid=b7fdbd98-b20e-5430-91f1-8d3b647a925f
 *
 *
 * === PARAMS ===
 * mac : the machine mac address
 * srv : the {next-server} value refers to the imaging server selected
 * ==============
 */

// set DEBUG to true to display debug elements
// set DEBUG to false to return the text file content
if(!empty($_GET['debug']) && $_GET['debug'] = 1) {
    define("DEBUG", true);
} else {
    define("DEBUG", false);
}

$debug_ipxe = "";

if(!DEBUG) {
    // Need to print text this page as text content
    // The Content-type header is the first element to be set
    header('Content-type: Application/text');
}

function read_conf($conffile)
{
    $tmp = [];
    if(is_file($conffile)) {
        $content = file_get_contents($conffile);
        $content = str_replace("#", ";", $content);
        $tmp = parse_ini_string($content, false, INI_SCANNER_RAW);
    }
    return $tmp;
}

$conffiles = [
    "glpi" => "/etc/mmc/plugins/glpi.ini",
    "imaging" => "/etc/mmc/plugins/imaging.ini",
    "xmppmaster" => "/etc/mmc/plugins/xmppmaster.ini",
    "dyngroup" => "/etc/mmc/plugins/dyngroup.ini",
    "package-server" => "/etc/mmc/pulse2/package-server/package-server.ini"
];

$config = [];
$db = [];

foreach($conffiles as $module => $conffile) {
    $config[$module] = read_conf($conffile);
    $config[$module] = array_replace($config[$module], read_conf($conffile.'.local'));

    if(!empty($config[$module]['dbhost'])) {
        $host = (!empty($config[$module]['dbhost'])) ? htmlentities($config[$module]['dbhost']) : "localhost";
        $port = (!empty($config[$module]['dbport'])) ? htmlentities($config[$module]['dbport']) : 3306;
        $user = (!empty($config[$module]['dbuser'])) ? htmlentities($config[$module]['dbuser']) : "mmc";
        $passwd = (!empty($config[$module]['dbpasswd'])) ? htmlentities($config[$module]['dbpasswd']) : "";
        $name = (!empty($config[$module]['dbname'])) ? htmlentities($config[$module]['dbname']) : $module;
        $driver = (!empty($config[$module]['driver'])) ? explode("+", htmlentities($config[$module]['driver']))[0] : ((!empty($config[$module]['dbdriver'])) ? explode("+", htmlentities($config[$module]['dbdriver']))[0] : "mysql");
        try {
            $db[$module] = new PDO($driver.':host='.$host.';dbname='.$name.';port='.$port, $user, $passwd, [PDO::ATTR_PERSISTENT => false]);
        } catch(Exception $e) {
            // Uncomment this line to see the error message
            // echo $e->getMessage();
            exit;
        }
    }
}

// Get the parameters sent through URL
$mac = (!empty($_GET['mac'])) ? htmlentities($_GET['mac']) : "";
$srv = (!empty($_GET['srv'])) ? htmlentities($_GET['srv']) : "";
$srvHost = "";
if(inet_pton($srv) == true) {
    $srvHost = gethostbyaddr($srv);
}

$computer = [];
$target = [];
$menuId = 2;
$defaultMenuId = 1;
$lang = 1;
$computerName = "";
$ims = [];
$title = "";
$multicast = false;
$multicast_image_uuid = null;
$multicast_image_name = null;
$timeout = 10;
$menuMulticastTemplate = "#!ipxe
set loaded-menu MENU
cpuid --ext 29 && set arch x86_64 || set arch i386
goto get_console
:console_set
colour --rgb 0x00567a 1 ||
colour --rgb 0x00567a 2 ||
colour --rgb 0x00567a 4 ||
cpair --foreground 7 --background 2 2 ||
goto \${loaded-menu}
:alt_console
cpair --background 0 1 ||
cpair --background 1 2 ||
goto \${loaded-menu}
:get_console
console --picture http://\${next-server}/downloads/davos/ipxe.png --left 100 --right 80 && goto console_set || goto alt_console
:MENU
menu
colour --rgb 0xff0000 0 ||
cpair --foreground 1 1 ||
cpair --foreground 0 3 ||
cpair --foreground 4 4 ||
item --gap Host %s - %s registered on %s!
item --gap -- -------------------------------------
item clonezilla Restore Multicast %s
choose --default clonezilla --timeout 10000 target && goto \${target}
:clonezilla
set url_path http://\${next-server}/downloads/davos/
set kernel_args boot=live config noswap edd=on nomodeset nosplash noprompt vga=788 fetch=\${url_path}fs.squashfs mac=%s revorestorenfs image_uuid=%s davos_action=RESTORE_IMAGE_MULTICAST initrd=initrd.img
kernel \${url_path}vmlinuz \${kernel_args}
initrd \${url_path}initrd.img
boot || goto MENU
";
/*
:1 = mac | hostname
:2 = menu_description
:3 = mac
:4 = master_uuid
*/

// Get imaging server infos

if ($srvHost != "" || $srv != "") {
    // Get ImagingServer infos
    $query1 = $db['imaging']->prepare("SELECT * FROM ImagingServer ims
    join Entity e on ims.fk_entity = e.id
    where ims.url REGEXP ? or ims.url REGEXP ?
    ");

    // try to find on ip or hostname, the imagingserver url can point either on ip or hostname
    $query1->execute([$srvHost, $srv]);
    $ims = $query1->fetch(PDO::FETCH_ASSOC);
} else {
    $query1 = $db['imaging']->prepare("SELECT * FROM ImagingServer ims
    join Entity e on ims.fk_entity = e.id
    where ims.fk_default_menu = ?
    ");
    $query1->execute([$menuId]);
    $ims = $query1->fetch(PDO::FETCH_ASSOC);
}

$lang = (!empty($ims['fk_language'])) ? $ims['fk_language'] : $lang;
$defaultMenuId = (!empty($ims["fk_default_menu"])) ? $ims["fk_default_menu"] : $menuId;
$debug_ipxe .= "# Next-server : $srv
# Imaging server : $ims[name]
# Imaging host : $srvHost
# Imaging url : $ims[url]
# Imaging Server id : $ims[id]
# uuid : $ims[packageserver_uuid]
";

if($mac != "") {

    // Get the inventory associated to the mac address
    $query = $db['glpi']->prepare("
    SELECT
    CONCAT('UUID', gc.id) as uuid,
    gc.entities_id as euid,
    gc.name as name,
    ge.completename as entity
    FROM glpi_networkports gnp
    JOIN glpi_computers gc ON gc.id = gnp.items_id AND gnp.itemtype='Computer'
    JOIN glpi_entities ge ON gc.entities_id = ge.id
    where mac is not NULL and mac = ?
    ORDER BY  gc.id LIMIT 1
    ");

    $query->execute([$mac]);
    $computer = $query->fetch(Pdo::FETCH_ASSOC);
} else {
    $computer = [];
}

if($computer != []) {
    $query2 = $db["dyngroup"]->prepare("SELECT Machines.id AS Machines_id, 
    Machines.uuid AS Machines_uuid, 
    Machines.name AS Machines_name, 
    Groups.id AS Groups_id, 
    Groups.name AS Groups_name, 
    Groups.query AS Groups_query, 
    Groups.bool AS Groups_bool, 
    Groups.FK_users AS Groups_FK_users, 
    Groups.type AS Groups_type, 
    Groups.parent_id AS Groups_parent_id
FROM Machines 
LEFT OUTER JOIN ProfilesResults ON Machines.id = ProfilesResults.FK_machines 
LEFT OUTER JOIN Groups ON Groups.id = ProfilesResults.FK_groups 
LEFT OUTER JOIN GroupType ON GroupType.id = Groups.type 
WHERE GroupType.value = ? AND Machines.uuid = ? ");

    $query2->execute(["profile", $computer["uuid"]]);
    $group = $query2->fetch(PDO::FETCH_ASSOC);

    $gid = null;
    if($group != []) {
        $gid = $group['Groups_id'];
    }
    // try to load the menu for the selected machine
    if($ims != []) {
        $query3 = $db["imaging"]->prepare("SELECT t.name as target_name, t.*, ims.*, m.image_uuid as multicast_image_uuid, m.image_name as multicast_image_name from Target t
        join Entity e on t.fk_entity = e.id
        join ImagingServer ims on e.id=ims.fk_entity
        left join Multicast m on m.target_uuid = t.uuid
        where t.uuid = ?");
    } else {
        $query3 = $db["imaging"]->prepare("SELECT *,name as target_name from Target t where t.uuid = ?");
    }
    if($gid == null) {
        $query3->execute([$computer["uuid"]]);
    } else {
        $query3->execute([$gid]);
    }

    $target = $query3->fetch(PDO::FETCH_ASSOC);

    $lang = (!empty($target['fk_language'])) ? $target['fk_language'] : ((!empty($ims["fk_language"])) ? $ims["fk_language"] : 1);
    $menuId = (!empty($target['fk_menu'])) ? $target['fk_menu'] : $menuId;

    $computerName = $computer['name'];
    $title = (!empty($target['target_name'])) ? "Host $computerName - $mac registered on $srvHost" : "Host $computerName - $mac not registered on $srvHost";
    $multicast_image_uuid = (!empty($target['multicast_image_uuid'])) ? $target['multicast_image_uuid'] : null;
    $multicast_image_name = (!empty($target['multicast_image_name'])) ? $target['multicast_image_name'] : null;
    $multicast = ($multicast_image_uuid != null && $multicast_image_name != null) ? true : false;
    $debug_ipxe .= "# hostname : $computer[name]
# mac : $mac
# entity : $computer[uuid]
# entity name: $computer[entity]
# menu id : $menuId
# multicast : $multicast
# multicast_image_uuid : $multicast_image_uuid
# multicast_image_name : $multicast_image_name
";
    $debug_ipxe .= (!empty($target['target_name'])) ? "# target : found
" : "# target : not found
";
} else {
    $title = "Host \${mac} is NOT registered on \${next-server}!";

    $debug_ipxe .= "# hostname : not registered
# mac : $mac
# entity : ".(!empty($computer['uuid']) ? $computer['uuid'] : $ims['uuid'])."
# entity name: ".(!empty($computer["entity"]) ? $computer["entity"] : $ims["name"])."
# target : not found
# menu id : $menuId
";
}

$queryLanguage = $db["imaging"]->prepare("SELECT * FROM Language WHERE id=?");
$queryLanguage->execute([$lang]);
$langs = $queryLanguage->fetch(PDO::FETCH_ASSOC);

$keymap = "en";
if($langs["keymap"] != "C") {
    $keymap = substr($langs["keymap"], 0, 2);
}
$debug_ipxe .= "# Lang : $langs[label] - $keymap
";

$query4 = $db['imaging']->prepare("SELECT *,
mi.id,
(case when iim.fk_image is not NULL then 'image' else 'service' end) as type,
(m.fk_default_item = mi.id) as default_item,
(m.fk_default_item_WOL = mi.id) as default_item_WOL,
-- (case when i1.label != '' then i1.label else m.default_name end)as menu_name,
(case when i2.label != '' then i2.label else bs.default_name end) as bootService_name,
(case when i3.label != '' then i3.label else bs.default_desc end) as bootService_desc
from MenuItem mi
left join BootServiceInMenu bsim on mi.id = bsim.fk_menuitem
left join ImageInMenu iim on iim.fk_menuitem = mi.id
left join BootService bs on bsim.fk_bootservice = bs.id
left join Image i on i.id = iim.fk_image
join Menu m on mi.fk_menu = m.id
join Protocol p on p.id = m.fk_protocol
left join Internationalization i1 on i1.id = m.fk_name and i1.fk_language=:lang1
left join Internationalization i2 on i2.id = bs.fk_name and i2.fk_language=:lang2
left join Internationalization i3 on i3.id = bs.fk_desc and i3.fk_language=:lang3
where m.id = :menuId
order by mi.order ASC
");
$query4->execute(
    [
    "lang1" => $lang,
    "lang2" => $lang,
    "lang3" => $lang,
    "menuId" => $menuId]
);
$menu = $query4->fetchAll(Pdo::FETCH_ASSOC);

$timeout = (!empty($menu[0]) && !empty($menu[0]['timeout'])) ? $menu[0]['timeout'] : $timeout;
$timeoutMs = $timeout * 1000;
$debug_ipxe .= "# items:
";
foreach($menu as $item) {
    if(empty($item['bootService_name']) && empty($item['name'])) {
        continue;
    }
    if($item['type'] == 'service') {
        $debug_ipxe .= "#    - $item[bootService_name] $item[bootService_desc] - $item[type]";
    } elseif($item['type'] == 'image' && !empty($mac)) {
        $debug_ipxe .= "#    - $item[name] $item[desc] - $item[type]";
    }

    if($item['default_item'] == '1') {
        $debug_ipxe .= " (default)
";
    } else {
        $debug_ipxe .= "
";
    }
}

// error_log('call with params: srv:'.$srv.', mac: '.$mac.', default: '.$default.', uuid: '.$uuid);

$default_item = "";

if(!$multicast) {

    $ipxe = "#!ipxe

# ###
# SUMMARY
# ###
$debug_ipxe
# ###

set keymap $keymap
set loaded-menu MENU
cpuid --ext 29 && set arch x86_64 || set arch i386
goto get_console
:console_set
colour --rgb 0x00567a 1 ||
colour --rgb 0x00567a 2 ||
colour --rgb 0x00567a 4 ||
cpair --foreground 7 --background 2 2 ||
goto \${loaded-menu}
:alt_console
cpair --background 0 1 ||
cpair --background 1 2 ||
goto \${loaded-menu}
:get_console
console --picture http://\${next-server}/downloads/davos/ipxe.png --left 100 --right 80 && goto console_set || goto alt_console
:MAIN
menu
colour --rgb 0xff0000 0 ||
cpair --foreground 1 1 ||
cpair --foreground 0 3 ||
cpair --foreground 4 4 ||\n";
    $ipxe .= "item --gap $title\n";
    $ipxe .= "item --gap -- -------------------------------------
";
    $ipxe .= "item continue Continue Usual Startup
item protected Password
choose --default continue --timeout $timeoutMs target && goto \${target}
:MENU
menu
colour --rgb 0xff0000 0 ||
cpair --foreground 1 1 ||
cpair --foreground 0 3 ||
cpair --foreground 4 4 ||\n";
    $ipxe .= "item --gap $title
item --gap -- -------------------------------------
";
    $itemValues = "";
    foreach($menu as $item) {
        if(empty($item['bootService_name']) && empty($item['name'])) {
            continue;
        }

        if($item['type'] == 'service') {
            $ipxe .= "item $item[bootService_name] $item[bootService_desc]
";
        } elseif($item['type'] == 'image' && !empty($mac)) {
            $ipxe .= "item ".str_replace(" ", "-", $item["name"])." $item[name]
";
        }

        if ($item["default_item"] == 1) {
            $default_item = !empty($item["bootService_name"]) ? $item['bootService_name'] : $item['name'];
        }

        if($item['type'] == 'service') {
            $itemValues .= ":$item[bootService_name]
$item[value]
";
        } elseif($item['type'] == 'image' && !empty($mac)) {
            $itemValues .= ":".str_replace(" ", "-", $item["name"])."
set url_path http://\${next-server}/downloads/davos/
set kernel_args boot=live config noswap edd=on nomodeset nosplash noprompt vga=788 fetch=\${url_path}fs.squashfs mac=".strtoupper($mac)." davos_action=RESTORE_IMAGE image_uuid=$item[uuid] initrd=initrd.img
kernel \${url_path}vmlinuz \${kernel_args}
initrd \${url_path}initrd.img
boot || goto MENU
";
        }
    }

    $ipxe .= "choose --default $default_item --timeout $timeoutMs target && goto \${target}
:protected
login || goto \${loaded-menu}
iseq \${username}  && iseq \${password}  && set loaded-menu MENU || set loaded-menu MAIN
goto \${loaded-menu}
:exceptions
### The next 2 lines (I believe) override the options picked up via DHCP (i.e. options 67 etc)
### Set 210 --&gt; configure the destination TFTP server (holding the PXELinux kernel and config files)
set 210:string tftp://\${next-server}/bootloader/
### Set 209 --&gt; configure the location to the GRUB-format config files in PXELinux 
set 209:string pxelinux.cfg/localboot.cfg
### Chain --&gt; Load the new PXELinux kernel
chain tftp://\${next-server}/bootloader/pxelinux.0
goto MENU
";

    $ipxe .= $itemValues;
} else {
    $ipxe = sprintf($menuMulticastTemplate, $computerName, $mac, $srv, $multicast_image_name, $mac, $multicast_image_uuid);
}

// Replace all ##PULSE2_PARAM_NAME## in the final menu
$keys = ["diskless_dir", "diskless_kernel", "inventories_dir", "pxe_time_reboot", "diskless_initrd", "tools_dir", "davos_opts"];
foreach($keys as $key) {
    $regex = "PULSE2_".strtoupper($key);
    $value = $ims[$key];
    $ipxe = preg_replace("@\#\#".$regex."\#\#@", $value, $ipxe);
}

$ipxe = preg_replace("@\#\#MACADDRESS\#\#@", $mac, $ipxe);

if(DEBUG) {
    echo '<pre><code>';
    echo($ipxe);
    echo '</code></pre>';
} else {
    print($ipxe);
}