<?php

if (!defined('ASTRAPATH'))
    define('ASTRAPATH', dirname(__FILE__) . '/');

if (!class_exists('Bad_bots')) {

    class Bad_bots {

		protected $user_agent;
		protected $ip_address;
		protected $is_fake_bot=false;
		protected $search_engine = array();
		
		function __construct($ua, $ip) {
            $this->user_agent = $ua;
			$this->ip_address = $ip;
			$this->search_engine = array(
			    'google' => false,
                'bing' => false,
            );
        }

		public function is_search_engine(){

			return $this->is_google_bot() || $this->is_bing_bot();
		}

		public function is_bing_bot(){
            if(!strstr(strtolower($this->user_agent), "bingbot")){
                return FALSE;
            }

            if(filter_var($this->ip_address, FILTER_VALIDATE_IP) === false){
                return FALSE;
            }


            $host = gethostbyaddr($this->ip_address);

            $allowed_domains = array("msn.com");
            $domain = $this->get_host($host);

            if(!in_array($domain, $allowed_domains)){
                $this->is_fake_bot = TRUE;
                return FALSE;
            }

            $forward_ip = gethostbyname($host);

            if($forward_ip === $this->ip_address){
                return TRUE;
            }

            return FALSE;

        }

		public function is_google_bot(){
			//Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)
			if(!strstr(strtolower($this->user_agent), "googlebot")){
				return FALSE;
			}
			
			return $this->verify_google_bot();
		}	
		
		public function is_fake_bot(){
				return $this->is_fake_bot;
		}
		
		protected function verify_google_bot(){
			
			if(filter_var($this->ip_address, FILTER_VALIDATE_IP) === false){
				return FALSE;
			}
			
			$host = gethostbyaddr($this->ip_address);

			$allowed_domains = array("google.com", "googlebot.com", "googleusercontent.com");
			$domain = $this->get_host($host);

			if(!in_array($domain, $allowed_domains)){
				//ip-returns-other-domain
				$this->is_fake_bot = TRUE;
				return FALSE;
			}

			$forward_ip = gethostbyname($host);

			if($forward_ip === $this->ip_address){
				return TRUE;
			}
			
			return FALSE;
		}

		protected function get_host($host_with_subdomain) {
			$array = explode(".", $host_with_subdomain);
			return (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : "").".".$array[count($array) - 1];
		}
		
        public function run() {
			
		}
	}
}
?>