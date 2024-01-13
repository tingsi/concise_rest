<?php

class ODB
{
    private PDO $pdo;
    private static array $dbs = [];

    private function __construct($dsn, $user, $password)
    {
        $opt = array(PDO::MYSQL_ATTR_MULTI_STATEMENTS => true);

        $this->pdo = new PDO($dsn, $user, $password, $opt);

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    public static function withdb($database = null): ODB
    {

        include_once( p(AROOT, 'config.php') );

        $db_config = $GLOBALS['config']['db'];

        $host = $db_config['db_host'] or 'localhost';
        $port = $db_config['db_port'];
        $user = $db_config['db_user'];
        $password = $db_config['db_password'];
        $db_name = empty($database) ? $db_config['db_name'] : $database;
        if (in_array($db_name, ODB::$dbs))
            return ODB::$dbs[$db_name];


        $dsn= "mysql:host=$host;dbname=$db_name;charset=utf8";
        if ($port) $dsn .= ";port=$port";

        $odb = new ODB($dsn, $user, $password);
        ODB::$dbs[$db_name] = $odb;
        return $odb;
    }

    public function getList($sql):array
    {
        defined('DEBUG') && error_log("R SQL:$sql");
        $data = array();
        $stm = $this->pdo->query($sql);
        if ($stm) {
            if ($stm->rowCount() > 0)
                $data = $stm->fetchAll(PDO::FETCH_ASSOC);
            $stm->closeCursor();
        }
        return $data;
    }

    public function getLine( $sql ):array
    {
        $data = $this->getList( $sql );
        return $data ? @reset($data) : [];
    }

    public function getValue( $sql )
    {
        $data = $this->getLine( $sql );
        if (!$data) return false;
        $data = array_values($data);
        return reset( $data );
    }

    public function lastID( )
    {
        return $this->pdo->lastInsertId();
    }

    // return true or false;
    public function runSql( $sql, $param=NULL)
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
    public function runBatchSql( $sql, $param=NULL)
    {
        defined('DEBUG') && error_log("B SQL:$sql");
        $this->pdo->beginTransaction();
        $statement = $this->pdo->prepare($sql);
        try {
            $statement->execute($param);
            while ($statement->nextRowset()) {/* https://bugs.php.net/bug.php?id=61613 */};
	        $code = $statement->errorCode();
            $err = $statement->errorInfo();
            if ($code !== "00000"){
                throw new REServerUnavailable($err[2]);
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new REServerUnavailable($e->getMessage());
        }
        $statement->closeCursor();
        return true;
    }


}

