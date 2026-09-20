<?php
require_once 'config.php';
require_once 'request.php';
date_default_timezone_set('Asia/Tehran');


function get_panel_list(array $panel)
{
    $url = $panel['url_panel'] . '?actions=list_panel';
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->get();
    return $response;
}


function create_user_mirza(array $panel, int $data_limit_gb, int $expire_days, string $username)
{
    $url = $panel['url_panel'];
    $headers = array(
        'accept: application/json'
    );
    $data = json_encode(array(
        'actions' => "user_create",
        'username' => $username,
        'data_limit_gb' => $data_limit_gb,
        'expire_days' => $expire_days,
        'panel_id' => $panel['inbounds']
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->post($data);
    return $response;
}


function get_user_data_mirza(array $panel, string $username)
{
    $url = $panel['url_panel'] . '?actions=get_user_data&username=' . urlencode($username);
    $headers = array(
        'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->get();
    return $response;
}
function add_time_service_mirza(array $panel, int $expire_days, string $username)
{
    $url = $panel['url_panel'];
    $headers = array(
        'accept: application/json'
    );
    $data = json_encode(array(
        'actions' => "add_time_service",
        'username' => $username,
        'time_day' => $expire_days,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->put($data);
    return $response;
}


function add_volume_service_mirza(array $panel, int $data_limit_gb, string $username)
{
    $url = $panel['url_panel'];
    $headers = array(
        'accept: application/json'
    );
    $data = json_encode(array(
        'actions' => "add_volume_service",
        'username' => $username,
        'data_limit_gb' => $data_limit_gb,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->put($data);
    return $response;
}



function extend_service_mirza(array $panel, int $data_limit_gb, int $time_day, string $username)
{
    $url = $panel['url_panel'];
    $headers = array(
        'accept: application/json'
    );
    $data = json_encode(array(
        'actions' => "extend_service",
        'username' => $username,
        'data_limit_gb' => $data_limit_gb,
        'time_day' => $time_day,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->put($data);
    return $response;
}

function remove_service_mirza(array $panel, string $username)
{
    $url = $panel['url_panel'];
    $headers = array(
        'accept: application/json'
    );
    $data = json_encode(array(
        'actions' => "user_delete",
        'username' => $username,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->delete($data);
    return $response;
}


function revoke_service_mirza(array $panel, string $username)
{
    $url = $panel['url_panel'];
    $headers = array(
        'accept: application/json'
    );
    $data = json_encode(array(
        'actions' => "change_link",
        'username' => $username,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    $response = $req->put($data);
    return $response;
}

