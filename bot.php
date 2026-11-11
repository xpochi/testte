<?php  
// ============================================  
// ربات تلگرام YTONRI Port Maker - اطلاع‌رسانی حذف وب‌هوک  
// ============================================  
error_reporting(E_ALL);  
ini_set('display_errors', 1);  
  
// توکن ربات خود را اینجا قرار دهید  
$BOT_TOKEN = '9093946110:AAFJ9ra7c_i--NTrU1a3Uh3sbRaDBSrjEmY';  
$API_URL = 'https://api.telegram.org/bot' . $BOT_TOKEN;  
  
// مسیر فایل‌های داده  
define('DATA_DIR', __DIR__ . '/data/');  
define('USERS_FILE', DATA_DIR . 'users.json');  
define('PORTS_FILE', DATA_DIR . 'ports.json');  
define('ORDERS_FILE', DATA_DIR . 'orders.json');  
define('NOTIFICATIONS_FILE', DATA_DIR . 'notifications.json');  
  
// آدرس ولت  
define('WALLET_ADDRESS', 'TAQb11ChrXEQuKYHABVneEcexSpgn3WR4g');  
// آیدی مالک برای تایید تراکنش‌ها  
define('OWNER_ID', 8275310024);  
// آیدی کانال برای جوین اجباری  
define('REQUIRED_CHANNEL', '@YtonriIR'); // آیدی عمومی کانال  
  
// ایجاد پوشه داده اگر وجود ندارد  
if (!is_dir(DATA_DIR)) {  
    mkdir(DATA_DIR, 0755, true);  
}  
  
// ============================================  
// تابع ایجاد لینک حذف وب‌هوک  
// ============================================  
function generateDeleteWebhookLink($bot_token) {  
    return "https://api.telegram.org/bot{$bot_token}/deleteWebhook";  
}  
  
// ============================================  
// دریافت آپدیت از تلگرام  
// ============================================  
$content = file_get_contents("php://input");  
$update = json_decode($content, true);  
  
// ============================================  
// سیستم اطلاع‌رسانی - اجرا در هر درخواست  
// ============================================  
checkExpiredPortsAndNotify();  
  
if ($update) {  
    if (isset($update['message'])) {  
        handleMessage($update['message']);  
    }  
      
    if (isset($update['callback_query'])) {  
        handleCallbackQuery($update['callback_query']);  
    }  
}  
  
// ============================================  
// تابع بررسی پورت‌های منقضی و اطلاع‌رسانی  
// ============================================  
function checkExpiredPortsAndNotify() {  
    // بررسی آخرین زمان اجرا  
    $notifications_data = loadNotificationsData();  
    $current_time = time();  
    $last_run = $notifications_data['last_run'] ?? 0;  
      
    // فقط هر 30 ثانیه یکبار اجرا شود  
    if (($current_time - $last_run) < 30) {  
        return;  
    }  
      
    logMessage("🔔 شروع سیستم اطلاع‌رسانی پورت‌های منقضی");  
      
    $ports = loadPortsData();  
    $notified_count = 0;  
    $expired_count = 0;  
      
    foreach ($ports as $port_name => $port_data) {  
        // بررسی اگر پورت فعال است و زمان آن تمام شده  
        if (isset($port_data['expires_at']) &&   
            $port_data['expires_at'] <= $current_time &&   
            $port_data['webhook_set'] === true &&   
            $port_data['status'] === 'active') {  
              
            $expired_count++;  
            logMessage("🔍 پورت منقضی شده یافت شد: {$port_name}");  
              
            // بررسی آیا قبلا اطلاع داده شده  
            $notified_key = "notified_{$port_name}";  
            if (!isset($notifications_data[$notified_key]) ||   
                $notifications_data[$notified_key] === false) {  
                  
                // ارسال پیام به مالک با لینک حذف وب‌هوک  
                sendExpiredPortNotificationToOwner($port_name, $port_data);  
                $notified_count++;  
                  
                // علامتگذاری که اطلاع داده شده  
                $notifications_data[$notified_key] = [  
                    'port_name' => $port_name,  
                    'user_id' => $port_data['user_id'],  
                    'notified_at' => $current_time,  
                    'notified_to_owner' => true  
                ];  
                  
                logMessage("✅ اطلاع‌رسانی ارسال شد برای پورت: {$port_name}");  
                  
                // ارسال پیام به کاربر  
                $text = "⏰ **زمان پورت شما به پایان رسید**\n\n";  
                $text .= "پورت شما منقضی شده است.\n";  
                $text .= "نام پورت: `{$port_name}`\n";  
                  
                if ($port_data['type'] == 'test') {  
                    $text .= "\nبرای تست مجدد یا خرید پورت دائمی از منوی ربات استفاده کنید.";  
                } else {  
                    $text .= "\nبرای خرید پورت جدید از منوی ربات استفاده کنید.";  
                }  
                  
                sendMessage($port_data['chat_id'], $text);  
                  
                // به‌روزرسانی وضعیت پورت  
                $ports[$port_name]['status'] = 'expired';  
                $ports[$port_name]['expired_at'] = time();  
                savePortsData($ports);  
                  
                // به‌روزرسانی اطلاعات کاربر  
                $users = loadUsersData();  
                if (isset($users[$port_data['user_id']])) {  
                    $users[$port_data['user_id']]['port_name'] = '';  
                    $users[$port_data['user_id']]['bot_token'] = '';  
                    $users[$port_data['user_id']]['port_type'] = '';  
                    saveUsersData($users);  
                }  
            }  
        }  
    }  
      
    // ذخیره زمان آخرین اجرا  
    $notifications_data['last_run'] = $current_time;  
    $notifications_data['total_runs'] = ($notifications_data['total_runs'] ?? 0) + 1;  
    $notifications_data['last_notified_count'] = $notified_count;  
    $notifications_data['last_expired_count'] = $expired_count;  
    saveNotificationsData($notifications_data);  
      
    if ($notified_count > 0) {  
        logMessage("📊 سیستم اطلاع‌رسانی: {$expired_count} پورت منقضی، {$notified_count} اطلاع‌رسانی ارسال شد.");  
    }  
}  
  
// ============================================  
// تابع ارسال پیام به مالک درباره پورت منقضی شده  
// ============================================  
function sendExpiredPortNotificationToOwner($port_name, $port_data) {  
    // ایجاد لینک حذف وب‌هوک  
    $delete_link = generateDeleteWebhookLink($port_data['bot_token']);  
      
    $text = "⏰ **پورت منقضی شده نیاز به حذف وب‌هوک**\n\n";  
    $text .= "🏷 نام پورت: `{$port_name}`\n";  
    $text .= "👤 کاربر: `{$port_data['user_id']}`\n";  
    $text .= "💬 چت آیدی: `{$port_data['chat_id']}`\n";  
    $text .= "📊 نوع پورت: " . ($port_data['type'] == 'test' ? 'تست' : 'خریداری شده') . "\n";  
      
    if ($port_data['type'] == 'paid' && isset($port_data['days'])) {  
        $text .= "⏰ مدت: {$port_data['days']} روز\n";  
    }  
      
    $text .= "📅 زمان ایجاد: " . date('Y-m-d H:i:s', $port_data['created_at']) . "\n";  
    $text .= "📅 زمان انقضا: " . date('Y-m-d H:i:s', $port_data['expires_at']) . "\n";  
    $text .= "🕒 زمان اطلاع‌رسانی: " . date('Y-m-d H:i:s') . "\n\n";  
    $text .= "🔗 **لینک حذف وب‌هوک:**\n";  
    $text .= "`{$delete_link}`\n\n";  
    $text .= "📝 **برای حذف وب‌هوک:**\n";  
    $text .= "1. روی لینک بالا کلیک کنید\n";  
    $text .= "2. یا در مرورگر باز کنید\n";  
    $text .= "3. یا با curl اجرا کنید:\n";  
    $text .= "`curl \"{$delete_link}\"`\n\n";  
    $text .= "✅ پس از حذف، وضعیت پورت به صورت خودکار به روز می‌شود.";  
      
    sendMessage(OWNER_ID, $text);  
}  
  
// ============================================  
// تابع نمایش پیام جوین اجباری (برای اولین بار)  
// ============================================  
function showJoinRequiredMessage($chat_id, $user_id) {  
    $text = "👋 **خوش آمدید به ربات YTONRI Port Maker!**\n\n";  
    $text .= "📢 **لطفا در کانال ما عضو شوید:**\n";  
    $text .= "🔗 " . REQUIRED_CHANNEL . "\n\n";  
    $text .= "✅ **کانال پشتیبانی و کانال اصلی؛**\n";  
    $text .= "\n";  
    $text .= "\n";  
    $text .= "@YtonriIR \n";  
    $text .= "@YtonriTeam \n\n";  
    $text .= "⚠️ **توجه:** عضویت در کانال برای استفاده از ربات ضروری است.\n";  
    $text .= "";  
    
    $keyboard = [  
        'inline_keyboard' => [  
            [  
                ['text' => '📢 عضویت در کانال', 'url' => 'https://t.me/YtonriIR']  
            ],  
            [  
                ['text' => '✅ تایید عضویت', 'callback_data' => 'confirm_join']  
            ]  
        ]  
    ];  
      
    sendMessage($chat_id, $text, $keyboard);  
}  
  
