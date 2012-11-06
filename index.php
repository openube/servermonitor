<?php

spl_autoload_extensions('.php');
spl_autoload_register();

$storage = Storage\StorageFactory::build(array(
    'type'=>'ArrayFile',
    'file'=>'data/servers.php',
));

var_dump($storage);
