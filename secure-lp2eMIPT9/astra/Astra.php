<?php

if (defined('CZ_DEBUG') && CZ_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}


if (!defined('ASTRAPATH'))
    define('ASTRAPATH', dirname(__FILE__) . '/');

$inc_path = get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/libraries/plugins';
set_include_path($inc_path);

/**
 * Description of Astra
 *
 *
 */
if (!class_exists('Astra')) {

    class Astra
    {

        public static $_db;
        public static $_config;
        public static $_ip;
        public static $_user_agent;
        public static $_is_search_engine;
        public static $_is_fake_search_engine_bot;
        public static $cz_lvl;

        public static function echo_debug($str)
        {
            if (CZ_DEBUG) {
                echo '<p>' . $str . "</p>\r\n";
            }
        }

        public function __construct()
        {

            require_once(ASTRAPATH . 'astra-config.php');
            require_once(ASTRAPATH . 'libraries/Config_options.php');

            if (!defined('CZ_DB_PATH')) {
                define('CZ_DB_PATH', dirname(__FILE__) . '/');
            }

            echo_debug('Astra Constuctor Loaded');
            echo_debug('Astra version - v' . CZ_ASTRA_CLIENT_VERSION . ' and PHP version - ' . PHP_VERSION);
            if (!$this->meets_requirements()) {
                echo_debug('Requirements not met');
                return FALSE;
            } else {
                echo_debug('Meets all Requirements');
            }

            self::$_config = new AstraConfig();

            if (CZ_ASTRA_ACTIVE) {

                echo_debug('Astra Active');
                require_once(ASTRAPATH . 'libraries/Astra_ip.php');

                $client_ip = new Astra_ip();

                self::$_ip = $client_ip->get_ip_address();
                if (trim(self::$_ip) == "") {
                    self::$_ip = '103.16.70.12';
                }

                self::$_user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

                if (!empty($cz_lvl)) {
                    self::$cz_lvl = $cz_lvl;
                } else {
                    self::$cz_lvl = get_cz_lvl();
                    $cz_lvl = self::$cz_lvl;
                }

                echo_debug('Current IP: ' . self::$_ip);
                echo_debug('Current UA: ' . self::$_user_agent);
                echo_debug(self::$cz_lvl);
                echo_debug($cz_lvl);

                $this->check_search_engine();

                if (CZ_SETUP) {
                    echo_debug('Has been Setup');
                    $this->init();
                } else {
                    echo_debug('NOT been Setup');
                    if (isset($_SERVER['HTTP_HOST']) && $this->setup()) {
                        echo_debug('Setup Complete');
                        $this->init();
                    }
                }
            } else {
                echo_debug('Astra not active');
                show_astra_debug_log();
                return;
            }

            show_astra_debug_log();
        }

        protected function meets_requirements()
        {
            if (!defined('PDO::ATTR_DRIVER_NAME')) {
                echo_debug('PDO Driver Missing');
                return FALSE;
            } else {
                echo_debug('PDO Loaded');
            }

            if (!in_array('sqlite', PDO::getAvailableDrivers(), TRUE)) {
                echo_debug('sqlite3 extension not loaded');
                return false;
            } else {
                echo_debug('SQLite loaded');
            }

            if (!file_exists(CZ_DB_PATH . 'db/' . CZ_DATABASE_NAME . '.db')) {
                echo_debug('<strong>Updating DB</strong>');
                $this->update_db();
            } else {
                /*
                  unlink(ASTRAPATH . 'db/' . CZ_DATABASE_NAME . '.db');
                  echo_debug('<strong>Updating DB</strong>');
                  $this->update_db();
                 *
                 */
            }
            return TRUE;
        }

        public function init()
        {
            echo_debug('Initializing');
            self::connect_db();
            $this->run();
        }

        public static function connect_db()
        {
            require_once(ASTRAPATH . 'libraries/SQLite_db.php');
            self::$_db = new SQLiteWrapper(self::$cz_lvl);
        }

        private function update_db()
        {
            require_once(ASTRAPATH . 'libraries/Update_DB.php');
            $update_db = new Update_DB();
        }

        public function setup()
        {

            if (empty($_SERVER)) {
                return false;
            }

            // Added for TC
            if (!defined("CZ_SECRET_KEY") || (defined("CZ_SECRET_KEY") && strlen(CZ_SECRET_KEY) < 2)) {
                echo_debug('Enc keys not set');
                return false;
            }

            /*
            if (file_exists(CZ_DB_PATH . 'db/' . CZ_DATABASE_NAME . 'db')) {
                if (unlink(CZ_DB_PATH . 'db/' . CZ_DATABASE_NAME . 'db')) {
                    echo_debug('Deletd Already Set Database');
                } else {
                    echo_debug('Unable to Delete Database');
                    return FALSE;
                }
            }
            */

            require_once(ASTRAPATH . 'libraries/Astra_setup.php');
            $astraSetup = new Astra_setup();
            $astraSetup->createAstraFiles();
            $rootApiUri = $astraSetup->getRootApiFileUri();

            require_once(ASTRAPATH . 'libraries/API_connect.php');
            $connect = new API_connect();

            $connect->setRootApiUri($rootApiUri);

            if (!$connect->setup()) {
                return FALSE;
            }

            require_once(ASTRAPATH . 'libraries/Update_DB.php');
            $update_db = new Update_DB();

            if (!$update_db) {
                return FALSE;
            }

            require_once(ASTRAPATH . 'libraries/Update_config.php');
            $update_config_file = Update_config('CZ_SETUP', base64_encode('TRUE'), FALSE);

            return $update_config_file;

        }

        public function run()
        {
            echo_debug('About to run');

            $blocked_or_trusted = self::$_db->is_blocked_or_trusted(self::$_ip, false, self::$_config);

            if ($blocked_or_trusted == "blocked" && !self::$_is_search_engine) {
                self::show_block_page();
            }

            if ($blocked_or_trusted == "trusted" || self::is_whitelisted_url() || self::$_is_search_engine) {
                echo_debug("Is trusted or whitelisted or a search engine");
                if (self::$_is_search_engine) {
                    $is_trusted = self::$_db->edit_ip_exception('add', 'trusted', self::$_ip);
                }
                return TRUE;
            }

            $is_bad_bot = $this->is_bad_bot();

            if ($blocked_or_trusted == "blocked" || $is_bad_bot || self::$_is_fake_search_engine_bot) {
                echo_debug('You have been blocked');
                require_once(ASTRAPATH . 'libraries/API_connect.php');
                $connect = new API_connect();
                if ($is_bad_bot || self::$_is_fake_search_engine_bot) {
                    // self::$_db->log_hit(self::$_ip);
                    self::$_db->edit_ip_exception('add', 'blocked', self::$_ip);

                    echo_debug('You are a badbot');
                    $connect->send_request("badbot", array());
                    echo_debug('Reported Blackbot status');
                }
                self::show_block_page();
            }

            $this->run_upload_scan();
            $this->run_IDS();
            $this->run_patches();

            if (!self::$_is_search_engine) {
                $this->run_token_bucket();
            }

        }

        protected function is_whitelisted_url()
        {
            if (!empty($_SERVER['REQUEST_URI'])) {
                $url = $_SERVER['REQUEST_URI'];


                $db_params = self::$_db->get_custom_params();

                $db_allowed_urls = $db_params['url'];

                $allowed_urls = array('/admin/', '/wp-admin', 'checkout', 'paypal', '/ipn.php', '/transaction', 'callback', 'contact-form-7', 'wc-ajax=', '/wc-api', '/wp-json', 'api/soap', 'api/v2_soap');

                $merge = array_merge($allowed_urls, $db_allowed_urls);

                //print_r($merge);

                foreach ($merge as $allowed_url) {
                    if (strpos($url, $allowed_url) !== false) {
                        return TRUE;
                    }
                }
            }

            return FALSE;
        }

        public static function show_block_page($attack_param = null)
        {
            if (defined('CZ_ASTRA_MODE') && CZ_ASTRA_MODE == 'monitor') {
                return TRUE;
            }

            if (!headers_sent()) {
                header('HTTP/1.0 403 Forbidden');
            }
            echo_debug('About to show block page');

            if (file_exists(ASTRAPATH . 'block-page-custom.php')) {
                $block_page_path = ASTRAPATH . 'block-page-custom.php';
            } else {
                $block_page_path = ASTRAPATH . 'block-page.php';
            }

            if (!headers_sent()) {
                header('X-XSS-Protection: 1; mode=block');
                header('X-Frame-Options: deny');
                header('X-Content-Type-Options: nosniff');

                /* No cache headers */
                header('X-LiteSpeed-Cache-Control: no-cache');
                header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
                header("Connection: close");
            }

            if (file_exists($block_page_path)) {
                include($block_page_path);
                exit;
                die();
            }

            if (!headers_sent()) {
                header("Location: " . CZ_BLOCK_PAGE_URL); /* Redirect browser */
            } else {
                echo '<script>';
                echo "window.location = '" . CZ_BLOCK_PAGE_URL . "';";
                echo '</script>';
            }
            die("You are blocked");
            exit();
        }

        protected function run_upload_scan()
        {

            if (empty($_FILES)) {
                return true;
            }

            if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                echo_debug('Including mussel');
                $config = new AstraConfig();
                $data['key'] = 'file_uploads';
                $musselResult = $config->get_config($data);
                if ($musselResult) {
                    $phpMusselConfig = json_decode($musselResult, true);
                    if ($phpMusselConfig['status'] == 'TRUE') {
                        require_once(ASTRAPATH . 'libraries/plugins/phpMussel/loader.php');
                    } else {
                        return false;
                    }
                } else { // Run upload scan by default
                    require_once(ASTRAPATH . 'libraries/plugins/phpMussel/loader.php');
                }

            } else {
                echo_debug('PHP version (' . PHP_VERSION . ') < 5.4.0 so not including Mussel');
            }

        }

        protected function run_ids()
        {
            require_once(ASTRAPATH . 'libraries/PHPIDS.php');
            echo_debug('Including IDS');
            $phpids = new PHPIDS(self::$_db, self::$_ip);
        }

        protected function run_patches()
        {
            require_once(ASTRAPATH . 'libraries/Virtual_patches.php');
            $patches = new Astra_virtual_patches();
            $patches->apply();

            $applied_patches = $patches->get_applied_patches();

            if (count($applied_patches) > 0) {
                self::$_db->log_hit(self::$_ip);
                self::show_block_page();
            }
        }

        protected function is_bad_bot()
        {
            $db = self::$_db->is_bad_bot(self::$_user_agent);

            if ($db && !self::$_is_search_engine) {
                return TRUE;
            }

            return FALSE;
        }

        protected function check_search_engine()
        {
            require_once(ASTRAPATH . 'libraries/Bad_bots.php');
            $bot = new Bad_bots(self::$_user_agent, self::$_ip);

            self::$_is_search_engine = $bot->is_search_engine();
            self::$_is_fake_search_engine_bot = $bot->is_fake_bot();

            return true;
        }

        function get_sso_token()
        {
            $time = strtolower(gmdate("FYhiA"));
            $str = CZ_CLIENT_KEY . '|' . CZ_ACCESS_KEY . '|' . $time;
            $token = hash_hmac('sha256', $str, CZ_SECRET_KEY, false);
            return $token;
        }

        protected function run_token_bucket()
        {
            return TRUE;
            require_once(ASTRAPATH . 'libraries/Token_bucket.php');
            Token_bucket::run();
        }

    }

}