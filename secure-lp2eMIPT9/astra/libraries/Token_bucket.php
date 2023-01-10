<?php

/**
 * Description of Token_bucket
 *
 * @author anandakrishna
 */
if (!defined('ASTRAPATH'))
    define('ASTRAPATH', dirname(__FILE__) . '/');

if (!class_exists('Token_bucket')) {

    class Token_bucket {

        protected static $_minute;
        protected static $_minute_limit;

        function __construct() {
            
        }

        public static function run() {

            require_once(ASTRAPATH . 'Astra.php');

            self::$_minute = ASTRA::$cz_lvl[CZ_CONFIG_LVL]['token_bucket']['minute'];
            self::$_minute_limit = ASTRA::$cz_lvl[CZ_CONFIG_LVL]['token_bucket']['minute_limit']; #users are limited to 100 requests/minute

            ASTRA::connect_db();

            $get_api_request = ASTRA::$_db->get_api_request(ASTRA::$_ip);

            //print_r($get_api_request);

            $last_api_request = $get_api_request['last_api_request']; # get from the DB; in epoch seconds
            $last_api_diff = time() - $last_api_request; # in seconds
            $minute_throttle = $get_api_request['throttle_minute']; # get from the DB
            $reported = isset($get_api_request['reported']) ? $get_api_request['reported'] : 0;

            if (is_null(self::$_minute_limit)) {
                $new_minute_throttle = 0;
            } else {
                $new_minute_throttle = $minute_throttle - $last_api_diff;
                $new_minute_throttle = $new_minute_throttle < 0 ? 0 : $new_minute_throttle;
                $new_minute_throttle += self::$_minute / self::$_minute_limit;
                $minute_hits_remaining = floor(( self::$_minute - $new_minute_throttle ) * self::$_minute_limit / self::$_minute);
                # can output this value with the request if desired:
                $minute_hits_remaining = $minute_hits_remaining >= 0 ? $minute_hits_remaining : 0;
                $reported = 0;
            }

            if ($new_minute_throttle > self::$_minute) {
                $wait = ceil($new_minute_throttle - self::$_minute);
                if (!$reported) {
                    /* Send API hit */
                    require_once(ASTRAPATH . 'libraries/API_connect.php');
                    $connect = new API_connect();
                    $reported = (int) $connect->send_request("token_bucket", array('blocking' => 2));
                }
                usleep(250000);
                /**/

                die('The one-minute Request limit of ' . self::$_minute_limit . ' requests has been exceeded. Please wait ' . $wait . ' seconds before attempting again.');
            }

            # Save the values back to the database.
            $data = array(
                'last_api_request' => time(),
            );

            ASTRA::$_db->save_api_request(ASTRA::$_ip, time(), $new_minute_throttle, $reported);
        }

    }

}