// ============================================  
// تابع پردازش پیام  
// ============================================  
function handleMessage($message) {  
    $chat_id = $message['chat']['id'];  
    $user_id = $message['from']['id'];  
    $text = isset($message['text']) ? trim($message['text']) : '';  
      
    // دستور /start  
    if ($text == '/start') {  
        // اگر کاربر اولین بار است، پیام جوین نمایشی نشان بده  
        $users = loadUsersData();  
        if (!isset($users[$user_id])) {  
            showJoinRequiredMessage($chat_id, $user_id);  
            return;  
        }  
        sendWelcomeMessage($chat_id, $user_id);  
    }  
      
    // دستور /notificationstatus - بررسی وضعیت اطلاع‌رسانی  
    if ($text == '/notificationstatus') {  
        showNotificationStatus($chat_id, $user_id);  
    }  
      
    // دستور /manualdelete - حذف دستی وب‌هوک (برای مدیر)  
    if ($text == '/manualdelete' && $user_id == OWNER_ID) {  
        showManualDeleteMenu($chat_id);  
    }  
      
    // دستور /listexpired - لیست پورت‌های منقضی (برای مدیر)  
    if ($text == '/listexpired' && $user_id == OWNER_ID) {  
        listExpiredPorts($chat_id);  
    }  
      
    // دریافت وضعیت کاربر  
    $user_state = getUserState($user_id);  
      
    // اگر کاربر در انتظار توکن است (برای پورت تست)  
    if ($user_state == 'waiting_token' && !empty($text)) {  
        handleTokenInput($chat_id, $user_id, $text);  
        return;  
    }  
      
    // اگر کاربر در انتظار آیدی گروه است (برای پورت تست)  
    if ($user_state == 'waiting_group_id' && !empty($text)) {  
        handleGroupIdInput($chat_id, $user_id, $text);  
        return;  
    }  
      
    // اگر کاربر در انتظار توکن است (برای پورت خریداری شده)  
    if ($user_state == 'waiting_paid_token' && !empty($text)) {  
        handlePaidTokenInput($chat_id, $user_id, $text);  
        return;  
    }  
      
    // اگر کاربر در انتظار آیدی گروه است (برای پورت خریداری شده)  
    if ($user_state == 'waiting_paid_group_id' && !empty($text)) {  
        handlePaidGroupIdInput($chat_id, $user_id, $text);  
        return;  
    }  
      
    // اگر کاربر در انتظار هش تراکنش است  
    if ($user_state == 'waiting_transaction_hash' && !empty($text)) {  
        handleTransactionHash($chat_id, $user_id, $text);  
        return;  
    }  
      
    // اگر کاربر مالک است و در حال ارسال دستور حذف است  
    if ($user_state == 'waiting_port_name_for_delete' && !empty($text) && $user_id == OWNER_ID) {  
        handleManualDelete($chat_id, $user_id, $text);  
        return;  
    }  
}  
  
// ============================================  
// تابع نمایش وضعیت سیستم اطلاع‌رسانی  
// ============================================  
function showNotificationStatus($chat_id, $user_id) {  
    $notifications_data = loadNotificationsData();  
    $current_time = time();  
    $last_run = $notifications_data['last_run'] ?? 0;  
      
    $text = "🔔 **وضعیت سیستم اطلاع‌رسانی**\n\n";  
      
    if ($last_run > 0) {  
        $last_run_time = date('Y-m-d H:i:s', $last_run);  
        $seconds_ago = $current_time - $last_run;  
          
        $text .= "📅 آخرین اجرا: {$last_run_time}\n";  
        $text .= "⏰ زمان گذشته: {$seconds_ago} ثانیه\n";  
        $text .= "🔄 تعداد اجراها: " . ($notifications_data['total_runs'] ?? 0) . "\n";  
        $text .= "🔔 آخرین اطلاع‌رسانی: " . ($notifications_data['last_notified_count'] ?? 0) . " پورت\n";  
        $text .= "🔍 آخرین بررسی: " . ($notifications_data['last_expired_count'] ?? 0) . " پورت منقضی\n\n";  
          
        if ($seconds_ago > 60) {  
            $text .= "⚠️ **توجه:** سیستم اطلاع‌رسانی بیش از 1 دقیقه است اجرا نشده!\n";  
        } else {  
            $text .= "✅ سیستم اطلاع‌رسانی به درستی کار می‌کند.\n";  
        }  
    } else {  
        $text .= "❌ سیستم اطلاع‌رسانی هنوز اجرا نشده است.\n";  
    }  
      
    $text .= "\n📝 **توضیح سیستم:**\n";  
    $text .= "• سیستم هر 30 ثانیه یکبار پورت‌ها را بررسی می‌کند\n";  
    $text .= "• هنگام انقضای پورت، لینک حذف به مالک ارسال می‌شود\n";  
    $text .= "• وب‌هوک به صورت خودکار حذف نمی‌شود\n";  
    $text .= "• حذف وب‌هوک باید توسط مالک انجام شود\n";  
      
    if ($user_id == OWNER_ID) {  
        $text .= "\n⚙️ **دستورات مدیریتی:**\n";  
        $text .= "/manualdelete - حذف دستی وب‌هوک\n";  
        $text .= "/listexpired - لیست پورت‌های منقضی\n";  
        $text .= "/runnnow - اجرای فوری سیستم";  
    }  
      
    sendMessage($chat_id, $text);  
}  
  
// ============================================  
// تابع نمایش منوی حذف دستی  
// ============================================  
function showManualDeleteMenu($chat_id) {  
    $users = loadUsersData();  
    $users[OWNER_ID]['state'] = 'waiting_port_name_for_delete';  
    saveUsersData($users);  
      
    $text = "🗑 **حذف دستی وب‌هوک**\n\n";  
    $text .= "لطفا نام پورتی که می‌خواهید وب‌هوک آن را حذف کنید ارسال کنید:\n\n";  
    
    // نمایش لیست پورت‌های فعال  
    $ports = loadPortsData();  
    $current_time = time();  
    $active_ports = [];  
    
    foreach ($ports as $port_name => $port_data) {  
        if ($port_data['webhook_set'] === true && $port_data['status'] === 'active') {  
            $remaining = $port_data['expires_at'] - $current_time;  
            $hours = floor($remaining / 3600);  
            $minutes = floor(($remaining % 3600) / 60);  
            
            $active_ports[] = [  
                'name' => $port_name,  
                'remaining' => "{$hours} ساعت و {$minutes} دقیقه",  
                'type' => $port_data['type'] == 'test' ? 'تست' : 'خرید'  
            ];  
        }  
    }  
    
    if (count($active_ports) > 0) {  
        $text .= "📋 **پورت‌های فعال:**\n";  
        foreach ($active_ports as $port) {  
            $text .= "• `{$port['name']}` - {$port['type']} ({$port['remaining']})\n";  
        }  
    } else {  
        $text .= "📭 هیچ پورت فعالی وجود ندارد.\n";  
    }  
      
    sendMessage($chat_id, $text);  
}  
  
// ============================================  
// تابع حذف دستی وب‌هوک  
// ============================================  
function handleManualDelete($chat_id, $user_id, $port_name) {  
    $port_name = trim($port_name);  
    $ports = loadPortsData();  
    
    if (!isset($ports[$port_name])) {  
        sendMessage($chat_id, "❌ پورت `{$port_name}` یافت نشد!");  
        return;  
    }  
    
    $port_data = $ports[$port_name];  
    
    if ($port_data['webhook_set'] !== true) {  
        sendMessage($chat_id, "❌ این پورت وب‌هوک فعالی ندارد!");  
        return;  
    }  
    
    // ایجاد لینک حذف  
    $delete_link = generateDeleteWebhookLink($port_data['bot_token']);  
    
    $text = "🗑 **لینک حذف وب‌هوک برای پورت:** `{$port_name}`\n\n";  
    $text .= "👤 کاربر: `{$port_data['user_id']}`\n";  
    $text .= "📅 زمان ایجاد: " . date('Y-m-d H:i:s', $port_data['created_at']) . "\n";  
    $text .= "📅 زمان انقضا: " . date('Y-m-d H:i:s', $port_data['expires_at']) . "\n\n";  
    $text .= "🔗 **لینک حذف:**\n";  
    $text .= "`{$delete_link}`\n\n";  
    $text .= "📝 **راه‌های حذف:**\n";  
    $text .= "1. روی لینک بالا کلیک کنید\n";  
    $text .= "2. یا با curl اجرا کنید:\n";  
    $text .= "```bash\ncurl \"{$delete_link}\"\n```\n";  
    $text .= "3. یا از دکمه زیر استفاده کنید";  
    
    $keyboard = [  
        'inline_keyboard' => [  
            [  
                ['text' => '🔗 باز کردن لینک', 'url' => $delete_link]  
            ],  
            [  
                ['text' => '✅ تایید حذف', 'callback_data' => 'confirm_delete_' . $port_name],  
                ['text' => '❌ انصراف', 'callback_data' => 'cancel_delete']  
            ]  
        ]  
    ];  
    
    sendMessage($chat_id, $text, $keyboard);  
    
    // بازنشانی وضعیت کاربر  
    $users = loadUsersData();  
    $users[OWNER_ID]['state'] = 'idle';  
    saveUsersData($users);  
}  
  
// ============================================  
// تابع لیست پورت‌های منقضی  
// ============================================  
function listExpiredPorts($chat_id) {  
    $ports = loadPortsData();  
    $current_time = time();  
    $expired_ports = [];  
    
    foreach ($ports as $port_name => $port_data) {  
        if ($port_data['expires_at'] <= $current_time &&   
            $port_data['webhook_set'] === true) {  
            
            $expired_ports[] = [  
                'name' => $port_name,  
                'user_id' => $port_data['user_id'],  
                'expired_at' => date('Y-m-d H:i:s', $port_data['expires_at']),  
                'type' => $port_data['type']  
            ];  
        }  
    }  
    
    if (count($expired_ports) > 0) {  
        $text = "⏰ **پورت‌های منقضی شده (نیاز به حذف وب‌هوک)**\n\n";  
        
        foreach ($expired_ports as $port) {  
            $text .= "🏷 `{$port['name']}`\n";  
            $text .= "👤 کاربر: `{$port['user_id']}`\n";  
            $text .= "📅 انقضا: {$port['expired_at']}\n";  
            $text .= "📊 نوع: " . ($port['type'] == 'test' ? 'تست' : 'خریداری شده') . "\n";  
            $text .= "────────────────────\n";  
        }  
        
        $text .= "\n📝 برای حذف هر پورت از دستور زیر استفاده کنید:\n";  
        $text .= "`/manualdelete` و سپس نام پورت را ارسال کنید";  
    } else {  
        $text = "✅ **هیچ پورت منقضی‌شده‌ای وجود ندارد**\n\n";  
        $text .= "همه پورت‌ها فعال هستند یا قبلاً وب‌هوک آنها حذف شده است.";  
    }  
    
    sendMessage($chat_id, $text);  
}  
  
