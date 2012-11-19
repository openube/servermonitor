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

    /**
     * @property array results container
     */
    private $_results = array();

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
        // do we need cache
        if ($this->useCache !== null)
        {

            if (!isset($this->cache['type']))
                throw new \Exception(__METHOD__.'. Cache provider not set');
            
            if (($this->cache['type'] === 'ArrayFile') && !file_exists($this->cache['file']))
                $this->_params['cache']['createFile'] = true;

            // we need to know for what time to save poll result
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
        // iterate through the storage
        while (($entry = $this->_storage->fetch()) !== null)
        {
            $host = $this->_storage->getHost();
            $port = $this->_storage->getPort();

            $result = $this->_cache->fetch($host);

            // if we have no cached results
            // or server was polled more than pollResultLifetime second ago
            // we need to poll it again
            if (
                ($result === null) ||
                (
                    (time() - $result->pollTime) > $this->cache['pollResultLifetime']
                )
            )
            {
                $result = $this->poll($host, $port);
                $result->pollTime = time();
            }

            // put results to cache
            $this->_cache->put($result, $host);

            // add array to render results from
            $this->_results[] = $result;
        }

        $this->_cache->save();
        $this->renderResults();
    }

    public function poll($host, $port)
    {
        $query = "\xFF\xFF\xFF\xFF\x54\x53\x6F\x75\x72\x63\x65\x20\x45\x6E\x67\x69\x6E\x65\x20\x51\x75\x65\x72\x79\x00";
        $socket = fsockopen('udp://'.$host, (int)$port);

        $status = new \stdClass;
        $status->host = $host;
        $status->port = $port;

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

    private function renderResults()
    {
        $dom = new \DOMDocument('1.0','UTF-8');
        $table = $dom->createElement('table');
        $table->setAttribute('border', 1);
        $table->setAttribute('cellpadding', 3);

        $thead = $dom->createElement('thead');
        $head_row = $dom->createElement('tr');
        $cell = $dom->createElement('th', 'Host');
        $head_row->appendChild($cell);

        foreach (array_keys($this->_serverStatusMap) as $key)
        {
            $cell = $dom->createElement('th', ucfirst($key));
            $head_row->appendChild($cell);
        }

        $thead->appendChild($head_row);
        $table->appendChild($thead);

        $tbody = $dom->createElement('tbody');

        foreach ($this->_results as $result)
        {
            $row = $dom->createElement('tr');
            $cell = $dom->createElement('td', $result->host . ':' . $result->port);
            $row->appendChild($cell);

            if ($result->status !== CounterStrike::SERVER_OFFLINE)
            {
                foreach (array_keys($this->_serverStatusMap) as $key)
                {
                    $cell = $dom->createElement('td', $result->$key);
                    $row->appendChild($cell);
                }
            }
            else
            {
                $cell = $dom->createElement('td',' OFFLINE');
                $cell->setAttribute('colspan', 4);
                $row->appendChild($cell);
            }

            $tbody->appendChild($row);
        }
        $table->appendChild($tbody);
        $dom->appendChild($table);
        echo $dom->saveHTML();
    }

}
