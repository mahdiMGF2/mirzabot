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
function telegram($method, $datas = [],$botToken = null)
{
    global $ApiToken;
    if($botToken != null){
        $ApiToken = $botToken;
    }
    foreach (['chat_id', 'from_chat_id'] as $chatIdKey) {
        if (array_key_exists($chatIdKey, $datas) && isTelegramChatIdEmpty($datas[$chatIdKey])) {
            return [
                'ok' => false,
                'error_code' => 400,
                'description' => 'Bad Request: chat_id is empty'
            ];
        }
    }
    $url = "https://api.telegram.org/bot" . $ApiToken . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res,true);
    }
}
function sendmessage($chat_id,$text,$keyboard,$parse_mode){
    if (isTelegramChatIdEmpty($chat_id)) {
        return ['ok' => false];
    }
    return telegram('sendmessage',[
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_markup' => $keyboard,
        'parse_mode' => $parse_mode,
        
        ]);
}
function sendDocument($chat_id, $documentPath, $caption) {
        telegram('sendDocument',[
        'chat_id' => $chat_id,
        'document' => new CURLFile($documentPath),
        'caption' => $caption,
        ]);
}

function forwardMessage($chat_id,$message_id,$chat_id_user){
    telegram('forwardMessage',[
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
#-----------------------------#
$update = json_decode(file_get_contents("php://input"), true);
$from_id = $update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? $update["inline_query"]['from']['id'] ?? 0;
$Chat_type = $update["message"]["chat"]["type"] ?? $update['callback_query']['message']['chat']['type'] ?? '';
$text = $update["message"]["text"]  ?? '';
$text =convertPersianNumbersToEnglish($text);
$text_inline = $update["callback_query"]["message"]['text'] ?? '';
$message_id = $update["message"]["message_id"] ?? $update["callback_query"]["message"]["message_id"] ?? 0;
$photo = $update["message"]["photo"] ?? 0;
$document = $update["message"]["document"] ?? 0;
$fileid = $update["message"]["document"]["file_id"] ?? 0;
$photoid = $photo ? end($photo)["file_id"] : '';
$caption = $update["message"]["caption"] ?? '';
$video = $update["message"]["video"] ?? 0;
$videoid = $video ? $video["file_id"] : 0;
$forward_from_id = $update["message"]["reply_to_message"]["forward_from"]["id"] ?? 0;
$datain = $update["callback_query"]["data"] ?? '';
$first_name = $update['message']['from']['first_name']  ?? $update["callback_query"]["from"]["first_name"] ?? $update["inline_query"]['from']['first_name'] ?? '';
$username = $update['message']['from']['username'] ?? $update['callback_query']['from']['username'] ?? $update["callback_query"]["from"]["username"] ?? 'NOT_USERNAME';
$user_phone =$update["message"]["contact"]["phone_number"] ?? 0;
$contact_id = $update["message"]["contact"]["user_id"] ?? 0;
$callback_query_id = $update["callback_query"]["id"] ?? 0;
$inline_query_id = $update["inline_query"]["id"] ?? 0;
$query = $update["inline_query"]["query"] ?? 0;