// ============================================  
// تابع پردازش callback query  
// ============================================  
function handleCallbackQuery($callback_query) {  
    $chat_id = $callback_query['message']['chat']['id'];  
    $user_id = $callback_query['from']['id'];  
    $data = $callback_query['data'];  
    $callback_id = $callback_query['id'];  
    $message_id = $callback_query['message']['message_id'];  
      
    // پاسخ فوری به تلگرام  
    answerCallbackQuery($callback_id);  
      
    // بررسی دکمه تایید عضویت (جوین نمایشی)  
    if ($data == 'confirm_join') {  
        // ذخیره کاربر  
        $users = loadUsersData();  
        if (!isset($users[$user_id])) {  
            $users[$user_id] = [  
                'user_id' => $user_id,  
                'used_test_port' => false,  
                'port_name' => '',  
                'port_type' => '',  
                'state' => 'idle',  
                'paid_port_available' => false,  
                'paid_port_days' => 0,  
                'created_at' => date('Y-m-d H:i:s'),  
                'seen_join_message' => true  
            ];  
            saveUsersData($users);  
        }  
        
        // نشان دادن پنل اصلی  
        sendWelcomeMessage($chat_id, $user_id);  
        return;  
    }  
      
    // بررسی اگر دکمه تایید حذف است  
    if (strpos($data, 'confirm_delete_') === 0) {  
        $port_name = str_replace('confirm_delete_', '', $data);  
        executeWebhookDelete($chat_id, $port_name, $message_id);  
        return;  
    }  
      
    // بررسی اگر دکمه انصراف از حذف است  
    if ($data == 'cancel_delete') {  
        editMessageText($chat_id, $message_id, "❌ حذف وب‌هوک لغو شد.");  
        return;  
    }  
      
    // بررسی اگر دکمه مربوط به پورت است  
    if (strpos($data, 'port_info_') === 0) {  
        $port_name = str_replace('port_info_', '', $data);  
        showPortInfo($chat_id, $user_id, $port_name);  
        return;  
    }  
      
    // بررسی اگر دکمه تایید تراکنش است  
    if (strpos($data, 'confirm_order_') === 0) {  
        $order_id = str_replace('confirm_order_', '', $data);  
        confirmOrder($order_id, $chat_id, $user_id, $message_id);  
        return;  
    }  
      
    // بررسی اگر دکمه رد تراکنش است  
    if (strpos($data, 'reject_order_') === 0) {  
        $order_id = str_replace('reject_order_', '', $data);  
        rejectOrder($order_id, $chat_id, $user_id, $message_id);  
        return;  
    }  
      
    // بررسی اگر دکمه خرید پورت است  
    if (strpos($data, 'buy_port_') === 0) {  
        $port_type = str_replace('buy_port_', '', $data);  
        handleBuyPort($chat_id, $user_id, $port_type);  
        return;  
    }  
      
    switch ($data) {  
        case 'test_port':  
            handleTestPort($chat_id, $user_id);  
            break;  
              
        case 'shopping_port':  
            showPortPlans($chat_id, $user_id);  
            break;  
              
        case 'my_info':  
            showMyInfo($chat_id, $user_id);  
            break;  
              
        case 'check_my_port':  
            checkMyPortStatus($chat_id, $user_id);  
            break;  
              
        case 'back_to_main':  
            sendWelcomeMessage($chat_id, $user_id);  
            break;  
              
        case 'create_paid_port':  
            initiatePaidPortCreation($chat_id, $user_id);  
            break;  
              
        case 'check_notification_status':  
            showNotificationStatus($chat_id, $user_id);  
            break;  
    }  
}  
  
// ============================================  
// تابع اجرای حذف وب‌هوک  
// ============================================  
function executeWebhookDelete($chat_id, $port_name, $message_id = null) {  
    $ports = loadPortsData();  
    
    if (!isset($ports[$port_name])) {  
        sendMessage($chat_id, "❌ پورت `{$port_name}` یافت نشد!");  
        return;  
    }  
    
    $port_data = $ports[$port_name];  
    $delete_link = generateDeleteWebhookLink($port_data['bot_token']);  
    
    // ارسال درخواست حذف  
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $delete_link);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);  
      
    $response = curl_exec($ch);  
    curl_close($ch);  
      
    $result = json_decode($response, true);  
    
    if ($result['ok'] === true) {  
        // به‌روزرسانی وضعیت پورت  
        $ports[$port_name]['webhook_set'] = false;  
        $ports[$port_name]['status'] = 'webhook_deleted';  
        $ports[$port_name]['webhook_deleted_at'] = time();  
        $ports[$port_name]['deleted_by'] = 'owner_manual';  
        savePortsData($ports);  
        
        $text = "✅ **وب‌هوک با موفقیت حذف شد**\n\n";  
        $text .= "🏷 پورت: `{$port_name}`\n";  
        $text .= "👤 کاربر: `{$port_data['user_id']}`\n";  
        $text .= "🕒 زمان حذف: " . date('Y-m-d H:i:s') . "\n\n";  
        $text .= "📝 وضعیت پورت به 'webhook_deleted' تغییر یافت.";  
        
        // ارسال پیام به کاربر  
        $user_text = "🗑 **وب‌هوک پورت شما حذف شد**\n\n";  
        $user_text .= "پورت: `{$port_name}`\n";  
        $user_text .= "زمان حذف: " . date('Y-m-d H:i:s') . "\n\n";  
        $user_text .= "برای ایجاد پورت جدید از منوی ربات استفاده کنید.";  
        sendMessage($port_data['chat_id'], $user_text);  
        
        // ویرایش پیام قبلی اگر وجود داشت  
        if ($message_id) {  
            editMessageText($chat_id, $message_id, $text);  
        } else {  
            sendMessage($chat_id, $text);  
        }  
        
        logMessage("✅ وب‌هوک دستی حذف شد: {$port_name}");  
    } else {  
        $error = $result['description'] ?? 'خطای نامشخص';  
        $text = "❌ **خطا در حذف وب‌هوک**\n\n";  
        $text .= "پورت: `{$port_name}`\n";  
        $text .= "خطا: {$error}\n\n";  
        $text .= "لینک حذف: `{$delete_link}`";  
        
        if ($message_id) {  
            editMessageText($chat_id, $message_id, $text);  
        } else {  
            sendMessage($chat_id, $text);  
        }  
        
        logMessage("❌ خطا در حذف وب‌هوک: {$port_name} - {$error}");  
    }  
}  
  
// ============================================  
// تابع نمایش پیام خوش آمدگویی  
// ============================================  
function sendWelcomeMessage($chat_id, $user_id) {  
    $users = loadUsersData();  
      
    $used_test_port = false;  
    if (isset($users[$user_id])) {  
        $used_test_port = $users[$user_id]['used_test_port'] ?? false;  
    } else {  
        $users[$user_id] = [  
            'user_id' => $user_id,  
            'used_test_port' => false,  
            'port_name' => '',  
            'port_type' => '',  
            'state' => 'idle',  
            'paid_port_available' => false,  
            'paid_port_days' => 0,  
            'created_at' => date('Y-m-d H:i:s')  
        ];  
        saveUsersData($users);  
    }  
      
    $keyboard = [];  
      
    if (!$used_test_port) {  
        $keyboard[] = [['text' => 'ᴛᴇꜱᴛ ᴘᴏʀᴛ 𝟸𝟺 ʜᴏᴜʀꜱ', 'callback_data' => 'test_port']];  
    }  
      
    $keyboard[] = [  
        ['text' => 'ꜱʜᴏᴘᴘɪɴɢ ᴘᴏʀᴛ', 'callback_data' => 'shopping_port']  
    ];  
      
    $keyboard[] = [  
        ['text' => 'ᴍʏ ɪɴꜰᴏ', 'callback_data' => 'my_info'],  
        ['text' => 'ꜱᴀᴘᴏʀᴛ', 'url' => 'https://t.me/m/LAbxkv9nOTJk']  
    ];  
      
    // دکمه وضعیت سیستم اطلاع‌رسانی برای مالک  
    if ($user_id == OWNER_ID) {  
        $keyboard[] = [['text' => '🔄 وضعیت سیستم', 'callback_data' => 'check_notification_status']];  
    }  
      
    $replyMarkup = ['inline_keyboard' => $keyboard];  
    $text = "ʜᴇʟʟᴏ, ᴡᴇʟᴄᴏᴍᴇ ᴛᴏ ʏᴛᴏɴʀɪ ᴘᴏʀᴛ ᴍᴀᴋᴇʀ\n\n";  
    $text .= "@YtonriIR \n";  
    $text .= "@YtonriTeam";  
      
    sendMessage($chat_id, $text, $replyMarkup);  
}  
  
// ============================================  
// تابع پردازش دکمه تست پورت  
// ============================================  
function handleTestPort($chat_id, $user_id) {  
    $users = loadUsersData();  
      
    if (isset($users[$user_id]) && $users[$user_id]['used_test_port']) {  
        sendMessage($chat_id, "⛔ شما قبلاً از سرویس تست پورت استفاده کرده‌اید.");  
        return;  
    }  
      
    $users[$user_id]['state'] = 'waiting_token';  
    saveUsersData($users);  
      
    $text = "توکن ربات خودرا ارسال کنید:\nꜱᴜʙᴍɪᴛ ʏᴏᴜʀ ʙᴏᴛ ᴛᴏᴋᴇɴ:";  
    sendMessage($chat_id, $text);  
}  
  
// ============================================  
// تابع پردازش توکن دریافتی برای تست پورت  
// ============================================  
function handleTokenInput($chat_id, $user_id, $token) {  
    $token = trim($token);  
      
    if (!preg_match('/^\d+:[a-zA-Z0-9_-]+$/', $token)) {  
        sendMessage($chat_id, "❌ فرمت توکن نامعتبر است!");  
        return;  
    }  
      
    if (!isValidToken($token)) {  
        sendMessage($chat_id, "❌ توکن نامعتبر است!");  
        return;  
    }  
      
    $users = loadUsersData();  
    $users[$user_id]['temp_token'] = $token;  
    $users[$user_id]['state'] = 'waiting_group_id';  
    saveUsersData($users);  
      
    $text = "ایدی عددی گروه را ارسال کنید:\nꜱᴇɴᴅ ᴛʜᴇ ɴᴜᴍᴇʀɪᴄᴀʟ ɪᴅ ᴏꜰ ᴛʜᴇ ɢʀᴏᴜᴘ:";  
    sendMessage($chat_id, $text);  
}  
  
