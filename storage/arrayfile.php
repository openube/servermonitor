<?php

namespace Storage;

class ArrayFile implements IStorage
{
    /**
     * @property array stores servers info entries
     */
    private $_entries = array();

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
                throw new \Exception(__METHOD__.' Servers storage file does not exist.');

            $fh = fopen($this->_storageFile, 'r');
            if (!$fh)
                throw new \Exception(__METHOD__.' Servers storage file cannot be read.');
            fclose($fh);

            foreach (require $this->_storageFile as $key=>$entry)
            {
                $obj = new \stdClass;
                foreach ($entry as $param=>$val)
                    $obj->$param = $val;
                $this->_entries[]=$obj;
            }
        }
    }

    /**
     * Tries to return a value of corresponding key in entry's array
     */
    private function getProperty($property)
    {
        if (isset($this->_currentEntry) && array_key_exists($property,get_object_vars($this->_currentEntry)))
            return $this->_currentEntry->$property;
        else
            throw new \Exception(__METHOD__.' Storage entry has no "'.$property.'" property defined');
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

        $this->_currentEntry =  (isset($this->_entries[$key])) ? $this->_entries[$key] : null;
        return $this->_currentEntry;
    }

    /**
     * Puts entry into the storage at specified place.
     * Puts data at the end of the storage if no key value was passed.
     */
    public function put(\stdClass $entry, $key = null)
    {
        if ($key !== null)
        {
            $this->_entries[$key] = $entry;
            if (!is_int($key))
            {
                $this->_entries[$this->_setter] = &$this->_entries[$key];
                $this->_setter++;
            }
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
        while (($entry = $this->fetch()) !== null)
            foreach(get_object_vars($entry) as $param=>$val)
                $store[$this->_getter-1][$param] = $val;

        file_put_contents($this->_storageFile,'<?php'.PHP_EOL.'return '.var_export($store,true).';');
    }

}
