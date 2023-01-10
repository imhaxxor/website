<?php
if (!defined('ASTRAPATH')) {

    define('ASTRAPATH', dirname(__FILE__) . '/');

}



$files_to_delete = array('abeo.txt');

foreach ($files_to_delete as $file) {

    if (file_exists(ASTRAPATH . $file)) {

        unlink(ASTRAPATH . $file);        

    }

}
?>