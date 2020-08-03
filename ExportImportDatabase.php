<?php 

/** 
* ExportImportDatabase
* Export and Import Mysql
*
* Made by phatnt93
* 03/08/2020
* 
* @license MIT License
* @author phatnt <thanhphat.uit@gmail.com>
* @github https://github.com/phatnt93/export-import-mysql
* @version 1.0.0
* 
*/

if (!defined('EX_BASE_DIR')) {
    define('EX_BASE_DIR', __DIR__);
}

/**
 * ExportImportDatabase
 */
class ExportImportDatabase
{
    private $exportDirPath = EX_BASE_DIR . DIRECTORY_SEPARATOR . 'export_db';
    private $logDirPath = EX_BASE_DIR . DIRECTORY_SEPARATOR . 'logs';
    private $options = [
        'export' => [
            'excludes_db' => 'phpmyadmin, test, mysql, information_schema, performance_schema',
            'export_databases' => '',
            'db_host' => 'localhost',
            'db_user' => 'root',
            'db_pass' => '',
            'mysqldump_path' => 'mysqldump',
        ],
        'import' => [
            'db_host' => 'localhost',
            'db_user' => 'root',
            'db_pass' => '',
            'dir_name' => '',
            'mysql_path' => 'mysql'
        ]
    ];
    private $dbExport = null;
    private $dbImport = null;
    private $excludesDb = [];
    private $exportDatabases = [];
    private $mysqldumpPath = '';
    private $mysqlPath = '';

    function __construct($options = []){
        if (isset($options['export'])) {
            $this->options['export'] = array_merge($this->options['export'], $options['export']);
        }
        if (isset($options['import'])) {
            $this->options['import'] = array_merge($this->options['import'], $options['import']);
        }

        // Create log dir
        $resLogDir = $this->create_dir($this->logDirPath);
        if ($resLogDir !== true) {
            die($resLogDir);
        }
    }

    private function setup_export(){
        $this->excludesDb = $this->explode_string($this->options['export']['excludes_db'], ',');
        $this->exportDatabases = $this->explode_string($this->options['export']['export_databases'], ',');

        if ($this->isWindow()) {
            if (empty($this->options['export']['mysqldump_path'])) {
                die("If you run on window. You have to config mysqldump path");
            }
            if (!file_exists($this->options['export']['mysqldump_path'])) {
                die("Mysqldump file was not found");
            }
        }
        $this->mysqldumpPath = $this->options['export']['mysqldump_path'];
    }

    private function setup_import(){
        if ($this->isWindow()) {
            if (empty($this->options['import']['mysql_path'])) {
                die("If you run on window. You have to config mysql path");
            }
            if (!file_exists($this->options['import']['mysql_path'])) {
                die("Mysql file was not found");
            }
        }
        $this->mysqlPath = $this->options['import']['mysql_path'];
    }

    private function explode_string($str, $flag){
        $res = [];
        $arr = array_filter(explode($flag, $str));
        foreach ($arr as $key => $value) {
            $res[] = trim($value);
        }
        return $res;
    }

    private function create_dir($dirPath = ''){
        if (!file_exists($dirPath)) {
            if (!mkdir($dirPath)) {
                return 'Create directory failed "' . $dirPath;
            }
        }
        return true;
    }

    private function write_log($msg = ''){
        $fileName = 'log_' . date('Ymd') . '.txt';
        $filePath = $this->logDirPath . DIRECTORY_SEPARATOR . $fileName;
        if ($msg != '') {
            $msgPut = date('Y-m-d H:i:s') . ' : ' . $msg . "\n";
            file_put_contents($filePath, $msgPut, FILE_APPEND);
        }
    }

    private function open_connection($optDB){
        try {
            $conn = new PDO("mysql:host={$optDB['db_host']}", $optDB['db_user'], $optDB['db_pass']);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
        return $conn;
    }

    private function query($db, $sql, $bind = []){
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }

    private function exec_query($db, $sql, $bind = []){
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }

    private function get_list_db($db){
        $sql = "SHOW DATABASES";
        $data = $this->query($db, $sql);
        return array_column($data, 'Database');
    }

    private function set_list_db_to_export(){
        $dbCheckArr = $this->get_list_db($this->dbExport);
        if (count($this->exportDatabases) > 0) {
            $resGet = [];
            foreach ($this->exportDatabases as $kd => $vd) {
                if (in_array($vd, $dbCheckArr)) {
                    $resGet[] = $vd;
                }
            }
            $this->exportDatabases = $resGet;
        }else{
            foreach ($dbCheckArr as $key => $dbname) {
                if (!in_array($dbname, $this->excludesDb)) {
                    $this->exportDatabases[] = $dbname;
                }
            }
        }
    }

    private function isWindow(){
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        }
        return false;
    }

