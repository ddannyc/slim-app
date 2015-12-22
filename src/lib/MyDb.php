<?php
/**
 * Created by wayne.
 * Date: 2015/7/3
 * Time: 11:04
 */
namespace App\lib;

use PDO;

/**
 * Class MyDb
 * @package App\lib
 *
 * @param \PDOStatement $sth
 */
class MyDb
{
    private static $instance = null;
    protected $db;
    private $debug;
    private $sth = null;
    private $orderBy = [];
    private $limit = [];

    private function __construct($dsn, $user, $passwd, $debug)
    {
        $this->debug = $debug;
        $this->db = new PDO($dsn, $user, $passwd);
    }

    private function __clone(){}

    /**
     * 单例对象只会初始化一次
     * @param $dsn
     * @param $user
     * @param $passwd
     * @return MyDb|null
     */
    public static function getInstance($dsn, $user, $passwd, $debug)
    {
        if (null === self::$instance) {
            self::$instance = new MyDb($dsn, $user, $passwd, $debug);
        }
        return self::$instance;
    }

    public function getRowCount($table, $condition = [])
    {
        $strFields = 'count(*) as cnt';
        $strWhere = $this->filter($condition);
        $sql = "SELECT $strFields FROM $table $strWhere";
        $this->query($sql);

        $result = $this->sth->fetch(PDO::FETCH_ASSOC);

        if (isset($result['cnt'])) {
            return $result['cnt'];
        } else {
            return 0;
        }
    }

    public function fetch($table, $conditions = [], $fields = '', $limit = [])
    {
        $strFields = $this->selectField($fields);
        $strWhere = $this->filter($conditions);

        $this->limit($limit);
        $sql = "SELECT $strFields FROM " . $table. $strWhere. $this->getOrderBy(). $this->getLimit();
        $this->query($sql, array_values($conditions));

        return $this->sth->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll($table, $conditions = [], $fields = '', $limit = [])
    {
        $strFields = $this->selectField($fields);
        $strWhere = $this->filter($conditions);

        $this->limit($limit);
        $sql = "SELECT $strFields FROM " . $table. $strWhere. $this->getOrderBy(). $this->getLimit();
        $this->query($sql, array_values($conditions));

        return $this->sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($table, $data, $conditions)
    {
        $arrFields = array_keys($data);
        foreach($arrFields as &$val) {
            $val = $val. '=?';
        }
        $strFields = implode(', ', $arrFields);
        $strConditions = $this->filter($conditions);

        $sql = "UPDATE ". $table. " SET $strFields WHERE $strConditions";
        $this->query($sql, array_merge(array_values($data), array_values($conditions)));
        $affected_rows = $this->sth->rowCount();

        return $affected_rows;
    }

    public function save($table, $data)
    {
        $fields = array_keys($data);
        $strFields = implode(', ', $fields);
        $strValues = str_repeat('?, ', count($fields));
        $strValues = substr($strValues, 0, strlen($strValues) - 2);

        $sql = "INSERT INTO ". $table. " ($strFields) VALUES ($strValues)";
        $this->query($sql, array_values($data));

        return $this->db->lastInsertId();
    }

    public function limit($limit)
    {
        if ($limit) {

            if (!is_array($limit)) {
                $this->limit = ['count' => $limit];
            } else {
                $this->limit = $limit;
            }
        }
    }

    public function orderBy($order, $direct = 'DESC')
    {
        $str_order = is_string($order)? $order: (is_array($order)? implode(', ', $order): '');

        if ($str_order) {
            $this->orderBy = [
                'order' => $order,
                'direct' => $direct
            ];
        }
    }

    public function lastRowCount()
    {
        return $this->sth->rowCount();
    }

    private function selectField($fields)
    {
        $result = '*';
        if ($fields) {
            if (is_string($fields)) {
                $result = $fields;
            } elseif (is_array($fields)) {
                $result = implode(', ', $fields);
            }
        }
        return $result;
    }

    private function filter($conditions)
    {
        if (!$conditions) {
            return '';
        }
        $conditions_key = array_keys($conditions);
        foreach($conditions_key as $key=>$val) {

            $val = trim($val);
            if(strpos($val, ' ') === false) {
                $conditions_key[$key] = $val. '=?';
            } else {
                $arr_val = explode(' ', $val);

                if(in_array($arr_val[1], array('>', '>=', '<', '<='))) {
                    $conditions_key[$key] = $val. '?';
                } else {
                    $conditions_key[$key] = '-1=?';
                }
            }
        }
        $strWhere = ' WHERE '. implode(' AND ', $conditions_key);
        return $strWhere;
    }

    private function getOrderBy()
    {
        $result = '';
        if ($this->orderBy) {
            $result = ' ORDER BY '. $this->orderBy['order']. ' '. $this->orderBy['direct'];
        }
        return $result;
    }

    private function query($sql, $values = [])
    {
        $this->sth = $this->db->prepare($sql);
        $this->sth->execute($values);
        $this->orderBy = [];
        $this->limit = [];

        if ($this->debug) {
            echo '<pre>';
            echo "<code>$sql</code>";
            print_r($this->db->errorInfo());
            echo '</pre>';
        }
    }

    private function getLimit()
    {
        $limit = $this->limit;
        $result = '';
        if ($limit) {
            if (sizeof($limit) > 1) {
                $result = ' LIMIT '. $limit['offset']. ', '. $limit['count'];
            } else {
                $result = ' LIMIT '. $limit['count'];
            }
        }
        return $result;
    }
}