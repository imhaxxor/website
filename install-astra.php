<?php


/*
 * error_reporting(E_ALL);
 * ini_set('display_errors', 1);
 */

if (function_exists("opcache_reset")) {
    opcache_reset();
}

class Deploy
{

    private $data_path;
    private $websites;
    private $response;
    private $log;
    private $log_count = 1;
    private $installed = false;

    function __construct()
    {
        $this->data_path = "astra-install.json";

        $this->response = array(
            'success' => false,
            'msg' => "",
        );

        $this->log = "";

        $this->run();
    }

    function __destruct()
    {
        //$this->print_log();
    }

    protected function log($msg)
    {

        $this->log .= "<p class='line'>" . $msg . "</p>\r\n";
        $this->log_count++;
    }

    public function getLogs()
    {
        return $this->log;
    }

    public function getInstalled(){
        return file_exists('astra/astra-inc.php') || $this->installed;
    }

    protected function print_log()
    {
        echo $this->log;
    }

    protected function respond($success, $msg = '')
    {
        $this->response['success'] = $success;
        if (!empty($msg)) {
            $this->response['msg'] = $msg;
        }

        header('Content-Type: application/json');
        echo json_encode($this->response, JSON_PRETTY_PRINT);
        exit;
    }

    protected function load_json()
    {
        if (file_exists($this->data_path)) {
            $string = file_get_contents($this->data_path);
            $json_data = json_decode($string, true);
        }

        if (!empty($json_data) && is_array($json_data)) {
            return $json_data;
        }

        return array();
    }

    protected function save_json()
    {
        return true;
        $saved = file_put_contents($this->data_path, json_encode($this->websites));
        return is_int($saved);
    }

    public function is_astra_installed($path)
    {
        return file_exists($path . 'astra');
    }

    protected function clean_install($path)
    {
        $zip_path = $this->get_astra_zip_path($path);
        unlink($zip_path);

        $this->log('Deleting the ZIP file.');

        return !file_exists($zip_path);
    }

    protected function do_install($path = '')
    {

        $key = 0;
        $this->websites[$key]['installed_astra'] = FALSE;

        if ($this->is_astra_installed($path)) {
            $this->log("Astra already installed. Nothing to do! :)");
            return $this->websites[$key];
        }


        $this->log("-----------");
        $this->log("Installing for " . $_SERVER['HTTP_HOST']);


        $function_name = "install_default";
        if (method_exists($this, $function_name)) {
            $installed = $this->$function_name($path);
            if ($installed == true) {
                $this->websites[$key]['installed_astra'] = TRUE;

                $this->log("Installed for $path");

                //$this->respond(true, 'Installed for ' . $website['user']);
            } else {
                $this->websites[$key]['installed_astra'] = FALSE;
                $this->websites[$key]['reason_for_failure'] = $installed;
                $this->log("Unable to install. Please email <a href='mailto:help@getastra.com'>help@getastra.com</a> if you need help");
                //$this->respond(false, 'Unable to install for ' . $website['user']);
            }


        }

        return $this->websites[$key];
    }


    protected function meets_requirements() {
        if (!defined('PDO::ATTR_DRIVER_NAME')) {
            $this->log('PDO Driver Missing. Please install or enable in cPanel.');
            return FALSE;
        }

        if(!class_exists('ZipArchive')){
            $this->log('ZipArchive Default Class is missing. Please install or enable in cPanel.');
            return FALSE;
        }

        if (!extension_loaded('sqlite3')) {
            $this->log('sqlite3 extension not loaded. Please install or enable in cPanel. <a href="https://www.getastra.com/kb/?s=sqlite" target="_blank" style="color: #fff!important;">Find installation guides here.</a>');
            return FALSE;
        }

        $pdo_drivers = PDO::getAvailableDrivers();

        if(!is_array($pdo_drivers) || !in_array('sqlite', $pdo_drivers)){
            $this->log('pdo_sqlite extension not loaded. Please install or enable in cPanel. <a href="https://www.getastra.com/kb/?s=sqlite" target="_blank" style="color: #fff!important;">Find installation guides here.</a>');
        }

        return TRUE;
    }

