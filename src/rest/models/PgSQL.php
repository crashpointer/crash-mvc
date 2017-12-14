<?php
/**
 * Created by PhpStorm.
 * User: crashpointer
 * Date: 4.3.2015
 * Time: 12:46
 */

class PgSQL{

    protected static $connection = null;
    protected static $singleton = null;
    protected static $stmt = null;
    protected static $host = "localhost";
    protected static $username = "postgres";
    protected static $password = "";
    protected static $database = "";

    public static function create(){
        if (is_null(self::$connection)) {
            self::$connection = new PDO("pgsql:dbname=" . self::$database .
                ";host=" . self::$host, self::$username, self::$password);
        }

        self::query("SET NAMES 'UTF8'");
        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return self::$connection;
    }

    public static function query($sql)
    {
        return self::$connection->query($sql);
    }

    public static function prepare($query)
    {
        $arguments = array_slice(func_get_args(), 1);
        self::$stmt = self::$connection->prepare($query);
        if (count($arguments)) {
            array_unshift($arguments, self::$stmt);
            call_user_func_array('PgSQL::bind', $arguments);
        }
    }

    public static function prepareSelectSQL($sql, $args)
    {
        $keys = array_keys($args);
        $sql = sprintf($sql, implode(",", preg_filter('/^/', ':', $keys)));
        $args = self::decomposeArr($args);
        array_unshift($args, $sql);
        call_user_func_array("PgSQL::prepare", $args);
        PgSQL::execute();
    }

    public static function executeSQL($query)
    {
        return self::execute(call_user_func_array('PgSQL::prepare', func_get_args()));
    }

    public static function bind($stmt)
    {
        $arguments = array_slice(func_get_args(), 1);
        if (count($arguments) % 2 !== 0)
            throw new Exception("Unknown Arguments");

        for ($i = 0; $i < count($arguments); $i += 2) {
            $stmt->bindParam(":" . $arguments[$i], $arguments[$i + 1], PgSQL::getParamAlias($arguments[$i + 1]));
        }
    }

    public static function getStatement(){
        return self::$stmt;
    }

    protected static function getParamAlias($type)
    {
        switch (gettype($type)) {
            case  "boolean":
                $alias = PDO::PARAM_BOOL;
                break;
            case  "integer":
                $alias = PDO::PARAM_INT;
                break;
            case  "double" :
                $alias = PDO::PARAM_STR;
                break;
            case  "string" :
                $alias = PDO::PARAM_STR;
                break;
            default:
                throw new Exception("Unmapped object");
        }
        return $alias;
    }

    public static function getLastInsertId()
    {
        return self::$connection->lastInsertId;
    }

    public static function execute()
    {
        return self::$stmt->execute();
    }

    public static function fetch()
    {
        return self::$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function fetchAll()
    {
        return self::$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function runArgs($args, $sql)
    {
        if(count($args)){
            $out = array();
            while(list($key, $value) = each($args))
                array_push($out, str_replace('_', '.', substr($key, 0, 2)) . substr($key, 2)  . "=:" . $key);

            $sql = sprintf($sql, " where " . implode(' and ', $out));
            $args = self::decomposeArr($args);
        }
        $sql = sprintf($sql, "");
        array_unshift($args, $sql);
        call_user_func_array("PgSQL::prepare", $args);
        self::execute();
    }

    public static function decomposeArr($arr){
        $out = array();
        foreach($arr as $key => $value){
            array_push($out, $key);
            array_push($out, $value);
        }
        return $out;
    }

    public static function insert($table, $args)
    {
        if(is_array($args)){
            $keys = array_keys($args);
            $sql = sprintf("insert into $table(%s) ", implode(",", $keys));
            $sql .= sprintf("values(%s) ", implode(",", preg_filter('/^/', ':', $keys)));
            $sql .= "returning uid";

            $args = self::decomposeArr($args);
            array_unshift($args, $sql);
            call_user_func_array('PgSQL::prepare', $args);
            self::execute();
        }
        else
            throw new Exception("Invalid parameter for insert process");
    }

    public static function update($table, $args)
    {
        if(!is_array($args) || !isset($args["uid"]))
            throw new Exception("Invalid uid parameter");

        $set = array();
        $sql = "update $table set %s where uid = :uid";
        $uid = $args["uid"];
        unset($args["uid"]);

        // fileds will be updated
        while(list($key, $val) = each($args))
            array_push($set, $key . "=:" . $key);

        $sql = sprintf($sql, implode(",", $set));
        $args = self::decomposeArr($args);
        array_unshift($args, $sql);
        array_push($args, "uid", $uid);
        call_user_func_array('PgSQL::prepare', $args);
        self::execute();
    }

    public static function delete($table, $args)
    {
        if(!is_array($args) || !isset($args["uid"]))
            throw new Exception("Invalid uid parameter");

        $args = self::decomposeArr($args);
        array_unshift($args, "delete from $table where uid = :uid");
        call_user_func_array('PgSQL::prepare', $args);
        self::execute();
    }

}
