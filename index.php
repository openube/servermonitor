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
    'tableName'=>'server',
    'tableMap'=>array(
        'host'=>'hostname',
        'port'=>'portnum',
    ),
));
var_dump($storage);