    protected function is_astra_included_in_user_ini($file_path)
    {
        if (!file_exists($file_path)) {
            return false;
        }

        $contents = file_get_contents($file_path);

        if (strpos($contents, 'astra-inc') !== false) {
            return true;
        }

        return false;
    }

    protected function update_user_ini($path, $astra_inc_path)
    {
        $path_user_ini = $path . '.user.ini';

        if ($this->is_astra_included_in_user_ini($path_user_ini)) {
            return true;
        }

        $file_content = "auto_prepend_file=$astra_inc_path\n\r";

        $write = false;


        if (!file_exists($path_user_ini)) {
            $write = file_put_contents($path_user_ini, $file_content);
        } else {
            $old_contents = file_get_contents($path_user_ini);
            $file_content .= "\r\n" . $old_contents;
            $write = file_put_contents($path_user_ini, $file_content);
            $this->log("Updated existing .user.ini");
        }

        return $write;
    }

    protected function install_default($path, $plugin_folder_path = '')
    {
        if (empty($plugin_folder_path)) {
            $plugin_folder_path = $path;
        }

        $plugin_zip_path = $this->get_astra_zip_path($path);
        $username = "";

        if (file_exists($plugin_zip_path . 'astra')) {
            $this->log("Astra is already installed");
            return false;
        }

        if (file_exists($plugin_folder_path) && is_writable($plugin_folder_path)) {

            if (!empty($plugin_zip_path) && file_exists($plugin_zip_path)) {
                if ($this->is_valid_zip($plugin_zip_path)) {
                    $extracted = $this->extract_zip($plugin_zip_path, $plugin_folder_path);

                    if ($extracted) {
                        if ($this->update_user_ini($path, $plugin_folder_path . 'astra/astra-inc.php')) {
                            return true;
                        } else {
                            $this->log("Unable to update the .user.ini file for $path");
                            return false;
                        }
                    } else {
                        $this->log( "Unable to extract the ZIP.");
                        return false;
                    }
                } else {
                    $this->log($this->response['msg']);
                    return false;
                }
            } else {
                $this->log("Astra ZIP does (secure-*.zip) not exist.<br/>Can you please check?");
                return false;
            }
        } else {
            $this->log( "Folder does not exist or isn't writable ($plugin_folder_path)");
            return false;
        }
    }

    protected function get_astra_zip_path($path)
    {
        $matches = glob($path . 'secure-*.zip');

        $file_name = "";
        if (!empty($matches[0])) {
            $file_name = str_replace("./", "/", $matches[0]);
        }

        //$this->log("ZIP file path: " . $file_name);

        return $file_name;
    }

    protected function extract_zip($zip_path, $extract_to)
    {
        $zip = new ZipArchive;
        if ($zip->open($zip_path) === TRUE) {
            $extracted = $zip->extractTo($extract_to);
            $zip->close();

            return $extracted;
        }
        return FALSE;
    }

    protected function is_valid_zip($filename)
    {

        $zip = new ZipArchive;

        $res = $zip->open($filename, ZipArchive::CHECKCONS);

        if ($res !== TRUE) {
            switch ($res) {
                case ZipArchive::ER_NOZIP :
                    $ret = FALSE;
                    $this->response['msg'] = "Invalid ZIP - Not a zip archive";
                case ZipArchive::ER_INCONS :
                    $ret = FALSE;
                    $this->response['msg'] = "Invalid ZIP - Consistency check failed";
                case ZipArchive::ER_CRC :
                    $this->response['msg'] = "Invalid ZIP - Error with CRC";
                    $ret = FALSE;
                default :
                    $this->response['msg'] = "Invalid ZIP - Checksum failed";
                    $ret = FALSE;
            }

            if ($ret) {
                $zip->close();
            }
            return $ret;
        } else {
            return TRUE;
        }
    }

    function run()
    {
        $path = getcwd() . "/";
        $this->log("Path to be secured: " . $path);

        $should_install = isset($_GET['complete']);

        if ($should_install && $this->meets_requirements()) {
            $website = $this->do_install($path);

            if ($website['installed_astra'] === true) {
                $this->clean_install($path);
                $this->log('Deleting Installer');
                $this->installed = true;
                unlink(__FILE__);
            }
        }

    }
}