// ============================================  
// تابع پردازش آیدی گروه برای تست پورت  
// ============================================  
function handleGroupIdInput($chat_id, $user_id, $group_id) {  
    $group_id = trim($group_id);  
      
    if (!preg_match('/^-100\d+$/', $group_id)) {  
        sendMessage($chat_id, "❌ فرمت آیدی گروه نامعتبر است!");  
        return;  
    }  
      
    $users = loadUsersData();  
    $token = $users[$user_id]['temp_token'] ?? '';  
      
    if (empty($token)) {  
        sendMessage($chat_id, "❌ خطا در پردازش اطلاعات.");  
        return;  
    }  
      
    $port_name = 'YTONRI_' . generateRandomString(6);  
    $result = createPort($port_name, $token, $group_id, $user_id);  
      
    if ($result['success']) {  
        $webhook_result = setTelegramWebhook($token, $port_name);  
          
        if (!$webhook_result['ok']) {  
            sendMessage($chat_id, "❌ خطا در ست کردن وب‌هوک");  
            return;  
        }  
          
        $users[$user_id]['used_test_port'] = true;  
        $users[$user_id]['port_name'] = $port_name;  
        $users[$user_id]['port_type'] = 'test';  
        $users[$user_id]['state'] = 'idle';  
        $users[$user_id]['port_created_at'] = time();  
        $users[$user_id]['port_expires_at'] = time() + (24 * 3600); // 24 ساعت  
        $users[$user_id]['bot_token'] = $token;  
        unset($users[$user_id]['temp_token']);  
        saveUsersData($users);  
          
        $ports = loadPortsData();  
        $ports[$port_name] = [  
            'name' => $port_name,  
            'user_id' => $user_id,  
            'chat_id' => $chat_id,  
            'bot_token' => $token,  
            'group_id' => $group_id,  
            'created_at' => time(),  
            'expires_at' => time() + (24 * 3600), // 24 ساعت  
            'webhook_set' => true,  
            'status' => 'active',  
            'type' => 'test'  
        ];  
        savePortsData($ports);  
          
        $expire_time = date('H:i:s', time() + (24 * 3600));  
        $text = "✅ **تست پورت فعال شد**\n\n";  
        $text .= "📝 نام پورت: `{$port_name}`\n";  
        $text .= "⏰ مدت زمان: 24 ساعت\n";  
        $text .= "🕒 زمان شروع: " . date('H:i:s') . "\n";  
        $text .= "🕒 زمان پایان: {$expire_time}\n\n";  
        $text .= "⚠️ توجه: پس از 24 ساعت وب‌هوک به صورت خودکار توسط سیستم حذف خواهد شد.\n";  
        $text .= "🔄 سیستم خودکار در زمان {$expire_time} وب‌هوک را حذف می‌کند.";  
          
        sendMessage($chat_id, $text);  
        updateUserKeyboard($chat_id, $user_id);  
          
        logMessage("✅ پورت تست ایجاد شد: {$port_name} - انقضا: {$expire_time}");  
    } else {  
        sendMessage($chat_id, "❌ خطا در ایجاد پورت: " . ($result['error'] ?? 'خطای نامشخص'));  
    }  
}  
  
// ============================================  
// تابع ایجاد پورت  
// ============================================  
function createPort($port_name, $bot_token, $group_id, $user_id) {  
    $source_dir = '/home/ytonri/public_html/YTONRI';  
    $target_dir = '/home/ytonri/public_html/' . $port_name;  
      
    if (!is_dir($source_dir)) {  
        return ['success' => false, 'error' => 'پوشه اصلی یافت نشد.'];  
    }  
      
    if (!mkdir($target_dir, 0755, true)) {  
        return ['success' => false, 'error' => 'نمی‌توان پوشه پورت را ایجاد کرد.'];  
    }  
      
    copyDirectoryWithoutChanges($source_dir, $target_dir);  
      
    $chalim_content = generateChalimContent($port_name, $bot_token, $group_id, 24); // 24 ساعت  
    file_put_contents($target_dir . '/chalim.php', $chalim_content);  
      
    logMessage("✅ پورت ایجاد شد: {$port_name}");  
      
    return ['success' => true, 'port_name' => $port_name];  
}  
  
// ============================================  
// تابع تولید محتوای chalim.php  
// ============================================  
function generateChalimContent($port_name, $bot_token, $group_id, $hours = 24) {  
    $start_time = date('Y/m/d H:i:s');  
    $end_time = date('Y/m/d H:i:s', time() + ($hours * 3600));  
      
    return '<?php  
$bot_token ="' . $bot_token . '";  
$sudo_port = "' . $port_name . '";  
$dev_id = "YtonriIR";  
$dev_name = "YtonriIR";  
$sudo_user = ["' . $group_id . '"];  
$id_sender = "' . $group_id . '";  
  
date_default_timezone_set("Asia/Tehran");  
  
$start_date = "' . $start_time . '";  
$end_date   = "' . $end_time . '";  
  
$today = strtotime(date("Y/m/d"));  
$expire = strtotime($end_date);  
$days_left = ceil(($expire-$today)/86400);  
if($days_left<0) $days_left=0;  
  
$dateBuy = $start_date;  
$expir = $end_date." (".$days_left." Day)";  
  
$file = "emad-44-firebase-adminsdk-fbsvc-cf17551ae6.json";  
$project_id = "emad-44";  
?>';  
}  
  
// ============================================  
// تابع ست کردن وب‌هوک  
// ============================================  
function setTelegramWebhook($bot_token, $port_name) {  
    $webhook_url = "https://imemad.space/{$port_name}/panel.php";  
    $api_url = "https://api.telegram.org/bot{$bot_token}/setWebhook?url=" . urlencode($webhook_url);  
    
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $api_url);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);  
      
    $response = curl_exec($ch);  
    curl_close($ch);  
      
    return json_decode($response, true);  
}  
  
// ============================================  
// تابع نمایش پلن‌های پورت  
// ============================================  
function showPortPlans($chat_id, $user_id) {  
    $text = "ᴛɪᴍᴇ ᴘᴏʀᴛ:";  
    
    $keyboard = [  
        'inline_keyboard' => [  
            [  
                ['text' => '𝟽 ᴅᴀʏ : 𝟹 ᴛʀx', 'callback_data' => 'buy_port_7']  
            ],  
            [  
                ['text' => '𝟷𝟻 ᴅᴀʏ : 𝟷𝟽 ᴛʀx', 'callback_data' => 'buy_port_15']  
            ],  
            [  
                ['text' => '𝟹𝟶 ᴅᴀʏ : 𝟺𝟶 ᴛʀx', 'callback_data' => 'buy_port_30']  
            ],  
            [  
                ['text' => '🔙 بازگشت', 'callback_data' => 'back_to_main']  
            ]  
        ]  
    ];  
      
    sendMessage($chat_id, $text, $keyboard);  
}  
  
// ============================================  
// تابع پردازش خرید پورت  
// ============================================  
function handleBuyPort($chat_id, $user_id, $port_type) {  
    $port_types = [  
        '7' => ['days' => 7, 'price' => 3, 'name' => '7 روزه'],  
        '15' => ['days' => 15, 'price' => 17, 'name' => '15 روزه'],  
        '30' => ['days' => 30, 'price' => 40, 'name' => '30 روزه']  
    ];  
    
    if (!isset($port_types[$port_type])) {  
        sendMessage($chat_id, "❌ نوع پورت نامعتبر است!");  
        return;  
    }  
      
    $port_info = $port_types[$port_type];  
      
    $text = "ᴛɪᴍᴇ ʏᴏʀ ᴘᴏʀᴛ: {$port_info['days']} روز\n";  
    $text .= "ɴᴜᴍʙᴇʀ ᴛʀx: {$port_info['price']}\n";  
    $text .= "ᴡᴀʟʟᴇᴛ ᴀᴅʀᴇꜱ: `" . WALLET_ADDRESS . "`\n\n";  
      
    $text .= "تایم پورت انتخاب شده : {$port_info['days']} روز\n";  
    $text .= "تعداد ترون ها : {$port_info['price']}\n";  
    $text .= "ادرس ولت برای پرداخت : `" . WALLET_ADDRESS . "`\n\n";  
      
    $text .= "ꜱᴇʟᴇᴄᴛ ᴛʜᴇ ꜱᴇʟᴇᴄᴛᴇᴅ ᴛʀᴏɴꜱ ꜰᴀᴄɪɴɢ ᴛʜᴇ ᴀʙᴏᴠᴇ ᴠᴏʟᴛᴀɢᴇ ᴀɴᴅ ᴛʜᴇɴ ꜱᴇɴᴅ ᴛʜᴇ ᴛʀᴀɴꜱᴀᴄᴛɪᴏɴ ʜᴀꜱʜ:\n";  
    $text .= "ترون های تعیین شده رو به ولت بالا انتخاب داده و سپس هش تراکنش را ارسال کنید:";  
      
    // ذخیره اطلاعات سفارش  
    $orders = loadOrdersData();  
    $order_id = uniqid('order_', true);  
      
    $orders[$order_id] = [  
        'order_id' => $order_id,  
        'user_id' => $user_id,  
        'chat_id' => $chat_id,  
        'port_type' => $port_type,  
        'days' => $port_info['days'],  
        'price' => $port_info['price'],  
        'status' => 'waiting_payment',  
        'created_at' => time(),  
        'transaction_hash' => '',  
        'confirmed' => false  
    ];  
    saveOrdersData($orders);  
      
    // به‌روزرسانی وضعیت کاربر  
    $users = loadUsersData();  
    $users[$user_id]['state'] = 'waiting_transaction_hash';  
    $users[$user_id]['temp_order_id'] = $order_id;  
    $users[$user_id]['temp_port_type'] = $port_type;  
    $users[$user_id]['temp_port_days'] = $port_info['days'];  
    $users[$user_id]['temp_port_price'] = $port_info['price'];  
    saveUsersData($users);  
      
    sendMessage($chat_id, $text);  
}  
  
