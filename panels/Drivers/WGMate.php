<?php

class WGMatePanelDriver extends BasePanelDriver
{
    public function create(array $vars)
    {
        $usernameC = $vars['usernameC'];
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $data_limit = $vars['data_limit'];
        $expire = $vars['expire'];
        $Get_Data_Product = $vars['Get_Data_Product'];
        $note = $vars['note'];
        $Output = $vars['Output'];
        $invoice = $vars['invoice'];
        $domainhosts = $vars['domainhosts'];
        $order_get = select("invoice", "*", "username", $usernameC, "select");
        $ConnectToPanel = adduser_wgmate($Get_Data_Panel['name_panel'], $data_limit, $usernameC, $expire, $Get_Data_Product['name_product'], $note, $order_get['limit_user'] ?? null);
        if (!empty($ConnectToPanel['error'])) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => $ConnectToPanel['error']
            );
        }
        $data_Output = json_decode($ConnectToPanel['body'], true);
        if ($ConnectToPanel['status'] >= 400 || !isset($data_Output['data']['id'])) {
            $Output['status'] = 'Unsuccessful';
            $Output['msg'] = $data_Output['error'] ?? ('error code : ' . $ConnectToPanel['status']);
        } else {
            $data_Output = $data_Output['data'];
            $subscription = subscription_wgmate($Get_Data_Panel['name_panel'], $data_Output['id']);
            $Output['status'] = 'successful';
            $Output['username'] = $data_Output['username'];
            $Output['subscription_url'] = $invoice != false ? "https://$domainhosts/sub/" . $invoice['id_invoice'] : rtrim($Get_Data_Panel['url_panel'], '/') . '/sub/' . $data_Output['subToken'];
            $Output['configs'] = array_merge(links_wgmate($Get_Data_Panel['name_panel'], $data_Output['id']), array_values(array_filter(array_column($subscription, 'shareUri'), fn($uri) => stripos($uri, 'amneziawg://') === 0)));
            $Output['wireguard'] = array_values(array_filter(array_column($subscription, 'shareUri'), fn($uri) => stripos($uri, 'wireguard://') === 0));
        }
        return $Output;
    }

    public function read(array $vars)
    {
        $username = $vars['username'];
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $Output = $vars['Output'];
        $invoice = $vars['invoice'];
        $domainhosts = $vars['domainhosts'];
        $UsernameData = getuser_wgmate($username, $Get_Data_Panel['name_panel']);
        if (!empty($UsernameData['error'])) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => $UsernameData['error']
            );
        } elseif ($UsernameData['status'] >= 400 || $UsernameData['user'] === null) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => json_decode($UsernameData['body'], true)['error'] ?? ('error code : ' . $UsernameData['status'])
            );
        } else {
            $UsernameData = $UsernameData['user'];
            $subscription = subscription_wgmate($Get_Data_Panel['name_panel'], $UsernameData['id']);
            $Output = array(
                'status' => status_wgmate($UsernameData),
                'username' => $UsernameData['username'],
                'data_limit' => $UsernameData['quotaBytes'] ?? 0,
                'expire' => $UsernameData['expiresAt'] == "" ? 0 : strtotime($UsernameData['expiresAt']),
                'online_at' => $UsernameData['connection'] == "online" ? "online" : $UsernameData['lastHandshakeAt'],
                'used_traffic' => $UsernameData['usedBytes'],
                'links' => [],
                'subscription_url' => $invoice != false ? "https://$domainhosts/sub/" . $invoice['id_invoice'] : rtrim($Get_Data_Panel['url_panel'], '/') . '/sub/' . $UsernameData['subToken'],
                'sub_updated_at' => null,
                'sub_last_user_agent' => null,
                'uuid' => null,
                'data_limit_reset' => 'no_reset',
                'wireguard' => array_values(array_filter(array_column($subscription, 'shareUri'), fn($uri) => stripos($uri, 'wireguard://') === 0))
            );
        }
        return $Output;
    }

    public function revokeSub(array $vars)
    {
        $name_panel = $vars['name_panel'];
        $username = $vars['username'];
        $Output = $vars['Output'];
        $revoke_sub = action_wgmate($name_panel, $username, "POST", '/revoke');
        if (!empty($revoke_sub['error'])) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => $revoke_sub['error']
            );
        } elseif ($revoke_sub['status'] >= 400) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => json_decode($revoke_sub['body'], true)['error'] ?? ('error code : ' . $revoke_sub['status'])
            );
        } else {
            $Data_User = $this->DataUser($name_panel, $username);
            $subscription = subscription_wgmate($name_panel, json_decode($revoke_sub['body'], true)['data']['id'] ?? '');
            $Output = array(
                'status' => 'successful',
                'configs' => array_merge(links_wgmate($name_panel, json_decode($revoke_sub['body'], true)['data']['id'] ?? ''), array_values(array_filter(array_column($subscription, 'shareUri'), fn($uri) => stripos($uri, 'amneziawg://') === 0))),
                'subscription_url' => $Data_User['subscription_url'],
                'wireguard' => array_values(array_filter(array_column($subscription, 'shareUri'), fn($uri) => stripos($uri, 'wireguard://') === 0))
            );
        }
        return $Output;
    }

    public function remove(array $vars)
    {
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $username = $vars['username'];
        $Output = $vars['Output'];
        $UsernameData = action_wgmate($Get_Data_Panel['name_panel'], $username, "DELETE");
        if (!empty($UsernameData['error'])) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => $UsernameData['error']
            );
        } elseif ($UsernameData['status'] >= 400) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => json_decode($UsernameData['body'], true)['error'] ?? ('error code : ' . $UsernameData['status'])
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
        $name_panel = $vars['name_panel'];
        $username = $vars['username'];
        $config = $vars['config'];
        $modify = Modifyuser_wgmate($name_panel, $username, $config);
        if (!empty($modify['error'])) {
            return array(
                'status' => false,
                'msg' => $modify['error']
            );
        } elseif ($modify['status'] >= 400) {
            return array(
                'status' => false,
                'msg' => json_decode($modify['body'], true)['error'] ?? ('error code : ' . $modify['status'])
            );
        }
        return array(
            'status' => true,
            'data' => json_decode($modify['body'], true)
        );
    }

    public function changeStatus(array $vars)
    {
        $name_panel = $vars['name_panel'];
        $username = $vars['username'];
        $DataUserOut = $vars['DataUserOut'];
        action_wgmate($name_panel, $username, "POST", $DataUserOut['status'] == "active" ? '/disable' : '/enable');
        $Output = array(
            'status' => 'successful',
            'msg' => null
        );
        return $Output;
    }

    public function resetUsage(array $vars)
    {
        $panel = $vars['panel'];
        $username = $vars['username'];
        $reset = action_wgmate($panel['name_panel'], $username, "POST", '/reset');
        if (!empty($reset['error'])) {
            return array(
                'status' => false,
                'msg' => 'error  : ' . $reset['error']
            );
        } elseif ($reset['status'] >= 400) {
            return array(
                'status' => false,
                'msg' => json_decode($reset['body'], true)['error'] ?? ('error code : ' . $reset['status'])
            );
        }
        return array(
            'status' => true,
            'msg' => 'successful'
        );
    }

    public function extendService(array $vars)
    {
        $data_limit_new = $vars['data_limit_new'];
        $time_new = $vars['time_new'];
        $username = $vars['username'];
        $panel = $vars['panel'];
        $data = array(
            'quotaBytes' => $data_limit_new,
            'expiresAt' => $time_new == 0 ? "" : gmdate('Y-m-d\TH:i:s\Z', $time_new),
        );
        return $this->applyModify($username, $panel['name_panel'], $data);
    }

    public function extraVolume(array $vars)
    {
        $new_limit = $vars['new_limit'];
        $username_account = $vars['username_account'];
        $panel = $vars['panel'];
        $data = array(
            'quotaBytes' => $new_limit,
        );
        return $this->applyModify($username_account, $panel['name_panel'], $data);
    }

    public function extraTime(array $vars)
    {
        $new_limit = $vars['new_limit'];
        $username_account = $vars['username_account'];
        $panel = $vars['panel'];
        $data = array(
            'expiresAt' => $new_limit == 0 ? "" : gmdate('Y-m-d\TH:i:s\Z', $new_limit),
        );
        return $this->applyModify($username_account, $panel['name_panel'], $data);
    }
}
