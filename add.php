<?php
header('Content-type: text/html; charset=utf8');

// database connection params
$database = array(
    'host'=>'localhost',
    'dbname'=>'test',
    'user'=>'test',
    'password'=>'53w5eg',
);

// error messages translation
$t = array(
    'required'=>'Поле обязательно к заполнению',
    'integer'=>'Должно быть целое число',
    'unmatch'=>'Значение не соотвествует формату',
    'invalid'=>'Указанное значение не существует',
);


// can we use PDO extension
define('PDOAvailable', extension_loaded('PDO'));

// if we do
if (PDOAvailable)
    // connect to database through PDO
    try {
        $db = new PDO('mysql:host='.$database['host'].';dbname='.$database['dbname'], $database['user'], $database['password']);
    }
    catch (PDOException $e)
    {
        $status = $e->getMessage();
    }

// otherwise - use MySQLi
else
{
    $db = new MySQLi($database['host'], $database['user'], $database['password'], $database['dbname']);
    $status = $db->error;
}

// if we cannot connect, service is unavailable
if (!empty($status))
    exit('Извините, сервис временно недоступен: '.$status);

// validation errors contaner
$errors = array();

// we use masterserver array to distinguish form data from other post data
if (isset($_POST['masterserver']))
{
    $request = $_POST['masterserver'];
    foreach (array('server','port','key') as $field)
    {
        // evey field is required
        if (empty($request[$field]))
            $errors[$field] = 'required';
    }

    // if all fields were filled with data
    if (!count($errors))
    {
        // validate server name to be valid IP address do domain name
        if (!preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[0-1]?[0-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|[0-1]?[0-9]?[0-9])$/', $request['server'])
            && !preg_match('/^[a-z0-9\-\._]+\.[a-z]{2,4}$/i', $request['server'])
        )
            $errors['server'] = 'unmatch';

        // validate port number to be a number
        if (!preg_match('/^\d{2,5}$/', $request['port']))
            $errors['port'] = 'unmatch';

        // validate key to be 10 chars length and contain numbers and capital letters
        if (!preg_match('/^[A-Z0-9]{10}$/', $request['key']))
            $errors['key'] = 'unmatch';

        // if validation passed
        if (!count($errors))
        {
            // check did we issued code entered through the form
            $q = 'SELECT 1 FROM `keys` WHERE `key`='.((PDOAvailable) ? ':key' : '?');
            $query = $db->prepare($q);

            if (PDOAvailable)
                $query->bindValue(':key', $request['key'], PDO::PARAM_STR);
            else
                $query->bind_param('s', $request['key']);

            $query->execute();
            $keyExists = (PDOAvailable) ? $query->rowCount() : $query->num_rows;

            // if code does not exists - get off
            if (!$keyExists)
                $errors['key'] = 'invalid';
            
            // or let's go forward
            else
            {
                // keys can be used only one
                $q = 'DELETE FROM `keys` WHERE `key`='.((PDOAvailable) ? ':key' : '?');
                $query = $db->prepare($q);

                if (PDOAvailable)
                    $query->bindValue(':key', $request['key'], PDO::PARAM_STR);
                else
                    $query->bind_param('s', $request['key']);

                $query->execute();

                // register autoload and load our storage 
                spl_autoload_register();
                $storage = Storage\StorageFactory::build(array(
                    'type'=>'MySQL',
                    'db'=>$database,
                    'tableName'=>'servers',
                    'tableMap'=>array(
                        'host'=>'server_name',
                        'port'=>'port_number',
                    ),
                ));

                // create new server record
                $server = new stdClass;
                $server->host = $request['server'];
                $server->port = (int)$request['port'];
                $server->create_time = date('Y-m-d H:i:s');

                // put it to the storage
                $storage->put($server);

                // and try to save everything
                try
                {
                    $storage->save();
?>
    <span style="color:#169350">Спасибо, сервер добавлен</span>
    <meta http-equiv="Refres" content="3" />
<?php
                }
                catch (\Exception $e)
                {
?>
    <span style="color:#f00">Ошибка: <?php echo $e->getMessage(); ?></span>
<?php
                }
            }
        }
    }
}
?>
<style>
    label {display: inline-block; width: 210px; }
</style>
<form name="master-server-add" action="" method="post">
    <label for="server-ip-domain-input">IP или доменное имя сервера</label>
    <input type="text" name="masterserver[server]" id="server-ip-domain-input" value="<?php echo (isset($request['server'])) ? $request['server'] : '';?>" />

    <?php if (isset($errors['server'])): ?>
        <span style="color: #F00"><?php echo $t[$errors['server']]; ?></span>
    <?php endif; ?>

    <br/>

    <label for="server-port-input">Номер порта</label>
    <input type="text" name="masterserver[port]" id="server-port=input" value="<?php echo (isset($request['port'])) ? $request['port'] : ''; ?>" />

    <?php if (isset($errors['port'])): ?>
        <span style="color: #F00"><?php echo $t[$errors['port']]; ?></span>
    <?php endif; ?>

    <br/>
    <label for="key-input">Регистрационный ключ</label>
    <input type="text" name="masterserver[key]" id="key-input" value="<?php echo (isset($request['key'])) ? $request['key'] : ''; ?>" />

    <?php if (isset($errors['key'])): ?>
        <span style="color: #F00"><?php echo $t[$errors['key']]; ?></span>
    <?php endif; ?>

    <br/>

    <input type="submit" value="Добавить"/>
    <input type="reset" value="Очистить"/>
</form>
<?php
?>
