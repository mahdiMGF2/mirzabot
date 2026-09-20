<?php namespace radiusApi\Modules;


class IBSng
{
    static public $_instance;

    protected $hostname, $username, $password, $port, $timeout;
    protected $isConnected = false;
    protected $loginData = [];
    protected $autoConnect = false;
    protected $cookiePathName = null;
    protected $handler = null;
    protected $agent = 'phpIBSng web Api';

    /**
     * @param boolean $autoConnect
     */
    public function setAutoConnect($autoConnect)
    {
        $this->autoConnect = $autoConnect;
    }


    public function __construct(Array $loginArray)
    {
        /*
         * Curl library existence
         */
        if (!extension_loaded('curl')) {
            throw new \Exception ("You need to load/activate the curl extension.");
        }

        /*
         * Hide LibXML parse errors
         */
        libxml_use_internal_errors(true);


        self::$_instance = $this;

        $this->loginData = $loginArray;
        if (!$this->loginData['username'] ||
            !$this->loginData['password'] ||
            !$this->loginData['hostname']
        ) {

            throw new \Exception('IBSng needs correct login information');
        }

        $this->hostname = $loginArray['hostname'];
        $this->username = $loginArray['username'];
        $this->password = $loginArray['password'];
        $this->port = $loginArray['port'];
        $this->timeout = $loginArray['timeout'];

        $this->cookiePathName = sys_get_temp_dir() . '/.ibsng_' . md5((string) $this->hostname . '|' . (string) $this->username);

        if ($this->autoConnect) {
            $this->connect();
        }

    }

    protected function hostNameHealth($hostname = false, $port = false)
    {
        if ($hostname == false) {
            $hostname = $this->hostname;
        }
        if ($port == false) {
            $port = $this->port;
        }
        $fp = @fsockopen($hostname, $port);
        return $fp;
    }

    protected function getCookie()
    {
        return $this->cookiePathName;
    }


    public function connect()
    {
        if ($this->isConnected()) {
            return true;
        }

        /*
        * Login
        */
        try {
            $this->login();
        } catch (\Exception $ex) {
            throw new \Exception ($ex->getMessage());
        }

        /*
         * set connection as valid
         */
        return $this->isConnected = true;
    }

    public function disconnect()
    {
        if ($this->handler) {
            @unlink($this->getCookie());
            @curl_close($this->handler);
        }
    }

    public function isConnected()
    {
        return $this->isConnected;
    }

    public function addUser($username = null, $password = null, $group = null, $credit = null)
    {
        return $this->_addUser($group, $username, $password, $credit);
    }

    public function deleteUser($username)
    {
        return $this->_delUser($username);
    }

    public function listUser()
    {
        return $this->fetchAllUsers(1, 100);
    }

    public function isUserValid()
    {
        // TODO: Implement isUserValid() method.
    }

    public function isUserExpired()
    {
        // TODO: Implement isUserExpired() method.
    }

    public function getUser($username)
    {
        return $this->infoByUsername($username, true);
    }


    protected function login()
    {
        $url = $this->hostname . '/IBSng/admin/';
        $postData['username'] = $this->username;
        $postData['password'] = $this->password;
        $output = $this->request($url, $postData, true);
        if (strpos($output, 'admin_index') > 0) {
            return true;
        }
        throw new \Exception ("Can't login to IBSng. Wrong username or password");
    }

