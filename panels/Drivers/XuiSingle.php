<?php

class XuiSinglePanelDriver extends BasePanelDriver
{
    public function create(array $vars)
    {
        $Get_Data_Product = $vars['Get_Data_Product'];
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $usernameC = $vars['usernameC'];
        $expire = $vars['expire'];
        $data_limit = $vars['data_limit'];
        $note = $vars['note'];
        $Output = $vars['Output'];
        $invoice = $vars['invoice'];
        $domainhosts = $vars['domainhosts'];
        $subId = bin2hex(random_bytes(8));
        if (isset($Get_Data_Product['inbounds']) and $Get_Data_Product['inbounds'] != null) {
            $inbounds = $Get_Data_Product['inbounds'];
        } else {
            $inbounds = $Get_Data_Panel['inbounds'];
        }
        $data_Output = addClient($Get_Data_Panel, $usernameC, $expire, $subId, $data_limit, $inbounds, $Get_Data_Product['name_product'], $note);
        if (!empty($data_Output['error'])) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $data_Output['error']
            );
        } elseif (!empty($data_Output['status']) && $data_Output['status'] != 200) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $data_Output['status']
            );
        } else {
            $data_Output = json_decode($data_Output['body'], true);
            if (!$data_Output['success']) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = $data_Output['msg'];
            } else {
                $links_user = outputlink($Get_Data_Panel['linksubx'] . "/{$subId}");
                if (isBase64($links_user)) {
                    $links_user = base64_decode($links_user);
                }
                $links_user = explode("\n", trim($links_user));
                $Output['status'] = 'successful';
                $Output['username'] = $usernameC;
                $Output['subscription_url'] = $Get_Data_Panel['linksubx'] . "/{$subId}";
                $Output['configs'] = $links_user;
                if ($invoice != false) {
                    $Output['subscription_url'] = "https://$domainhosts/sub/" . $invoice['id_invoice'];
                }
            }
        }
        return $Output;
    }

    public function read(array $vars)
    {
        $username = $vars['username'];
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $invoice = $vars['invoice'];
        $domainhosts = $vars['domainhosts'];
        $Output = $vars['Output'];
        $user_data = get_clinets($username, $Get_Data_Panel);
        if (!empty($user_data['error'])) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $user_data['error']
            );
        } elseif (!empty($user_data['status']) && $user_data['status'] != 200) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => json_encode($user_data)
            );
        }
        $user_data = json_decode($user_data['body'], true);

        if (!is_array($user_data)) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => 'object invalid'
            );
        }
        if (empty($user_data['obj'])) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => "User not found"
            );
        }
        $user_data = $user_data['obj'];
        $expire = $user_data['client']['expiryTime'] / 1000;

        $used_data_3xui = used_data_3xui($Get_Data_Panel, $username);
        if (!empty($used_data_3xui['error'])) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $used_data_3xui['error']
            );
        } elseif (!empty($used_data_3xui['status']) && $used_data_3xui['status'] != 200) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $used_data_3xui['status']
            );
        }
        $used_data_3xui = json_decode($used_data_3xui['body'], true);
        if (!$used_data_3xui['success']) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $used_data_3xui['msg']
            );
        }
        if ($user_data['client']['enable']) {
            $user_data['client']['enable'] = "active";
        } else {
            $user_data['client']['enable'] = "disabled";
        }
        if ((intval($user_data['client']['totalGB'])) != 0) {
            if ((intval($user_data['client']['totalGB']) - ($used_data_3xui['obj']['up'] + $used_data_3xui['obj']['down'])) <= 0)
                $user_data['client']['enable'] = "limited";
        }
        if (intval($user_data['client']['expiryTime']) != 0) {
            if ($expire - time() <= 0)
                $user_data['client']['enable'] = "expired";
        }
        if ($user_data['client']['expiryTime'] < -10000) {
            $user_data['client']['enable'] = "on_hold";
            $expire = 0;
        }
        $linksub = $Get_Data_Panel['linksubx'] . "/{$user_data['client']['subId']}";
        $used_data_3xui['obj']['lastOnline'] = $used_data_3xui['obj']['lastOnline'] == 0 ? "offline" : date('Y-m-d H:i:s', $used_data_3xui['obj']['lastOnline'] / 1000);
        $links_user = outputlink($linksub);
        if (isBase64($links_user))
            $links_user = base64_decode($links_user);
        $links_user = explode("\n", trim($links_user));
        if ($invoice != false)
            $linksub = "https://$domainhosts/sub/" . $invoice['id_invoice'];
        $Output = array(
            'status' => $user_data['client']['enable'],
            'username' => $user_data['client']['email'],
            'data_limit' => $user_data['client']['totalGB'],
            'expire' => $expire,
            'expiry_time' => $user_data['client']['expiryTime'],
            'online_at' => $used_data_3xui['obj']['lastOnline'],
            'used_traffic' => $used_data_3xui['obj']['up'] + $used_data_3xui['obj']['down'],
            'links' => $links_user,
            'subscription_url' => $linksub,
            'sub_updated_at' => null,
            'sub_last_user_agent' => null,
        );

        return $Output;
    }

    public function revokeSub(array $vars)
    {
        $username = $vars['username'];
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $Output = $vars['Output'];
        $subId = bin2hex(random_bytes(8));
        $config = array(
            "email" => $username,
            "id" => generateUUID(),
            "enable" => true,
            "subId" => $subId,
        );
        $updateinbound = $this->Modifyuser($username, $Get_Data_Panel['name_panel'], $config);
        if (!$updateinbound['status']) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => 'Unsuccessful'
            );
        } else {
            $links_user = outputlink($Get_Data_Panel['linksubx'] . "/{$subId}");
            if (isBase64($links_user))
                $links_user = base64_decode($links_user);
            $Output = array(
                'status' => 'successful',
                'configs' => explode("\n", trim($links_user)),
                'subscription_url' => $Get_Data_Panel['linksubx'] . "/{$subId}",
            );
        }
        return $Output;
    }

    public function remove(array $vars)
    {
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $username = $vars['username'];
        $Output = $vars['Output'];
        $UsernameData = removeClient($Get_Data_Panel, $username);
        if (!empty($UsernameData['status']) && $UsernameData['status'] != 200) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $UsernameData['status']
            );
        } elseif (!empty($UsernameData['error'])) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $UsernameData['error']
            );
        }
        $UsernameData = json_decode($UsernameData['body'], true);
        if (!$UsernameData['success']) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => $UsernameData['msg']
            );
        } else {
            $Output = array(
                'status' => 'successful',
                'username' => $username,
            );
        }
        return $Output;
    }

    public function modify(array $vars)
    {
        $username = $vars['username'];
        $config = $vars['config'];
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $current = get_clinets($username, $Get_Data_Panel);
        $client = json_decode($current['body'] ?? '', true)['obj']['client'] ?? null;
        if (!is_array($client)) {
            return array(
                'status' => false,
                'msg' => $current['error'] ?? 'User not found'
            );
        }
        $data = array_intersect_key($client, array_flip(['limitIp', 'limitHwid', 'flow', 'comment', 'tgId', 'group', 'reset']));
        $data['email'] = $username;
        $data['totalGB'] = $config['totalGB'] ?? $client['totalGB'];
        $data['expiryTime'] = $config['expiryTime'] ?? $client['expiryTime'];
        $data['enable'] = $config['enable'] ?? true;
        if (!empty($config['id']))
            $data['id'] = $config['id'];
        if (!empty($config['subId']))
            $data['subId'] = $config['subId'];

        $modify = updateClient($Get_Data_Panel, $username, $data);
        attach_service($Get_Data_Panel, $username, json_encode(['inboundIds' => json_decode($Get_Data_Panel['inbounds'], true)]));
        if (!empty($modify['error'])) {
            return array(
                'status' => false,
                'msg' => $modify['error']
            );
        } elseif (!empty($modify['status']) && $modify['status'] != 200) {
            return array(
                'status' => false,
                'msg' => 'error code : ' . $modify['status']
            );
        }
        $modify = json_decode($modify['body'], true);
        if (!$modify['success']) {
            return array(
                'status' => false,
                'msg' => 'error :' . $modify['msg']
            );
        }
        return array(
            'status' => true,
            'data' => $modify
        );
    }

    public function changeStatus(array $vars)
    {
        $DataUserOut = $vars['DataUserOut'];
        $username = $vars['username'];
        $name_panel = $vars['name_panel'];
        if ($DataUserOut['status'] == "active") {
            $status = false;
        } else {
            $status = true;
        }
        $configs = array(
            "enable" => $status,
        );
        $this->Modifyuser($username, $name_panel, $configs);
        $Output = array(
            'status' => 'successful',
            'msg' => null
        );
        return $Output;
    }

    public function resetUsage(array $vars)
    {
        $username = $vars['username'];
        $panel = $vars['panel'];
        $reset = ResetUserDataUsagex_uisin($username, $panel);
        if (!empty($reset['status']) && $reset['status'] != 200) {
            return array(
                'status' => false,
                'msg' => 'error code : ' . $reset['status']
            );
        } elseif (!empty($reset['error'])) {
            return array(
                'status' => false,
                'msg' => 'error  : ' . $reset['error']
            );
        }
        $reset = json_decode($reset['body'], true);
        if (!$reset['success']) {
            return array(
                'status' => false,
                'msg' => 'error :' . $reset['msg']
            );
        }
        return array(
            'status' => true,
            'data' => $reset
        );
    }

    public function extendService(array $vars)
    {
        $data_limit_new = $vars['data_limit_new'];
        $time_new = $vars['time_new'];
        $username = $vars['username'];
        $panel = $vars['panel'];
        $data = array(
            "totalGB" => $data_limit_new,
            "expiryTime" => $time_new * 1000,
            "enable" => true,
        );
        return $this->applyModify($username, $panel['name_panel'], $data);
    }

    public function extraVolume(array $vars)
    {
        $new_limit = $vars['new_limit'];
        $username_account = $vars['username_account'];
        $panel = $vars['panel'];
        $data = array(
            "totalGB" => $new_limit,
        );
        return $this->applyModify($username_account, $panel['name_panel'], $data);
    }

    public function extraTime(array $vars)
    {
        $new_limit = $vars['new_limit'];
        $username_account = $vars['username_account'];
        $panel = $vars['panel'];
        $new_limit = $new_limit * 1000;
        $data = array(
            "expiryTime" => $new_limit,
        );
        return $this->applyModify($username_account, $panel['name_panel'], $data);
    }
}
