<?php

namespace Storage;
use Storage;

class StorageFactory
{
    public static function build($params)
    {
        return self::getStorage($params);
    }

    private function getStorage($params)
    {
        if (!isset($params['type']))
            throw new Exception(__NAMESPACE__.'\\'.__CLASS__.'::'.__METHOD__.' Storage Stype no set');

        $class = __NAMESPACE__. '\\' .$params['type'];

        if (in_array(__NAMESPACE__.'\\IStorage', class_implements($class)))
            return new $class($params);
        else
            throw new Exception(__METHOD__ . '. Storage Provider must impement IStorage interface');
    }

}
