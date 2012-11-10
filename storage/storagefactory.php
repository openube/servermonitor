<?php

namespace Storage;
use Storage;

/**
 * Storage Factory class to build storage instances
 * Storage classes should implement IStorage interface.
 */
class StorageFactory
{
    /**
     * Single public factory method to build storage instances
     *
     * @return object storage instance build with user params
     */
    public static function build($params)
    {
        return self::getStorage($params);
    }

    /**
     * Builds storage objectinstance using <code>type</code> param to define 
     * provider class and tries to load it.
     *
     * @return object storage provider instance
     */
    private function getStorage($params)
    {
        // we need to know storage type
        if (!isset($params['type']))
            throw new \Exception(__METHOD__.'. Storage Stype no set');

        // we suppose that storage provider class lays in the same namespace
        $class = __NAMESPACE__. '\\' .$params['type'];

        // we need storage provider to implement our interface
        if (in_array(__NAMESPACE__.'\\IStorage', class_implements($class)))
            return new $class($params);
        else
            throw new \Exception(__METHOD__ . '. Storage Provider must impement IStorage interface');
    }

}
