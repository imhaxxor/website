<?php

if (!class_exists('Update_DB')) {

    class Update_DB {

        function __construct() {
            $this->run();
            echo_debug('<strong>Constructor DB</strong>');
        }

        protected function is_request_from_astra(){
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : "empty";

            if(strpos($user_agent, "astra") !== false){
                return true;
            }

            if(file_exists(ASTRAPATH . 'db/astra-setup.flag')){
                return true;
            }

            return false;
        }

        protected function make_request() {


            if($this->is_request_from_astra()){
                return false;
            }

            require_once(ASTRAPATH . 'libraries/Crypto.php');
            
            echo_debug('cURLing to ' . CZ_API_URL);
            
            $dataArray['client_key'] = CZ_CLIENT_KEY;
            $dataArray['api'] = "get_db";

            $str = serialize($dataArray);
            $crypto = new Astra_crypto();
            $encrypted_data = $crypto->encrypt($str, CZ_SECRET_KEY);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, CZ_API_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "encRequest=" . $encrypted_data . "&access_code=" . CZ_ACCESS_KEY);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $output = curl_exec($ch);
            echo_debug('cURL Executed');

            echo_debug('cURL Error >> ' . curl_error($ch));
            echo_debug("<pre style='border: 2px solid #000; padding: 20px;'>$output</pre>");

            $errors = curl_error($ch);
            curl_close($ch);

            if ($errors) {
                return FALSE;
            } else {
                return $output;
            }
        }

        function run() {

            $server_reply = $this->make_request();

            if ($server_reply === FALSE) {
                echo_debug('Update False');
                return FALSE;
            } else {
                $server_reply = json_decode($server_reply);
                echo_debug('JSON Decoded');
            }

            if ($server_reply->code == 1) {
                echo_debug('Valid Response');
                $bots = json_decode($server_reply->bots);
                $custom_rules = json_decode($server_reply->custom_rules);
            }
            else{
                echo_debug('Not a valid Response');
                echo_debug(json_encode($server_reply));
            }

            //print_r($bots);
            //print_r($custom_rules);

            echo_debug('Connecting DB');
            ASTRA::connect_db();
            echo_debug('DB Connected');

            $update_bad_bots = ASTRA::$_db->update_bad_bots($bots);

            $update_custom_params = ASTRA::$_db->update_custom_params($custom_rules->params);
            $update_custom_ip = ASTRA::$_db->update_custom_ip($custom_rules->ip);

            if ($update_bad_bots && $update_custom_params && $update_custom_ip) {
                return TRUE;
            } else {
                return FALSE;
            }
        }

    }

}