    protected function infoByUsername($username, $withPassword = false, $output = null)
    {
//        $username = strtolower($username);

        if ($output == null) {
            $url = $this->hostname . '/IBSng/admin/user/user_info.php?normal_username_multi=' . $username;
            $output = $this->request($url);
        }

        if (strpos($output, 'does not exists') == true) {
            throw new \Exception ("[" . $username . "] not found on IBSng Server");
        }

        $dom = new \DomDocument();
        $dom->loadHTML($output);
        $finder = new \DomXPath($dom);


        $classname = 'Form_Content_Row_Right_textarea_td_light';
        $nodes = $finder->query("//*[contains(@class, '$classname')]");
        $lock = trim($nodes->item(0)->nodeValue);
        if (strpos($lock, 'Yes') === false) {
            $locked = '0';
        } else {
            $locked = '1';
        }

        $classname = 'Form_Content_Row_Right_userinfo_light';
        $nodes = $finder->query("//*[contains(@class, '$classname')]");
        $group_pattern = '	    Traffic Limit';
        $data_limit = explode($group_pattern,$output)[1];
        $data_limit = explode('<img border="0" src="/IBSng/images/row/begin_of_row_light.gif">',$data_limit)[1];
        $data_limit = explode('<img border="0" src="/IBSng/images/row/end_of_row_light.gif">',$data_limit)[0];
        $data_limit = explode('G',$data_limit)[0];
        $data_limit = intval(explode(' ',$data_limit)[26]) * pow(1024,3);
        $multi = trim($nodes->item(4)->nodeValue);
        if (strpos($multi, 'instances') === false) {
            $multi = 0;
        } else {
            $multi = trim(str_replace('instances', '', $multi));
        }

        $group_pattern = '<a href="/IBSng/admin/group/group_info.php?group_name=';
        $group_pos1 = strpos($output, $group_pattern);
        $group_trim1 = substr($output, $group_pos1 + strlen($group_pattern), 100000);
        $group_pos2 = strpos($group_trim1, '"');
        $group_name = substr($group_trim1, 0, $group_pos2); // final for group name
        if (substr($group_name, 0, 6) == 'Server') {
            throw new \Exception ("failed to retrieve group name");
        }
        $uid_pattern = 'User ID';
        $uid_pos1 = strpos($output, $uid_pattern);
        $uid_trim1 = substr($output, $uid_pos1, 100000);
        $uid_pattern2 = '<td class="Form_Content_Row_Right_light">';
        $uid_pos2 = strpos($uid_trim1, $uid_pattern2);
        $uid_trim2 = substr($uid_trim1, $uid_pos2 + strlen($uid_pattern2), 100);
        $uid_pattern3 = '</td>';
        $uid_pos3 = strpos($uid_trim2, $uid_pattern3);
        $uid_trim3 = substr($uid_trim2, 0, $uid_pos3);
        $uid = trim($uid_trim3);

        $owner_pattern = 'Owner Admin';
        $owner_pos1 = strpos($output, $owner_pattern);
        $owner_trim1 = substr($output, $owner_pos1, 100000);
        $owner_pattern2 = '<td class="Form_Content_Row_Right_dark">';
        $owner_pos2 = strpos($owner_trim1, $owner_pattern2);
        $owner_trim2 = substr($owner_trim1, $owner_pos2 + strlen($owner_pattern2), 100);
        $owner_pattern3 = '</td>';
        $owner_pos3 = strpos($owner_trim2, $owner_pattern3);
        $owner_trim3 = substr($owner_trim2, 0, $owner_pos3);
        $owner = trim($owner_trim3);

        $comment_pattern = ' Comment
     :';
        $comment_pos1 = strpos($output, $comment_pattern);
        $comment_trim1 = substr($output, $comment_pos1, 100000);
        $comment_pattern2 = '<td class="Form_Content_Row_Right_textarea_td_dark">';
        $comment_pos2 = strpos($comment_trim1, $comment_pattern2);
        $comment_trim2 = substr($comment_trim1, $comment_pos2 + strlen($comment_pattern2), 100);
        $comment_pattern3 = '</td>';
        $comment_pos3 = strpos($comment_trim2, $comment_pattern3);
        $comment_trim3 = substr($comment_trim2, 0, $comment_pos3);
        $comment = trim($comment_trim3);
        if ($comment == '---------------') {
            $comment = '0';
        }

        $name_pattern = ' Name
     :';
        $name_pos1 = strpos($output, $name_pattern);
        $name_trim1 = substr($output, $name_pos1, 100000);
        $name_pattern2 = '<td class="Form_Content_Row_Right_textarea_td_light">';
        $name_pos2 = strpos($name_trim1, $name_pattern2);
        $name_trim2 = substr($name_trim1, $name_pos2 + strlen($name_pattern2), 100);
        $name_pattern3 = '</td>';
        $name_pos3 = strpos($name_trim2, $name_pattern3);
        $name_trim3 = substr($name_trim2, 0, $name_pos3);
        $name = trim($name_trim3);
        if ($name == '---------------') {
            $name = '0';
        }

        $phone_pattern = ' Phone
     :';
        $phone_pos1 = strpos($output, $phone_pattern);
        $phone_trim1 = substr($output, $phone_pos1, 100000);
        $phone_pattern2 = '<td class="Form_Content_Row_Right_textarea_td_dark">';
        $phone_pos2 = strpos($phone_trim1, $phone_pattern2);
        $phone_trim2 = substr($phone_trim1, $phone_pos2 + strlen($phone_pattern2), 100);
        $phone_pattern3 = '</td>';
        $phone_pos3 = strpos($phone_trim2, $phone_pattern3);
        $phone_trim3 = substr($phone_trim2, 0, $phone_pos3);
        $phone = trim($phone_trim3);
        if ($phone == '---------------') {
            $phone = '0';
        }

        $creation_pattern = 'Creation Date';
        $creation_pos1 = strpos($output, $creation_pattern);
        $creation_trim1 = substr($output, $creation_pos1, 100000);
        $creation_pattern2 = '<td class="Form_Content_Row_Right_light">';
        $creation_pos2 = strpos($creation_trim1, $creation_pattern2);
        $creation_trim2 = substr($creation_trim1, $creation_pos2 + strlen($creation_pattern2), 100);
        $creation_pattern3 = '</td>';
        $creation_pos3 = strpos($creation_trim2, $creation_pattern3);
        $creation_trim3 = substr($creation_trim2, 0, $creation_pos3);
        $creation_date = trim($creation_trim3);

        $status_pattern = 'Status';
        $status_pos1 = strpos($output, $status_pattern);
        $status_trim1 = substr($output, $status_pos1, 100000);
        $status_pattern2 = '<td class="Form_Content_Row_Right_dark">';
        $status_pos2 = strpos($status_trim1, $status_pattern2);
        $status_trim2 = substr($status_trim1, $status_pos2 + strlen($status_pattern2), 100);
        $status_pattern3 = '</td>';
        $status_pos3 = strpos($status_trim2, $status_pattern3);
        $status_trim3 = substr($status_trim2, 0, $status_pos3);
        $status = trim($status_trim3);

        $exp_pattern = 'Nearest Expiration Date:';
        $exp_pos1 = strpos($output, $exp_pattern);
        $exp_trim1 = substr($output, $exp_pos1, 10000);
        $exp_pattern2 = '<td class="Form_Content_Row_Right_userinfo_light">';
        $exp_pos2 = strpos($exp_trim1, $exp_pattern2);
        $exp_trim2 = substr($exp_trim1, $exp_pos2 + strlen($exp_pattern2), 1000);
        $exp_pattern3 = '</td>';
        $exp_pos3 = strpos($exp_trim2, $exp_pattern3);
        $exp_trim3 = substr($exp_trim2, 0, $exp_pos3);
        $exp = trim($exp_trim3);
        if ($exp == '---------------') {
            $exp = '0';
        }

        $absExp_pattern = 'Nearest Expiration Date:';
        $absExp_pos1 = strpos($output, $absExp_pattern);
        $absExp_trim1 = substr($output, $absExp_pos1, 10000);
        $absExp_pattern2 = '<td class="Form_Content_Row_Right_userinfo_light">';
        $absExp_pos2 = strpos($absExp_trim1, $absExp_pattern2);
        $absExp_trim2 = substr($absExp_trim1, $absExp_pos2 + strlen($absExp_pattern2), 1000);
        $absExp_pattern3 = '</td>';
        $absExp_pos3 = strpos($absExp_trim2, $absExp_pattern3);
        $absExp_trim3 = substr($absExp_trim2, 0, $absExp_pos3);
        $absExp = trim($absExp_trim3);
        if ($absExp == '---------------') {
            $absExp = '0';
        }

        $relExp_pattern = 'Relative Expiration Date:';
        $relExp_pos1 = strpos($output, $relExp_pattern);
        $relExp_trim1 = substr($output, $relExp_pos1, 10000);
        $relExp_pattern2 = '<td class="Form_Content_Row_Right_userinfo_dark">';
        $relExp_pos2 = strpos($relExp_trim1, $relExp_pattern2);
        $relExp_trim2 = substr($relExp_trim1, $relExp_pos2 + strlen($relExp_pattern2), 1000);
        $relExp_pattern3 = '</td>';
        $relExp_pos3 = strpos($relExp_trim2, $relExp_pattern3);
        $relExp_trim3 = substr($relExp_trim2, 0, $relExp_pos3);
        $relExp = trim($relExp_trim3);
        if ($relExp == '---------------') {
            $relExp = '0';
        }

        $first_login_pattern = 'First Login:';
        $first_login_pos1 = strpos($output, $first_login_pattern);
        $first_login_trim1 = substr($output, $first_login_pos1, 10000);
        $first_login_pattern2 = '<td class="Form_Content_Row_Right_userinfo_dark">';
        $first_login_pos2 = strpos($first_login_trim1, $first_login_pattern2);
        $first_login_trim2 = substr($first_login_trim1, $first_login_pos2 + strlen($first_login_pattern2), 1000);
        $first_login_pattern3 = '</td>';
        $first_login_pos3 = strpos($first_login_trim2, $first_login_pattern3);
        $first_login_trim3 = substr($first_login_trim2, 0, $first_login_pos3);
        $first_login = trim($first_login_trim3);
        if ($first_login == '---------------') {
            $first_login = '0';
        }

        $credit_pattern = '<td class="Form_Content_Row_Left_dark"> 	Credit';
        $credit_pos1 = strpos($output, $credit_pattern);
        $credit_trim1 = substr($output, $credit_pos1, 10000);
        $credit_pattern2 = '<td class="Form_Content_Row_Right_dark">';
        $credit_pos2 = strpos($credit_trim1, $credit_pattern2);
        $credit_trim2 = substr($credit_trim1, $credit_pos2 + strlen($credit_pattern2), 1000);
        $credit_pattern3 = '<a class';
        $credit_pos3 = strpos($credit_trim2, $credit_pattern3);
        $credit_trim3 = substr($credit_trim2, 0, $credit_pos3);
        $credit = trim($credit_trim3);
        $credit = str_replace(',', '', $credit);
        
        $data_limit_pattern = '<td class="Form_Content_Row_Left_userinfo_light"><nobr>	    Traffic Limit';
        $data_limit_pos1 = strpos($output, $data_limit_pattern);
        $data_limit_trim1 = substr($output, $data_limit_pos1, 10000);
        $data_limit_pattern2 = '<td class="Form_Content_Row_Right_userinfo_light">';
        $data_limit_pos2 = strpos($data_limit_trim1, $data_limit_pattern2);
        $data_limit_trim2 = substr($data_limit_trim1, $data_limit_pos2 + strlen($data_limit_pattern2), 1000);
        $data_limit_pattern3 = '</td>';
        $data_limit_pos3 = strpos($data_limit_trim2, $data_limit_pattern3);
        $data_limit_trim3 = substr($data_limit_trim2, 0, $data_limit_pos3);
        $data_limit = trim($data_limit_trim3);
        $data_limit = str_replace('G','',$data_limit);
        $data_limit = str_replace('M','',$data_limit);
        $data_limit = intval($data_limit) * pow(1024,3);
        
        
        
        $used_traffic_pattern = '<td class="Form_Content_Row_Left_userinfo_light"><nobr>	    Traffic Limit';
        $used_traffic_pos1 = strpos($output, $used_traffic_pattern);
        $used_traffic_trim1 = substr($output, $used_traffic_pos1, 10000);
        $used_traffic_pattern2 = '<td class="Form_Content_Row_Right_userinfo_dark">';
        $used_traffic_pos2 = strpos($used_traffic_trim1, $used_traffic_pattern2);
        $used_traffic_trim2 = substr($used_traffic_trim1, $used_traffic_pos2 + strlen($used_traffic_pattern2), 1000);
        $used_traffic_pattern3 = '</td>';
        $used_traffic_pos3 = strpos($used_traffic_trim2, $used_traffic_pattern3);
        $used_traffic_trim3 = substr($used_traffic_trim2, 0, $used_traffic_pos3);
        $used_traffic = trim($used_traffic_trim3);
        $used_traffic = str_replace('G','',$used_traffic);
        $used_traffic = str_replace('M','',$used_traffic);
        if($used_traffic == false){
        $used_traffic = intval($used_traffic) * pow(1024,3);
        }else{
        $used_traffic = intval($used_traffic) * pow(1024,2);
        }

        $info['username'] = $username;
        $info['name'] = $name;
        $info['comment'] = $comment;
        $info['phone'] = $phone;
        $info['owner'] = $owner;
        $info['credit'] = $credit;
        $info['uid'] = $uid;
        $info['group'] = $group_name;
        $info['first_login'] = $first_login;
        $info['creation_date'] = $creation_date;
        $info['nearest_expire_date'] = $exp;
        $info['expire_date'] = $exp;
        $info['absolute_expire_date'] = $absExp;
        $info['relative_expire_date'] = $relExp;
        $info['status'] = $status;
        $info['locked'] = $locked;
        $info['multi'] = $multi;
        $info['data_limit'] = $data_limit;
        $info['used_traffic'] = $used_traffic;
        if ($withPassword) {
            $info['password'] = $this->getPassword($username, $uid);
        }
        return $info;
    }

