<?php

if (!defined('ASTRAPATH'))
    exit('No direct script access allowed');

use MaxMind\Db\Reader;

if (!class_exists("Astra_country")) {
    class Astra_country
    {
        function __construct()
        {
        }

        function get_country($ip_address){
            $country_cf = $this->get_country_cf($ip_address);

            if($country_cf !== false){
                return $country_cf;
            }
            

            $ignore_ips = array("0.0.0.0", "0.255.255.255");
            if(!in_array($ip_address, $ignore_ips) ){
                require_once(ASTRAPATH . 'libraries/plugins/MaxMind-DB-Reader/autoload.php');
                $reader = new Reader(ASTRAPATH . 'libraries/plugins/MaxMind-DB-Reader/GeoLite2-Country.mmdb');
        
                if(isset($reader->get($ip_address)['country']['iso_code'])){
                    return  strtoupper($reader->get($ip_address)['country']['iso_code']);
                }
            }
            return 'US';
        }


        protected function get_country_cf($ip_address){
            if(isset($_SERVER["HTTP_CF_IPCOUNTRY"]) && strlen($_SERVER["HTTP_CF_IPCOUNTRY"]) == 2 && is_string($_SERVER["HTTP_CF_IPCOUNTRY"])){
                return strtoupper($_SERVER["HTTP_CF_IPCOUNTRY"]);
            }

            return false;
        }

    }
}