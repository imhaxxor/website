<?php

if (!function_exists('Update_config')) {

    function Update_config($str_constant, $str_replace, $check_for_quotes = TRUE) {

        if (base64_decode($str_replace, true))
            $str_replace = base64_decode($str_replace);
        else
            return FALSE;

        $first_char = substr($str_replace, 0, 1);
        $last_char = substr($str_replace, -1);
        $allowed = array("'", '"');

        if ($check_for_quotes == TRUE) {
            if (in_array($first_char, $allowed) && in_array($last_char, $allowed)) {

            } else {
                return FALSE;
            }
        }

        $path_to_astra_config = CZ_CONFIG_PATH;

        if (file_exists($path_to_astra_config))
            $config_file = file($path_to_astra_config);
        else
            return FALSE;

        if(count($config_file) < 4){
            $dirty = file_get_contents($path_to_astra_config);
            $config_file = preg_split("/\\r\\n|\\r|\\n/", $dirty);
        }

        foreach ($config_file as $line_num => $line) {
            $line_trim = trim($line);
            $str_define = strpos($line_trim, 'define("');

            if ($str_define === false || $str_define != 0)
                continue;

            $line_temp = str_replace('define(', '', $line_trim);
            $line_temp = str_replace(');', '', $line_temp);

            $matches = explode(",", $line_temp);
            $constant = $matches[0];
            $padding = $matches[1];

            $constant = str_replace('"', "", $constant);

            json_decode(substr($str_replace, 1, -1));
            if (json_last_error() == 0) {
                $data['key'] = 'file_uploads';
                $config = new AstraConfig();
                if($config->get_config($data)){
                    $data['value'] =  "";
                    $config->delete_config($data);
                    $data['value'] = json_decode(substr($str_replace, 1, -1));
                    $data['autoload'] = 1;
                    $config->add_config($data);
                }else{
                    $data['value'] = json_decode(substr($str_replace, 1, -1));
                    $data['autoload'] = 1;
                    $config->add_config($data);
                }


            }else{

                if ($constant == $str_constant) {
                    $config_file[$line_num] = 'define("' . $constant . '", ' . $str_replace . ');' . "\r\n";
                    break;
                } else {

                }

            }
        }

        $handle = fopen($path_to_astra_config, 'w');
        foreach ($config_file as $line) {
            fwrite($handle, rtrim($line) . "\r\n");
        }
        fclose($handle);
        chmod($path_to_astra_config, 0666);

        return TRUE;
    }

}