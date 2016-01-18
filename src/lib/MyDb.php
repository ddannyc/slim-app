<?php
/**
 * Created by wayne.
 * Date: 2015/7/3
 * Time: 11:04
 */
namespace App\lib;

use PDO;
use Psr\Log\InvalidArgumentException;

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

    private function __construct($dsn, $user, $passwd, $debug)
    {
        $this->debug = $debug;
        $this->db = new PDO($dsn, $user, $passwd);
    }

    private function __clone(){}

    /**
     * Singleton class
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

    public function getRowCount($table, $conditions = [])
    {
        $strFields = 'count(*) as cnt';
        $strWhere = $this->filter($conditions);
        $sql = "SELECT $strFields FROM $table $strWhere";
        $this->query($sql, array_values($conditions));

        $result = $this->sth->fetch(PDO::FETCH_ASSOC);

        if (isset($result['cnt'])) {
            return $result['cnt'];
        } else {
            return 0;
        }
    }

    public function fetch($table, $conditions = [], $fields = '', $limit = [], $orderBy = [])
    {
        $strFields = $this->selectField($fields);
        $strWhere = $this->filter($conditions);
        $strOrder = $this->getOrderBy($orderBy);
        $strLimit = $this->getLimit($limit);
        $sql = "SELECT $strFields FROM " . $table . $strWhere . $strOrder . $strLimit;
        $this->query($sql, array_values($conditions));

        return $this->sth->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll($table, $conditions = [], $fields = '', $limit = [], $orderBy = [])
    {
        $strFields = $this->selectField($fields);
        $strWhere = $this->filter($conditions);

        $strOrder = $this->getOrderBy($orderBy);
        $strLimit = $this->getLimit($limit);
        $sql = "SELECT $strFields FROM " . $table . $strWhere . $strOrder . $strLimit;
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

        $sql = "UPDATE " . $table . " SET $strFields $strConditions";
        $this->query($sql, array_merge(array_values($data), array_values($conditions)));
        $affected_rows = $this->sth->rowCount();

        return $affected_rows;
    }

    public function insert($table, $data)
    {
        $fields = array_keys($data);
        $strFields = implode(', ', $fields);
        $strValues = str_repeat('?, ', count($fields));
        $strValues = substr($strValues, 0, strlen($strValues) - 2);

        $sql = "INSERT INTO ". $table. " ($strFields) VALUES ($strValues)";
        $this->query($sql, array_values($data));

        return $this->db->lastInsertId();
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
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

    private function filter(&$conditions)
    {
        if (!$conditions) {
            return '';
        }
        $arrWhere = [];
        $conditions_key = array_keys($conditions);
        foreach ($conditions_key as $val) {

            $val = trim($val);
            if(strpos($val, ' ') === false) {
                $arrWhere[] = $val . '=?';
            } else {
                $arr_val = explode(' ', $val);

                if (in_array($arr_val[1], array('>', '>=', '<', '<=', 'like'))) {
                    $arrWhere[] = $val . '?';
                } elseif (strtoupper($arr_val[1]) == 'IN') {
                    if ($conditions[$val]) {
                        $inValues = implode("','", array_map('htmlspecialchars', $conditions[$val]));
                        $arrWhere[] = $arr_val[0] . " IN('$inValues')";
                    }
                    unset($conditions[$val]);
                } else {
                    $arrWhere[] = '-1=?';
                }
            }
        }

        if ($arrWhere) {
            $strWhere = ' WHERE ' . implode(' AND ', $arrWhere);
        } else {
            $strWhere = '';
        }
        return $strWhere;
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
            var_dump($this->db->errorInfo());
            echo '</pre>';
        }
    }

    private function getOrderBy(array $order)
    {
        if (!$order) {
            return '';
        }
        if (!(isset($order['order']) && isset($order['direct']))) {
            throw new InvalidArgumentException('Params order by must set order and direct indexs.');
        }

        return ' ORDER BY ' . $order['order'] . ' ' . $order['direct'];
    }

    private function getLimit(array $limit)
    {
        if (!$limit) {
            return '';
        }
        if (!isset($limit['count'])) {
            throw new InvalidArgumentException('Params limit must set count index');
        }

        if (!isset($limit['offset'])) {
            $result = ' LIMIT ' . $limit['count'];
        } else {
            $result = ' LIMIT ' . $limit['offset'] . ', ' . $limit['count'];
        }
        return $result;
    }
}