    protected function getPassword($username, $uid = null)
    {
        if ($uid == null) {
            $uid = $this->isUsername($username);
        }

        $url = $this->hostname . '/IBSng/admin/plugins/edit.php';
        $postData['user_id'] = $uid;
        $postData['edit_user'] = 1;
        $postData['attr_edit_checkbox_2'] = 'normal_username';

        $output = $this->request($url, $postData);

        $phrase = '<td class="Form_Content_Row_Right_light">	<input type=text id="password" name="password" value="';
        $pos1 = strpos($output, $phrase);
        $leftover = str_replace($phrase, '', substr($output, $pos1, strlen($phrase) + 1000));
        $password = substr($leftover, 0, strpos($leftover, '"'));
        if (isset($password)) {
            return trim($password);
        } else {
            return false;
        }
    }


    public function fetchAllUsers($startUID, $pagePerRequest)
    {
//        $bench = new \Ubench();
//        $bench->start();
        $totalPages = $this->_csvPages($startUID, $pagePerRequest);
//        $bench->end();
//        echo 'Total Pages: ' . $totalPages . ' (time: ' . $bench->getTime(true) . ')' . PHP_EOL;
//        unset($bench);
        if ($totalPages < 1) {
            return false;
        }

        $processed = 1;
        $userPages = array();

//        $bench = new \Ubench();
//        $bench->start();
        while ($processed <= $totalPages) {
//            $userPages[] = is_array($this->_csv($startUID, $pagePerRequest, $processed)) ? $this->_csv($startUID, $pagePerRequest, $processed) : array();

            $temp = $this->_csv($startUID, $pagePerRequest, $processed);
            if (is_array($temp)) {
                $userPages[] = $temp;
            } else {
                $userPages[] = array();
            }

            $processed++;
        }

        $arrayMerge = new \ReflectionFunction("array_merge");
        $usersList = $arrayMerge->invokeArgs($userPages);

//        $bench->end();
//        echo 'got ' . count($usersList) . ' users (' . $bench->getTime(true) . ')' . PHP_EOL;
        return $usersList;
    }


