<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

list($headers, $data, $action) = apiRequestContext();
$method = $_SERVER['REQUEST_METHOD'];
$setting = select("setting", "*");

$editableColumns = [
    'name' => 'name_panel',
    'url' => 'url_panel',
    'username_panel' => 'username_panel',
    'password_panel' => 'password_panel',
    'status' => 'status',
    'agent' => 'agent',
    'sublink' => 'sublink',
    'config' => 'config',
    'type' => 'type',
    'MethodUsername' => 'MethodUsername',
    'Methodextend' => 'Methodextend',
    'TestAccount' => 'TestAccount',
    'limit_panel' => 'limit_panel',
    'namecustom' => 'namecustom',
    'conecton' => 'conecton',
    'linksubx' => 'linksubx',
    'inboundid' => 'inboundid',
    'inboundstatus' => 'inboundstatus',
    'inbound_deactive' => 'inbound_deactive',
    'time_usertest' => 'time_usertest',
    'val_usertest' => 'val_usertest',
    'secret_code' => 'secret_code',
    'priceChangeloc' => 'priceChangeloc',
    'status_extend' => 'status_extend',
    'subvip' => 'subvip',
    'changeloc' => 'changeloc',
    'version_panel' => 'version_panel',
    'on_hold_test' => 'on_hold_test',
];

$jsonColumns = [
    'priceextravolume' => 'priceextravolume',
    'pricecustomvolume' => 'pricecustomvolume',
    'pricecustomtime' => 'pricecustomtime',
    'priceextratime' => 'priceextratime',
    'mainvolume' => 'mainvolume',
    'maxvolume' => 'maxvolume',
    'maintime' => 'maintime',
    'maxtime' => 'maxtime',
    'customvolume' => 'customvolume',
    'hide_user' => 'hide_user',
];

