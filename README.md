# Getting started
This library help export / import all db or number db in mysql.

## Required
- PHP >= 5.6

## Config default
```
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
```
- export_databases: More db with ",". If empty then export all db.
- mysqldump_path: Path of tool export for window
- mysql_path: Path of tool import for window

## Example
- For window
```
require "ExportImportDatabase.php";
$exdb = new ExportImportDatabase([
    'export' => [
        'mysqldump_path' => 'D:\xampp\mysql\bin\mysqldump.exe',
        'export_databases' => 'abc1, abc2'
    ],
    'import' => [
        'mysql_path' => 'D:\xampp\mysql\bin\mysql.exe'
    ]
]);
// $exdb->export();
// $exdb->import();
```

- For Linux
```
require "ExportImportDatabase.php";
$exdb = new ExportImportDatabase([
    'export' => [
        'export_databases' => 'abc1, abc2'
    ],
    'import' => []
]);
// $exdb->export();
// $exdb->import();
```