    /**
     * Checks for user existence
     * @param $username
     * @return bool|Integer False on failure and uid of user on success
     * @throws Exception
     * @throws \Exception
     */
    private function isUsername($username)
    {
        $url = $this->hostname . '/IBSng/admin/user/user_info.php?normal_username_multi=' . $username;
        $output = $this->request($url);

        if (strpos($output, 'does not exists') == true) {
            return false;
        } else {
            $pattern1 = 'change_credit.php?user_id=';
            $pos1 = strpos($output, $pattern1);
            $sub1 = substr($output, $pos1 + strlen($pattern1), 100);
            $pattern2 = '"';
            $pos2 = strpos($sub1, $pattern2);
            $sub2 = substr($sub1, 0, $pos2);
            return $sub2;
        }
    }

    private function _csvPages($startUid = 1, $limit = 10)
    {
        $url = $this->hostname . '/IBSng/admin/user/search_user.php';
        $post_data['user_id_op'] = '>=';
        $post_data['user_id'] = $startUid;
        $post_data['normal_username_op'] = 'equals';
        $post_data['normal_username'] = '';
        $post_data['voip_username_op'] = 'equals';
        $post_data['voip_username'] = '';
        $post_data['caller_id_op'] = 'equals';
        $post_data['caller_id'] = '';
        $post_data['credit_op'] = '=';
        $post_data['credit'] = '';
        $post_data['abs_exp_date_op'] = '=';
        $post_data['abs_exp_date'] = '';
        $post_data['abs_exp_date_unit'] = 'days';
        $post_data['rel_exp_date_op'] = '=';
        $post_data['rel_exp_date'] = '';
        $post_data['rel_exp_date_unit'] = 'days';
        $post_data['rel_exp_value_op'] = '=';
        $post_data['rel_exp_value'] = '';
        $post_data['rel_exp_value_unit'] = 'Days';
        $post_data['first_login_op'] = '=';
        $post_data['first_login'] = '';
        $post_data['first_login_unit'] = 'days';
        $post_data['lock_reason_op'] = 'equals';
        $post_data['lock_reason'] = '';
        $post_data['persistent_lan_mac'] = '';
        $post_data['persistent_lan_ip'] = '';
        $post_data['persistent_lan_ras_ip'] = '';
        $post_data['limit_mac_op'] = 'equals';
        $post_data['limit_mac'] = '';
        $post_data['limit_station_ip_op'] = 'equals';
        $post_data['limit_station_ip'] = '';
        $post_data['comment_op'] = 'equals';
        $post_data['comment'] = '';
        $post_data['name_op'] = 'equals';
        $post_data['name'] = '';
        $post_data['phone_op'] = 'equals';
        $post_data['phone'] = '';
        $post_data['email_address_op'] = 'equals';
        $post_data['email_address'] = '';
        $post_data['multi_login_op'] = '=';
        $post_data['multi_login'] = '';
        $post_data['ippool'] = '';
        $post_data['assign_ip_op'] = 'equals';
        $post_data['assign_ip'] = '';
        $post_data['order_by'] = 'creation_date';
        $post_data['rpp'] = $limit;
        $post_data['view_options'] = '0';
        $post_data['Internet_Username'] = 'show__attrs_normal_username';
        $post_data['Credit'] = 'show__basic_credit|price';
        $post_data['Group'] = 'show__basic_group_name';
        $post_data['Owner'] = 'show__basic_owner_name';
        $post_data['Creation_Date'] = 'show__basic_creation_date';
        $post_data['Relative_ExpDate'] = 'show__attrs_rel_exp_date,show__attrs_rel_exp_date_unit';
        $post_data['Lock'] = 'show__attrs_lock|lockFormat';
        $post_data['Multi_Login'] = 'show__attrs_multi_login';
        $post_data['x'] = '18';
        $post_data['y'] = '9';
        $post_data['search'] = '1';
        $post_data['show_reports'] = '1';
        $post_data['page'] = 1;
        $post_data['order_by'] = 'creation_date';
//        $post_data['desc'] = 'on';
        $post_data['Absolute_ExpDate'] = 'show__attrs_abs_exp_date';

        $output = $this->request($url, $post_data);

        $phrase = ');" title="Last Page"';
        $phrasePosition = strpos($output, $phrase);
        $output2 = substr($output, $phrasePosition - 20);

        $phrase2 = 'Page(';
        $phrasePosition2 = strpos($output2, $phrase2) + strlen($phrase2);

        $output3 = substr($output2, $phrasePosition2);

        $pages = substr($output3, 0, strpos($output3, $phrase));

        return $pages;
    }