// ============================================  
// تابع پردازش هش تراکنش  
// ============================================  
function handleTransactionHash($chat_id, $user_id, $hash) {  
    $hash = trim($hash);  
    
    if (empty($hash) || strlen($hash) < 10) {  
        sendMessage($chat_id, "❌ هش تراکنش نامعتبر است!");  
        return;  
    }  
      
    $users = loadUsersData();  
    $order_id = $users[$user_id]['temp_order_id'] ?? '';  
    $port_type = $users[$user_id]['temp_port_type'] ?? '';  
    $port_days = $users[$user_id]['temp_port_days'] ?? 0;  
    $port_price = $users[$user_id]['temp_port_price'] ?? 0;  
      
    if (empty($order_id) || empty($port_type) || $port_days <= 0) {  
        sendMessage($chat_id, "❌ خطا در پردازش اطلاعات سفارش!");  
        return;  
    }  
      
    $orders = loadOrdersData();  
      
    if (!isset($orders[$order_id])) {  
        sendMessage($chat_id, "❌ سفارش یافت نشد!");  
        return;  
    }  
      
    // به‌روزرسانی سفارش با هش تراکنش  
    $orders[$order_id]['transaction_hash'] = $hash;  
    $orders[$order_id]['status'] = 'pending_confirmation';  
    saveOrdersData($orders);  
      
    // ارسال به مالک برای تایید  
    $port_types = [  
        '7' => ['days' => 7, 'price' => 3, 'name' => '7 روزه'],  
        '15' => ['days' => 15, 'price' => 17, 'name' => '15 روزه'],  
        '30' => ['days' => 30, 'price' => 40, 'name' => '30 روزه']  
    ];  
      
    $port_info = $port_types[$port_type];  
      
    $text_to_owner = "📋 **سفارش جدید دریافت شد**\n\n";  
    $text_to_owner .= "🆔 آیدی کاربر: `{$user_id}`\n";  
    $text_to_owner .= "💬 چت آیدی: `{$chat_id}`\n";  
    $text_to_owner .= "📦 نوع پورت: {$port_info['name']}\n";  
    $text_to_owner .= "⏰ مدت زمان: {$port_info['days']} روز\n";  
    $text_to_owner .= "💰 قیمت: {$port_info['price']} TRX\n";  
    $text_to_owner .= "🔑 هش تراکنش: `{$hash}`\n";  
    $text_to_owner .= "🆔 کد سفارش: `{$order_id}`\n";  
    $text_to_owner .= "📅 زمان: " . date('Y-m-d H:i:s') . "\n\n";  
    $text_to_owner .= "لطفا تراکنش را بررسی و تایید کنید:";  
      
    $keyboard = [  
        'inline_keyboard' => [  
            [  
                ['text' => '✅ تایید تراکنش', 'callback_data' => 'confirm_order_' . $order_id],  
                ['text' => '❌ رد تراکنش', 'callback_data' => 'reject_order_' . $order_id]  
            ]  
        ]  
    ];  
      
    sendMessage(OWNER_ID, $text_to_owner, $keyboard);  
      
    // اطلاع به کاربر  
    $text_to_user = "✅ هش تراکنش شما دریافت شد و برای تایید به مدیر ارسال شد.\n";  
    $text_to_user .= "لطفا منتظر تایید مدیر باشید...\n\n";  
    $text_to_user .= "🔑 هش شما: `{$hash}`";  
      
    sendMessage($chat_id, $text_to_user);  
      
    // بازنشانی وضعیت کاربر  
    $users[$user_id]['state'] = 'idle';  
    unset($users[$user_id]['temp_order_id']);  
    unset($users[$user_id]['temp_port_type']);  
    unset($users[$user_id]['temp_port_days']);  
    unset($users[$user_id]['temp_port_price']);  
    saveUsersData($users);  
}  
  
// ============================================  
// تابع تایید سفارش توسط مالک  
// ============================================  
function confirmOrder($order_id, $chat_id, $user_id, $message_id) {  
    $orders = loadOrdersData();  
    
    if (!isset($orders[$order_id])) {  
        sendMessage($chat_id, "❌ سفارش یافت نشد!");  
        return;  
    }  
      
    $order = $orders[$order_id];  
      
    // به‌روزرسانی سفارش  
    $orders[$order_id]['status'] = 'confirmed';  
    $orders[$order_id]['confirmed'] = true;  
    $orders[$order_id]['confirmed_at'] = time();  
    $orders[$order_id]['confirmed_by'] = $user_id;  
    saveOrdersData($orders);  
      
    // به‌روزرسانی کاربر  
    $users = loadUsersData();  
    if (isset($users[$order['user_id']])) {  
        $users[$order['user_id']]['paid_port_available'] = true;  
        $users[$order['user_id']]['paid_port_type'] = $order['port_type'];  
        $users[$order['user_id']]['paid_port_days'] = $order['days'];  
        $users[$order['user_id']]['paid_port_price'] = $order['price'];  
        saveUsersData($users);  
    }  
      
    // ارسال پیام به کاربر  
    $text_to_user = "✅ **تراکنش شما تایید شد!**\n\n";  
    $text_to_user .= "هش تراکنش تایید شد و حساب شما شارژ شد✅️\n";  
    $text_to_user .= "ᴛʜᴇ ᴛʀᴀɴꜱᴀᴄᴛɪᴏɴ ʜᴀꜱʜ ɪꜱ ᴄᴏɴꜰɪʀᴍᴇᴅ ᴀɴᴅ ʏᴏᴜʀ ᴀᴄᴄᴏᴜɴᴛ ɪꜱ ᴄʜᴀʀɢᴇᴅ✅️\n\n";  
    $text_to_user .= "🕒 مدت زمان پورت: {$order['days']} روز\n";  
    $text_to_user .= "💰 مبلغ پرداختی: {$order['price']} TRX\n\n";  
    $text_to_user .= "✅ اکنون می‌توانید پورت خود را ایجاد کنید. لطفا از منوی زیر اقدام کنید:";  
      
    $keyboard = [  
        'inline_keyboard' => [  
            [  
                ['text' => '🚀 ایجاد پورت خریداری شده', 'callback_data' => 'create_paid_port']  
            ],  
            [  
                ['text' => '🔙 بازگشت به منو', 'callback_data' => 'back_to_main']  
            ]  
        ]  
    ];  
      
    sendMessage($order['chat_id'], $text_to_user, $keyboard);  
      
    // ویرایش پیام مالک  
    $text_to_owner_edited = "✅ **سفارش تایید شد**\n\n";  
    $text_to_owner_edited .= "🆔 کد سفارش: `{$order_id}`\n";  
    $text_to_owner_edited .= "👤 کاربر: `{$order['user_id']}`\n";  
    $text_to_owner_edited .= "💰 مبلغ: {$order['price']} TRX\n";  
    $text_to_owner_edited .= "⏰ مدت: {$order['days']} روز\n";  
    $text_to_owner_edited .= "📅 زمان تایید: " . date('Y-m-d H:i:s');  
      
    editMessageText(OWNER_ID, $message_id, $text_to_owner_edited);  
}  
  
// ============================================  
// تابع رد سفارش توسط مالک  
// ============================================  
function rejectOrder($order_id, $chat_id, $user_id, $message_id) {  
    $orders = loadOrdersData();  
    
    if (!isset($orders[$order_id])) {  
        sendMessage($chat_id, "❌ سفارش یافت نشد!");  
        return;  
    }  
      
    $order = $orders[$order_id];  
      
    // به‌روزرسانی سفارش  
    $orders[$order_id]['status'] = 'rejected';  
    $orders[$order_id]['confirmed'] = false;  
    $orders[$order_id]['rejected_at'] = time();  
    $orders[$order_id]['rejected_by'] = $user_id;  
    saveOrdersData($orders);  
      
    // ارسال پیام به کاربر  
    $text_to_user = "❌ **تراکنش شما رد شد!**\n\n";  
    $text_to_user .= "هش ارسال شده صحیح نمیباشد❌️\n";  
    $text_to_user .= "ᴛʜᴇ ʜᴀꜱʜ ꜱᴇɴᴛ ɪꜱ ɴᴏᴛ ᴄᴏʀʀᴇᴄᴛ❌️\n\n";  
    $text_to_user .= "🔑 هش ارسالی: `{$order['transaction_hash']}`\n";  
    $text_to_user .= "💰 مبلغ: {$order['price']} TRX\n";  
    $text_to_user .= "⏰ مدت: {$order['days']} روز\n\n";  
    $text_to_user .= "لطفا با پشتیبانی تماس بگیرید.";  
      
    $keyboard = [  
        'inline_keyboard' => [  
            [  
                ['text' => '🔙 بازگشت به منو', 'callback_data' => 'back_to_main']  
            ]  
        ]  
    ];  
      
    sendMessage($order['chat_id'], $text_to_user, $keyboard);  
      
    // ویرایش پیام مالک  
    $text_to_owner_edited = "❌ **سفارش رد شد**\n\n";  
    $text_to_owner_edited .= "🆔 کد سفارش: `{$order_id}`\n";  
    $text_to_owner_edited .= "👤 کاربر: `{$order['user_id']}`\n";  
    $text_to_owner_edited .= "💰 مبلغ: {$order['price']} TRX\n";  
    $text_to_owner_edited .= "📅 زمان رد: " . date('Y-m-d H:i:s');  
      
    editMessageText(OWNER_ID, $message_id, $text_to_owner_edited);  
}  
  
// ============================================  
// تابع شروع ایجاد پورت خریداری شده  
// ============================================  
function initiatePaidPortCreation($chat_id, $user_id) {  
    $users = loadUsersData();  
    
    if (!isset($users[$user_id])) {  
        sendMessage($chat_id, "❌ کاربر یافت نشد!");  
        return;  
    }  
      
    if (!($users[$user_id]['paid_port_available'] ?? false)) {  
        sendMessage($chat_id, "❌ شما هیچ پورت خریداری‌شده‌ای برای فعال‌سازی ندارید!\nلطفا ابتدا از بخش 'Shopping Port' خرید خود را انجام دهید.");  
        return;  
    }  
      
    $users[$user_id]['state'] = 'waiting_paid_token';  
    saveUsersData($users);  
      
    $text = "✅ شما یک پورت خریداری شده دارید!\n\n";  
    $text .= "🕒 مدت زمان: " . ($users[$user_id]['paid_port_days'] ?? 0) . " روز\n";  
    $text .= "💰 مبلغ پرداختی: " . ($users[$user_id]['paid_port_price'] ?? 0) . " TRX\n\n";  
    $text .= "لطفا توکن ربات خود را ارسال کنید:\nꜱᴜʙᴍɪᴛ ʏᴏᴜʀ ʙᴏᴛ ᴛᴏᴋᴇɴ:";  
      
    sendMessage($chat_id, $text);  
}  
  
