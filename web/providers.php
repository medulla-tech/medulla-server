<?php
require('phpseclib3/autoload.php');
require_once "oidc/OpenID-Connect-PHP-master/src/OpenIDConnectClient.php";

// part for error management before generation of the session following the return of authentication with the provider
require("includes/PageGenerator.php");
session_name("PULSESESSION");
session_start();

use Jumbojett\OpenIDConnectClient;

function fetchProvidersConfig() {
    $iniPath = "/etc/mmc/authproviders.ini";
    if (is_readable($iniPath)) {
        return parse_ini_file($iniPath, true);
    } else {
        echo "Error: Impossible to read the configuration file of the providers.";
        return false;
    }
}

function generateStr($length = 50) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// language management
if (isset($_POST['selectedLang'])) {
    $lang = $_POST['selectedLang'];
    setcookie('userLang', $lang, time() + 86400, '/');
}

$providersConfig = fetchProvidersConfig();

if ($providersConfig && (isset($_POST['selectedProvider']) || isset($_GET['code']))) {
    $provider = $_POST['selectedProvider'] ?? 'google';
    $providerKey = ucfirst(strtolower($provider));

    if (array_key_exists($providerKey, $providersConfig)) {
        $clientUrl = $providersConfig[$providerKey]['urlProvider'];
        $clientId = $providersConfig[$providerKey]['clientId'];
        $clientSecret = $providersConfig[$providerKey]['clientSecret'];

        // Check if the Provider URL is configured
        if (empty($clientUrl) || $clientUrl == "https://url_de_mon_provider") {
            new NotifyWidgetFailure("Supplier $providerKey selected is not properly configured.Please check the supplier URL in the configuration settings.");
            header("Location: /mmc/index.php");
            exit;
        }

        $oidc = new OpenIDConnectClient($clientUrl, $clientId, $clientSecret);
        $oidc->setRedirectURL('https://localhost/mmc/providers.php');
        $oidc->addScope(['email']);

        if (!isset($_GET['code'])) {
            $oidc->authenticate();
        } else {
            try {
                $oidc->authenticate();
                $token = $oidc->getAccessToken();
                $userInfo = $oidc->requestUserInfo();

                require_once("includes/utils.inc.php");
                require("includes/config.inc.php");
                require("modules/base/includes/users.inc.php");
                require("modules/base/includes/edit.inc.php");
                require("modules/base/includes/groups.inc.php");

                global $conf;
                $error = "";
                $login = "";

                /* Session creation */
                $ip = preg_replace('@\.@', '', $_SERVER["REMOTE_ADDR"]);
                $sessionid = md5(time() . $ip . mt_rand());

                session_id($sessionid);
                session_name("PULSESESSION");
                session_start();

                $_SESSION["ip_addr"] = $_SERVER["REMOTE_ADDR"];
                $_SESSION["XMLRPC_agent"] = parse_url($conf["server_01"]["url"]);
                $_SESSION["agent"] = "server_01";
                $_SESSION["XMLRPC_server_description"] = $conf["server_01"]["description"];
                $_SESSION['lang'] = $_COOKIE['userLang'];

                // TODO
                $login = "";
                $pass = "";

                include("includes/createSession.inc.php");

                // Users list
                $res = get_users_detailed($error, '', 0, 20);

                $newUser = $providerKey . "/" . $userInfo->email;
                $newPassUser = generateStr(50);
                $aclString =  $providersConfig[$providerKey]['lmcACL'];

                $found = false;
                foreach ($res[1] as $user) {
                    if ($user['uid']->scalar === $newUser) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $add = add_user(
                        $newUser,
                        prepare_string($newPassUser),
                        "family_name",
                        "given_name",
                        false,
                        true,
                        false,
                        'MedullaUsers'
                    );

                    if($add['code'] == 0) {
                        $setlmcACL = setAcl($newUser, $aclString);
                        $login = $newUser;
                        $pass = $newPassUser;
                        include("includes/createSession.inc.php");
                        /* Redirect to main page */
                        header("Location: main.php");
                        exit;
                    } else {
                        new NotifyWidgetFailure("Impossible to set up acls for the user $newUser", "Erreur acl");
                        header("Location: /mmc/index.php");
                        exit;
                    }
                } else {
                    $newPassUser = generateStr(50);
                    // Here we edit the user password
                    $ret = callPluginFunction("changeUserPasswd",
                    array(array($newUser, prepare_string($newPassUser)))
                    );

                    if (auth_user($newUser, $newPassUser, true)) {
                        $login = $newUser;
                        $pass = $newPassUser;
                        include("includes/createSession.inc.php");
                        /* Redirect to main page */
                        header("Location: main.php");
                        exit;
                    } else {
                        new NotifyWidgetFailure("Your account doesn't have access rights to Medulla. Contact the administrator of your entity to provide this access.", "Erreur d'authentification");
                        header("Location: /mmc/index.php");
                        exit;
                    }
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    } else {
        new NotifyWidgetFailure("The selected supplier is not supported or its configuration is missing.Please check the configuration settings for this supplier.");
        header("Location: /mmc/index.php");
        exit;
    }
}
?>