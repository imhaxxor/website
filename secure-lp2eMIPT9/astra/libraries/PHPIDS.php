<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PHPIDS
 *
 * @author anandakrishna
 */
require_once(ASTRAPATH . 'Astra.php');

class PHPIDS
{

    public function __construct($db, $ip)
    {
        $this->run();
    }


    protected function encode_items(&$item, $key)
    {
        $orig = $item;

        //$item = iconv("UTF-8", "ASCII//IGNORE", $item);
        $item = iconv("UTF-8", "ISO-8859-1//IGNORE", $item);


        if ($orig !== $item) {
            $item = preg_replace('/[^a-zA-Z0-9]/', '', $item);
            $item = str_replace("--", "", $item);
        }
    }

    protected function get_scan_array()
    {
        $keys_to_scan = defined('CZ_SCAN_ARRAYS') ? explode(',', CZ_SCAN_ARRAYS) : array('GET', 'POST');

        $request = array();

        if (in_array('GET', $keys_to_scan)) {
            $request['GET'] = $_GET;
        }

        if (in_array('POST', $keys_to_scan)) {
            $request['POST'] = $_POST;
        }

        if (in_array('REQUEST', $keys_to_scan)) {
            $request['REQUEST'] = $_REQUEST;
        }

        if (in_array('COOKIE', $keys_to_scan)) {
            $request['COOKIE'] = $_COOKIE;
        }

        return $request;
    }

    protected function is_monitoring_mode($keys_to_scan, $param)
    {
        $which_key = strtok($param, '.');
        return !in_array($which_key, $keys_to_scan);
    }

    public function run()
    {
        if (file_exists(dirname(__FILE__) . '/plugins/IDS/Init.php')) {
            require_once(dirname(__FILE__) . '/plugins/IDS/Init.php');
            require_once(dirname(__FILE__) . '/plugins/IDS/Monitor.php');
        } else {
            return FALSE;
        }

        try {

            $request = $this->get_scan_array();

//            $request = array(
//                'GET' => $_GET,
//                'POST' => $_POST,
//            );

            if (defined('CZ_ALLOW_FOREIGN_CHARS') && CZ_ALLOW_FOREIGN_CHARS == TRUE) {
                array_walk_recursive($request, array($this, 'encode_items'));
            }

            $tmpFolderPath = ASTRAPATH . 'libraries/plugins/IDS/Config/Config.ini.php';

            if (!is_writable($tmpFolderPath)) {
                //chmod($tmpFolderPath, 0775);
            }

            $init = IDS_Init::init(ASTRAPATH . 'libraries/plugins/IDS/Config/Config.ini.php');
            $init->config['General']['base_path'] = ASTRAPATH . 'libraries/plugins/IDS/';
            $init->config['General']['use_base_path'] = true;
            $init->config['Caching']['caching'] = 'none';

            $exceptions = $this->get_exceptions($request);

            $ids = new IDS_Monitor($request, $init);
            $ids->setExceptions($exceptions['exceptions']);
            $ids->setHtml($exceptions['html']);
            $ids->setJson($exceptions['json']);
            echo_debug('IDS Initialized');
            $result = $ids->run();
            echo_debug('IDS has run');

            if (!$result->isEmpty()) {

                if ($result->getImpact() < 10) {
                    return true;
                }

                echo_debug('IDS found issues. Will do +1 now.');
                //Increment Hack Attempt Count

                $str_tags = "";
                $dataArray = array();

                foreach ($result->getTags() as $tag)
                    $str_tags .= '|' . $tag;

                $iterator = $result->getIterator();

                $param = "";
                $attack_param = null;

                $is_monitoring = true;

//                $keys_to_scan = defined('CZ_SCAN_ARRAYS') ? explode(',', CZ_SCAN_ARRAYS) : array('GET', 'POST');

                foreach ($iterator as $threat) {

                    $param = urlencode($threat->getName());

                    /*if (!$this->is_monitoring_mode($keys_to_scan, $param)) {
                        $is_monitoring = false;
                    }*/

                    if (CZ_ASTRA_MODE == 'monitor') {
                        $is_monitoring = TRUE;
                    } else {
                        $is_monitoring = FALSE;
                    }

                    $attack_param[] = urlencode($threat->getName());
                    $param .= "p=" . urlencode($threat->getName()) . "|v=" . urlencode($threat->getValue());
                    $param .= "|id=";

                    foreach ($threat->getFilters() as $filter) {
                        $param .= $filter->getId() . ",";
                    }
                    $param = rtrim($param, ',') . "#";
                }

                if ($is_monitoring) {
                    $dataArray['blocking'] = 3;
                } else {
                    ASTRA::$_db->log_hit(ASTRA::$_ip);
                    $dataArray['blocking'] = (ASTRA::$_db->is_blocked_or_trusted(ASTRA::$_ip, false, ASTRA::$_config) == "blocked") ? 2 : 1;
                }

                $param = rtrim($param, '#');
                $dataArray['i'] = $result->getImpact();
                $dataArray["tags"] = $str_tags;
                $dataArray['param'] = $param;

                require_once(ASTRAPATH . 'libraries/API_connect.php');
                $connect = new API_connect();
                $connect->send_request("ids", $dataArray);

                if (!$is_monitoring) {
                    ASTRA::show_block_page($attack_param);
                }

            } else {
                echo_debug('IDS says all is okay');
            }
        } catch (Exception $e) {
            printf('An error occured: %s', $e->getMessage());
        }
    }