    private function _csv($startUid = 1, $limit = 10, $page = 1)
    {
        $url = $this->hostname . '/IBSng/admin/user/search_user.php';
        $post_data['user_id_op'] = '>=';
        $post_data['user_id'] = $startUid;
        $post_data['normal_username_op'] = 'equals';
        $post_data['normal_username'] = '';
        $post_data['voip_username_op'] = 'equals';
        $post_data['voip_username'] = '';
        $post_data['caller_id_op'] = 'equals';
        $post_data['caller_id'] = '';
        $post_data['credit_op'] = '=';
        $post_data['credit'] = '';
        $post_data['abs_exp_date_op'] = '=';
        $post_data['abs_exp_date'] = '';
        $post_data['abs_exp_date_unit'] = 'days';
        $post_data['rel_exp_date_op'] = '=';
        $post_data['rel_exp_date'] = '';
        $post_data['rel_exp_date_unit'] = 'days';
        $post_data['rel_exp_value_op'] = '=';
        $post_data['rel_exp_value'] = '';
        $post_data['rel_exp_value_unit'] = 'Days';
        $post_data['first_login_op'] = '=';
        $post_data['first_login'] = '';
        $post_data['first_login_unit'] = 'days';
        $post_data['lock_reason_op'] = 'equals';
        $post_data['lock_reason'] = '';
        $post_data['persistent_lan_mac'] = '';
        $post_data['persistent_lan_ip'] = '';
        $post_data['persistent_lan_ras_ip'] = '';
        $post_data['limit_mac_op'] = 'equals';
        $post_data['limit_mac'] = '';
        $post_data['limit_station_ip_op'] = 'equals';
        $post_data['limit_station_ip'] = '';
        $post_data['comment_op'] = 'equals';
        $post_data['comment'] = '';
        $post_data['name_op'] = 'equals';
        $post_data['name'] = '';
        $post_data['phone_op'] = 'equals';
        $post_data['phone'] = '';
        $post_data['email_address_op'] = 'equals';
        $post_data['email_address'] = '';
        $post_data['multi_login_op'] = '=';
        $post_data['multi_login'] = '';
        $post_data['ippool'] = '';
        $post_data['assign_ip_op'] = 'equals';
        $post_data['assign_ip'] = '';
        $post_data['order_by'] = 'creation_date';
        $post_data['rpp'] = $limit;
        $post_data['view_options'] = '1';
        $post_data['Internet_Username'] = 'show__attrs_normal_username';
        $post_data['Credit'] = 'show__basic_credit|price';
        $post_data['Group'] = 'show__basic_group_name';
        $post_data['Owner'] = 'show__basic_owner_name';
        $post_data['Creation_Date'] = 'show__basic_creation_date';
        $post_data['Relative_ExpDate'] = 'show__attrs_rel_exp_date,show__attrs_rel_exp_date_unit';
        $post_data['Lock'] = 'show__attrs_lock|lockFormat';
        $post_data['Multi_Login'] = 'show__attrs_multi_login';
        $post_data['x'] = '18';
        $post_data['y'] = '9';
        $post_data['search'] = '1';
        $post_data['show_reports'] = '1';
        $post_data['page'] = $page;
        $post_data['order_by'] = 'creation_date';
//        $post_data['desc'] = 'on';
        $post_data['Absolute_ExpDate'] = 'show__attrs_abs_exp_date';

        $output = $this->request($url, $post_data);

        $lines = explode("\n", $output);
        $i = 0;
        $j = 0;
        foreach ($lines as $line) {
            if ($line == "")
                continue;
            $line_splited = explode(",", $line);
            if (!isset($line_splited))
                continue;
            if ($line_splited[0] == 'User ID')
                continue;
            $j++;
            if ($line_splited[1] == '-') {
                continue;

            }
            $ibsid = $line_splited[0];
//            $ibsid = $j - 1;
            $users[$ibsid]['ibsid'] = $line_splited[0];
            $users[$ibsid]['username'] = $line_splited[1];
            $users[$ibsid]['credit'] = $line_splited[2];
            $users[$ibsid]['group'] = $line_splited[3];
            $users[$ibsid]['creation'] = $line_splited[5];
            $users[$ibsid]['relexpdate'] = $line_splited[6];
            $users[$ibsid]['locked'] = (trim($line_splited[7]) == '-') ? 0 : 1;
            $users[$ibsid]['multiLogin'] = (trim($line_splited[8]) == '-') ? 1 : $line_splited[8];
            $users[$ibsid]['absoluteExpire'] = (trim($line_splited[9]) == '-') ? 0 : $line_splited[9];
            $i++;
        }
        return isset($users) ? $users : $j;
    }

