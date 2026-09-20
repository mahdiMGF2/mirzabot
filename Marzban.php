<?php
include('config.php');
require_once 'request.php';
date_default_timezone_set('Asia/Tehran');
#-----------------------------#
function token_panel($code_panel, $verify = true)
{
    $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");

    // Check if panel data exists
    if ($panel === null) {
        return null;
    }

    $url_get_token = $panel['url_panel'] . '/api/admin/token';
    $username_panel = $panel['username_panel'];
    $password_panel = $panel['password_panel'];
    if ($panel['datelogin'] != null && $verify) {
        $date = json_decode($panel['datelogin'], true);
        if (isset($date['time'])) {
            $start_date = time() - strtotime($date['time']);
            if ($start_date <= 3600) {
                return $date;
            }
        }
    }
    $data_token = array(
        'username' => $username_panel,
        'password' => $password_panel
    );
    $headers = array(
        'Content-Type: application/x-www-form-urlencoded',
        'accept: application/json'
    );
    $req = new CurlRequest($url_get_token);
    $req->setHeaders($headers);
    $response = $req->post($data_token);
    if (!empty($response['error'])) {
        return array("error" => $response['error']);
    }
    $body = json_decode($response['body'], true);
    if (isset($body['access_token'])) {
        $time = date('Y/m/d H:i:s');
        $data = json_encode(array(
            'time' => $time,
            'access_token' => $body['access_token']
        ));
        update("marzban_panel", "datelogin", $data, 'name_panel', $panel['name_panel']);
    }
    return $body;
}
#-----------------------------#

function getuser($username_account, $location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/user/' . $username_account;
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}
#-----------------------------#

function Get_Nodes($location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/nodes';
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}
function Get_usage_Nodes($location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/nodes/usage';
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}
function Get_Node($location, $Nodeid)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/node/' . $Nodeid;
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}