    protected function get_exceptions($request = array())
    {

        $data = array('exceptions' => array(), 'json' => array(), 'html' => array());
        $db_params = ASTRA::$_db->get_custom_params();

        $default_exceptions = array(
            'REQUEST._pk_ref_3_913b',
            'COOKIE._pk_ref_3_913b',
            'REQUEST._lf',
            'REQUEST.comment',
            'POST.comment',
            'REQUEST.permalink_structure',
            'POST.permalink_structure',
            'REQUEST.selection',
            'POST.selection',
            'REQUEST.content',
            'POST.content',
            'REQUEST.__utmz',
            'COOKIE.__utmz',
            'REQUEST.s_pers',
            'COOKIE.s_pers',
            'REQUEST.user_pass',
            'POST.user_pass',
            'REQUEST.pass1',
            'POST.pass1',
            'REQUEST.pass2',
            'POST.pass2',
            'REQUEST.password',
            'POST.password',
            'GET.gclid',
            'GET.access_token',
            'POST.customize',
            'POST.post_data',
            'POST.mail.body',
            'POST.mail.subject',
            'POST.mail.sender',
            'POST.form',
            'POST.customized',
            'POST.partials',
            'GET.mc_id',
            'POST.shortcode',
            'POST.mail_2.body',
            'POST.mail_2.subject',
            'POST.enquiry',
            'POST.pwd',
            'POST.g-recaptcha-response',
            'POST.g-recaptcha-res',
            '/POST.state_([0-999]*)$/',
            '/POST.input_([0-999]*)$/',
            'POST.form_token',
            'GET.fbclid',
            'POST.itsec_two_factor_on_board_data',
            'GET.mainwpsignature',
            'POST.wp_statistics_hit',
            'POST.customize_messenger_channel',
            'POST.product.media_gallery.values',
            'GET.utm_source',
            'GET.utm_medium',
            'GET.utm_campaign',
            'POST.fl_builder_data.data.settings.content',
            '/POST.fl_builder_data.node_preview.items.([0-999]*)$/',
            'POST.fl_builder_data.settings.text',
            '/POST.fl_builder_data.*/',

        );


        $data['exceptions'] = array_merge($default_exceptions, $db_params['exception']);
        $data['html'] = $db_params['html'];

        //if (!defined('CZ_JSON_EXCLUDE_ALL') || CZ_JSON_EXCLUDE_ALL === FALSE) {
        //    $data['json'] = $db_params['json'];
        //} else {
            $data['json'] = array();

            foreach ($request as $array => $keys) {
                foreach ($keys as $k => $par) {
                    $data['json'][] = "$array.$k";
                }
            }

        //}

        return $data;
    }

}
