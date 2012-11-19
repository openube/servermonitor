<?php

namespace Storage;

class ArrayFile implements IStorage
{
    /**
     * @property array stores servers info entries
     */
    private $_entries = array();

    /**
     * @property array hosts index stores hostname to array index mapping
     */
    private $_hostsIndex = array();

    /**
     * @property string script filename which returns entries info
     */
    private $_storageFile;

    /**
     * Internal storage pointer 
     * Used by <code>getHost()</code>, <code>getPort()</code> and <code>fetch()</code> methods
     */
    private $_getter = 0;

    private $_setter = 0;

    /**
     * @property object current fetched entry
     */
    private $_currentEntry = null;

    /**
     * Buils storage instance
     */
    public function __construct(array $params)
    {
        $this->_storageFile = $params['file'];
        $this->loadStorage($params);
    }

    private function loadStorage(array $params)
    {
        // we don't need to create servers array file
        if (empty($params['createFile']))
        {
            if (!file_exists($this->_storageFile))
                throw new \Exception('Servers storage file does not exist.');

            $fh = fopen($this->_storageFile, 'r');
            if (!$fh)
                throw new \Exception('Servers storage file cannot be read.');
            fclose($fh);

            foreach (require $this->_storageFile as $key=>$entry)
            {
                $obj = new \stdClass;
                foreach ($entry as $param=>$val)
                {
                    $obj->$param = $val;
                }

                if (array_key_exists('host',get_object_vars($obj)))
                    $this->_hostsIndex[$obj->host] = count($this->_entries);

                $this->_entries[]=$obj;
            }
        }
    }

    /**
     * Tries to return a value of corresponding key in entry's array
     */
    private function getProperty($property)
    {
        if (array_key_exists($property,get_object_vars($this->_currentEntry)))
            return $this->_currentEntry->$property;
        else
            throw new \Exception('Storage entry has no "'.$property.'" property defined');
    }

    /**
     * Returns IP of hostname of current entry
     */
    public function getHost()
    {
        return $this->getProperty('host');
    }

    /**
     * Returns port number of current entry
     */
    public function getPort()
    {
        return $this->getProperty('port');
    }

    public function resetCursor()
    {
        $this->_getter = $this->_setter = 0;
    }

    /**
     * Returs server's entry, stored at specified key.
     * Iterates through storage if no key value was passed.
     *
     * @return mixed server entry
     */
    public function fetch($key = null)
    {
        if ($key === null)
        {
            $key = $this->_getter;
            $this->_getter++;
        }
        elseif (!is_int($key) && isset($this->_hostsIndex[$key]))
            $key = $this->_hostsIndex[$key];

        if (isset($this->_entries[$key]))
        {
            $this->_currentEntry =  $this->_entries[$key];
            return $this->_currentEntry;
        }
        else
             return null;
    }

    /**
     * Puts entry into the storage at specified place.
     * Puts data at the end of the storage if no key value was passed.
     */
    public function put(\stdClass $entry, $key = null)
    {
        if ($key !== null)
        {
            if (!is_int($key))
            {
                $key = $this->_hostsIndex[$key] = $this->_setter;
                $this->_setter++;
            }
            $this->_entries[$key] = $entry;
        }
        else
            $this->_entries[] = $entry;
    }

    /**
     * Saves entries array to a file
     */
    public function save()
    {
        $store = array();
        while (($entry = $this->fetch()) != null)
        {
            // check is host is valid IP or domain admins
            if (
                !preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[0-1]?[0-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|[0-1]?[0-9]?[0-9])$/', $this->getHost())
                &&
                !preg_match('/^[a-z0-9\.\-_]+\.[a-z]{2,4}/i', $this->getHost())
            )
                throw new \Exception('Hostname "'.$this->getHost().'" must be a valib IP address or domain name');

            if (!is_numeric($this->getPort()))
                throw new \Exception('Port number must be an interger');

            foreach(get_object_vars($entry) as $param=>$val)
                $store[$this->_getter-1][$param] = $val;
        }

        file_put_contents($this->_storageFile,'<?php'.PHP_EOL.'return '.var_export($store,true).';');
    }

}