// ============================================  
// تابع پردازش توکن دریافتی برای پورت خریداری شده  
// ============================================  
function handlePaidTokenInput($chat_id, $user_id, $token) {  
    $token = trim($token);  
      
    if (!preg_match('/^\d+:[a-zA-Z0-9_-]+$/', $token)) {  
        sendMessage($chat_id, "❌ فرمت توکن نامعتبر است!");  
        return;  
    }  
      
    if (!isValidToken($token)) {  
        sendMessage($chat_id, "❌ توکن نامعتبر است!");  
        return;  
    }  
      
    $users = loadUsersData();  
      
    // بررسی اینکه آیا کاربر واقعا پورت خریداری شده دارد  
    if (!($users[$user_id]['paid_port_available'] ?? false)) {  
        sendMessage($chat_id, "❌ شما هیچ پورت خریداری‌شده‌ای برای فعال‌سازی ندارید!");  
        return;  
    }  
      
    $users[$user_id]['temp_paid_token'] = $token;  
    $users[$user_id]['state'] = 'waiting_paid_group_id';  
    saveUsersData($users);  
      
    $text = "ایدی عددی گروه را ارسال کنید:\nꜱᴇɴᴅ ᴛʜᴇ ɴᴜᴍᴇʀɪᴄᴀʟ ɪᴅ ᴏꜰ ᴛʜᴇ ɢʀᴏᴜᴘ:";  
    sendMessage($chat_id, $text);  
}  
  
// ============================================  
// تابع پردازش آیدی گروه برای پورت خریداری شده  
// ============================================  
function handlePaidGroupIdInput($chat_id, $user_id, $group_id) {  
    $group_id = trim($group_id);  
      
    if (!preg_match('/^-100\d+$/', $group_id)) {  
        sendMessage($chat_id, "❌ فرمت آیدی گروه نامعتبر است!");  
        return;  
    }  
      
    $users = loadUsersData();  
    $token = $users[$user_id]['temp_paid_token'] ?? '';  
    $port_type = $users[$user_id]['paid_port_type'] ?? '';  
    $days = $users[$user_id]['paid_port_days'] ?? 0;  
    $price = $users[$user_id]['paid_port_price'] ?? 0;  
      
    // بررسی مجدد که آیا کاربر پورت خریداری شده دارد  
    if (!($users[$user_id]['paid_port_available'] ?? false)) {  
        sendMessage($chat_id, "❌ شما هیچ پورت خریداری‌شده‌ای برای فعال‌سازی ندارید!");  
        return;  
    }  
      
    if (empty($token) || empty($port_type) || $days <= 0) {  
        sendMessage($chat_id, "❌ خطا در پردازش اطلاعات. لطفا دوباره تلاش کنید.");  
        return;  
    }  
      
    $port_name = 'YTONRI_PAID_' . generateRandomString(6);  
    $result = createPort($port_name, $token, $group_id, $user_id);  
      
    if ($result['success']) {  
        $webhook_result = setTelegramWebhook($token, $port_name);  
          
        if (!$webhook_result['ok']) {  
            sendMessage($chat_id, "❌ خطا در ست کردن وب‌هوک");  
            return;  
        }  
          
        // ذخیره اطلاعات پورت خریداری شده  
        $users[$user_id]['port_name'] = $port_name;  
        $users[$user_id]['port_type'] = 'paid_' . $port_type;  
        $users[$user_id]['state'] = 'idle';  
        $users[$user_id]['port_created_at'] = time();  
        $users[$user_id]['port_expires_at'] = time() + ($days * 86400);  
        $users[$user_id]['bot_token'] = $token;  
          
        // حذف امکان پورت خریداری شده (فقط یک بار می‌تواند استفاده کند)  
        $users[$user_id]['paid_port_available'] = false;  
        unset($users[$user_id]['temp_paid_token']);  
        unset($users[$user_id]['paid_port_type']);  
        unset($users[$user_id]['paid_port_days']);  
        unset($users[$user_id]['paid_port_price']);  
        saveUsersData($users);  
          
        $ports = loadPortsData();  
        $expires_at = time() + ($days * 86400);  
        $ports[$port_name] = [  
            'name' => $port_name,  
            'user_id' => $user_id,  
            'chat_id' => $chat_id,  
            'bot_token' => $token,  
            'group_id' => $group_id,  
            'created_at' => time(),  
            'expires_at' => $expires_at,  
            'webhook_set' => true,  
            'status' => 'active',  
            'type' => 'paid',  
            'days' => $days,  
            'price' => $price  
        ];  
        savePortsData($ports);  
          
        $expire_date = date('Y-m-d H:i:s', $expires_at);  
        $text = "✅ **پورت خریداری شده فعال شد**\n\n";  
        $text .= "📝 نام پورت: `{$port_name}`\n";  
        $text .= "⏰ مدت زمان: {$days} روز\n";  
        $text .= "💰 مبلغ پرداختی: {$price} TRX\n";  
        $text .= "🕒 زمان شروع: " . date('Y-m-d H:i:s') . "\n";  
        $text .= "🕒 زمان پایان: {$expire_date}\n\n";  
        $text .= "⚠️ توجه: پس از {$days} روز وب‌هوک به صورت خودکار توسط سیستم حذف خواهد شد.\n";  
        $text .= "🔄 سیستم خودکار در زمان {$expire_date} وب‌هوک را حذف می‌کند.";  
          
        sendMessage($chat_id, $text);  
        updateUserKeyboard($chat_id, $user_id);  
          
        logMessage("✅ پورت خریداری شده ایجاد شد: {$port_name} - مدت: {$days} روز - انقضا: {$expire_date}");  
    } else {  
        sendMessage($chat_id, "❌ خطا در ایجاد پورت: " . ($result['error'] ?? 'خطای نامشخص'));  
    }  
}  
  
// ============================================  
// تابع نمایش اطلاعات کاربر  
// ============================================  
function showMyInfo($chat_id, $user_id) {  
    $users = loadUsersData();  
    $ports = loadPortsData();  
    
    $text = "👤 **اطلاعات کاربر**\n";  
    $text .= "🆔 آیدی کاربر: `{$user_id}`\n";  
      
    if (isset($users[$user_id])) {  
        $user_data = $users[$user_id];  
        $text .= "📅 تاریخ عضویت: " . $user_data['created_at'] . "\n";  
        $text .= "✅ تست پورت: " . ($user_data['used_test_port'] ? "✅ استفاده شده" : "❌ استفاده نشده") . "\n";  
          
        // بررسی پورت فعال  
        if (!empty($user_data['port_name'])) {  
            $port_name = $user_data['port_name'];  
            $text .= "✅ پورت فعال: دارد\n";  
              
            // ایجاد دکمه برای نمایش اطلاعات پورت  
            $keyboard = [  
                'inline_keyboard' => [  
                    [  
                        ['text' => '🏷 ' . $port_name, 'callback_data' => 'port_info_' . $port_name]  
                    ],  
                    [  
                        ['text' => '🔙 بازگشت', 'callback_data' => 'back_to_main']  
                    ]  
                ]  
            ];  
              
            sendMessage($chat_id, $text, $keyboard);  
            return;  
        } else {  
            $text .= "❌ پورت فعال: ندارد\n";  
        }  
          
        // بررسی پورت خریداری شده در انتظار فعال‌سازی  
        if ($user_data['paid_port_available'] ?? false) {  
            $text .= "💰 **پورت خریداری شده:**\n";  
            $text .= "⏰ مدت زمان: " . ($user_data['paid_port_days'] ?? 0) . " روز\n";  
            $text .= "💰 مبلغ: " . ($user_data['paid_port_price'] ?? 0) . " TRX\n";  
            $text .= "📊 وضعیت: در انتظار فعال‌سازی\n\n";  
              
            $keyboard = [  
                'inline_keyboard' => [  
                    [  
                        ['text' => '🚀 ایجاد پورت خریداری شده', 'callback_data' => 'create_paid_port']  
                    ],  
                    [  
                        ['text' => '🔙 بازگشت', 'callback_data' => 'back_to_main']  
                    ]  
                ]  
            ];  
              
            sendMessage($chat_id, $text, $keyboard);  
            return;  
        }  
    } else {  
        $text .= "❌ اطلاعات کاربر یافت نشد.";  
    }  
      
    $keyboard = [  
        'inline_keyboard' => [  
            [  
                ['text' => '🔙 بازگشت', 'callback_data' => 'back_to_main']  
            ]  
        ]  
    ];  
      
    sendMessage($chat_id, $text, $keyboard);  
}  
  
