<?php
require_once 'config.php';

class CurlRequest {
    private $url;
    private $headers = [];
    private $timeout = null;
    private $authToken = null;
    private $cookie = null;
    public function __construct($url) {
        global $request_exec_timeout;
        $this->url = $url;
        $this->timeout = $request_exec_timeout;
    }

    public function setHeaders(array $headers) {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function setBearerToken($token) {
        $this->authToken = $token;
    }
    
    public function setCookie($cookieStr) {
        $this->cookie = $cookieStr;
    }

    private function prepareHeaders() {
        $headers = $this->headers;

        if ($this->authToken) {
            $headers[] = "Authorization: Bearer {$this->authToken}";
        }
        return $headers;
    }

    private function execute($method, $data = null) {
        $this->timeout = !$this->timeout  ?  10000 : $this->timeout;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $finalHeaders = $this->prepareHeaders();
        if (!empty($finalHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $finalHeaders);
        }
        if ($this->cookie) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);   
        }
        if ($data) {
            if (is_array($data)) {
                $data = http_build_query($data);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'status' => null,
                'body' => null,
                'error' => $error,
            ];
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body' => $response
        ];
    }

    public function get() {
        return $this->execute("GET");
    }

    public function post($data) {
        return $this->execute("POST", $data);
    }

    public function put($data) {
        return $this->execute("PUT", $data);
    }

    public function delete($data = null) {
        return $this->execute("DELETE", $data);
    }
    public function PATCH($data = null){
        return $this->execute('PATCH',$data);
    }
}