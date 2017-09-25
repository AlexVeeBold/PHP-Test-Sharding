<?php 

////////////////////////////////////////////////////////////////

/*
    Так как данные пользователей хранятся в разных БД, нужна ещё одна БД ("основная"), по которой можно определить, откуда запрашивать данные конкретного пользователя.

    Основная БД содержит информацию о размещении данных пользователей:

    testusers.uids:
        uid         integer primary key
        dsnid       integer                 -> dsns.id

    testusers.dsns:
        id          integer primary key
        driver      char(32)
        host        char(64)
        port        smallint unsigned
        username    char(32)
        password    char(32)
        dbname      char(32)

    Дополнительные БД содержат данные о пользователях:

    testusers1.users:
    testusers2.users:
        userid      integer unique          == uids.uid
        name        char(64)
        lastname    char(64)
        dob         date



    Класс MasterDB работает с основной БД:
    - предоставляет список идентификаторов пользователей
    - предоставляет класс-обёртку для подключения к дополнительным БД

    Класс-обёртка dd_mysql реализует простой абстрактный интерфейс драйвера БД для работы с базами данных MySQL
    Имя класса-обёртки строится следующим образом:
        class_name = 'dd_' + driver_name

    Функция createDBDriver создаёт экземпляр класса-обёртки по имени из DSN



    DSN:

$dsn = array(
    // внутреннее имя
    'id' => 'some_id_or_name',

    // имя драйвера БД для создания экземпляра класса-обёртки
    'driver' => 'mysql',

    // сведения об источнике данных
    'host' => 'ip',
    'port' => 'port',
    'username' => 'user',
    'password' => 'pwd',
    'dbname' => 'database_name',
);

*/

function createDBDriver($dsn) {
    $ddclassname = 'dd_' . $dsn['driver'];
    return new $ddclassname($dsn);
}

////////////////////////////////////////////////////////////////
// Класс-обёртка для MySQL

class dd_mysql {
    // Create DB driver with given DSN
    function __construct($dsn) {
        $this->dsnid = $dsn['id'];
        mysqli_report(MYSQLI_REPORT_STRICT);    // set mysqli to use exceptions instead of warnings
        try {
            $this->ms = new mysqli($dsn['host'], $dsn['username'], $dsn['password'], $dsn['dbname'], $dsn['port']);
        } catch (mysqli_sql_exception $e) {
            throw new Exception("Couldn't connect to ds-".$dsn['id']." at ".$dsn['host'].":".$dsn['port']."/".$dsn['dbname'].".");
        }
    }

    function __destruct() {
    }

    // Запрос множества записей

    function queryOpen($query_string) {
        $query = $this->ms->query($query_string);
        return ($query === false) ? null : $query;      // replace false (if any) with null
    }

    function fetchNextRecord($query) {
        return $query->fetch_assoc();
    }
    //... здесь могут быть методы позиционирования и т.п.

    function queryClose(&$query) {
        $query->close();
        $query = null;
    }

    // Запрос одной записи

    function simpleQuery($query_string) {
        $query = $this->ms->query($query_string);
        if($query !== false) {
            $result = $query->fetch_assoc();
            $query->close();
            return $result;
        }
        return null;
    }
}



////////////////////////////////////////////////////////////////
// Работа с основной базой

class MasterDB {
    function __construct($dsn) {
        $this->dd = createDBDriver($dsn);
        $this->dsns = array();
    }

    function __destruct() {
    }

    // получить количество пользователей
    function getUserCount() {
        $query_string = "SELECT count(`uid`) as `count` FROM `uids`";
        $result = $this->dd->simpleQuery($query_string);
        if(!$result) {
            throw new Exception("Couldn't get user count.");
        }
        return (int) $result['count'];
    }

    // получить список идентификаторов пользователей в виде массива
    function getUserIDList() {
        $query_string = "SELECT `uid` FROM `uids`";

        $query = $this->dd->queryOpen($query_string);
        if (!$query) {
            throw new Exception("Couldn't get userid list.");
        }
        $list = array();
        while ($row = $this->dd->fetchNextRecord($query)) {
            $list[] = $row['uid'];
        }
        $this->dd->queryClose($query);

        return $list;
    }

    // получить/создать класс-обёртку для доступа к БД с данными пользователя
    function getDBForUser($userid) {
        $uid = (int) $userid;
        $query_string = "SELECT `dsnid` FROM `uids` WHERE `uid`=$uid";

        $result = $this->dd->simpleQuery($query_string);
        if(!$result) {
            throw new Exception("Couldn't get DSN id for user#$uid.");
        }

        $dsnid = $result['dsnid'];
        if(isset($this->dsns[$dsnid]) == false) {
            $query_string = "SELECT * FROM `dsns` WHERE `id`=$dsnid";

            $result = $this->dd->simpleQuery($query_string);
            if(!$result) {
                throw new Exception("Couldn't get DSN for user#$uid.");
            }
            $this->dsns[$dsnid] = createDBDriver($result);
        }
        return $this->dsns[$dsnid];
    }
}

function createDBMaster() {
    $dsn_master = array(
        'id' => 'master',
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'username' => 'root',
        'password' => 'pass',
        'dbname' => 'testusers',
    );
    return new MasterDB($dsn_master);
}


////////////////////////////////////////////////////////////////
// HTML page

?>
<html>
<head>
<title>index</title>
<style>
body {font-family: "Palatino Linotype","Times"; font-size: 16pt; background: PeachPuff;}
</style>
</head>
<body>

<?php

////////////////////////////////////////////////////////////////
// user info printing

function getUser($dd, $userid) {
    $uid = (int) $userid;
    $query_string = "SELECT * FROM `users` WHERE `userid`=$uid";
    return $dd->simpleQuery($query_string);
}

function printUserShort($dd, $userid) {
    $user = getUser($dd, $userid);
    echo '<a href="/user/'.$user['userid'].'">';
    echo "#".$user['userid'].": ".$user['name']." ".$user['lastname'];
    echo '</a>';
    echo "<br>".PHP_EOL;
}

function printUserLong($dd, $userid) {
    $user = getUser($dd, $userid);
    echo '<p style="font-size: 125%;">';
    echo "User #".$user['userid'].": ".$user['name']." ".$user['lastname']." (".$user['dob'].")";
    echo '</p>'.PHP_EOL;
}


////////////////////////////////////////////////////////////////
// entry

try {
    $mdb = createDBMaster();

    // вывод информации о запрошенном пользователе
    if(isset($_REQUEST['user'])) {
        $userid = $_REQUEST['user'];
        $dd = $mdb->getDBForUser($userid);
        if($dd) {
            printUserLong($dd, $userid);
        }
        echo "<hr>";
    }

    // вывод информации о всех пользователях
    $useridList = $mdb->getUserIDList();
    foreach ($useridList as $userid) {
        try {
            $dd = $mdb->getDBForUser($userid);
            if($dd) {
                printUserShort($dd, $userid);
            }
        }
        catch(Exception $e) {
            // ловим исключение здесь, чтобы продолжить вывод данных для других пользователей
            echo '<ERROR>: ', $e->getMessage(), PHP_EOL;
        }
    }

    echo "<hr>";
    $numUsers = $mdb->getUserCount();
    echo "Total users number: $numUsers" . "<br>" . PHP_EOL;
}
catch(Exception $e) {
    echo '<ERROR>: ', $e->getMessage(), PHP_EOL;
}


?>

</body>
</html>