function getusers($location, $status)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/users?status=' . $status;
    if (!isset($Check_token['access_token']))
        return;
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, ($GLOBALS['request_exec_timeout'] ?? null) ?: 10000);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value . $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $data_useer = json_decode($output, true);
    return $data_useer;
}
#-----------------------------#
function getinbounds($location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!isset($Check_token['access_token']))
        return null;
    $url = $marzban_list_get['url_panel'] . '/api/inbounds';
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, ($GLOBALS['request_exec_timeout'] ?? null) ?: 10000);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value . $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $inbounds = json_decode($output, true);
    return $inbounds;
}
#-----------------------------#
function ResetUserDataUsage($username_account, $location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/user/' . $username_account . '/reset';

    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->post(array());
    return $response;
}
function revoke_sub($username_account, $location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/user/' . $username_account . '/revoke_sub';
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->post(array());
    return $response;
}
#-----------------------------#
function adduser($location, $data_limit, $username_ac, $timestamp, $note = '', $data_limit_reset = 'no_reset', $name_product = false)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . "/api/user";
    if ($marzban_list_get['inbounds'] != null and $marzban_list_get['inbounds'] != "null") {
        if ($name_product != false and $name_product != "usertest") {
            $product = select("product", "*", "name_product", $name_product, "select");
            if ($product == false || $product['inbounds'] == false) {
                $inbounds = json_decode($marzban_list_get['inbounds'], true);
            } else {
                $inbounds = json_decode($product['inbounds'], true);
                $marzban_list_get['proxies'] = $product['proxies'];
            }
        } else {
            $inbounds = json_decode($marzban_list_get['inbounds'], true);
        }
    }
    if (!panelProtocolsConfigured($marzban_list_get['proxies'])) {
        return panelProtocolsMissingError($marzban_list_get['name_panel']);
    }
    if ($marzban_list_get['version_panel'] == "1") {
        $data = array(
            "proxy_settings" => json_decode($marzban_list_get['proxies']),
            "data_limit" => $data_limit,
            "username" => $username_ac,
            "note" => $note,
            "data_limit_reset_strategy" => $data_limit_reset
        );
        if (isset($inbounds)) {
            $data['group_ids'] = $inbounds;
        }
        if ($name_product == "usertest") {
            if ($marzban_list_get['on_hold_test'] == "0") {
                if ($timestamp == 0) {
                    $data["expire"] = 0;
                } else {
                    $data["expire"] = date('c', $timestamp);
                }
            } else {
                if ($timestamp == 0) {
                    $data["expire"] = 0;
                } else {
                    $data["expire"] = 0;
                    $data["status"] = "on_hold";
                    $data["on_hold_expire_duration"] = $timestamp - time();
                }
            }
        } else {
            if ($marzban_list_get['conecton'] == "offconecton") {
                if ($timestamp == 0) {
                    $data["expire"] = 0;
                } else {
                    $data["expire"] = date('c', $timestamp);
                }
            } else {
                if ($timestamp == 0) {
                    $data["expire"] = 0;
                } else {
                    $data["expire"] = 0;
                    $data["status"] = "on_hold";
                    $data["on_hold_expire_duration"] = $timestamp - time();
                }
            }
        }
    } else {
        $data = array(
            "proxies" => json_decode($marzban_list_get['proxies']),
            "data_limit" => $data_limit,
            "username" => $username_ac,
            "note" => $note,
            "data_limit_reset_strategy" => $data_limit_reset
        );
        if (isset($inbounds)) {
            $data['inbounds'] = $inbounds;
        }
        if ($name_product == "usertest") {
            if ($marzban_list_get['on_hold_test'] == "0") {
                if ($timestamp == 0) {
                    $data["expire"] = 0;
                } else {
                    $data["expire"] = $timestamp;
                }
            } else {
                if ($timestamp == 0) {
                    $data["expire"] = 0;
                } else {
                    $data["expire"] = 0;
                    $data["status"] = "on_hold";
                    $data["on_hold_expire_duration"] = $timestamp - time();
                }
            }
        } else {
            if ($marzban_list_get['conecton'] == "offconecton") {
                if ($timestamp == 0) {
                    $data["expire"] = 0;
                } else {
                    $data["expire"] = $timestamp;
                }
            } else {
                if ($timestamp == 0) {
                    $data["expire"] = 0;
                } else {
                    $data["expire"] = 0;
                    $data["status"] = "on_hold";
                    $data["on_hold_expire_duration"] = $timestamp - time();
                }
            }
        }
    }
    $headers = array(
        'accept: application/json',
        'Content-Type: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->post(json_encode($data));
    return $response;
}
//----------------------------------
function Get_System_Stats($location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!isset($Check_token['access_token']))
        return null;
    $url = $marzban_list_get['url_panel'] . '/api/system';
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, ($GLOBALS['request_exec_timeout'] ?? null) ?: 10000);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value . $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $Get_System_Stats = json_decode($output, true);
    return $Get_System_Stats;
}
//----------------------------------
function removeuser($location, $username)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/user/' . $username;
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->delete();
    return $response;
}
function removenode($location, $nodeid)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/node/' . $nodeid;
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->delete();
    return $response;
}
//----------------------------------
function Modifyuser($location, $username, array $data)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/user/' . $username;
    $headers = array(
        'accept: application/json',
        'Content-Type: application/json'
    );
    $payload = json_encode($data);
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->put($payload);
    return $response;
}
//----------------------------------

function Modifyuser_node($location, $id_node, array $data)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    $url = $marzban_list_get['url_panel'] . '/api/node/' . $id_node;
    $payload = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Authorization: Bearer ' . $Check_token['access_token'];
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    curl_close($ch);
    $data_useer = json_decode($result, true);
    return $data_useer;
}
//----------------------------------
function reconnect_node($location, $id_node)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . '/api/node/' . $id_node . '/reconnect';
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->post(array());
    return $response;
}

function get_list_update($location, $username)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = $marzban_list_get['url_panel'] . "/api/user/$username/sub_update?offset=0&limit=1";
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}
