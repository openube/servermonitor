<?php

spl_autoload_extensions('.php');
spl_autoload_register();

$storage = Storage\StorageFactory::build(array(
    'type'=>'File',
    'file'=>'ip.php',
));
var_dump($storage->fetch());
var_dump($storage->fetch());
