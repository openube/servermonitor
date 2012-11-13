<?php

spl_autoload_extensions('.php');
spl_autoload_register();

$storage = Storage\StorageFactory::build(array(
    'type'=>'MySQL',
    'db'=>array(
        'host'=>'localhost',
        'dbname'=>'test',
        'user'=>'test',
        'password'=>'53w5eg',
    ),
    'tableName'=>'servers',
    'tableMap'=>array(
        'host'=>'server_name',
        'port'=>'port_number',
    ),
));
$obj = new stdClass;
$obj->host = '10.251.251.35';
$obj->port = '27014';
$obj->create_time = date('Y-m-d H:i:s');
$storage->put($obj);
$storage->save();
var_dump($storage);
