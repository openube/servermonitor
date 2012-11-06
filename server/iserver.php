
<?php

namespace Server;

interface IServer
{
    function poll($host, $port);
}
