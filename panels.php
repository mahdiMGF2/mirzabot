<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/panels/bootstrap.php';

class ManagePanel
{
    public $pdo, $domainhosts, $name_panel;
    function splitWireguardLinks($panel, $result)
    {
        if (!is_array($result) || ($panel['type'] != "x-ui_single" && !($panel['type'] == "marzban" && $panel['version_panel'] == "1"))) {
            return $result;
        }
        $isWireguard = fn($link) => stripos(trim((string) $link), 'wireguard://') === 0;
        $result['wireguard'] = [];
        foreach (['configs', 'links'] as $key) {
            if (is_array($result[$key] ?? null)) {
                $result['wireguard'] = array_values(array_unique(array_merge($result['wireguard'], array_filter($result[$key], $isWireguard))));
                $result[$key] = array_values(array_filter($result[$key], fn($link) => !$isWireguard($link)));
            }
        }
        return $result;
    }
    function createUser($name_panel, $code_product, $usernameC, array $Data_Config)
    {
        $Output = [];
        global $pdo, $domainhosts, $textbotlang;
        if (strlen($usernameC) < 3) {
            return array(
                "status" => "Unsuccessful",
                "msg" => "Username must be at least 3 characters long."
            );
        }
        // input time expire timestep use $Data_Config
        // input data_limit byte use $Data_Config
        // input username use $Data_Config
        // input from_id use $Data_Config
        // input type config use $Data_Config
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel == false) {
            $Output['status'] = 'Unsuccessful';
            $Output['msg'] = 'Panel Not Found';
            return $Output;
        }
        if ($Get_Data_Panel['subvip'] == "onsubvip") {
            $invoice = select("invoice", "*", "username", $usernameC, "select");
        } else {
            $invoice = false;
        }
        if (!in_array($code_product, ["usertest", $textbotlang['users']['customSellVolume']['btnVolume'], "customvolume"])) {

            $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :name_panel OR Location = '/all')  AND code_product = :code_product");
            $stmt->bindParam(':name_panel', $name_panel);
            $stmt->bindParam(':code_product', $code_product);
            $stmt->execute();
            $Get_Data_Product = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            if ($code_product == "usertest") {
                $Get_Data_Product['name_product'] = "usertest";
            } else {
                $Get_Data_Product['name_product'] = false;
            }
            $Get_Data_Product['data_limit_reset'] = "no_reset";
        }
        $expire = $Data_Config['expire'];
        $data_limit = $Data_Config['data_limit'];
        $note = "{$Data_Config['from_id']} | {$Data_Config['username']} | {$Data_Config['type']}";
        $driver = PanelRegistry::driver($Get_Data_Panel['type'], $this);
        if ($driver !== null && ($result = $driver->create(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $this->splitWireguardLinks($Get_Data_Panel, $result);
        }
        $Output['status'] = 'Unsuccessful';
        $Output['msg'] = 'Panel Not Found';
        return $Output;
    }
    function DataUser($name_panel, $username)
    {
        $Output = array();
        global $pdo, $domainhosts;
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if (!$Get_Data_Panel || !is_array($Get_Data_Panel)) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            );
        }
        if (isset($Get_Data_Panel['subvip']) && $Get_Data_Panel['subvip'] == "onsubvip") {
            $invoice = select("invoice", "*", "username", $username, "select");
        } else {
            $invoice = false;
        }
        $driver = PanelRegistry::driver($Get_Data_Panel['type'], $this);
        if ($driver !== null && ($result = $driver->read(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $this->splitWireguardLinks($Get_Data_Panel, $result);
        }
        $Output = array(
            'status' => 'Unsuccessful',
            'msg' => 'Panel Not Found'
        );
        return $Output;
    }
    function Revoke_sub($name_panel, $username)
    {
        $Output = array();
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        $driver = PanelRegistry::driver($Get_Data_Panel['type'], $this);
        if ($driver !== null && ($result = $driver->revokeSub(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $this->splitWireguardLinks($Get_Data_Panel, $result);
        }
        $Output = array(
            'status' => 'Unsuccessful',
            'msg' => 'Panel Not Found'
        );
        return $Output;
    }
    function RemoveUser($name_panel, $username)
    {
        $Output = array();
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        $driver = PanelRegistry::driver($Get_Data_Panel['type'], $this);
        if ($driver !== null && ($result = $driver->remove(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $result;
        }
        $Output = array(
            'status' => 'Unsuccessful',
            'msg' => 'Panel Not Found'
        );
        return $Output;
    }
    function Modifyuser($username, $name_panel, $config = array())
    {
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        $driver = PanelRegistry::driver($Get_Data_Panel['type'], $this);
        if ($driver !== null && ($result = $driver->modify(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $result;
        }
    }
    function Change_status($username, $name_panel)
    {
        $DataUserOut = $this->DataUser($name_panel, $username);
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($DataUserOut['status'] == "Unsuccessful") {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $DataUserOut['msg']
            );
        }
        if (!in_array($DataUserOut['status'], ["active", "disabled"])) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => "status invalid"
            );
        }
        $driver = PanelRegistry::driver($Get_Data_Panel['type'], $this);
        if ($driver !== null && ($result = $driver->changeStatus(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $result;
        }

        return $Output;
    }
    function ResetUserDataUsage($username, $name_panel)
    {
        $panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($panel == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $driver = PanelRegistry::driver($panel['type'], $this);
        if ($driver !== null && ($result = $driver->resetUsage(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $result;
        }
    }
    function extend($Method_extend, $new_limit, $time_day, $username, $code_product, $name_panel)
    {
        $panel = select("marzban_panel", "*", "code_panel", $name_panel, "select");
        $product = select("product", "*", "code_product", $code_product, "select");
        $invoice = select("invoice", "*", "username", $username, "select");
        if ($code_product == "custom_volume")
            $product = true;
        if ($panel == false || $product == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $data_user = $this->DataUser($panel['name_panel'], $username);
        if ($data_user['status'] == "Unsuccessful") {
            return array(
                'status' => false,
                'msg' => $data_user['msg']
            );
        }
        $notifctions = json_encode(array(
            'volume' => false,
            'time' => false,
        ));
        update("invoice", "notifctions", $notifctions, 'id_invoice', $invoice['id_invoice']);
        $data_limit_old = $data_user['data_limit'];
        $time_old = $data_user['expire'];
        $time_old = time() - $time_old > 0 ? time() : $time_old;
        $data_limit_new = $new_limit == 0 ? 0 : $new_limit * pow(1024, 3);
        $data_limit_new_add = $new_limit == 0 ? 0 : $data_limit_old + ($new_limit * pow(1024, 3));
        $time_new = $time_day == 0 ? 0 : time() + $time_day * 86400;
        $time_old = $time_old == 0 ? time() : $time_old;
        $time_new_add = $time_day == 0 ? 0 : $time_old + ($time_day * 86400);
        //inboud and proxies 
        $inbound_id = isset($panel['inboundid']) ? $panel['inboundid'] : 1;
        $inbounds = is_string($panel['inbounds']) ? json_decode($panel['inbounds']) : "{}";
        $inbounds = $product['inbounds'] != null ? json_decode($product['inbounds']) : $inbounds;
        if ($panel['type'] != "WGDashboard") {
            update("invoice", 'user_info', null, "username", $username);
        }
        update("invoice", 'uuid', null, "username", $username);
        update("invoice", 'Status', "active", "username", $username);
        $Method_extend = extendMethodKey($Method_extend);
        if ($Method_extend == "resetVolumeTime") {
            $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
            if ($reset['status'] == false) {
                return array(
                    'status' => false,
                    'msg' => 'error reset : ' . $reset['msg']
                );
            }
        } elseif ($Method_extend == "addTimeVolumeNextMonth") {
            $data_limit_new = $data_limit_new_add;
            $time_new = $time_new_add;
        } elseif ($Method_extend == "resetTimeAddVolume") {
            $data_limit_new = $data_limit_new_add;
        } elseif ($Method_extend == "resetVolumeAddTime") {
            $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
            if ($reset['status'] == false) {
                return array(
                    'status' => false,
                    'msg' => 'error reset : ' . $reset['msg']
                );
            }
            $time_new = $time_new_add;
        } elseif ($Method_extend == "addTimeConvertVolume") {
            $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
            if ($reset['status'] == false) {
                return array(
                    'status' => false,
                    'msg' => 'error reset : ' . $reset['msg']
                );
            }
            $time_new = $time_new_add;
            $data_limit_last = $data_user['data_limit'] - $data_user['used_traffic'];
            $data_limit_last = $data_limit_last < 0 ? 0 : $data_limit_last;
            // an unlimited plan (0) must stay unlimited, so the leftover volume is only carried over for limited plans
            if ($data_limit_new != 0) {
                $data_limit_new = $data_limit_new + $data_limit_last;
            }
        }
        $driver = PanelRegistry::driver($panel['type'], $this);
        if ($driver !== null && ($result = $driver->extendService(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $result;
        }
        $extend = $this->Modifyuser($username, $panel['name_panel'], $data);
        if ($extend['status'] == false) {
            return array(
                'status' => false,
                'msg' => $extend['msg']
            );
        }
        return $extend;
    }
    function extra_volume($username_account, $code_panel, $limit_volume_new)
    {
        $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
        $invoice = select("invoice", "*", "username", $username_account, "select");
        if ($panel == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $notif_value = json_decode($invoice['notifctions'], true);
        $notifctions = json_encode(array(
            'volume' => false,
            'time' => $notif_value['time'],
        ));
        update("invoice", "notifctions", $notifctions, 'id_invoice', $invoice['id_invoice']);
        $user_info = $this->DataUser($panel['name_panel'], $username_account);
        if ($user_info['status'] == "Unsuccessful") {
            return array(
                'status' => false,
                'msg' => $user_info['msg']
            );
        }
        $old_limit_volume = $user_info['data_limit'];
        $new_limit = $limit_volume_new == 0 ? 0 : ($limit_volume_new * pow(1024, 3)) + $old_limit_volume;
        $inbound_id = isset($panel['inboundid']) ? $panel['inboundid'] : 1;
        $inbounds = is_string($panel['inbounds']) ? json_decode($panel['inbounds']) : "{}";
        if ($panel['type'] != "WGDashboard") {
            update("invoice", 'user_info', null, "username", $username_account);
        }
        update("invoice", 'uuid', null, "username", $username_account);
        update("invoice", 'Status', "active", "username", $username_account);
        $driver = PanelRegistry::driver($panel['type'], $this);
        if ($driver !== null && ($result = $driver->extraVolume(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $result;
        }
        $extra_volume = $this->Modifyuser($username_account, $panel['name_panel'], $data);
        if ($extra_volume['status'] == false) {
            return array(
                'status' => false,
                'msg' => $extra_volume['msg']
            );
        }
        return $extra_volume;
    }
    function extra_time($username_account, $code_panel, $limit_time_new)
    {
        $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
        $invoice = select("invoice", "*", "username", $username_account, "select");
        if ($panel == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $notif_value = json_decode($invoice['notifctions'], true);
        $notifctions = json_encode(array(
            'volume' => $notif_value['volume'],
            'time' => false,
        ));
        update("invoice", "notifctions", $notifctions, 'id_invoice', $invoice['id_invoice']);
        $user_info = $this->DataUser($panel['name_panel'], $username_account);
        if ($user_info['status'] == "Unsuccessful") {
            return array(
                'status' => false,
                'msg' => $user_info['msg']
            );
        }
        $old_limit_time = $user_info['expire'];
        $old_limit_time = time() - $old_limit_time > 0 ? time() : $old_limit_time;
        $new_limit = $limit_time_new == 0 ? 0 : $limit_time_new * 86400 + $old_limit_time;
        $inbound_id = isset($panel['inboundid']) ? $panel['inboundid'] : 1;
        $inbounds = is_string($panel['inbounds']) ? json_decode($panel['inbounds']) : "{}";
        if ($panel['type'] != "WGDashboard") {
            update("invoice", 'user_info', null, "username", $username_account);
        }
        update("invoice", 'uuid', null, "username", $username_account);
        update("invoice", 'Status', "active", "username", $username_account);
        $driver = PanelRegistry::driver($panel['type'], $this);
        if ($driver !== null && ($result = $driver->extraTime(get_defined_vars())) !== BasePanelDriver::UNSUPPORTED) {
            return $result;
        }
        $extra_time = $this->Modifyuser($username_account, $panel['name_panel'], $data);
        if ($extra_time['status'] == false) {
            return array(
                'status' => false,
                'msg' => $extra_time['msg']
            );
        }
        return $extra_time;
    }
}
