
<?php

namespace Server;

/**
 * Server Monitor Interface
 *
 * People want monitor different types of servers. But we need to know what to 
 * ask different servers' types
 */
interface IServer
{
    /**
     * Polls host to get it's info
     */
    function poll($host, $port);
}
