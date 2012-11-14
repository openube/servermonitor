<?php

namespace Server;

/**
 * Server Monitor factory
 *
 * I'm thinking of making factory abstract class to extend it in each factory 
 * class. Maybe, someday, I will :)
 */
class ServerFactory {

    /**
     * We don't need any factory instances
     */
    private function __construct()
    {
    }

    private function __sleep()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    /**
     * The only method available to invoke
     */

    public static function build(array $params)
    {
        return self::getServerInstance($params);
    }

    private static function getServerInstance(array $params)
    {
        if (!isset($params['type']))
            throw new \Exception(__METHOD__.'. Server Monitor type not set');

        if (isset($params['storage']) && !in_array('Storage\\IStorage', class_implements($params['storage'])))
            throw new \Exception(__METHOD__.'. Storage object must implement Storage\\IStorage interface');

        $class = __NAMESPACE__.'\\'.$params['type'];

        if (in_array(__NAMESPACE__.'\\IServer', class_implements($class)))
            return new $class($params);
        else
            throw new \Exception(__METHOD__.'. Server Monitor class must implement IStorage interface');
    }
}