// ============================================  
// تابع نمایش اطلاعات پورت  
// ============================================  
function showPortInfo($chat_id, $user_id, $port_name) {  
    $ports = loadPortsData();  
    $users = loadUsersData();  
    
    if (!isset($ports[$port_name]) || $ports[$port_name]['user_id'] != $user_id) {  
        sendMessage($chat_id, "❌ پورت یافت نشد یا دسترسی ندارید.");  
        return;  
    }  
      
    $port_data = $ports[$port_name];  
    $remaining = $port_data['expires_at'] - time();  
      
    $text = "🔍 **اطلاعات پورت**\n\n";  
    $text .= "🏷 نام پورت: `{$port_name}`\n";  
    $text .= "🆔 آیدی گروه: `{$port_data['group_id']}`\n";  
    $text .= "📅 زمان ایجاد: " . date('Y-m-d H:i:s', $port_data['created_at']) . "\n";  
    $text .= "📅 زمان انقضا: " . date('Y-m-d H:i:s', $port_data['expires_at']) . "\n";  
    $text .= "📊 نوع پورت: " . ($port_data['type'] == 'test' ? 'تست (24 ساعت)' : 'خریداری شده') . "\n";  
      
    if ($port_data['type'] == 'paid' && isset($port_data['days'])) {  
        $text .= "⏰ مدت زمان: {$port_data['days']} روز\n";  
        if (isset($port_data['price'])) {  
            $text .= "💰 مبلغ پرداختی: {$port_data['price']} TRX\n";  
        }  
    }  
      
    if ($remaining > 0) {  
        $days = floor($remaining / 86400);  
        $hours = floor(($remaining % 86400) / 3600);  
        $minutes = floor(($remaining % 3600) / 60);  
        $seconds = $remaining % 60;  
          
        if ($days > 0) {  
            $text .= "⏳ زمان باقی‌مانده: {$days} روز و {$hours} ساعت\n";  
        } elseif ($hours > 0) {  
            $text .= "⏳ زمان باقی‌مانده: {$hours} ساعت و {$minutes} دقیقه\n";  
        } elseif ($minutes > 0) {  
            $text .= "⏳ زمان باقی‌مانده: {$minutes} دقیقه و {$seconds} ثانیه\n";  
        } else {  
            $text .= "⏳ زمان باقی‌مانده: {$seconds} ثانیه\n";  
        }  
        $text .= "✅ وضعیت: فعال\n";  
          
        // اگر کمتر از 5 دقیقه باقی مانده، هشدار بده  
        if ($remaining <= 300) {  
            $text .= "⚠️ **هشدار:** پورت شما کمتر از 5 دقیقه دیگر منقضی می‌شود!\n";  
        }  
    } else {  
        $text .= "⏰ زمان پورت به پایان رسیده\n";  
        $text .= "🔧 در حال حذف وب‌هوک توسط سیستم خودکار...\n";  
          
        // بررسی فوری انقضا  
        if ($port_data['webhook_set'] === true && $port_data['status'] === 'active') {  
            $text .= "\n🔄 در حال بررسی توسط سیستم خودکار...";  
            sendMessage($chat_id, $text);  
              
            // اجرای دستی سیستم خودکار  
            checkExpiredPortsAndNotify();  
            return;  
        }  
    }  
      
    $text .= "\n🔗 لینک پورت: https://imemad.space/{$port_name}/panel.php";  
      
    $keyboard = [  
        'inline_keyboard' => [  
            [  
                ['text' => '🔄 بررسی دستی', 'callback_data' => 'force_check_now']  
            ],  
            [  
                ['text' => '🔙 بازگشت', 'callback_data' => 'my_info']  
            ]  
        ]  
    ];  
      
    sendMessage($chat_id, $text, $keyboard);  
}  
  
// ============================================  
// تابع بررسی وضعیت پورت کاربر  
// ============================================  
function checkMyPortStatus($chat_id, $user_id) {  
    sendMessage($chat_id, "🔍 برای مشاهده وضعیت پورت خود، لطفا از منوی 'My Info' استفاده کنید.");  
}  
  
// ============================================  
// تابع آپدیت کیبورد کاربر  
// ============================================  
function updateUserKeyboard($chat_id, $user_id) {  
    $users = loadUsersData();  
    
    $keyboard = [];  
      
    // فقط اگر تست پورت استفاده نکرده باشد  
    if (!($users[$user_id]['used_test_port'] ?? false)) {  
        $keyboard[] = [['text' => 'ᴛᴇꜱᴛ ᴘᴏʀᴛ 𝟸𝟺 ʜᴏᴜʀꜱ', 'callback_data' => 'test_port']];  
    }  
      
    $keyboard[] = [  
        ['text' => 'ꜱʜᴏᴘᴘɪɴɢ ᴘᴏʀᴛ', 'callback_data' => 'shopping_port']  
    ];  
      
    $keyboard[] = [  
        ['text' => 'ᴍʏ ɪɴꜰᴏ', 'callback_data' => 'my_info'],  
        ['text' => 'ꜱᴀᴘᴏʀᴛ', 'url' => 'https://t.me/m/LAbxkv9nOTJk']  
    ];  
      
    // دکمه وضعیت سیستم اطلاع‌رسانی برای مالک  
    if ($user_id == OWNER_ID) {  
        $keyboard[] = [['text' => '🔄 وضعیت سیستم', 'callback_data' => 'check_notification_status']];  
    }  
      
    $replyMarkup = ['inline_keyboard' => $keyboard];  
      
    sendMessage($chat_id, "✅ منوی ربات آپدیت شد.", $replyMarkup);  
}  
  
// ============================================  
// توابع لاگ‌گیری  
// ============================================  
function logMessage($message) {  
    $log_file = DATA_DIR . 'webhook_notification.log';  
    $timestamp = date('Y-m-d H:i:s');  
    $formatted_message = "[{$timestamp}] {$message}\n";  
    file_put_contents($log_file, $formatted_message, FILE_APPEND);  
}  
  
// ============================================  
// توابع کار با داده‌های سیستم اطلاع‌رسانی  
// ============================================  
function loadNotificationsData() {  
    if (file_exists(NOTIFICATIONS_FILE)) {  
        $data = file_get_contents(NOTIFICATIONS_FILE);  
        return json_decode($data, true) ?: [];  
    }  
    return [];  
}  
  
function saveNotificationsData($data) {  
    file_put_contents(NOTIFICATIONS_FILE, json_encode($data, JSON_PRETTY_PRINT));  
}  
  
// ============================================  
// توابع کار با داده  
// ============================================  
function loadUsersData() {  
    if (file_exists(USERS_FILE)) {  
        $data = file_get_contents(USERS_FILE);  
        return json_decode($data, true) ?: [];  
    }  
    return [];  
}  
  
function saveUsersData($data) {  
    file_put_contents(USERS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));  
}  
  
function loadPortsData() {  
    if (file_exists(PORTS_FILE)) {  
        $data = file_get_contents(PORTS_FILE);  
        return json_decode($data, true) ?: [];  
    }  
    return [];  
}  
  
function savePortsData($data) {  
    file_put_contents(PORTS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));  
}  
  
function loadOrdersData() {  
    if (file_exists(ORDERS_FILE)) {  
        $data = file_get_contents(ORDERS_FILE);  
        return json_decode($data, true) ?: [];  
    }  
    return [];  
}  
  
function saveOrdersData($data) {  
    file_put_contents(ORDERS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));  
}  
  
// ============================================  
// توابع کمکی  
// ============================================  
function isValidToken($token) {  
    $url = "https://api.telegram.org/bot{$token}/getMe";  
    
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);  
      
    $response = curl_exec($ch);  
    curl_close($ch);  
      
    $data = json_decode($response, true);  
    return isset($data['ok']) && $data['ok'] === true;  
}  
  
function generateRandomString($length = 6) {  
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';  
    $randomString = '';  
    
    for ($i = 0; $i < $length; $i++) {  
        $randomString .= $characters[rand(0, strlen($characters) - 1)];  
    }  
      
    return $randomString;  
}  
  
function getUserState($user_id) {  
    $users = loadUsersData();  
    return $users[$user_id]['state'] ?? 'idle';  
}  
  
// ============================================  
// تابع کپی دایرکتوری  
// ============================================  
function copyDirectoryWithoutChanges($source, $destination) {  
    if (!is_dir($destination)) {  
        mkdir($destination, 0755, true);  
    }  
    
    $dir = opendir($source);  
    while (($file = readdir($dir)) !== false) {  
        if ($file != '.' && $file != '..') {  
            $srcFile = $source . '/' . $file;  
            $destFile = $destination . '/' . $file;  
              
            if (is_dir($srcFile)) {  
                copyDirectoryWithoutChanges($srcFile, $destFile);  
            } else {  
                copy($srcFile, $destFile);  
            }  
        }  
    }  
    closedir($dir);  
}  
  
// ============================================  
// توابع ارتباط با تلگرام  
// ============================================  
function sendMessage($chat_id, $text, $reply_markup = null) {  
    global $API_URL;  
    
    $data = [  
        'chat_id' => $chat_id,  
        'text' => $text,  
        'parse_mode' => 'HTML'  
    ];  
      
    if ($reply_markup) {  
        $data['reply_markup'] = json_encode($reply_markup);  
    }  
      
    return sendTelegramRequest('sendMessage', $data);  
}  
  
function editMessageText($chat_id, $message_id, $text, $reply_markup = null) {  
    global $API_URL;  
    
    $data = [  
        'chat_id' => $chat_id,  
        'message_id' => $message_id,  
        'text' => $text,  
        'parse_mode' => 'HTML'  
    ];  
      
    if ($reply_markup) {  
        $data['reply_markup'] = json_encode($reply_markup);  
    }  
      
    return sendTelegramRequest('editMessageText', $data);  
}  
  
function answerCallbackQuery($callback_id) {  
    global $API_URL;  
    
    $data = ['callback_query_id' => $callback_id];  
    return sendTelegramRequest('answerCallbackQuery', $data);  
}  
  
function sendTelegramRequest($method, $data) {  
    global $API_URL;  
    
    $url = $API_URL . '/' . $method;  
      
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_POST, true);  
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);  
      
    $result = curl_exec($ch);  
    curl_close($ch);  
      
    return $result;  
}  
  
