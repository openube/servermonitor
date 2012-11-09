<?php

spl_autoload_extensions('.php');
spl_autoload_register();

$storage = Storage\StorageFactory::build(array(
    'type'=>'ArrayFile',
    /*
    'db'=>array(
        'host'=>'localhost',
        'dbname'=>'test',
        'user'=>'test',
        'password'=>'53w5eg',
    ),
    'tableName'=>'server',
    'where'=>array('host'=>'8.8.8.8'),
     */
    'file'=>'data/test.php',
    'createFile'=>true,
));
$obj = new \stdClass;
$obj->host = '91.193.35.251';
$obj->port = '27016';
$storage->put($obj);
$storage->save();
