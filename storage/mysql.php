<?php

namespace Storage;

class MySQL implements IDatabase,IStorage
{
    /**
     * @property array database connection parameters 
     */
    private $_connectionParams = array(
        'host'=>null,
        'dbname'=>null,
        'user'=>null,
        'password'=>null,
    );

    /**
     * @property array storage parameters
     */
    private $_params = array();

    /**
     * @property array cache for already fetched entries
     */
    private $_entries = array();

    /**
     * @property object database connection object
     */
    private $_connection = null;

    /**
     * @property int internal pointer
     * to use in query limits
     */
    private $_pointer = 0;

    public function __construct($params)
    {
        if (!isset($params['db']))
            throw new \Exception(__METHOD__.'. Connection params not found.');

        foreach ($this->_connectionParams as $key => $val)
        {
            if (isset($params['db'][$key]))
                $this->_connectionParams[$key] = $params['db'][$key];
        }

        foreach ($params as $key=>$val)
        {
            if (($key !== 'db') && ($key !== 'type'))
                $this->_params[$key] = $val;
        }

        $this->_params['usePDO'] = extension_loaded('PDO');

        $this->connect();
    }

    public function __get($param)
    {
        if (isset($this->_params[$param]))
            return $this->_params[$param];
        elseif (isset($this->_connectionParams[$param]))
            return $this->_connectionParams[$param];
        else return null;
    }

    public function connect()
    {
        if ($this->tableName === null)
            throw new \Exception(__METHOD__.'. Table name param no set');

        foreach ($this->_connectionParams as $key=>$val)
        {
            if ($val === null)
                throw new \Exception(__METHOD__.'. Connection param "'.$key.'" no set.');
        }

        if ($this->usePDO)
            $this->_connection = new \PDO('mysql:dbname='.$this->dbname.';host='.$this->host, $this->user, $this->password);
        else
            $this->_connection = new \MySQLi($this->host, $this->user, $this->password, $this->dbname);
    }

    public function getHost()
    {
    }

    public function getPort()
    {
    }

    public function fetch($key = null)
    {
        if ($this->tableMetadata === null)
            var_dump($this->tableName);
            //$this->_params['query'] = 'SELECT `host`, `port` FROM `'.$this->tableName.'` LIMIT '.$this->_pointer.', 1';
        else
            var_dump($this->tableMetadata);
            // use PDO 
    }

    public function put($entry, $key)
    {
    }

    public function save()
    {
    }
}