function panel_panels(array $data, string $method): void
{
    global $pdo;

    validateMethod('GET', $method);

    $paging = paginationParams($data);
    $limit = $paging['limit'];
    $page = $paging['page'];
    $offset = $paging['offset'];
    $q = $paging['q'];

    try {
        $search = "%$q%";
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM marzban_panel WHERE name_panel LIKE :name_panel");
        $stmt->execute([':name_panel' => $search]);
        $totalpanel = (int) $stmt->fetchColumn();
        $totalPages = (int) ceil($totalpanel / $limit);

        $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE name_panel LIKE CONCAT('%', :name_panel, '%') ORDER BY id LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':name_panel', $q, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $panels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($panels as &$panelRow) {
            unset($panelRow['password_panel'], $panelRow['datelogin'], $panelRow['secret_code']);
        }
        unset($panelRow);

        sendJsonResponse(true, "Successful", [
            'panels' => $panels,
            'pagination' => [
                'total_panel' => $totalpanel,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]
        ]);

    } catch (Exception $e) {
        error_log("Database error in panel: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function panel_panel(array $data, string $method): void
{
    global $jsonColumns;

    validateMethod('GET', $method);
    requireFields($data, ['id']);

    try {
        $panel = select("marzban_panel", "*", "id", $data['id'], "select");
        if (!$panel) {
            sendJsonResponse(false, "panel not found", ['panel' => []], 200);
        }
        unset($panel['password_panel'], $panel['datelogin'], $panel['secret_code']);
        foreach (array_keys($jsonColumns) as $column) {
            if (isset($panel[$column])) {
                $decoded = json_decode($panel[$column], true);
                $panel[$column] = $decoded === null ? $panel[$column] : $decoded;
            }
        }
        sendJsonResponse(true, "Successful", [
            'panel' => $panel,
            'category' => select("category", "*", null, null, "fetchAll"),
        ]);
    } catch (Exception $e) {
        error_log("Database error in panel: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function panel_panel_add(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['name', 'url', 'username_panel', 'password_panel']);

    if (select("marzban_panel", "*", "name_panel", $data['name'], "count") != 0) {
        sendJsonResponse(false, "panel name exits", [], 200);
    }

    try {
        $defaultAgentValue = json_encode(['f' => '0', 'n' => '0', 'n2' => '0']);
        $defaultPrice = json_encode(['f' => "4000", 'n' => "4000", 'n2' => "4000"]);
        $defaultMain = json_encode(['f' => "1", 'n' => "1", 'n2' => "1"]);
        $defaultMaxVolume = json_encode(['f' => "1000", 'n' => "1000", 'n2' => "1000"]);
        $defaultMaxTime = json_encode(['f' => "365", 'n' => "365", 'n2' => "365"]);

        $panelData = [
            'code_panel' => bin2hex(random_bytes(3)),
            'name_panel' => $data['name'],
            'url_panel' => normalizePanelUrl(htmlspecialchars_decode((string) $data['url'], ENT_QUOTES)),
            'username_panel' => htmlspecialchars_decode((string) $data['username_panel'], ENT_QUOTES),
            'password_panel' => htmlspecialchars_decode((string) $data['password_panel'], ENT_QUOTES),
            'status' => empty($data['status']) ? "active" : $data['status'],
            'agent' => empty($data['agent']) ? "all" : $data['agent'],
            'sublink' => empty($data['sublink']) ? "onsublink" : $data['sublink'],
            'config' => empty($data['config']) ? "offconfig" : $data['config'],
            'type' => empty($data['type']) ? "marzban" : $data['type'],
            'MethodUsername' => empty($data['MethodUsername']) ? null : usernameMethodKey($data['MethodUsername']),
            'Methodextend' => empty($data['Methodextend']) ? null : extendMethodKey($data['Methodextend']),
            'TestAccount' => empty($data['TestAccount']) ? "ONTestAccount" : $data['TestAccount'],
            'limit_panel' => empty($data['limit_panel']) ? "unlimted" : $data['limit_panel'],
            'namecustom' => empty($data['namecustom']) ? "vpn" : $data['namecustom'],
            'conecton' => "offconecton",
            'linksubx' => empty($data['linksubx']) ? null : htmlspecialchars_decode((string) $data['linksubx'], ENT_QUOTES),
            'inboundid' => empty($data['inboundid']) ? "1" : $data['inboundid'],
            'inboundstatus' => "offinbounddisable",
            'inbound_deactive' => "0",
            'time_usertest' => empty($data['time_usertest']) ? "1" : $data['time_usertest'],
            'val_usertest' => empty($data['val_usertest']) ? "100" : $data['val_usertest'],
            'priceChangeloc' => "0",
            'status_extend' => "on_extend",
            'subvip' => "offsubvip",
            'changeloc' => "offchangeloc",
            'version_panel' => empty($data['version_panel']) ? "0" : $data['version_panel'],
            'on_hold_test' => "1",
            'priceextravolume' => $defaultPrice,
            'pricecustomvolume' => $defaultPrice,
            'pricecustomtime' => $defaultPrice,
            'priceextratime' => $defaultPrice,
            'mainvolume' => $defaultMain,
            'maxvolume' => $defaultMaxVolume,
            'maintime' => $defaultMain,
            'maxtime' => $defaultMaxTime,
            'customvolume' => $defaultAgentValue,
        ];

        $columns = implode(',', array_keys($panelData));
        $placeholders = ':' . implode(', :', array_keys($panelData));

        $stmt = $pdo->prepare("INSERT INTO marzban_panel ({$columns}) VALUES ({$placeholders})");
        foreach ($panelData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->execute();

        sendJsonResponse(true, "Successful");

    } catch (Exception $e) {
        error_log("Error in panel_add: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while adding panel");
    }
}

function panel_panel_edit(array $data, string $method): void
{
    global $pdo, $editableColumns, $jsonColumns;

    validateMethod('POST', $method);
    requireFields($data, ['id']);

    $panel = select("marzban_panel", "*", "id", $data['id'], "select");
    if (!$panel) {
        sendJsonResponse(false, "panel not found", [], 200);
    }

    // Renaming a panel has to follow the invoices that point at it by name.
    if (isset($data['name']) && $panel['name_panel'] != $data['name']) {
        if (select("marzban_panel", "*", "name_panel", $data['name'], "count") != 0) {
            sendJsonResponse(false, "panel name exits", [], 200);
        }
        update("invoice", "Service_location", $data['name'], "Service_location", $panel['name_panel']);
    }

    // The panel/panels responses redact these, so a client that round-trips a
    // record sends them back empty; ignore blanks instead of wiping the
    // credentials the bot needs to reach the panel.
    $redactedColumns = ['password_panel', 'secret_code'];

    try {
        $panelData = [];
        foreach ($editableColumns as $field => $column) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            if (in_array($column, $redactedColumns, true) && trim((string) $data[$field]) === '') {
                continue;
            }
            $panelData[$column] = $data[$field];
        }
        foreach ($jsonColumns as $field => $column) {
            if (array_key_exists($field, $data)) {
                $panelData[$column] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
            }
        }

        if ($panelData === []) {
            sendJsonResponse(false, "nothing to update", [], 200);
        }

        if (isset($panelData['Methodextend'])) {
            $panelData['Methodextend'] = extendMethodKey($panelData['Methodextend']);
        }

        if (isset($panelData['MethodUsername'])) {
            $panelData['MethodUsername'] = usernameMethodKey($panelData['MethodUsername']);
        }

        foreach (['url_panel', 'username_panel', 'password_panel', 'linksubx'] as $rawColumn) {
            if (isset($panelData[$rawColumn]) && is_string($panelData[$rawColumn])) {
                $panelData[$rawColumn] = htmlspecialchars_decode($panelData[$rawColumn], ENT_QUOTES);
            }
        }

        if (isset($panelData['url_panel']) && is_string($panelData['url_panel'])) {
            $panelData['url_panel'] = normalizePanelUrl($panelData['url_panel']);
        }

        // Credential changes invalidate the cached panel session.
        if (isset($panelData['url_panel']) || isset($panelData['username_panel']) || isset($panelData['password_panel'])) {
            $panelData['datelogin'] = null;
        }

        $setParts = [];
        foreach ($panelData as $column => $value) {
            $setParts[] = "{$column} = :{$column}";
        }
        $stmt = $pdo->prepare("UPDATE marzban_panel SET " . implode(", ", $setParts) . " WHERE id = :id");
        foreach ($panelData as $column => $value) {
            $stmt->bindValue(":{$column}", $value);
        }
        $stmt->bindValue(":id", $data['id'], PDO::PARAM_INT);
        $stmt->execute();

        sendJsonResponse(true, "panel updated successfully", [], 200);

    } catch (Exception $e) {
        error_log("Error in panel_edit: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while editing panel");
    }
}

function panel_panel_delete(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id']);

    $panel = select("marzban_panel", "*", "id", $data['id'], "select");
    if (!$panel) {
        sendJsonResponse(false, "panel not found", [], 200);
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM marzban_panel WHERE id = :id");
        $stmt->bindValue(":id", $data['id'], PDO::PARAM_INT);
        $stmt->execute();

        sendJsonResponse(true, "panel delete successfully", [], 200);

    } catch (Exception $e) {
        error_log("Error in panel delete : " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while delete panel");
    }
}

function panel_set_inbounds(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id', 'input']);

    $panel = select("marzban_panel", "*", "id", $data['id'], "select");
    if (!$panel) {
        sendJsonResponse(false, "panel not found", [], 200);
    }

    $proxy_output = null;
    if ($panel['type'] == "marzban") {
        $DataUserOut = getuser($data['input'], $panel['name_panel']);
        if (!empty($DataUserOut['error']))
            sendJsonResponse(false, $DataUserOut['error'], [], 200);
        if (!empty($DataUserOut['status']) && $DataUserOut['status'] != 200)
            sendJsonResponse(false, $DataUserOut['msg'], [], 200);
        $DataUserOut = json_decode($DataUserOut['body'] ?? '', true);
        if (!is_array($DataUserOut))
            sendJsonResponse(false, "User Not Found", [], 200);

        // Marzban >= 1.0 renamed "proxies" to "proxy_settings" and moved the
        // inbound list into "group_ids".
        $isNewMarzban = $panel['version_panel'] == "1";
        $proxyKey = $isNewMarzban ? 'proxy_settings' : 'proxies';
        if ((isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") or !isset($DataUserOut[$proxyKey]))
            sendJsonResponse(false, "User Not Found", [], 200);

        foreach ($DataUserOut[$proxyKey] as $key => $value) {
            if ($key == "shadowsocks" || $key == "trojan") {
                unset($DataUserOut[$proxyKey][$key]['password']);
            } elseif ($key == "wireguard") {
                unset($DataUserOut[$proxyKey][$key]['private_key']);
                unset($DataUserOut[$proxyKey][$key]['public_key']);
                unset($DataUserOut[$proxyKey][$key]['peer_ips']);
            } else {
                unset($DataUserOut[$proxyKey][$key]['id']);
            }
            if (count($DataUserOut[$proxyKey][$key]) == 0) {
                $DataUserOut[$proxyKey][$key] = new stdClass();
            }
        }
        $proxy_output = json_encode($DataUserOut[$proxyKey]);
        $datainbound = json_encode($isNewMarzban ? ($DataUserOut['group_ids'] ?? []) : ($DataUserOut['inbounds'] ?? []));
    } elseif ($panel['type'] == "marzneshin") {
        $userdata = json_decode(getuserm($data['input'], $panel['name_panel'])['body'] ?? '', true);
        if (!is_array($userdata) || (isset($userdata['detail']) and $userdata['detail'] == "User not found"))
            sendJsonResponse(false, "User Not Found", [], 200);
        $datainbound = json_encode($userdata['service_ids'] ?? []);
    } elseif ($panel['type'] == "x-ui_single") {
        $user_data = get_clinets($data['input'], $panel['name_panel']);
        if (!empty($user_data['error']))
            sendJsonResponse(false, $user_data['error'], [], 200);
        if (!empty($user_data['status']) && $user_data['status'] != 200)
            sendJsonResponse(false, $user_data['msg'], [], 200);
        $user_data = json_decode($user_data['body'] ?? '', true)['obj'] ?? null;
        if ($user_data == null)
            sendJsonResponse(false, "User Not Found", [], 200);
        $datainbound = $user_data['inboundId'];
    } elseif ($panel['type'] == "s_ui") {
        $user_data = GetClientsS_UI($data['input'], $panel['name_panel']);
        if (!is_array($user_data) || count($user_data) == 0 || !isset($user_data['inbounds'])) {
            sendJsonResponse(false, "User Not Found", [], 200);
        }
        $servies = [];
        foreach ($user_data['inbounds'] as $service) {
            $servies[] = $service;
        }
        $datainbound = json_encode($servies);
    } elseif ($panel['type'] == "ibsng" || $panel['type'] == "mikrotik") {
        $datainbound = $data['input'];
    } else {
        sendJsonResponse(false, "panel_not_support_options", [], 200);
    }

    if ($proxy_output !== null) {
        $stmt = $pdo->prepare("UPDATE marzban_panel SET proxies = :proxies WHERE id = :id_panel");
        $stmt->execute([':proxies' => $proxy_output, ':id_panel' => $data['id']]);
    }
    $stmt = $pdo->prepare("UPDATE marzban_panel SET inbounds = :inbounds WHERE id = :id_panel");
    $stmt->execute([':inbounds' => $datainbound, ':id_panel' => $data['id']]);
    sendJsonResponse(true, "successfully", [], 200);
}

function panel_remove_inbounds(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id']);

    $panel = select("marzban_panel", "*", "id", $data['id'], "select");
    if (!$panel) {
        sendJsonResponse(false, "panel not found", [], 200);
    }
    $stmt = $pdo->prepare("UPDATE marzban_panel SET inbounds = NULL, proxies = NULL WHERE id = :id_panel");
    $stmt->execute([':id_panel' => $data['id']]);
    sendJsonResponse(true, "successfully", [], 200);
}

match ($action) {
    'panels' => panel_panels($data, $method),
    'panel' => panel_panel($data, $method),
    'panel_add' => panel_panel_add($data, $method),
    'panel_edit' => panel_panel_edit($data, $method),
    'panel_delete' => panel_panel_delete($data, $method),
    'set_inbounds' => panel_set_inbounds($data, $method),
    'remove_inbounds' => panel_remove_inbounds($data, $method),
    default => sendJsonResponse(false, "Action Invalid"),
};

