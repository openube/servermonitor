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
    'order'=>'create_time DESC',
    'limit'=>6,
));

$monitor = Server\ServerFactory::build(array(
    'type'=>'CounterStrike',
    'storage'=>$storage,
    'useCache'=>true,
    'cache'=>array(
        'type'=>'ArrayFile',
        'file'=>'cache/csPoll.php',
        'pollResultLifetime'=>600,
    ),
));

$monitor->run();