    protected function _addUser($group_name, $username, $password, $credit)
    {
        /*
         * change owner to whatever reseller you are using to login
         */
        $owner = 'system';
        $IBSng_uid = $this->cr8_uid($group_name, $credit);
        $url = $this->hostname . '/IBSng/admin/plugins/edit.php?edit_user=1&user_id=' . $IBSng_uid . '&submit_form=1&add=1&count=1&credit=1&owner_name=' . $owner . '&group_name=' . $group_name . '&x=35&y=1&edit__normal_username=normal_username';
        $post_data['target'] = 'user';
        $post_data['target_id'] = $IBSng_uid;
        $post_data['update'] = 1;
        $post_data['edit_tpl_cs'] = 'normal_username';
        $post_data['attr_update_method_0'] = 'normalAttrs';
        $post_data['has_normal_username'] = 't';
        $post_data['current_normal_username'] = '';
        $post_data['normal_username'] = $username; // username
        $post_data['password'] = $password; //password
        $post_data['normal_save_user_add'] = 't';
        $post_data['credit'] = $credit;
        $output = $this->request($url, $post_data, true);
        if (strpos($output, 'exist')) {
            throw new \Exception("username already exists");
//            return false;
        }
        if (strpos($output, 'IBSng/admin/user/user_info.php?user_id_multi')) {
            return true;
        }
    }

