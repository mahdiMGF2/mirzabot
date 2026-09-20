<?php
require_once 'config.php';
require_once 'request.php';
ini_set('error_log', 'error_log');
function alirezaCookiePath($code_panel)
{
    return sys_get_temp_dir() . '/mirza_alireza_' . md5((string) $code_panel) . '.cookie';
}
function panel_login_cookie($code_panel)
{
    $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $panel['url_panel'] . '/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => ($GLOBALS['request_exec_timeout'] ?? null) ?: 10000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => "username=" . urlencode($panel['username_panel']) . "&password=" . urlencode($panel['password_panel']),
        CURLOPT_COOKIEJAR => alirezaCookiePath($code_panel),
    ));
    $response = curl_exec($curl);
    if (curl_error($curl)) {
        return json_encode(array(
            'success' => false,
            'msg' => curl_error($curl)
        ));
    }
    return $response;
}
function login($code_panel, $verify = true)
{
    $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
    $cookieFile = alirezaCookiePath($code_panel);
    if ($panel['datelogin'] != null && $verify) {
        $date = json_decode($panel['datelogin'], true);
        if (isset($date['time']) && !empty($date['access_token'])) {
            $start_date = time() - strtotime($date['time']);
            if ($start_date <= 3000) {
                file_put_contents($cookieFile, $date['access_token']);
                return;
            }
        }
    }
    $response = panel_login_cookie($panel['code_panel']);
    $cookieContent = is_file($cookieFile) ? file_get_contents($cookieFile) : false;
    if ($cookieContent !== false && $cookieContent !== '') {
        $data = json_encode(array(
            'time' => date('Y/m/d H:i:s'),
            'access_token' => $cookieContent
        ));
        update("marzban_panel", "datelogin", $data, 'name_panel', $panel['name_panel']);
    }
    if (!is_string($response))
        return array('success' => false);
    return json_decode($response, true);
}

function get_clinetsalireza($username, $namepanel)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    login($marzban_list_get['code_panel']);
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/xui/API/inbounds',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT_MS => ($GLOBALS['request_exec_timeout'] ?? null) ?: 4000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json'
        ),
        CURLOPT_COOKIEFILE => alirezaCookiePath($marzban_list_get['code_panel']),
    ));
    $output = [];
    $response = json_decode(curl_exec($curl), true)['obj'];
    if (!isset($response))
        return;
    foreach ($response as $client) {
        $clientdata = json_decode($client['settings'], true)['clients'];
        foreach ($clientdata as $clinets) {
            if ($clinets['email'] == $username) {
                $output[] = $clinets;
                break;
            }
        }
        $clientStats = $client['clientStats'];
        foreach ($clientStats as $clinetsup) {
            if ($clinetsup['email'] == $username) {
                $output[] = $clinetsup;
                break;
            }
        }

    }
    curl_close($curl);
    @unlink(alirezaCookiePath($marzban_list_get['code_panel']));
    return $output;
}
function addClientalireza_singel($namepanel, $usernameac, $Expire, $Total, $Uuid, $Flow, $subid, $inboundid)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    login($marzban_list_get['code_panel']);
    $config = array(
        "id" => intval($inboundid),
        'settings' => json_encode(array(
            'clients' => array(
                array(
                    "id" => $Uuid,
                    "flow" => $Flow,
                    "email" => $usernameac,
                    "totalGB" => $Total,
                    "expiryTime" => $Expire,
                    "enable" => true,
                    "tgId" => "",
                    "subId" => $subid,
                    "reset" => 0
                )
            ),
            'decryption' => 'none',
            'fallbacks' => array(),
        ))
    );

    $configpanel = json_encode($config, true);
    $url = $marzban_list_get['url_panel'] . '/xui/API/inbounds/addClient';
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setCookie(alirezaCookiePath($marzban_list_get['code_panel']));
    $response = $req->post($configpanel);
    @unlink(alirezaCookiePath($marzban_list_get['code_panel']));
    return $response;
}
function updateClientalireza($namepanel, $username, array $config)
{
    $UsernameData = get_clinetsalireza($username, $namepanel)[0];
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    login($marzban_list_get['code_panel']);
    $configpanel = json_encode($config, true);
    $url = $marzban_list_get['url_panel'] . '/xui/API/inbounds/updateClient/' . $UsernameData['id'];
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setCookie(alirezaCookiePath($marzban_list_get['code_panel']));
    $response = $req->post($configpanel);
    @unlink(alirezaCookiePath($marzban_list_get['code_panel']));
    return $response;
}
function ResetUserDataUsagealirezasin($usernamepanel, $namepanel)
{
    $data_user = get_clinetsalireza($usernamepanel, $namepanel)[0];
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    login($marzban_list_get['code_panel']);
    $url = $marzban_list_get['url_panel'] . "/xui/API/inbounds/{$marzban_list_get['inboundid']}/resetClientTraffic/" . $data_user['email'];
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setCookie(alirezaCookiePath($marzban_list_get['code_panel']));
    $response = $req->post(array());
    @unlink(alirezaCookiePath($marzban_list_get['code_panel']));
    return $response;
}
function removeClientalireza_single($location, $username)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $data_user = get_clinetsalireza($username, $location)[0];
    login($marzban_list_get['code_panel']);
    $url = $marzban_list_get['url_panel'] . "/xui/API/inbounds/{$marzban_list_get['inboundid']}/delClient/" . $data_user['id'];
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setCookie(alirezaCookiePath($marzban_list_get['code_panel']));
    $response = $req->post(array());
    @unlink(alirezaCookiePath($marzban_list_get['code_panel']));
    return $response;

}
function get_onlineclialireza($name_panel, $username)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $name_panel, "select");
    login($marzban_list_get['code_panel']);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/xui/API/inbounds/onlines',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json'
        ),
        CURLOPT_COOKIEFILE => alirezaCookiePath($marzban_list_get['code_panel']),
    ));
    $response = json_decode(curl_exec($curl), true)['obj'] ?? null;
    if ($response == null)
        return "offline";
    if (in_array($username, $response))
        return "online";
    return "offline";

}