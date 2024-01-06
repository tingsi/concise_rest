<?php

class DB
{
    static public function setDB()
    {
        include_once(p(AROOT, 'config.php'));

        $db_config = $GLOBALS['config']['db'];

        $host = $db_config['db_host'] or 'localhost';
        $port = $db_config['db_port'];
        $user = $db_config['db_user'];
        $password = $db_config['db_password'];
        $db_name = empty($database) ? $db_config['db_name'] : $database;
        if (in_array($db_name, ODB::$dbs))
            return ODB::$dbs[$db_name];

        $dsn = "mysql:host=$host;charset=utf8";
        if ($port)
            $dsn .= ";port=$port";

        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_name = "`" . str_replace("`", "``", $db_name) . "`";
        $pdo->query("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'");
        $pdo->query("use $db_name");
    }
    static private function getDB()
    {
        static $pdo = null;

        if (empty($pdo)) {
            include_once(p(AROOT, 'config.php'));

            $db_config = $GLOBALS['config']['db'];

            $host = $db_config['db_host'] or 'localhost';
            $port = $db_config['db_port'];
            $user = $db_config['db_user'];
            $password = $db_config['db_password'];
            $db_name = $db_config['db_name'];


            $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8";
            if ($port)
                $dsn .= ";port=$port";
            $opt = array(PDO::MYSQL_ATTR_MULTI_STATEMENTS => true);

            $pdo = new PDO($dsn, $user, $password, $opt);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // pdo thown PDOException. so no error procedure need here.
        }
        return $pdo;
    }

    static function getList($sql)
    {
        defined('DEBUG') && error_log("R SQL:$sql");
        $data = array();
        $pdo = self::getDB();
        $stm = $pdo->query($sql);
        if ($stm) {
            if ($stm->rowCount() > 0)
                $data = $stm->fetchAll(PDO::FETCH_ASSOC);
            $stm->closeCursor();
        }
        return $data;
    }

    static function getLine($sql) :?array
    {
        $data = self::getList($sql);
        return $data ? @reset($data) : null;
    }

    static function getValue($sql)
    {
        $data = self::getLine($sql);
        if (!$data)
            return false;
        $data = array_values($data);
        return reset($data);
    }

    static function lastID()
    {
        return self::getDB()->lastInsertId();
    }

    // return true or false;
    static function runSql($sql, $param = NULL)
    {
        defined('DEBUG') && error_log("W SQL:$sql");
        defined('DEBUG') && ($param != NULL) && error_log("W SQL:" . print_r($param, true));
        $stmt = $this->pdo->prepare($sql);
        try {
            if (!$stmt->execute($param)) {
                $err = $stmt->errorInfo();
                if ($err) {
                    throw new REServerUnavailable($err[2]);
                }
            }
            ;
        } catch (PDOException $e) {
            throw new REServerUnavailable($e->getMessage());
        }

        $stmt->closeCursor();
        return true;
    }

    // return true or false;
    static function runBatchSql($sql, $param = NULL)
    {
        defined('DEBUG') && error_log("B SQL:$sql");
        $pdo = self::getDB();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($sql);
        try {
            $statement->execute($param);
            while ($statement->nextRowset()) { /* https://bugs.php.net/bug.php?id=61613 */
            }
            ;
            $code = $statement->errorCode();
            $err = $statement->errorInfo();
            if ($code !== "00000") {
                throw new REServerUnavailable($err[2]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        $statement->closeCursor();
        return true;
    }


}
