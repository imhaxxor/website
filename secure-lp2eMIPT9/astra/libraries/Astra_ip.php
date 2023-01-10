<?php

if (!class_exists("Astra_ip")) {
    class Astra_ip
    {
        function __construct()
        {
        }

        function get_ip_address()
        {

            if (defined("CZ_IP_HEADER")) {
                if (array_key_exists(CZ_IP_HEADER, $_SERVER)) {
                    return $_SERVER[CZ_IP_HEADER];
                }
            }

            $serverIp = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';

            $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_SUCURI_CLIENTIP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
            foreach ($ip_keys as $key) {
                if (array_key_exists($key, $_SERVER) === true) {
                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                        // trim for safety measures
                        $ip = trim($ip);
                        // attempt to validate IP
                        if (($this->validate_ip($ip) || $this->validate_ipv6($ip)) && $ip !== $serverIp) {
                            return $ip;
                        }
                    }
                }
            }
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
        }

        /**
         * Ensures an ip address is both a valid IP and does not fall within
         * a private network range.
         */

        function validate_ipv6($ip)
        {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return true;
            }

            return false;
        }

        function validate_ip($ip)
        {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
            return true;
        }


    }
}