$deploy = new Deploy();
$installed = $deploy->getInstalled();
$logs = $deploy->getLogs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Astra Installer</title>
    <style>
        .text-center {
            text-align: center;
        }

        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            border: 1px solid transparent;
            padding: .375rem .75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: .25rem;
            transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .btn-group-lg > .btn, .btn-lg {
            padding: .5rem 1rem;
            font-size: 1.25rem;
            line-height: 1.5;
            border-radius: .3rem;
        }

        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-danger {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-success {
            color: #fff;

            background-image: linear-gradient(to right, #075fc9 0%, #363795 51%, #075fc9 100%);
            transition: 0.5s;
            background-size: 200% auto;

        }

        .btn-success:hover {
            background-position: right center;
            color: #fff;
        }

        .btn:not(:disabled):not(.disabled) {
            cursor: pointer;
        }

        .btn-danger:hover {
            color: #fff;
            background-color: #c82333;
            border-color: #bd2130;
        }

        .container {
            height: 80vh;
            position: relative;
        }

        .center {
            margin: 0;
            position: absolute;
            top: 50%;
            left: 50%;
            -ms-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
        }

        .text-muted {
            color: #6c757d !important;
        }

        .form-text {
            display: block;
            margin-top: .25rem;
        }

        .small, small {
            font-size: 80%;
            font-weight: 400;
        }

        .block {
            display: block;
        }


        body {
            /*background-color: #272727;*/
            padding: 10px;
        }

        .fakeButtons {
            height: 10px;
            width: 10px;
            border-radius: 50%;
            border: 1px solid #000;
            position: relative;
            top: 6px;
            left: 6px;
            background-color: #ff3b47;
            border-color: #9d252b;
            display: inline-block;
        }

        .fakeMinimize {
            left: 11px;
            background-color: #ffc100;
            border-color: #9d802c;
        }

        .fakeZoom {
            left: 16px;
            background-color: #00d742;
            border-color: #049931;
        }

        .fakeMenu {
            width: 550px;
            box-sizing: border-box;
            height: 25px;
            background-color: #bbb;
            margin: 0 auto;
            border-top-right-radius: 5px;
            border-top-left-radius: 5px;
        }

        .fakeScreen {
            background-color: #151515;
            box-sizing: border-box;
            width: 550px;
            margin: 0 auto;
            padding: 20px;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
        }

        p {
            /*
            position: relative;
            left: 50%;
            margin-left: -8.5em;
            */
            text-align: left;
            font-size: 1.25em;
            font-family: monospace;
            white-space: normal;
            /*overflow: hidden;*/
            /*width: 0;*/
        }

        span {
            color: #fff;
            font-weight: bold;
        }

        .line {
            color: #9CD9F0;
            -webkit-animation: type .5s 1s steps(20, end) forwards;
            -moz-animation: type .5s 1s steps(20, end) forwards;
            -o-animation: type .5s 1s steps(20, end) forwards;
            animation: type .5s 1s steps(20, end) forwards;
        }

        .cursor {

        }

        .line:nth-child(even) {
            color: #9CD9F0;
            -webkit-animation: type .5s 1s steps(20, end) forwards;
            -moz-animation: type .5s 1s steps(20, end) forwards;
            -o-animation: type .5s 1s steps(20, end) forwards;
            animation: type .5s 1s steps(20, end) forwards;
        }

        .line:nth-child(odd) {
            color: #CDEE69;
            -webkit-animation: type .5s 4.25s steps(20, end) forwards;
            -moz-animation: type .5s 4.25s steps(20, end) forwards;
            -o-animation: type .5s 4.25s steps(20, end) forwards;
            animation: type .5s 4.25s steps(20, end) forwards;
        }

        .line1 {
            color: #9CD9F0;
            -webkit-animation: type .5s 1s steps(20, end) forwards;
            -moz-animation: type .5s 1s steps(20, end) forwards;
            -o-animation: type .5s 1s steps(20, end) forwards;
            animation: type .5s 1s steps(20, end) forwards;
        }

        .cursor1 {
            -webkit-animation: blink 1s 2s 2 forwards;
            -moz-animation: blink 1s 2s 2 forwards;
            -o-animation: blink 1s 2s 2 forwards;
            animation: blink 1s 2s 2 forwards;
        }

        .line2 {
            color: #CDEE69;
            -webkit-animation: type .5s 4.25s steps(20, end) forwards;
            -moz-animation: type .5s 4.25s steps(20, end) forwards;
            -o-animation: type .5s 4.25s steps(20, end) forwards;
            animation: type .5s 4.25s steps(20, end) forwards;
        }

        .cursor2 {
            -webkit-animation: blink 1s 5.25s 2 forwards;
            -moz-animation: blink 1s 5.25s 2 forwards;
            -o-animation: blink 1s 5.25s 2 forwards;
            animation: blink 1s 5.25s 2 forwards;
        }

        .line3 {
            color: #E09690;
            -webkit-animation: type .5s 7.5s steps(20, end) forwards;
            -moz-animation: type .5s 7.5s steps(20, end) forwards;
            -o-animation: type .5s 7.5s steps(20, end) forwards;
            animation: type .5s 7.5s steps(20, end) forwards;
        }

        .cursor3 {
            -webkit-animation: blink 1s 8.5s 2 forwards;
            -moz-animation: blink 1s 8.5s 2 forwards;
            -o-animation: blink 1s 8.5s 2 forwards;
            animation: blink 1s 8.5s 2 forwards;
        }

        .line4 {
            color: #fff;
            -webkit-animation: type .5s 10.75s steps(20, end) forwards;
            -moz-animation: type .5s 10.75s steps(20, end) forwards;
            -o-animation: type .5s 10.75s steps(20, end) forwards;
            animation: type .5s 10.75s steps(20, end) forwards;
        }

        .cursor4 {
            -webkit-animation: blink 1s 11.5s infinite;
            -moz-animation: blink 1s 8.5s infinite;
            -o-animation: blink 1s 8.5s infinite;
            animation: blink 1s 8.5s infinite;
        }

        @-webkit-keyframes blink {
            0% {
                opacity: 0;
            }
            40% {
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }

        @-moz-keyframes blink {
            0% {
                opacity: 0;
            }
            40% {
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }

        @-o-keyframes blink {
            0% {
                opacity: 0;
            }
            40% {
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }

        @keyframes blink {
            0% {
                opacity: 0;
            }
            40% {
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }

        @-webkit-keyframes type {
            to {
                width: 27em;
            }
        }

        @-moz-keyframes type {
            to {
                width: 27em;
            }
        }

        @-o-keyframes type {
            to {
                width: 27em;
            }
        }

        @keyframes type {
            to {
                width: 27em;
            }
        }

    </style>
</head>
<body>

<div class="container">
    <div class="center">
        <div class="text-center">
            <img style="height: 150px;" src="data:image/png;base64,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"/>
            <?php if (!empty(trim($logs))) { ?>
                <div class=fakeMenu>
                    <div class="fakeButtons fakeClose"></div>
                    <div class="fakeButtons fakeMinimize"></div>
                    <div class="fakeButtons fakeZoom"></div>
                </div>
                <div class="fakeScreen">
                    <?php echo($logs); ?>
                    <p class="line4">><span class="cursor4">_</span></p>
                </div>
            <?php } ?>
            <br/><br/>
            <?php if(!$installed) { ?>
                <form method="GET">
                    <input type="hidden" name="complete"/>
                    <input type="submit"
                           value="<?php echo !isset($_GET['complete']) ? "Complete Astra Installation" : "Retry Astra Installation" ?>"
                           class="btn btn-lg btn-submit btn-success"/>
                    <span class="text-muted small block form-text">PHP Version: <?php echo phpversion(); ?></span>
                </form>
            <?php } else { ?>
                <h2 style="text-align: center; color: green; font-family: Trebuchet MS,Lucida Grande,Lucida Sans Unicode,Lucida Sans,Tahoma,sans-serif; ">Installation complete!</h2>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>