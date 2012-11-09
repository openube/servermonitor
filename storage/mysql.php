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

    /**
     * @property array database table data
     */
    private $_tableMetadata = array();

    /**
     * @property object query result
     */
    private $_result = false;

    public function __construct($params)
    {
        $this->setParams($params);
        $this->connect();
        $this->importTableMetadata();
        $this->loadStorage();
    }

    /**
     * Setting storage parameters using passed array
     * @param array params passed to object constructor
     */
    private function setParams($params)
    {
        if (!isset($params['db']))
            throw new \Exception(__METHOD__.'. Connection params not found.');

        // database params are moved to their own property
        foreach ($this->_connectionParams as $key => $val)
        {
            if (isset($params['db'][$key]))
                $this->_connectionParams[$key] = $params['db'][$key];
        }

        // deleting database and storage type params
        unset($params['db'],$params['type']);

        // loading other params
        foreach ($params as $key=>$val)
                $this->_params[$key] = $val;

        // check can we use PDO
        $this->_params['usePDO'] = extension_loaded('PDO');

        // set result object fetch function name
        if ($this->usePDO)
            $this->_fetchFunc = 'fetchObject';
        else
            $this->_fetchFunc = 'fetch_object';
    }

    /**
     * Using magic method to return storage parameters
     * @return mixed storage param value
     */
    public function __get($param)
    {
        // if common params container has such param
        if (isset($this->_params[$param]))
            return $this->_params[$param];

        // or maybe database params has such
        elseif (isset($this->_connectionParams[$param]))
            return $this->_connectionParams[$param];

        // or we are trying to get fields for database quering
        elseif ($param == 'select')
            return $this->getTableFields();

        // give it a NULL if nothing was found 
        else return null;
    }

    /**
     * Connect to mysql server
     * Checks all mandatory params to be set to get database connection and 
     * queries work
     */
    public function connect()
    {
        if ($this->tableName === null)
            throw new \Exception(__METHOD__.'. Table name param no set');

        foreach ($this->_connectionParams as $key=>$val)
            if ($val === null)
                throw new \Exception(__METHOD__.'. Connection param "'.$key.'" no set.');

        // if table mappings where passed to the storage
        if (is_array($this->tableMap))
            // we should check is there 'host' and 'port' properties mappings
            if (!array_key_exists('host',$this->tableMap) || !array_key_exists('port',$this->tableMap))
                throw new \Exception(__METHOD__.'. No table field was mapped to "host" or "port" property');

        // establish connection regarding is PDO available 
        if ($this->usePDO)
            $this->_connection = new \PDO('mysql:dbname='.$this->dbname.';host='.$this->host, $this->user, $this->password);
        else
            $this->_connection = new \MySQLi($this->host, $this->user, $this->password, $this->dbname);

        if (!$this->_connection)
            throw new \Exception(__METHOD__.'. Cannot connect to database');

    }

    /**
     * Imports table meta data from database
     */
    private function importTableMetadata()
    {
        $schema = $this->_connection->query('DESCRIBE '.$this->tableName);
        $func = $this->_fetchFunc;
        while (($row = $schema->$func()) != null)
        {
            $this->_tableMetadata[] = $row;
        }
    }

    /**
     * Iterates over table meta data
     * @return array table fields
     */
    private function getTableFields()
    {
        $filelds = array();
        foreach ($this->_tableMetadata as $field)
            $fields[] = $field->Field;

        return $fields;
    }

    /**
     * Builds where clause for SELECT's
     * @return string where clause string to append to query
     */
    private function whereClause()
    {
        $where = '';
        if ($this->where !== null)
        {
            $where .= 'WHERE ';
            // if we have more than one field condition
            if (is_array($this->where))
            {
                // we are going to iterate through them
                $clauseLength = count($this->where);

                // counting conditions
                $i = 0;
                foreach ($this->where as $field=>$condition)
                {
                    // use named params if we have PDO, otherwise use 
                    // placeholders
                    $where .= $field.'='.(($this->usePDO) ? ':'.$field : '?');
                    $i++;

                    // add sql AND if it's not the last condition
                    if ($i < $clauseLength)
                        $where .= ' && ';
                }
                unset($i);
            }

            // if our where clause is just a sting
            elseif (is_string($this->where))
                // just add it 
                $where .= $this->where;

            // if where param not an array neigther a string we don't know how 
            // to work with it
            else
                throw new \Exception(__METHOD__.'. WHERE condition has uknown type');
        }
        return $where;
    }

    /**
     * Build query ORDER clause
     * @return string order clause
     */
    private function order()
    {
        if ($this->order !== null)
        {
            if (!is_string($this->order))
                throw new \Exception(__METHOD__.'. Order param should be a string');
            else
                return ' ORDER BY '.$this->order;
        }
    }

    /**
     * Build query LIMIT clause
     * @return string limit
     */
    private function limit()
    {
        return ($this->limit !== null) ? ' LIMIT 0,'.$this->limit : '';
    }

    /**
     * Glue all query part
     * @return string SELECT query
     */
    private function selectQuery()
    {
        return 'SELECT `t`.`'.implode('`, `t`.`', $this->select).
            '` FROM `'.$this->tableName.'` `t` '.
            $this->whereClause().
            $this->order().
            $this->limit();
    }

    /**
     * Lets load all entries
     */
    private function loadStorage()
    {
        // we cannot load anything if there are no table mappings and no existing 
        // 'host' and 'port' fields in the table
        if (
            ($this->tableMap === null) &&
            (!in_array('host',$this->select)) &&
            (!in_array('port',$this->select))
        )
            throw new \Exception(__METHOD__.'. Storage table has no "host" neigther "port" fields');

        // prepare our query
        $this->_result = $this->_connection->prepare($this->selectQuery());

        // our WHERE clause is an array, so we need to bind fields conditions to 
        // query parameters or placeholders
        if (is_array($this->where))
        {
            foreach($this->where as $field=>$condition)
            {
                // if we can use PDO, we bind to named parameters
                if ($this->usePDO)
                {
                    switch (gettype($condition))
                    {
                    case 'integer':
                        $type = \PDO::PARAM_INT;
                        break;
                    default:
                        $type = \PDO::PARAM_STR;
                        break;
                    }
                    $this->_result->bindParam(':'.$field,$condition,$type);
                }

                // otherwise we bind to placeholders
                else
                    $this->_result->bind_param(substr(gettype($condition),0,1), $condition);
            }
        }


        $this->_result->execute();

        // get query result object fech function
        $fetchFunc = $this->_fetchFunc;

        // if there is no PDO we need to bind results and create our entries by 
        // our own
        if (!$this->usePDO)
        {
            // override mysqli_result::fetch_object to use mysqli_smtm::fetch
            $fetchFunc = 'fetch';

            // array to store row properties references
            $properties = array();

            // row object
            $row = new \stdClass;

            // fill properties array with references
            foreach ($this->select as $field)
                $properties[] =& $row->$field;

            // bind results to properties
            call_user_func_array(array($this->_result, 'bind_result') ,$properties);
        }

        while(($obj = $this->_result->$fetchFunc()) != false)
        {
            // we need to create each entry object
            // if we have no PDO
            if (!$this->usePDO)
            {
                // new entry object
                $obj = new \stdClass;

                // loading entry properties from row result
                foreach ($row as $field=>$val)
                    $obj->$field = $val;
            }

            // storing entry
            $this->_entries[] = $obj;
        }
        // delete supplementary objects
        unset($row,$obj,$properties);
    }

    /**
     * Fetch next entry of stored under the specified key
     * @return mixed stored object or NULL if there is nothing left
     */
    public function fetch($key = null)
    {
        if ($key === null)
        {
            $key = $this->_pointer;
            $this->_pointer++;
        }
        return (isset($this->_entries[$key])) ? $this->_entries[$key] : null;
    }

    /**
     * Return entry property value
     * if it was mapped to a table or is a field
     * @return mixed entry property value
     */
    private function getProperty($property)
    {
        if (isset($this->tableMap[$property])) 
            $prop = $this->tableMap[$property];
        elseif (in_array($property, $this->getTableFields()))
            $prop = $property;
        else
            throw new \Exception(__METHOD__.'. Unknown storage entry property "'.$property.'".');

        return ($this->_entries[$this->_pointer]  !== null) ? $this->_entries[$this->_pointer]->$property : null;
    }

    /**
     * Get entry IP or hostname
     * @return string
     */
    public function getHost()
    {
        return $this->getProperty('host');
    }

    /**
     * Get entry port number
     * @return string
     */
    public function getPort()
    {
        return $this->getProperty('port');
    }


    public function put(\stdClass $entry, $key)
    {

    }

    public function save()
    {
    }
}
