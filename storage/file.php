<?php

namespace Storage;

class File implements IStorage
{
    private $_entries;
    private $_storageFile;
    private $_getter = 0;

    public function __construct($params)
    {
        if (empty($params['createFile']))
        {
            if (!file_exists($params['file']))
                throw new \Exception(__NAMESPACE__ . '\\' . __CLASS__ . '::' . __METHOD__ . ' Servers storage file does not exist.');

            $fh = fopen($params['file'], 'r');
            if (!$fh)
                throw new \Exception(__NAMESPACE . '\\' . __CLASS__ . '::' . __METHOD__ . ' Servers storage file cannot be read.');
            fclose($fh);

            $this->_storageFile = $params['file'];
            $this->_entries = require $this->_storageFile;
        }
        else
            $this->_entries = array();
    }

    public function fetch($key = null)
    {
        if ($key === null)
        {
            $key = $this->_getter;
            $this->_getter++;
        }
        return (isset($this->_entries[$key])) ? $this->_entries[$key] : null;
    }

    public function put($entry, $key = null)
    {
        if ($key !== null)
            $this->_entries[$key] = $entry;
        else
            $this->_entries[] = $entry;
    }

    public function save()
    {
        file_put_contents('<?php' . PHP_EOL . 'return ' . var_export($this->_entries), $this->_storageFile);
    }

}
