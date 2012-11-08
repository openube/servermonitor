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
     * @propery array query parts
     */
    private $_defaultQuery = array(
        'select'=>array(
            'host',
            'port',
        ),
        'where'=>null,
        'order'=>null,
        'limit'=>null,
        'having'=>null,
    );

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
        $this->importTableMetadata();
        $this->loadStorage();
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

        if (!$this->_connection)
            throw new \Exception(__METHOD__.'. Cannot connect to database');

        if ($this->usePDO)
            $this->_fetchFunc = 'fetchObject';
        else
            $this->_fetchFunc = 'fetch_object';
    }

    private function importTableMetadata()
    {
        $schema = $this->_connection->query('DESCRIBE '.$this->tableName);
        $func = $this->_fetchFunc;
        while (($row = $schema->$func()) != null)
        {
            $this->_tableMetadata[] = $row;
        }
    }

    private function getTableFields()
    {
        $filelds = array();
        foreach ($this->_tableMetadata as $field)
            $fields[] = $field->Field;

        return $fields;
    }

    private function whereClause()
    {
        $where = '';
        if ($this->where !== null)
        {
            $where .= 'WHERE ';
            $clauseLength = count($this->where);
            if (is_array($this->where))
            {
                $i = 0;
                foreach ($this->where as $field=>$condition)
                {
                    $where .= $field.'='.(($this->usePDO) ? ':'.$field : '?');
                    $i++;
                    if ($i < $clauseLength)
                        $where .= ' && ';
                }
                unset($i);
            }
            elseif (is_string($this->where))
                $where .= $this->where;
            else
                throw new \Exception(__METHOD__.'. WHERE condition has uknown type');
        }
        return $where;
    }

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

    private function limit()
    {
        $limit = ($this->limit === null) ? 1 : $this->limit;
        return ' LIMIT '.$this->_pointer.','.$limit;
    }

    private function selectQuery()
    {

        $select = ($this->select !== null) ? $this->select : $this->getTableFields();

        foreach ($this->_defaultQuery['select'] as $field)
            if (!in_array($field, $select)) throw new \Exception(__METHOD__.'. Field "'.$field.'" not present in query result');

        return 'SELECT `t`.`'.implode('`, `t`.`', $select).'` FROM `'.$this->tableName.'` `t` '.$this->whereClause().$this->order().$this->limit();
    }

    public function getHost()
    {
    }

    public function getPort()
    {
    }

    private function loadStorage()
    {
        $this->_result = $this->_connection->prepare($this->selectQuery());
        if (is_array($this->where))
        {
            foreach($this->where as $field=>$condition)
            {
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
                else
                    $this->_result->bind_param(substr(gettype($condition),0,1), $condition);
            }
        }

        $this->_result->execute();
        var_dump($this->_result->fetch());
        die;
        while(($obj = $this->_result->fetch()) != false)
        {
            $this->_entries[] = $obj;
        }
    }

    public function fetch($key = null)
    {
        if ($key === null)
        {
            $key = $this->_pointer;
            $this->_pointer++;
        }
        return (isset($this->_entries[$key])) ? $this->_entries[$key] : null;
    }

    public function put($entry, $key)
    {
    }

    public function save()
    {
    }
}
