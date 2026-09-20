<?php
require_once 'config.php';
require_once 'request.php';
ini_set('error_log', 'error_log');

function get_clinets($username, $panel)
{
    $url = $panel['url_panel'] . "/panel/api/clients/get/$username";
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->get();
    return $response;
}
function addClient($panel, $usernameac, $Expire, $subId, $Total, $inboundid, $name_product, $note = "")
{
    if ($name_product == "usertest") {
        if ($panel['on_hold_test'] == "1") {
            if ($Expire == 0) {
                $timeservice = 0;
            } else {
                $timelast = $Expire - time();
                $timeservice = -intval(($timelast / 86400) * 86400000);
            }
        } else {
            $timeservice = $Expire * 1000;
        }
    } else {
        if ($panel['conecton'] == "onconecton") {
            if ($Expire == 0) {
                $timeservice = 0;
            } else {
                $timelast = $Expire - time();
                $timeservice = -intval(($timelast / 86400) * 86400000);
            }
        } else {
            $timeservice = $Expire * 1000;
        }
    }
    $data = [
        "email" => $usernameac,
        "totalGB" => $Total,
        "expiryTime" => $timeservice,
        "tgId" => 0,
        "comment" => $note,
        "enable" => true,
        "subId" => $subId
    ];
    $config = array(
        "inboundIds" => json_decode($inboundid, true),
        'client' => $data
    );
    $configpanel = json_encode($config, true);
    $url = $panel['url_panel'] . '/panel/api/clients/add';
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->post($configpanel);
    return $response;
}
function updateClient($panel, $uuid, array $config)
{

    $configpanel = json_encode($config, true);
    $url = $panel['url_panel'] . '/panel/api/clients/update/' . $uuid;
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->post($configpanel);
    return $response;
}
function ResetUserDataUsagex_uisin($usernamepanel, $panel)
{
    $url = $panel['url_panel'] . "/panel/api/clients/resetTraffic/" . $usernamepanel;
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->post(array());
    return $response;
}
function removeClient($panel, $username)
{
    $url = $panel['url_panel'] . "/panel/api/clients/del/" . $username;
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->post(array());
    return $response;
}
function status_server_xui($panel)
{
    $url = $panel['url_panel'] . "/panel/api/server/status";
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->get();
    return $response;
}
function attach_service($panel, $username, $configpanel)
{
    $url = $panel['url_panel'] . "/panel/api/clients/$username/attach";
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->post($configpanel);
    return $response;
}

function used_data_3xui($panel, $username)
{
    $url = $panel['url_panel'] . "/panel/api/clients/traffic/$username";
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->get();
    return $response;
}