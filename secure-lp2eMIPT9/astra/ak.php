<?php

if (function_exists("opcache_reset")) {
    opcache_reset();
}

if (!class_exists('Astra_ak')) {
    class Astra_ak
    {
        function auth()
        {
            $rcvd = !empty($_REQUEST['key']) ? $_REQUEST['key'] : "";
            $expected = $this->get_sso_key();

            $stored = $this->get_session_key();

            if ($stored === $expected || $rcvd === $expected) {
                $this->store_key($expected);
                unset($_GET['key']);
                return true;
            }
            header("HTTP/1.0 404 Not Found");
            exit;
        }

        protected function get_session_key()
        {

            if (function_exists('session_status')) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
            } else {
                if (session_id() == '') {
                    session_start();
                }
            }

            $key = !empty($_SESSION['astra_ak_key']) ? $_SESSION['astra_ak_key'] : "";

            return $key;
        }

        protected function store_key($key)
        {
            $_SESSION['astra_ak_key'] = $key;
        }

        protected function get_sso_key()
        {
            define("CZ_DEBUG", TRUE);
            require_once __DIR__ . '/astra-config.php';
            $time = strtolower(gmdate("FYhiA"));
            $str = CZ_CLIENT_KEY . '|' . CZ_ACCESS_KEY . '|' . $time;
            $token = hash_hmac('sha256', $str, CZ_SECRET_KEY, false);
            return $token;
        }

        public function __construct()
        {
            if ($this->auth()) {
                $this->load();
            }
        }

        function load()
        {
            require __DIR__ . '/Astra.php';
            $astra = new Astra();
        }
    }

    new Astra_ak;
}