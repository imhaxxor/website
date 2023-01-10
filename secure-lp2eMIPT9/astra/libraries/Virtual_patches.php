<?php
/**
 *
 *
 * @author Ananda Krishna <ak@getastra.com>
 * @date   12/5/19
 */

if (!class_exists('Astra_virtual_patches')) {

    class Astra_virtual_patches
    {

        protected $url;
        protected $applied_patches = array();

        function __construct()
        {
            if (!empty($_SERVER['REQUEST_URI'])) {
                $this->url = $_SERVER['REQUEST_URI'];
            }
        }

        function apply()
        {

            $methods = preg_grep('/^patch_/', get_class_methods($this));
            foreach ($methods as $method) {
                $is_applied = $this->{$method}();
                if ($is_applied === TRUE) {
                    $this->applied_patches[] = $method;
                }
            }

            //print_r($this->applied_patches);
        }

        function get_applied_patches()
        {
            return $this->applied_patches;
        }

        function url_contains($slug = '')
        {
            return (false !== strpos($this->url, $slug)) ? TRUE : FALSE;
        }

        function string_after($haystack, $needle)
        {
            return substr($haystack, strpos($haystack, $needle) + strlen($needle));
        }

        function patch_magento_smartwave_quickview()
        {

            if (!$this->url_contains('quickview/index/view/path')) {
                return false;
            }

            $string_after = $this->string_after($this->url, 'quickview/index/view/path');
            if ($this->url_contains(';') || strlen($string_after) > 3) {
                return true;
            }

        }
    }
}