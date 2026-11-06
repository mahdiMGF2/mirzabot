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
// Tell the admin (at most once a day per panel and inbound) that a configured inbound no longer exists.
function xui_notify_missing_inbounds($panel, array $missing)
{
    try {
        global $pdo;
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
        $stmt = $pdo->query("SELECT Channel_Report FROM setting LIMIT 1");
        $group = $stmt ? $stmt->fetchColumn() : '';
        if ($group) {
            telegram('sendmessage', array(
                'chat_id' => $group,
                'text' => "⚠️ اینباند حذف‌شده روی پنل «{$panel['name_panel']}»\n\nاینباند(های) " . implode('، ', $fresh) . " در پنل وجود ندارند و هنگام ساخت اکانت رد شدند (اکانت‌ها با بقیهٔ اینباندها ساخته می‌شوند). لطفاً شناسهٔ اینباندها را در تنظیمات پنل یا محصول اصلاح کنید.",
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
    if (is_array($inboundIds) && $inboundIds) {
        // Skip inbounds that were deleted on the panel instead of failing the whole account creation.
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
                error_log("x-ui {$panel['name_panel']}: skipped missing inbounds " . implode(',', $missing));
                xui_notify_missing_inbounds($panel, $missing);
            }
            if (!$kept) {
                return array('status' => 200, 'body' => json_encode(array('success' => false, 'msg' => 'none of the configured inbounds exist on the panel')));
            }
            $inboundIds = $kept;
        }
    }
    $config = array(
        "inboundIds" => $inboundIds,
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