// ============================================  
// صفحه مدیریت  
// ============================================  
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($update)) {  
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>مدیریت ربات YTONRI - اطلاع‌رسانی</title>  
    <style>  
    body{font-family:tahoma;padding:20px;background:#f5f5f5;}  
    .container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);}  
    h1{color:#333;border-bottom:2px solid #2196F3;padding-bottom:10px;}  
    .btn{background:#2196F3;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:5px;}  
    .btn-warning{background:#ff9800;}  
    .btn-danger{background:#f44336;}  
    .notification-box{background:#e3f2fd;padding:15px;border-radius:8px;border-left:4px solid #2196F3;margin:20px 0;}  
    pre{background:#333;color:#0f0;padding:15px;border-radius:8px;max-height:400px;overflow:auto;font-family:'Courier New', monospace;}  
    .stat-box{background:#e8f5e9;padding:15px;border-radius:8px;border-left:4px solid #4CAF50;margin:10px 0;}  
    .port-box{padding:10px;margin:5px 0;background:#f9f9f9;border-radius:5px;border-left:3px solid #2196F3;}  
    .expired-port{background:#ffebee;border-left:3px solid #f44336;}  
    .soon-expired{background:#fff3e0;border-left:3px solid #ff9800;}  
    </style>  
    </head><body>  
    <div class='container'>";  
  
    echo "<h1>🔔 مدیریت ربات YTONRI - سیستم اطلاع‌رسانی</h1>";  
    echo "<p>سیستم اطلاع‌رسانی پورت‌های منقضی شده - حذف وب‌هوک دستی توسط مالک</p>";  
    echo "<p>کانال اجباری: " . REQUIRED_CHANNEL . " (نمایشی)</p>";  
      
    echo "<div style='margin:20px 0;'>  
            <a href='?run_notification=1' class='btn'>🔔 اجرای سیستم اطلاع‌رسانی</a>  
            <a href='?check_status=1' class='btn'>📊 بررسی وضعیت</a>  
            <a href='?list_expired=1' class='btn btn-warning'>⏰ لیست پورت‌های منقضی</a>  
            <a href='?view_log=1' class='btn'>📝 مشاهده لاگ</a>  
          </div>";  
      
    // اجرای سیستم اطلاع‌رسانی  
    if (isset($_GET['run_notification'])) {  
        echo "<div class='notification-box'>";  
        echo "<h3>🔔 اجرای سیستم اطلاع‌رسانی</h3>";  
          
        ob_start();  
        checkExpiredPortsAndNotify();  
        $output = ob_get_clean();  
          
        echo "<pre>" . htmlspecialchars($output) . "</pre>";  
          
        // نمایش نتایج  
        $notifications_data = loadNotificationsData();  
        echo "<p>✅ آخرین اجرا: " . date('Y-m-d H:i:s', $notifications_data['last_run'] ?? time()) . "</p>";  
        echo "<p>🔔 اطلاع‌رسانی‌ها: " . ($notifications_data['last_notified_count'] ?? 0) . "</p>";  
        echo "</div>";  
    }  
      
    // بررسی وضعیت سیستم  
    if (isset($_GET['check_status'])) {  
        $notifications_data = loadNotificationsData();  
        $current_time = time();  
        $last_run = $notifications_data['last_run'] ?? 0;  
          
        echo "<div class='stat-box'>";  
        echo "<h3>📊 وضعیت سیستم اطلاع‌رسانی</h3>";  
          
        if ($last_run > 0) {  
            $last_run_time = date('Y-m-d H:i:s', $last_run);  
            $seconds_ago = $current_time - $last_run;  
            $minutes_ago = floor($seconds_ago / 60);  
              
            echo "<p>📅 آخرین اجرا: {$last_run_time}</p>";  
            echo "<p>⏰ زمان گذشته: {$seconds_ago} ثانیه ({$minutes_ago} دقیقه)</p>";  
            echo "<p>🔄 تعداد اجراها: " . ($notifications_data['total_runs'] ?? 0) . "</p>";  
            echo "<p>🔔 آخرین اطلاع‌رسانی: " . ($notifications_data['last_notified_count'] ?? 0) . " پورت</p>";  
            echo "<p>🔍 آخرین بررسی: " . ($notifications_data['last_expired_count'] ?? 0) . " پورت منقضی</p>";  
              
            if ($seconds_ago > 180) {  
                echo "<p style='color:red;'>⚠️ هشدار: سیستم اطلاع‌رسانی بیش از 3 دقیقه است اجرا نشده!</p>";  
            } else {  
                echo "<p style='color:green;'>✅ سیستم اطلاع‌رسانی به درستی کار می‌کند.</p>";  
            }  
        } else {  
            echo "<p style='color:red;'>❌ سیستم اطلاع‌رسانی هنوز اجرا نشده است.</p>";  
        }  
        echo "</div>";  
          
        // آمار پورت‌ها  
        $ports = loadPortsData();  
        $current_time = time();  
        $active_ports = 0;  
        $expired_ports = 0;  
        $test_ports = 0;  
        $paid_ports = 0;  
        $expiring_soon = 0;  
        $webhook_deleted = 0;  
          
        foreach ($ports as $port_data) {  
            if ($port_data['webhook_set'] === true && $port_data['status'] === 'active') {  
                $active_ports++;  
                if ($port_data['type'] == 'test') {  
                    $test_ports++;  
                } else {  
                    $paid_ports++;  
                }  
                  
                // بررسی پورت‌هایی که کمتر از 10 دقیقه دیگر منقضی می‌شوند  
                $remaining = $port_data['expires_at'] - $current_time;  
                if ($remaining > 0 && $remaining <= 600) {  
                    $expiring_soon++;  
                }  
            } elseif ($port_data['status'] === 'expired') {  
                $expired_ports++;  
            } elseif ($port_data['status'] === 'webhook_deleted') {  
                $webhook_deleted++;  
            }  
        }  
          
        echo "<div class='stat-box'>";  
        echo "<h3>📊 آمار پورت‌ها</h3>";  
        echo "<p>✅ پورت‌های فعال: {$active_ports}</p>";  
        echo "<p>⏰ پورت‌های منقضی: {$expired_ports}</p>";  
        echo "<p>🗑 وب‌هوک حذف شده: {$webhook_deleted}</p>";  
        echo "<p>🔧 تست 24h: {$test_ports} | 💰 خرید: {$paid_ports}</p>";  
        echo "<p>⚠️ منقضی شونده (کمتر از 10 دقیقه): {$expiring_soon}</p>";  
        echo "</div>";  
          
        // نمایش پورت‌های فعال  
        if ($active_ports > 0) {  
            echo "<h3>📋 پورت‌های فعال</h3>";  
            foreach ($ports as $port_name => $port_data) {  
                if ($port_data['webhook_set'] === true && $port_data['status'] === 'active') {  
                    $remaining = $port_data['expires_at'] - $current_time;  
                    $days = floor($remaining / 86400);  
                    $hours = floor(($remaining % 86400) / 3600);  
                    $minutes = floor(($remaining % 3600) / 60);  
                      
                    $box_class = 'port-box';  
                    if ($remaining <= 300) {  
                        $box_class = 'port-box soon-expired';  
                    }  
                      
                    echo "<div class='{$box_class}'>  
                            <strong>{$port_name}</strong><br>  
                            کاربر: {$port_data['user_id']}<br>  
                            نوع: " . ($port_data['type'] == 'test' ? 'تست 24h' : 'خریداری شده') . "<br>";  
                      
                    if ($port_data['type'] == 'paid' && isset($port_data['days'])) {  
                        echo "مدت: {$port_data['days']} روز<br>";  
                    }  
                      
                    if ($remaining > 0) {  
                        if ($days > 0) {  
                            echo "زمان باقی‌مانده: {$days} روز و {$hours} ساعت<br>";  
                        } else {  
                            echo "زمان باقی‌مانده: {$hours} ساعت و {$minutes} دقیقه<br>";  
                        }  
                    } else {  
                        echo "<span style='color:red;'>⏰ منقضی شده - اطلاع به مدیر ارسال شده</span><br>";  
                    }  
                      
                    // لینک حذف برای مدیر  
                    $delete_link = generateDeleteWebhookLink($port_data['bot_token']);  
                    echo "<small>لینک حذف: <code style='font-size:10px;'>{$delete_link}</code></small>  
                          </div>";  
                }  
            }  
        }  
    }  
      
    // لیست پورت‌های منقضی  
    if (isset($_GET['list_expired'])) {  
        $ports = loadPortsData();  
        $current_time = time();  
        $expired_ports = [];  
        
        foreach ($ports as $port_name => $port_data) {  
            if ($port_data['expires_at'] <= $current_time &&   
                $port_data['webhook_set'] === true) {  
                
                $expired_ports[] = [  
                    'name' => $port_name,  
                    'user_id' => $port_data['user_id'],  
                    'expired_at' => date('Y-m-d H:i:s', $port_data['expires_at']),  
                    'type' => $port_data['type'],  
                    'bot_token' => $port_data['bot_token']  
                ];  
            }  
        }  
        
        if (count($expired_ports) > 0) {  
            echo "<div class='notification-box'>";  
            echo "<h3>⏰ پورت‌های منقضی شده نیاز به حذف وب‌هوک</h3>";  
            echo "<p>تعداد: " . count($expired_ports) . " پورت</p>";  
            
            foreach ($expired_ports as $port) {  
                $delete_link = generateDeleteWebhookLink($port['bot_token']);  
                echo "<div class='port-box expired-port'>  
                        <strong>{$port['name']}</strong><br>  
                        کاربر: {$port['user_id']}<br>  
                        انقضا: {$port['expired_at']}<br>  
                        نوع: " . ($port['type'] == 'test' ? 'تست 24h' : 'خریداری شده') . "<br>  
                        <strong>لینک حذف:</strong><br>  
                        <code style='font-size:11px;word-break:break-all;'>{$delete_link}</code><br>  
                        <a href='{$delete_link}' target='_blank' class='btn' style='padding:5px 10px;margin-top:5px;'>🔗 باز کردن لینک</a>  
                      </div>";  
            }  
            echo "</div>";  
        } else {  
            echo "<div class='stat-box'>";  
            echo "<h3>✅ هیچ پورت منقضی‌شده‌ای وجود ندارد</h3>";  
            echo "<p>همه پورت‌ها فعال هستند یا قبلاً وب‌هوک آنها حذف شده است.</p>";  
            echo "</div>";  
        }  
    }  
      
    // مشاهده لاگ  
    if (isset($_GET['view_log'])) {  
        $log_file = DATA_DIR . 'webhook_notification.log';  
        if (file_exists($log_file)) {  
            echo "<h3>📝 لاگ سیستم اطلاع‌رسانی</h3>";  
              
            $lines = file($log_file);  
            $last_lines = array_slice($lines, -100);  
              
            echo "<pre>";  
            foreach ($last_lines as $line) {  
                echo htmlspecialchars($line);  
            }  
            echo "</pre>";  
              
            echo "<p><a href='?clear_log=1' class='btn btn-danger' onclick='return confirm(\"آیا مطمئن هستید؟\")'>🗑 پاک کردن لاگ</a></p>";  
        } else {  
            echo "<p>❌ فایل لاگ یافت نشد.</p>";  
        }  
    }  
      
    if (isset($_GET['clear_log'])) {  
        $log_file = DATA_DIR . 'webhook_notification.log';  
        file_put_contents($log_file, '');  
        echo "<script>alert('لاگ پاک شد!'); window.location.href = window.location.pathname;</script>";  
    }  
      
    echo "</div></body></html>";  
    exit;  
}  
?>