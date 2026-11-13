<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../request.php';
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
// Inbound ids that really exist on the panel right now, or null when the list cannot be read.
function xui_existing_inbound_ids($panel)
{
    $req = new CurlRequest(rtrim($panel['url_panel'], '/') . '/panel/api/inbounds/list');
    $req->setHeaders(array('Accept: application/json', 'Content-Type: application/json'));
    $req->setBearerToken($panel['password_panel']);
    $res = $req->get();
    if (!empty($res['error']) || ($res['status'] ?? 0) != 200) {
        return null;
    }
    $json = json_decode((string) ($res['body'] ?? ''), true);
    if (!is_array($json) || empty($json['success']) || !is_array($json['obj'] ?? null)) {
        return null;
    }
    $ids = array();
    foreach ($json['obj'] as $inbound) {
        if (isset($inbound['id'])) {
            $ids[] = intval($inbound['id']);
        }
    }
    return $ids;
}
// Tell the admins (at most once a day per panel and inbound) that a configured inbound no longer exists.
function xui_notify_missing_inbounds($panel, array $missing)
{
    try {
        $file = __DIR__ . '/../../storage/missing_inbounds.json';
        $seen = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: array()) : array();
        $today = date('Y-m-d');
        $fresh = array();
        foreach ($missing as $id) {
            $key = $panel['name_panel'] . '#' . $id;
            if (($seen[$key] ?? '') !== $today) {
                $fresh[] = $id;
                $seen[$key] = $today;
            }
        }
        if (!$fresh) {
            return;
        }
        @file_put_contents($file, json_encode($seen));
        $group = config_get('setting', 'Channel_Report', '');
        if ($group) {
            $textbotlang = languagechange();
            telegram('sendmessage', array(
                'chat_id' => $group,
                'message_thread_id' => config_get('topic', 'errorreport'),
                'text' => sprintf($textbotlang['Admin']['reportgroup']['xuiMissingInbound'], $panel['name_panel'], implode(', ', $fresh)),
                'parse_mode' => 'HTML',
            ));
        }
    } catch (Throwable $e) {
        error_log('xui missing inbound notice failed: ' . $e->getMessage());
    }
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
    $inboundIds = json_decode($inboundid, true);
    $url = $panel['url_panel'] . '/panel/api/clients/add';
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $send = function ($ids) use ($url, $headers, $panel, $data) {
        $req = new CurlRequest($url);
        $req->setHeaders($headers);
        $req->setBearerToken($panel['password_panel']);
        return $req->post(json_encode(array("inboundIds" => $ids, 'client' => $data), true));
    };
    $response = $send($inboundIds);
    // Only when the panel refused the request, look for inbounds that were deleted on the panel:
    // create the account on the ones that still exist and tell the admins about the rest.
    $body = json_decode((string) ($response['body'] ?? ''), true);
    $failed = !empty($response['error']) || ($response['status'] ?? 0) != 200 || !is_array($body) || empty($body['success']);
    if ($failed && is_array($inboundIds) && $inboundIds) {
        $alive = xui_existing_inbound_ids($panel);
        if ($alive !== null) {
            $kept = array();
            $missing = array();
            foreach ($inboundIds as $id) {
                if (in_array(intval($id), $alive, true)) {
                    $kept[] = $id;
                } else {
                    $missing[] = $id;
                }
            }
            if ($missing) {
                error_log("x-ui {$panel['name_panel']}: missing inbounds " . implode(',', $missing));
                xui_notify_missing_inbounds($panel, $missing);
                if ($kept) {
                    $response = $send($kept);
                }
            }
        }
    }
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