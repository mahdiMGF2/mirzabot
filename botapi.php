<?php
require_once 'config.php';
function isTelegramChatIdEmpty($chat_id): bool
{
    if ($chat_id === null || $chat_id === false) {
        return true;
    }
    if (is_array($chat_id) || is_object($chat_id)) {
        return true;
    }
    $chat_id = trim((string) $chat_id);
    if ($chat_id === '') {
        return true;
    }
    return is_numeric($chat_id) && (int) $chat_id === 0;
}
function walkKeyboardButtons($replyMarkup, callable $handler)
{
    if (!is_string($replyMarkup) || stripos($replyMarkup, '<tg-emoji') === false) {
        return $replyMarkup;
    }
    $markup = json_decode($replyMarkup, true);
    if (!is_array($markup)) {
        return $replyMarkup;
    }
    $changed = false;
    foreach (['keyboard', 'inline_keyboard'] as $markupKey) {
        if (!isset($markup[$markupKey]) || !is_array($markup[$markupKey])) {
            continue;
        }
        foreach ($markup[$markupKey] as $rowKey => $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach ($row as $btnKey => $button) {
                if (!is_array($button) || !isset($button['text']) || !is_string($button['text'])) {
                    continue;
                }
                $updated = $handler($button);
                if ($updated !== $button) {
                    $markup[$markupKey][$rowKey][$btnKey] = $updated;
                    $changed = true;
                }
            }
        }
    }
    if (!$changed) {
        return $replyMarkup;
    }
    $encoded = json_encode($markup, JSON_UNESCAPED_UNICODE);
    return $encoded === false ? $replyMarkup : $encoded;
}
function applyCustomEmojiToMarkup($replyMarkup)
{
    return walkKeyboardButtons($replyMarkup, function ($button) {
        $label = splitCustomEmojiLabel($button['text']);
        $button['text'] = $label['text'];
        if ($label['icon'] !== '' && !isset($button['icon_custom_emoji_id'])) {
            $button['icon_custom_emoji_id'] = $label['icon'];
        }
        return $button;
    });
}
function stripCustomEmojiFromMarkup($replyMarkup)
{
    return walkKeyboardButtons($replyMarkup, function ($button) {
        $button['text'] = stripCustomEmojiTags($button['text']);
        unset($button['icon_custom_emoji_id']);
        return $button;
    });
}
function payloadHasCustomEmoji(array $datas)
{
    foreach (['text', 'caption', 'reply_markup'] as $key) {
        if (isset($datas[$key]) && is_string($datas[$key]) && stripos($datas[$key], '<tg-emoji') !== false) {
            return true;
        }
    }
    return false;
}
function applyCustomEmojiPayload(array $datas)
{
    if (!payloadRendersHtml($datas)) {
        foreach (['text', 'caption'] as $key) {
            if (isset($datas[$key]) && is_string($datas[$key])) {
                $datas[$key] = stripCustomEmojiTags($datas[$key]);
            }
        }
    }
    if (isset($datas['reply_markup'])) {
        $datas['reply_markup'] = applyCustomEmojiToMarkup($datas['reply_markup']);
    }
    return $datas;
}
function stripCustomEmojiPayload(array $datas)
{
    foreach (['text', 'caption'] as $key) {
        if (isset($datas[$key]) && is_string($datas[$key])) {
            $datas[$key] = stripCustomEmojiTags($datas[$key]);
        }
    }
    if (isset($datas['reply_markup'])) {
        $datas['reply_markup'] = stripCustomEmojiFromMarkup($datas['reply_markup']);
    }
    return $datas;
}
function payloadRendersHtml(array $datas)
{
    $parseMode = $datas['parse_mode'] ?? '';
    return is_string($parseMode) && strtolower($parseMode) === 'html';
}
function customEmojiBlocked($token = null, $block = false)
{
    global $APIKEY;

    static $state = [];
    $key = md5((string) ($token === null ? $APIKEY : $token));
    $cacheDir = __DIR__ . '/storage/cache';
    $cacheFile = null;
    if (is_dir($cacheDir) || @mkdir($cacheDir, 0775, true) || is_dir($cacheDir)) {
        $cacheFile = $cacheDir . '/custom_emoji.json';
    }
    if ($block) {
        $state[$key] = true;
        if ($cacheFile !== null) {
            $stored = is_file($cacheFile) ? json_decode((string) file_get_contents($cacheFile), true) : [];
            if (!is_array($stored)) {
                $stored = [];
            }
            $now = time();
            foreach ($stored as $storedKey => $expiresAt) {
                if (!is_numeric($expiresAt) || $expiresAt <= $now) {
                    unset($stored[$storedKey]);
                }
            }
            $stored[$key] = $now + 21600;
            $encoded = json_encode($stored);
            if ($encoded !== false) {
                @file_put_contents($cacheFile, $encoded, LOCK_EX);
            }
        }
        return true;
    }
    if (array_key_exists($key, $state)) {
        return $state[$key];
    }
    $state[$key] = false;
    if ($cacheFile !== null && is_file($cacheFile)) {
        $stored = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($stored) && isset($stored[$key]) && is_numeric($stored[$key]) && $stored[$key] > time()) {
            $state[$key] = true;
        }
    }
    return $state[$key];
}
function telegram($method, $datas = [], $token = null, $allowEmojiFallback = true)
{
    global $APIKEY;

    $token = $token === null ? $APIKEY : $token;
    $url = "https://api.telegram.org/bot" . $token . "/" . $method;

    foreach (['chat_id', 'from_chat_id'] as $chatIdKey) {
        if (array_key_exists($chatIdKey, $datas) && isTelegramChatIdEmpty($datas[$chatIdKey])) {
            return [
                'ok' => false,
                'error_code' => 400,
                'description' => 'Bad Request: chat_id is empty'
            ];
        }
    }

    if (isset($datas['message_thread_id']) && intval($datas['message_thread_id']) <= 0) {
        unset($datas['message_thread_id']);
    }

    $premiumEmojiPayload = null;
    if (function_exists('splitCustomEmojiLabel') && payloadHasCustomEmoji($datas)) {
        $premiumEmojiPayload = $datas;
        $datas = customEmojiBlocked($token)
            ? stripCustomEmojiPayload($datas)
            : applyCustomEmojiPayload($datas);
    }

    $ch = curl_init($url);
    if ($ch === false) {
        error_log('Unable to initialise cURL for Telegram request.');
        return [
            'ok' => false,
            'description' => 'Unable to initialise cURL for Telegram request.'
        ];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);

    $rawResponse = curl_exec($ch);
    if ($rawResponse === false) {
        $curlError = curl_error($ch);

        if ($curlError !== '') {
            error_log('Telegram request failed: ' . $curlError);
        }

        return [
            'ok' => false,
            'description' => $curlError !== '' ? $curlError : 'Telegram request failed.'
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $decodedResponse = json_decode($rawResponse, true);
    if (!is_array($decodedResponse)) {
        $logSnippet = substr($rawResponse, 0, 200);
        error_log(sprintf('Invalid response from Telegram API (HTTP %d): %s', $httpCode, $logSnippet));

        return [
            'ok' => false,
            'error_code' => $httpCode,
            'description' => 'Invalid response received from Telegram.'
        ];
    }

    if (isset($decodedResponse['ok']) && !$decodedResponse['ok']) {
        $errorCode = $decodedResponse['error_code'] ?? 0;
        $description = $decodedResponse['description'] ?? '';
        $silent = $errorCode === 403
            || ($errorCode === 400 && (
                str_contains($description, 'message is not modified')
                || str_contains($description, "message can't be deleted")
                || str_contains($description, 'message to delete not found')
                || str_contains($description, 'chat not found')
            ));
        if ($allowEmojiFallback && $errorCode === 400 && !$silent && is_array($premiumEmojiPayload)) {
            $retry = telegram($method, stripCustomEmojiPayload($premiumEmojiPayload), $token, false);
            $emojiRejected = stripos($description, 'emoji') !== false
                || stripos($description, 'entit') !== false;
            if ($emojiRejected && (!isset($retry['ok']) || $retry['ok'])) {
                customEmojiBlocked($token, true);
            }
            return $retry;
        }
        if (!$silent) {
            error_log(json_encode($decodedResponse));
        }
    }

    return $decodedResponse;
}
function sendmessage($chat_id,$text,$keyboard,$parse_mode,$bot_token = null){
    if (isTelegramChatIdEmpty($chat_id)) {
        return ['ok' => false];
    }
    return telegram('sendmessage',[
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_markup' => $keyboard,
        'parse_mode' => $parse_mode,
        
        ],$bot_token);
}
function sendDocument($chat_id, $documentPath, $caption) {
        return telegram('sendDocument',[
        'chat_id' => $chat_id,
        'document' => new CURLFile($documentPath),
        'caption' => $caption,
        ]);
}

function forwardMessage($chat_id,$message_id,$chat_id_user){
    return telegram('forwardMessage',[
        'from_chat_id'=> $chat_id,
        'message_id'=> $message_id,
        'chat_id'=> $chat_id_user,
    ]);
}
function sendphoto($chat_id,$photoid,$caption){
    telegram('sendphoto',[
        'chat_id' => $chat_id,
        'photo'=> $photoid,
        'caption'=> $caption,
    ]);
}
function sendvideo($chat_id,$videoid,$caption){
    telegram('sendvideo',[
        'chat_id' => $chat_id,
        'video'=> $videoid,
        'caption'=> $caption,
    ]);
}
function Editmessagetext($chat_id, $message_id, $text, $keyboard,$parse_mode = 'HTML'){
    return telegram('editmessagetext', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'reply_markup' => $keyboard,
        'parse_mode' => $parse_mode,

    ]);
}
 function deletemessage($chat_id, $message_id){
  telegram('deletemessage', [
'chat_id' => $chat_id, 
'message_id' => $message_id,
]);
 }
function getFileddire($photoid){
  return telegram('getFile', [
'file_id' => $photoid, 
]);
 }
function pinmessage($from_id,$message_id){
  return telegram('pinChatMessage', [
'chat_id' => $from_id, 
'message_id' => $message_id, 
]);
 }
 function unpinmessage($from_id){
  return telegram('unpinAllChatMessages', [
'chat_id' => $from_id, 
]);
 }
  function answerInlineQuery($inline_query_id,$results){
  return telegram('answerInlineQuery', [
      "inline_query_id" => $inline_query_id,
        "results" => json_encode($results)
]);
 }
function convertPersianNumbersToEnglish($string) {
    $persian_numbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    return str_replace($persian_numbers, $english_numbers, $string);
}

function isDuplicateUpdate($updateId)
{
    if (!is_numeric($updateId) || $updateId <= 0) {
        return false;
    }

    $cacheDir = __DIR__ . '/storage/cache';
    if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
        return false;
    }

    $cacheFile = $cacheDir . '/recent_updates.json';
    $handle = fopen($cacheFile, 'c+');
    if ($handle === false) {
        return false;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return false;
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        $recentUpdates = $contents ? json_decode($contents, true) : [];
        if (!is_array($recentUpdates)) {
            $recentUpdates = [];
        }

        $now = time();
        $timeToLive = 120; // seconds

        // Drop expired entries
        foreach ($recentUpdates as $id => $timestamp) {
            if (!is_numeric($timestamp) || ($now - (int)$timestamp) > $timeToLive) {
                unset($recentUpdates[$id]);
            }
        }

        if (array_key_exists($updateId, $recentUpdates)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return true;
        }

        $recentUpdates[$updateId] = $now;

        // keep size reasonable
        if (count($recentUpdates) > 200) {
            asort($recentUpdates);
            $recentUpdates = array_slice($recentUpdates, -200, null, true);
        }

        $encoded = json_encode($recentUpdates);
        if ($encoded !== false) {
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $encoded);
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    } catch (Throwable $e) {
        try {
            flock($handle, LOCK_UN);
        } catch (Throwable $ignored) {
        }
        fclose($handle);
        return false;
    }

    return false;
}
// #-----------------------------#
$update = json_decode(file_get_contents("php://input"), true);
$update_id = $update['update_id'] ?? 0;
if (isDuplicateUpdate($update_id)) {
    http_response_code(200);
    exit;
}
$from_id = $update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? $update["inline_query"]['from']['id'] ?? 0;
$time_message = $update['message']['date'] ?? $update['callback_query']['date'] ?? $update["inline_query"]['date'] ?? 0;
$is_bot = $update['message']['from']['is_bot'] ?? false;
$chat_member = $update['chat_member'] ?? null;
$Chat_type = $update["message"]["chat"]["type"] ?? $update['callback_query']['message']['chat']['type'] ?? '';
$text = $update["message"]["text"]  ?? '';
$entities = $update['message']['entities'] ?? null;
if ($text !== '' && is_array($entities)) {
    $customEmojis = [];
    foreach ($entities as $entity) {
        if (($entity['type'] ?? null) === 'custom_emoji' && isset($entity['custom_emoji_id'])) {
            $customEmojis[] = $entity;
        }
    }

    if ($customEmojis) {
        usort($customEmojis, fn($a, $b) => $a['offset'] <=> $b['offset']);

        $utf16 = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
        $utf16Length = strlen($utf16);
        $tagOpen = mb_convert_encoding('<tg-emoji emoji-id="', 'UTF-16LE', 'UTF-8');
        $tagOpenEnd = mb_convert_encoding('">', 'UTF-16LE', 'UTF-8');
        $tagClose = mb_convert_encoding('</tg-emoji>', 'UTF-16LE', 'UTF-8');

        $result = '';
        $cursor = 0;
        foreach ($customEmojis as $entity) {
            $start = ((int) $entity['offset']) * 2;
            $length = ((int) $entity['length']) * 2;
            if ($start < $cursor || $length <= 0 || $start + $length > $utf16Length) {
                continue;
            }

            $emojiId = mb_convert_encoding(
                htmlspecialchars((string) $entity['custom_emoji_id'], ENT_QUOTES, 'UTF-8'),
                'UTF-16LE',
                'UTF-8'
            );

            $result .= substr($utf16, $cursor, $start - $cursor)
                . $tagOpen . $emojiId . $tagOpenEnd
                . substr($utf16, $start, $length)
                . $tagClose;
            $cursor = $start + $length;
        }

        if ($cursor > 0) {
            $text = mb_convert_encoding($result . substr($utf16, $cursor), 'UTF-8', 'UTF-16LE');
        }
    }
}
if(isset($update['pre_checkout_query'])){
    $Chat_type = "private";
    $from_id = $update['pre_checkout_query']['from']['id'];
}
$text =convertPersianNumbersToEnglish($text);
$text_inline = $update["callback_query"]["message"]['text'] ?? '';
$message_id = $update["message"]["message_id"] ?? $update["callback_query"]["message"]["message_id"] ?? 0;
$time_message = $update["message"]["date"] ?? $update["callback_query"]["date"] ?? 0;
$photo = $update["message"]["photo"] ?? 0;
$document = $update["message"]["document"] ?? 0;
$fileid = $update["message"]["document"]["file_id"] ?? 0;
$photoid = $photo ? end($photo)["file_id"] : '';
$caption = $update["message"]["caption"] ?? '';
$video = $update["message"]["video"] ?? 0;
$videoid = $video ? $video["file_id"] : 0;
$datain = $update["callback_query"]["data"] ?? '';
$first_name = $update['message']['from']['first_name']  ?? $update["callback_query"]["from"]["first_name"] ?? $update["inline_query"]['from']['first_name'] ?? '';
$username = $update['message']['from']['username'] ?? $update['callback_query']['from']['username'] ?? $update["callback_query"]["from"]["username"] ?? 'NOT_USERNAME';
$user_phone =$update["message"]["contact"]["phone_number"] ?? 0;
$contact_id = $update["message"]["contact"]["user_id"] ?? 0;
$callback_query_id = $update["callback_query"]["id"] ?? 0;
$inline_query_id = $update["inline_query"]["id"] ?? 0;
$query = $update["inline_query"]["query"] ?? 0;