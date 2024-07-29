<?php
/*
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007-2008 Mandriva, http://www.mandriva.com
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

$MMCApp =& MMCApp::getInstance();
$MMCApp->render();

$login = $_SESSION['login'];
$server = $conf['notification']['url'];
$port = $conf['notification']['port'];
$protocol = $conf['notification']['protocol'];
$jid = $conf['notification']['jid'];
$pwd = $conf['notification']['password'];
?>

<div class="clearer"></div>
</div><!-- section -->
</div><!-- content -->

<div id="footer">
    <a href="http://www.siveo.net" target="blank"><img src="graph/mandriva-logo.png" alt="[x]" /></a>
    &nbsp;|&nbsp;&nbsp;MMC Agent <a href="#" onclick="showPopupUp(event,'version.php'); return false;"><?php echo $_SESSION["modListVersion"]['ver'] ?></a>
    <img id="notificationBell" src="graph/actif.png" alt="[x]" onclick="toggleNotificationCenter()" />
</div>

<div id="notificationCenter" class="hidden">
    <div class="close-btn" onclick="toggleNotificationCenter()">&times;</div>
    <p id="time"></p>
    <p id="noMessages">Aucune notification</p>
    <div id="notifications"></div>
</div>

</div><!-- wrapper -->

</body>
<script>
const PING_INTERVAL = 30000;
const CLOSE_POPUP_DELAY = 5000;

function toggleNotificationCenter() {
    const notificationCenter = document.getElementById("notificationCenter");
    notificationCenter.classList.toggle("hidden");
    updateNoMessagesText();
}

function closeNotificationCenterAfterDelay() {
    setTimeout(() => {
        const notificationCenter = document.getElementById("notificationCenter");
        notificationCenter.classList.add("hidden");
    }, CLOSE_POPUP_DELAY);
}

function updateTime() {
    const now = new Date();
    document.getElementById("time").textContent = now.toTimeString().split(' ')[0];
}

updateTime();
setInterval(updateTime, 1000);

const connection = new Strophe.Connection('<?php echo $server . ':' . $port . '/' . $protocol; ?>');
let pingTimer;

const login = "<?php echo $login; ?>";
const resource = "<?php echo $jid . '/' . $login; ?>";

connection.connect(resource, "<?php echo $pwd ?>", handleConnectionStatus);

function handleConnectionStatus(status) {
    if (status === Strophe.Status.CONNECTED) {
        console.log('Connecté à ejabberd en tant que ' + connection.jid + ' !');
        connection.addHandler(onMessage, null, 'message', null, null, null);
        connection.send($pres().tree());
        startPing();
    } else if (status === Strophe.Status.DISCONNECTED) {
        clearInterval(pingTimer);
    }
}

function startPing() {
    pingTimer = setInterval(() => {
        const ping = $iq({type: 'get'}).c('ping', {xmlns: 'urn:xmpp:ping'});
        connection.send(ping.tree());
    }, PING_INTERVAL);
}

function onMessage(msg) {
    const from = msg.getAttribute('from');
    const elems = msg.getElementsByTagName('body');
    if (elems.length > 0) {
        const body = elems[0];
        const encodedMessage = Strophe.getText(body);
        console.log('Encoded message received:', encodedMessage);
        processMessage(encodedMessage, from);
    }
    return true;
}

function processMessage(encodedMessage, from) {
    try {
        const decodedMessage = atob(encodedMessage);
        console.log('Decoded message:', decodedMessage);
        const messageData = JSON.parse(decodedMessage.replace(/^"|"$/g, ''));

        const type = messageData.type.trim();
        const receivedAt = new Date().toISOString();

        // stores the necessary information in LocalStorage
        storeMessageInLocalStorage(type, messageData, receivedAt);

        displayNotification(from, { type: type, data: messageData, receivedAt: receivedAt });
        if (document.getElementById("notificationCenter").classList.contains("hidden")) {
            toggleNotificationCenter();
        }
        closeNotificationCenterAfterDelay();
    } catch (e) {
        console.error('Error decoding or parsing message:', e);
    }
}

function storeMessageInLocalStorage(type, data, receivedAt) {
    const notifications = JSON.parse(localStorage.getItem('notifications')) || [];

    const notificationData = { type, data, receivedAt };

    notifications.push(notificationData);
    notifications.sort((a, b) => new Date(a.receivedAt) - new Date(b.receivedAt));

    localStorage.setItem('notifications', JSON.stringify(notifications));
    updateNoMessagesText();
}

function displayNotification(de, notificationData, fromLocalStorage = false) {
    const container = document.getElementById("notifications");
    const notificationDiv = document.createElement("div");
    notificationDiv.classList.add("notification");

    const typeLabel = notificationData.type;
    const data = notificationData.data;
    let backgroundColor = "#f0f0f0";

    switch (notificationData.type) {
        case "INFO":
            backgroundColor = "#dff0d8";
            break;
        case "WARNING":
            backgroundColor = "#fcf8e3";
            break;
        case "DEPLOYMENT":
            backgroundColor = "#d9edf7";
            break;
        case "ALERT":
            backgroundColor = "#f8d7da";
            break;
        case "ERROR":
            backgroundColor = "#f5c6cb";
            break;
        case "DEPLOYMENT SUCCESS":
            backgroundColor = "#d4edda";
            break;
        default:
            backgroundColor = "#f0f0f0";
            break;
    }

    notificationDiv.style.backgroundColor = backgroundColor;

    let contentHtml = `<span class='notification-detail'>${typeLabel}</span>`;
    for (const [key, value] of Object.entries(data)) {
        let keyLabel = key.charAt(0).toUpperCase() + key.slice(1);
        if (key === 'machine' && value.includes('@')) {
            const jidParts = value.split('@')[0].split('.');
            contentHtml += `<span class='notification-detail'>${keyLabel}: ${jidParts[0]}</span>`;
        } else if (key !== 'type') {
            if (key === 'start_date') {
                const formattedDate = new Date(value).toLocaleString();
                contentHtml += `<span class='notification-detail'>Date de début: ${formattedDate}</span>`;
            } else {
                contentHtml += `<span class='notification-detail'>${keyLabel}: ${value}</span>`;
            }
        }
    }

    contentHtml += `
        <span class='notification-time'>${new Date(notificationData.receivedAt).toLocaleTimeString()}</span>
        <span class='close-notification' onclick='removeNotification("${notificationData.receivedAt}")'>×</span>
    `;

    notificationDiv.innerHTML = contentHtml;

    if (fromLocalStorage) {
        container.insertBefore(notificationDiv, container.firstChild);
    } else {
        if (container.children.length >= 3) {
            container.removeChild(container.children[0]);
        }
        container.appendChild(notificationDiv);
    }
    updateNoMessagesText();
}

function removeNotification(timestamp) {
    let notifications = JSON.parse(localStorage.getItem('notifications')) || [];
    notifications = notifications.filter(notification => notification.receivedAt !== timestamp);
    localStorage.setItem('notifications', JSON.stringify(notifications));
    loadNotificationsFromLocalStorage();
}

function loadNotificationsFromLocalStorage() {
    const container = document.getElementById("notifications");
    container.innerHTML = "";
    const notifications = JSON.parse(localStorage.getItem('notifications')) || [];
    notifications.forEach(notification => displayNotification("Local", notification, true));
    updateNoMessagesText();
}

function updateNoMessagesText() {
    const container = document.getElementById("notifications");
    const noMessagesText = document.getElementById("noMessages");
    const notificationCenter = document.getElementById("notificationCenter");
    if (container.children.length === 0) {
        noMessagesText.style.display = "block";
        notificationCenter.classList.add("no-notifications");
    } else {
        noMessagesText.style.display = "none";
        notificationCenter.classList.remove("no-notifications");
    }
}

window.onload = function() {
    loadNotificationsFromLocalStorage();
}
</script>
</html>
