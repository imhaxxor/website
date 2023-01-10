<?php 

/**
 * Class AstraConfig
 */
if (!class_exists('AstraConfig')) {
    Class AstraConfig 
    {
        private $_config = array();

        private $_blocked_countries = null;
        private $_trusted_countries = null;

        function __construct(){
            ASTRA::connect_db();
            
            $this->_autoload_config();
        }

        protected function _autoload_config()
        {
            $rows = ASTRA::$_db->get_config_autoload();
            foreach($rows as $row){
                $this->_config[$row['config_key']] = $row['config_value'];
            }
        }

        function get_config($data)
        {
            if(isset($this->_config[$data['key']])){
                return $this->_config[$data['key']];
            }
    
            $key_value = ASTRA::$_db->get_config($data['key']);

            $this->_config[$data['key']] = $key_value; 
            return $key_value;
        }

        function add_config($data)
        {
            $key_value = ASTRA::$_db->add_config($data['key'],$data['value'],$data['autoload']);

            return $key_value;
        }

        function add_config_value($data)
        {
            $key_value = ASTRA::$_db->add_config_value($data['key'],$data['value']);

            return $key_value;
        }

        function delete_config($data)
        {
        
            $key_value = ASTRA::$_db->delete_config($data['key'],$data['value']);

            return $key_value;
        }

        function delete_config_value($data)
        {
        
            $key_value = ASTRA::$_db->delete_config_value($data['key'],$data['value']);

            return $key_value;
        }

        function update_config($data)
        {
            $key_value = ASTRA::$_db->update_config($data['key'],$data['value'],$data['autoload']);

            return $key_value;
        }

        function update_country($data)
        {
            $key_value = ASTRA::$_db->update_country($data['key'],$data['value']);

            return $key_value;
        }
    }
}