    protected function request($url, $postData = array(), $header = FALSE)
    {
        if (empty($url)) {
            throw new \Exception('Url specified in curl request is empty ');
        }
        $this->handler = curl_init();
        curl_setopt($this->handler, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($this->handler, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_PORT, $this->port);
        curl_setopt($this->handler, CURLOPT_POST, true);
        curl_setopt($this->handler, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($this->handler, CURLOPT_HEADER, $header);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, TRUE);
//        curl_setopt($this->handler, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->handler, CURLOPT_USERAGENT, $this->agent);
        curl_setopt($this->handler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->handler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->handler, CURLOPT_COOKIEFILE, $this->getCookie());
        curl_setopt($this->handler, CURLOPT_COOKIEJAR, $this->getCookie());
        $output = curl_exec($this->handler);
        if (curl_errno($this->handler) != 0) {
            $curlError = curl_error($this->handler);
            curl_close($this->handler);
            throw new \Exception('Curl Error: ' . $curlError . $url);
        }
        curl_close($this->handler);
        return $output;
    }

    private function cr8_uid($group_name, $credit)
    {
        $url = $this->hostname . '/IBSng/admin/user/add_new_users.php';
        $post_data['submit_form'] = 1;
        $post_data['add'] = 1;
        $post_data['count'] = 1;
        $post_data['credit'] = $credit;
        $post_data['owner_name'] = $this->username;
        $post_data['group_name'] = $group_name;
        $post_data['edit__normal_username'] = 1;
        $output = $this->request($url, $post_data, true);
        $pattern1 = 'user_id=';
        $pos1 = strpos($output, $pattern1);
        $sub1 = substr($output, $pos1 + strlen($pattern1), 100);
        $pattern2 = '&su';
        $pos2 = strpos($sub1, $pattern2);
        $sub2 = substr($sub1, 0, $pos2);
        return $sub2;
    }

    protected function _delUser($username, $logs = true, $audit = true)
    {
        $uid = $this->_userExists($username);
        if ($uid == false)
            throw new \Exception("user does not exists");
//            return false;
        $url = $this->hostname . '/IBSng/admin/user/del_user.php';
        $post_data['user_id'] = $uid;
        $post_data['delete'] = 1;
        $post_data['delete_comment'] = '';
        if ($logs)
            $post_data['delete_connection_logs'] = 'on';
        if ($audit)
            $post_data['delete_audit_logs'] = 'on';
        $output = $this->request($url, $post_data, true);
        if (strpos($output, 'Successfully')) {
            return true;
        } else {
            return false;
        }
    }

    public function _userExists($username)
    {
        $url = $this->hostname . '/IBSng/admin/user/user_info.php?normal_username_multi=' . $username;
        $output = $this->request($url, array(), true);
        if (strpos($output, 'does not exists') == true) {
            return false;
        } else {
            $pattern1 = 'change_credit.php?user_id=';
            $pos1 = strpos($output, $pattern1);
            $sub1 = substr($output, $pos1 + strlen($pattern1), 100);
            $pattern2 = '"';
            $pos2 = strpos($sub1, $pattern2);
            $sub2 = substr($sub1, 0, $pos2);
            return $sub2;
        }
    }
}