    private function runCommand($cmd){
        exec($cmd);
    }

    /**
     * Export db
     * @return [type] [description]
     */
    public function export(){
        try {
            $this->setup_export();
            // Open connection
            $this->dbExport = $this->open_connection($this->options['export']);
            // Create export dir
            $resCreateExportDir = $this->create_dir($this->exportDirPath);
            if ($resCreateExportDir !== true) {
                throw new \Exception($resCreateExportDir);
            }
            $this->set_list_db_to_export();
            $targetDirExportName = 'export_' . date('YmdHis');
            $targetDirExportPath = $this->exportDirPath . DIRECTORY_SEPARATOR . $targetDirExportName;
            $resTargetDirExport = $this->create_dir($targetDirExportPath);
            if ($resTargetDirExport !== true) {
                throw new \Exception($resTargetDirExport);
            }
            if (count($this->exportDatabases) == 0) {
                throw new \Exception('No database name to export');
            }
            echo "<pre>";
            var_dump($this->exportDatabases);
            die;
            foreach ($this->exportDatabases as $ked => $vedName) {
                $cmdStr = implode(' ', [
                    $this->mysqldumpPath,
                    '--host="' . $this->options['export']['db_host'] . '"',
                    '--user="' . $this->options['export']['db_user'] . '"',
                    '--password="' . $this->options['export']['db_pass'] . '"',
                    $vedName,
                    '>',
                    $targetDirExportPath . DIRECTORY_SEPARATOR . $vedName . '.sql'
                ]);
                $this->runCommand($cmdStr);
            }

            echo "OK. File exported in {$targetDirExportName} directory";
        } catch (\Exception $e) {
            $this->write_log($e->getMessage());
            echo "Error!!! Please see log file";
        }
    }


    /**
     * Import
     * @return [type] [description]
     */
    public function import(){
        try {
            $this->setup_import();
            $this->dbImport = $this->open_connection($this->options['import']);
            $resGetFilesSQL = $this->get_files_sql();
            if (count($resGetFilesSQL['list_files']) == 0) {
                throw new \Exception('Directory ' . $resGetFilesSQL['dirname'] . ' is empty');
            }
            $this->detect_db_exist($resGetFilesSQL['list_files']);
            foreach ($resGetFilesSQL['list_files'] as $filePath) {
                $dbname = basename($filePath, '.sql');
                $this->createDB($dbname);
                $cmdImport = implode(' ', [
                    $this->mysqlPath,
                    '--host="' . $this->options['import']['db_host'] . '"',
                    '--user="' . $this->options['import']['db_user'] . '"',
                    '--password="' . $this->options['import']['db_pass'] . '"',
                    $dbname,
                    '<',
                    $filePath
                ]);
                $this->runCommand($cmdImport);
            }
            echo "Import Directory (" . $resGetFilesSQL['dirname'] . ") success.";
        } catch (\Exception $e) {
            $this->write_log($e->getMessage());
            echo "Error!!! Please see log file";
        }
    }

    private function get_files_sql(){
        $res = [
            'dirname' => '',
            'list_files' => []
        ];
        $dirName = $this->options['import']['dir_name'];
        if ($dirName == '') {
            $exportDirs = glob($this->exportDirPath . DIRECTORY_SEPARATOR . '*');
            $filesName = [];
            foreach ($exportDirs as $ke => $ve) {
                $fileInfo = pathinfo($ve);
                $filesName[] = $fileInfo['filename'];
            }
            if (count($filesName) > 0) {
                rsort($filesName);
                $dirName = array_shift($filesName);
            }
        }
        $targetDir = $this->exportDirPath . DIRECTORY_SEPARATOR . $dirName;
        if (!file_exists($targetDir)) {
            return $res;
        }
        $filesSQL = glob($targetDir . DIRECTORY_SEPARATOR . '*.sql');
        $res['dirname'] = $dirName;
        $res['list_files'] = $filesSQL;
        return $res;
    }

    private function detect_db_exist($listFiles){
        $dbArr = $this->get_list_db($this->dbImport);
        $listFilesName = [];
        foreach ($listFiles as $key => $value) {
            $tmpName = basename($value, '.sql');
            if (in_array($tmpName, $dbArr)) {
                throw new \Exception('DB (' . $tmpName . ') already exists. Remove it before import');
            }
        }
        return true;
    }

    private function createDB($dbname){
        $sql = "CREATE DATABASE {$dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $this->exec_query($this->dbImport, $sql);
    }

}