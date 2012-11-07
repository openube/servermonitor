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

    /**
     * Buils storage instance
     */
    public function __construct($params)
    {
        $this->_storageFile = $params['file'];

        // we don't need to create servers array file
        if (empty($params['createFile']))
        {
            if (!file_exists($this->_storageFile))
                throw new \Exception(__METHOD__.' Servers storage file does not exist.');

            $fh = fopen($this->_storageFile, 'r');
            if (!$fh)
                throw new \Exception(__METHOD__.' Servers storage file cannot be read.');
            fclose($fh);

            // everything seems ok, loading entries
            $this->_entries = require $this->_storageFile;
        }
    }

    /**
     * Tries to return a value of corresponding key in entry's array
     */
    private function getProperty($property)
    {
        if (isset($this->_entries[$this->_getter]) && array_key_exists($property,$this->_entries[$this->_getter]))
            return $this->_entries[$this->_getter][$property];
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
        return (isset($this->_entries[$key])) ? $this->_entries[$key] : null;
    }

    /**
     * Puts entry into the storage at specified place.
     * Puts data at the end of the storage if no key value was passed.
     */
    public function put($entry, $key = null)
    {
        if ($key !== null)
            $this->_entries[$key] = $entry;
        else
            $this->_entries[] = $entry;
    }

    /**
     * Saves entries array to a file
     */
    public function save()
    {
        file_put_contents($this->_storageFile,'<?php'.PHP_EOL.'return '.var_export($this->_entries,true).';');
    }

}
