<?php

require_once('Crypto.php');
require_once('browser.php');

class API_connect
{

    protected $site = "";
    protected $get_api;
    protected $api_url;
    private $rootApiUri;

    public function __construct($url = "")
    {
        if ($url == "") {
            $this->api_url = CZ_API_URL;
        } else {
            $this->api_url = $url;
        }
    }

    public function ping()
    {
        $dataArray = array();
        return $this->send_request("ping", $dataArray);
    }

    public function report_update()
    {
        return true;
        $ak = $this->get_end_point_url(FALSE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ak . 'ak.php');
        curl_setopt($ch, CURLOPT_POST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cz-Setup: true'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);
        echo_debug('cURL Executed');

        echo_debug('cURL Error >> ' . curl_error($ch));
        echo_debug("<pre style='border: 2px solid #000; padding: 20px;'>$output</pre>");

        curl_close($ch);
        echo_debug('cURLd');

        $resp = $output;
        $resp = json_decode($resp);

        if (isset($resp->code) && $resp->code == 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    protected function get_end_point_url($api = TRUE)
    {

        $current_url = $this->getHttpsPrefix();

        $current_url .= str_replace(realpath($_SERVER["DOCUMENT_ROOT"]), $_SERVER['HTTP_HOST'], realpath(dirname(dirname(__FILE__))));

        if ($api) {
            $current_url .= '/api.php';
        } else {
            $current_url .= '/';
        }

        $current_url = rtrim($current_url, '/');

        return $current_url;

    }

    public function setRootApiUri($rootApiUri)
    {
        if ($rootApiUri == false) {
            return false;
        }

        $subFolderIfAny = str_replace($_SERVER['DOCUMENT_ROOT'], '', $rootApiUri);


        $current_url = $this->getHttpsPrefix();

        $current_url .= rtrim($_SERVER['HTTP_HOST'], '/') . '/' . ltrim($subFolderIfAny, '/');
        $this->rootApiUri = $current_url;

        return;

    }

    protected function getHttpsPrefix(){
        if (@$_SERVER['HTTPS'] == 'on' || @$_SERVER['SERVER_PORT'] == '443' || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
            return "https://";
        } else {
            return "http://";
        }
    }

    protected function is_request_from_astra()
    {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : "empty";

        if (strpos($user_agent, "astra") !== false) {
            return true;
        }

        return false;
    }

    public function setup()
    {

        $res = explode(".", CZ_ASTRA_CLIENT_VERSION);

        $version['major'] = $res[0];
        $version['minor'] = $res[1];
        $version['patch'] = $res[2];

        $client_api_url = !empty($this->rootApiUri) ? $this->rootApiUri : $this->get_end_point_url();

        $dataArray = array(
            "version" => $version,
            "client_api_url" => $client_api_url,
            "platform" => CZ_PLATFORM,
        );

        /* print_r($dataArray); */
        echo_debug('SETUP API Send Request');
        echo_debug(serialize($dataArray));

        return $this->send_request("setup", $dataArray);
    }

    public function custom_rule($action, $type, $ip_address = "")
    {
        $dataArray['rule_action'] = $action;
        $dataArray['rule_type'] = $type;
        $dataArray['ip_address'] = $ip_address;
        return $this->send_request("custom_rule", $dataArray);
    }

    /*
      public function custom_rule($action, $type, $ip_address = "") {

      }
     * 
     */

    public function hook_has_loggedin($dataArray = array())
    {

        if ($dataArray[0]['success'] === 1) {
            return false;
        }

        require_once(__DIR__ . '/../Astra.php');
        $astra = new Astra();
        ASTRA::$_db->log_hit(ASTRA::$_ip);

        $dataArray[0]['blocking'] = (ASTRA::$_db->is_blocked_or_trusted(ASTRA::$_ip, false, ASTRA::$_config) == "blocked") ? 1 : 0;

        return true;
    }

    public function send_request($api = "", $dataArray = array(), $platform = "php")
    {

        if ($this->is_request_from_astra()) {
            return false;
        }

        $callback = array($this, 'hook_' . $api);

        if (is_callable($callback)) {
            $hookResponse = call_user_func($callback, array($dataArray));
            if (is_array($hookResponse)) {
                $dataArray = $hookResponse;
            }
        }


        $dataArray['client_key'] = CZ_CLIENT_KEY;
        $dataArray['api'] = $api;

        //$dataArray['site'] = $_SERVER['SERVER_NAME'];
        $dataArray['site'] = $_SERVER['SERVER_NAME'];
        if (!isset($dataArray['version'])) {
            $dataArray['version'] = CZ_ASTRA_CLIENT_VERSION;
        }

        $browser = new Browser_Astra();
        $browser_info['useragent'] = $browser->getUserAgent();
        $browser_info['browser_name'] = $browser->getBrowser();
        $browser_info['version'] = $browser->getVersion();
        $browser_info['platform'] = $browser->getPlatform();
        $browser_info['isMobile'] = $browser->isMobile();
        $browser_info['isTablet'] = $browser->isTablet();

        $dataArray['ip'] = ASTRA::$_ip;

        if ($dataArray['ip'] == "") {
            $dataArray['ip'] = "::1";
            return false;
        }

        $dataArray['browser'] = $browser_info;
        $dataArray['attack_url'] = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');

        echo_debug($this->api_url);
        echo_debug($dataArray);

        $str = serialize($dataArray);

        $crypto = new Astra_crypto();
        $encrypted_data = $crypto->encrypt($str, CZ_SECRET_KEY);

        if ($platform == "wordpress") {
            $post_param = array(
                "encRequest" => $encrypted_data,
                "access_code" => CZ_ACCESS_KEY,
            );
            $response = wp_remote_post($this->api_url, array(
                    'method' => 'POST',
                    'timeout' => 5,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => $post_param,
                    'cookies' => array()
                )
            );

            if (is_wp_error($response) || (isset($response['body']) && is_wp_error($response['body']))) {
                return true;
            }

            $resp = $response['body'];
        } else {
            echo_debug('cURLing');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->api_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "encRequest=" . $encrypted_data . "&access_code=" . CZ_ACCESS_KEY);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $output = curl_exec($ch);
            echo_debug('cURL Executed');

            echo_debug('cURL Error >> ' . curl_error($ch));
            echo_debug("<pre style='border: 2px solid #000; padding: 20px;'>$output</pre>");

            if (curl_error($ch)) {
                $error_msg = curl_error($ch);
            }

            curl_close($ch);
            echo_debug('cURLd');
            $resp = $output;

        }


        $resp = json_decode($resp);

        if (isset($error_msg)) {
            //echo $error_msg;
        }

        //var_dump($output);


        if (isset($resp->code) && $resp->code == 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

}

/* End of file api_connect.php */
?>