<?php

namespace Server;

/**
 * Counter-Strike servers monitoring class
 */
class CounterStrike implements IServer
{

    const SERVER_OFFLINE = 0;
    const SERVER_ONLINE = 1;
    /**
     * @property object server list storage instance 
     */
    private $_storage = null;

    /**
     * @property array monitor parameters
     */
    private $_params = array();

    /**
     * @property object cache provider instance
     */
    private $_cache = null;

    /**
     * @property array server response status mapping
     */
    private $_serverStatusMap = array(
        'name' => 1,
        'map' => 2,
        'players' => array(
            'index'=>5,
            'start'=>0,
            'lenght'=>1,
        ),
        'max_player' => array(
            'index'=>5,
            'start'=>1,
            'lenght'=>1,
        ),
    );

    public function __construct(array $params)
    {
        if (isset($params['type']))
            unset($params['type']);

        $this->_storage = $params['storage'];
        unset($params['storage']);

        $this->_params = $params;

        $this->isCacheAvailable();
    }

    private function isCacheAvailable()
    {
        if ($this->useCache !== null)
        {
            if (!file_exists($this->cache['file']))
                $this->_params['cache']['createFile'] = true;

            if (!isset($this->cache['type']))
                throw new \Exception(__METHOD__.'. Cache provider not set');

            if (!isset($this->cache['pollResultLifetime']))
                throw new \Exception(__METHOD__.'. Cache result lifetime not set');

            $class = 'Storage\\'.$this->cache['type'];
            if (!in_array('Storage\\IStorage', class_implements($class)))
                throw new \Exception(__METHOD__.'. Cache provider must implement Storage\\IStorage interface');

            unset($this->_params['cache']['type']);
            $this->_cache = new $class($this->cache);
        }
    }

    public function __get($property)
    {
        return (isset($this->_params[$property]))
            ? $this->_params[$property]
            : null;
    }

    public function run()
    {
        $i = 0;
        while($this->_storage->fetch() !== null)
        {
            $host = $this->_storage->getHost();
            $port = $this->_storage->getPort();

            $result = $this->_cache->fetch($i);
            if (
                ($result === null) ||
                (
                    ($result->pollTime - time()) > $this->cache['pollResultLifetime']
                )
            )
            {
                $result = $this->poll($host, $port);
                $result->pollTime = time();
            }

            $this->_cache->put($result, $i);
            $i++;
            var_dump($result);
        }
        $this->_cache->save();
    }

    public function poll($host, $port)
    {
        $query = "\xFF\xFF\xFF\xFF\x54\x53\x6F\x75\x72\x63\x65\x20\x45\x6E\x67\x69\x6E\x65\x20\x51\x75\x65\x72\x79\x00";
        $socket = fsockopen('udp://'.$host, (int)$port);

        $status = new \stdClass;
        $status->host = $host;

        fwrite($socket, $query);
        stream_set_timeout($socket, 1);

        fread($socket, 1);
        $state = stream_get_meta_data($socket);
        if (!$state['unread_bytes'])
        {
            $status->status = CounterStrike::SERVER_OFFLINE;
        }

        $response = substr(stream_get_contents($socket), 4);
        fclose($socket);
        unset($socket);

        if (!isset($status->status))
        {
            $response = explode("\x00", $response);

            foreach ($this->_serverStatusMap as $key=>$mapping)
            {
                if (!is_array($mapping))
                    $status->$key = (!empty($response[$mapping])) ? $response[$mapping] : null;
                else
                    $status->$key = ord(substr($response[$mapping['index']], $mapping['start'], $mapping['lenght']));
            }

            if ($status->name !== null)
                $status->status = CounterStrike::SERVER_ONLINE;
        }

        return $status;
    }

}
