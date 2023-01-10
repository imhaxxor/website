<?php
/**
 * Plugin logtoastra for phpMussel.
 *
 * PLUGIN INFORMATION BEGIN
 *         Plugin Name: Astra PhpMussel.
 *       Plugin Author: Sumit Sharma.
 *      Plugin Version: 1.0.0
 *    Download Address:
 *     Min. Compatible: 1.0.0-DEV
 *     Max. Compatible: -
 *        Tested up to: 1.0.0-DEV
 *       Last Modified: 2019.02.25
 * PLUGIN INFORMATION END
 *
 * This plugin can be used to write the logging information to a astra database.
 */
/**
 * Prevents direct access (the plugin should only be called from the phpMussel
 * plugin system).
 */
//$phpMussel['Config']['files']['filesize_limit'] = '1MB';

if (!defined('phpMussel')) {
    die('[phpMussel] This should not be accessed directly.');
}



$config = new AstraConfig();
$data['key'] = 'file_uploads';
$musselResult = $config->get_config($data);
if($musselResult){
    $phpMusselConfig = json_decode($musselResult,true);
    $phpMussel['Config']['files']['filesize_limit'] = ($phpMusselConfig['filesize']) ? $phpMusselConfig['filesize'].'MB' : $phpMussel['Config']['files']['filesize_limit'];
    $phpMussel['Config']['files']['filetype_whitelist'] = isset($phpMusselConfig['allowed']) ? $phpMussel['Config']['files']['filetype_whitelist'].','.$phpMusselConfig['allowed'] : $phpMussel['Config']['files']['filetype_whitelist'];
    $phpMussel['Config']['files']['filetype_blacklist'] = isset($phpMusselConfig['blocked']) ? $phpMussel['Config']['files']['filetype_blacklist'].','.$phpMusselConfig['blocked'] : $phpMussel['Config']['files']['filetype_blacklist'];
}

/**
 * Registers the `$phpMussel_logtoastra` closure to the `before_html_out`
 * hook.
 */

$phpMussel['Register_Hook']('phpMussel_logtoastra', 'before_html_out');


/**
 * @return bool Returns true if everything is working correctly.
 */
$GLOBALS['phpMussel_logtoastra'] = $phpMussel_logtoastra = function () use (&$phpMussel) {

    $checkDuplicatInclude =  get_included_files();
    
    if(array_search(ASTRAPATH.'astra-config.php', $checkDuplicatInclude)){
    }else{
        require_once(ASTRAPATH.'astra-config.php');
    }
    
    $fileData = null;
    foreach($phpMussel['upload']['FilesData']['FileSet'] as $key => $value){
        $fileData[$key][] = $value[0];
    }
    require_once(ASTRAPATH.'libraries/API_connect.php');
    $connect = new API_connect();

    $dataArray['ip'] = $_SERVER[$phpMussel['Config']['general']['ipaddr']];
    $dataArray['hash'] = $phpMussel['killdata'];
    $dataArray['error'] = trim($phpMussel['whyflagged']);
    $dataArray['filedata'] = json_encode($fileData);
    $dataArray['param'] = 'FILES.'.$phpMussel['upload']['FilesData']['k'];

    $connect->send_request("phpMussel", $dataArray);
    return true;
};
