<?php
/**
 *
 *
 * @author Ananda Krishna <ak@getastra.com>
 * @date   2019-04-15
 */

class Astra_setup
{

    private $basePath;
    private $filesCreated = false;

    public function getFilesCreated()
    {
        return $this->filesCreated;
    }

    public function createAstraFiles()
    {
        $folderPath = $this->getBasePath();

        if ($folderPath == false || !file_exists($folderPath)) {
            return false;
        }

        $astraPath = rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'astra' . DIRECTORY_SEPARATOR;

        if (!file_exists($astraPath) && !is_dir($astraPath) && is_writable($folderPath)) {
            mkdir($astraPath, 0755);
        }

        if (file_exists($astraPath)) {
            $this->createFiles($folderPath, $astraPath);
        }


    }

    public function getRootApiFilePath()
    {
        $folderPath = $this->getBasePath();

        if ($folderPath == false || !file_exists($folderPath)) {
            return false;
        }

        $astraPath = rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'astra' . DIRECTORY_SEPARATOR;
        return $astraPath . 'api.php';
    }

    public function getRootApiFileUri()
    {
        $folderPath = $this->getBasePath();

        if ($folderPath == false || !file_exists($folderPath)) {
            return false;
        }

        $astraPath = rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'astra' . DIRECTORY_SEPARATOR;
        $astraPath = str_replace('\\', '/', $astraPath);

        return $astraPath . 'api.php';
    }

    public function getRealApiFileUri(){

    }

    public function createFiles($folderPath, $astraPath)
    {

        $dir = $this->processPath(dirname(__DIR__));

        $files = array('api.php', 'ak.php', 'gk.php');
        $relPath = $this->getRelativePluginFolderPath();

        foreach ($files as $file) {
            $content = "";
            $content = <<<EDT
<?php

\$astraPath = getcwd() . "/../{$relPath}/{$file}";
if(file_exists(\$astraPath)){
	include_once(\$astraPath);
}
EDT;

            if (!file_exists($astraPath . $file)) {
                @file_put_contents($astraPath . $file, $content);
            }

            if (file_exists($astraPath . $file)) {
                $this->filesCreated = true;
            }

        }
    }

    public function getRelativePluginFolderPath()
    {
        $base = $this->getBasePath();
        $dir = $this->processPath(__DIR__);
        $rel = dirname(str_replace($base, "", $dir));

        return $rel;
    }

    protected function processPath($path)
    {
        $isBitnami = strpos($path, "bitnami") !== false;
        $isOpt = strpos($path, "/opt/") !== false;

        if ($isBitnami && !$isOpt) {
            return DIRECTORY_SEPARATOR . 'opt' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }

        return $path;
    }

    public function getBasePath()
    {

        if (!empty($basePath)) {
            return $basePath;
        }

        $path = $this->processPath(__FILE__);

        $basePath = "";
        $possibleBreakWords = array('/wp-content/', '/catalog/controller/extension/', '/sites/all/modules/', '/modules/', '/app/code/', '/astra/');

        foreach ($possibleBreakWords as $word) {
            if (strpos($path, $word) !== false) {
                $basePath = strstr($path, $word, true) . DIRECTORY_SEPARATOR;
                break;
            }
        }

        if (empty($basePath)) {
            $basePath = false;
        }

        $this->basePath = $basePath;

        return $basePath;
    }

}