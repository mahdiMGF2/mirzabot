<?php
chdir(__DIR__);

ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
$ManagePanel = new ManagePanel();


$setting = select("setting", "*");
$errorreport = select("topicid","idreport","report","errorreport","select")['idreport'];

$textbotlang = languagechange();
if(!is_file('gift'))return;
if(!is_file('username.json'))return;


$userid = json_decode(file_get_contents('username.json'));
if(is_file('gift')){
$info = json_decode(file_get_contents('gift'),true);
}
$count = 0;
if(count($userid) == 0){
    if(isset($info['id_admin'])){
    deletemessage($info['id_admin'], $info['id_message']);
    sendmessage($info['id_admin'], $textbotlang['Admin']['gift']['done'], null, 'HTML');
    unlink('gift');
    unlink('username.json');
    }
    return;
    
}

if(!isset($info['typegift']))return;
if($info['typegift'] == "volume"){
    $count =  0;
foreach ($userid as $iduser){
    $count +=1;
    if($count == 5)break;
    $get_username_info = $ManagePanel->DataUser($info['name_panel'],$iduser->username);
    unset($userid[0]);
    $userid = array_values($userid);
    if(!(empty($get_username_info['expire']) || empty($get_username_info['data_limit']) || $get_username_info['status'] == "Unsuccessful")){
    $invoce = select("invoice","*","username",$iduser->username,"select");
    $marzban_list_get = select("marzban_panel","*","name_panel",$info['name_panel'],"select");
    $data_limit = $get_username_info['data_limit'] / pow(1024,3);
    $data_limit_new = $data_limit + intval($info['value']);
    $data_limit_byte = $data_limit_new *pow(1024,3);
    $extra_volume = $ManagePanel->extra_volume($invoce['username'],$marzban_list_get['code_panel'],$info['value']);
     if($extra_volume['status'] == false){
            $extra_volume['msg'] = json_encode($extra_volume['msg']);
            $textreports = sprintf($textbotlang['Admin']['gift']['volumeAddError'], $marzban_list_get['name_panel'], $iduser->username, $extra_volume['msg']);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage',[
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $textreports,
                    'parse_mode' => "HTML"
                ]);
            }
        }else{
sendmessage($invoce['id_user'],$info['text'],null,"html");
}
$data_for_database = json_encode(array(
        'volume_value' => $info['value'],
        'old_volume' => $get_username_info['data_limit'],
        'expire_old' => $get_username_info['expire']
    ));
$volumepricelast = 0;
$stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price, output) VALUES (:id_user, :username, :value, :type, :time, :price, :output)");
    $value = $data_for_database;
    $dateacc = date('Y/m/d H:i:s');
    $type = "gift_volume";
    $stmt->execute([
    ':id_user' => $invoce['id_user'],
    ':username' => $invoce['username'],
    ':value' => $value,
    ':type' => $type,
    ':time' => $dateacc,
    ':price' => $volumepricelast,
    ':output' => json_encode($extra_volume),
    ]);
}
}
}
else{
    
$count =  0;
foreach ($userid as $iduser){
    $count +=1;
    if($count == 5)break;
    $get_username_info = $ManagePanel->DataUser($info['name_panel'],$iduser->username);
    unset($userid[0]);
    $userid = array_values($userid);
    if(!(empty($get_username_info['expire']) || $get_username_info['status'] == "Unsuccessful")){
    $invoce = select("invoice","*","username",$iduser->username,"select");
    $marzban_list_get = select("marzban_panel","*","name_panel",$info['name_panel'],"select");
    $extra_time = $ManagePanel->extra_time($get_username_info['username'],$marzban_list_get['code_panel'],intval($info['value']));
     if($extra_time['status'] == false){
            $extra_time['msg'] = json_encode($extra_time['msg']);
            $textreports = sprintf($textbotlang['Admin']['gift']['volumeAddError2'], $marzban_list_get['name_panel'], $iduser->username, $extra_time['msg']);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage',[
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $textreports,
                    'parse_mode' => "HTML"
                ]);
            }
        }else{
            sendmessage($invoce['id_user'],$info['text'],null,"html");
        }
$data_for_database = json_encode(array(
        'time_value' => $info['value'],
        'old_volume' => $get_username_info['data_limit'],
        'expire_old' => $get_username_info['expire']
    ));
$volumepricelast = 0;
$stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price, output) VALUES (:id_user, :username, :value, :type, :time, :price, :output)");
    $value = $data_for_database;
    $dateacc = date('Y/m/d H:i:s');
    $type = "gift_time";
    $stmt->execute([
    ':id_user' => $invoce['id_user'],
    ':username' => $invoce['username'],
    ':value' => $value,
    ':type' => $type,
    ':time' => $dateacc,
    ':price' => $volumepricelast,
    ':output' => json_encode($extra_time),
    ]);
}
}
}
file_put_contents('username.json',json_encode($userid,true));