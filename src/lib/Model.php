<?php
/**
 * Created by wayne.
 * Date: 2015/12/19
 * Time: 16:29
 */

namespace App\lib;


use Slim\Container;

/**
 * Class Model
 * @package App\lib
 *
 * @property-read \Slim\Container $container
 * @property-read \App\lib\Mydb $db
 */
abstract class Model
{
    protected $table;
    protected $container;
    protected $db;
    protected $filter;
    protected $selectFields;
    protected $limit;
    protected $order;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->db = $this->container->get('db');
        $this->initialize();
    }

    public function select($fields)
    {
        $this->selectFields = $fields;
        return $this;
    }

    public function filter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    public function orderBy($order, $direct = 'DESC')
    {
        $this->order = [
            'order' => is_string($order) ? $order : implode(',', $order),
            'direct' => $direct
        ];
        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        $this->limit['count'] = $limit;
        if ($offset > 0) {
            $this->limit['offset'] = $offset;
        }
        return $this;
    }

    public function rowCount()
    {
        $result = $this->db->getRowCount($this->table, $this->filter);
        $this->initialize();
        return $result;
    }

    public function fetch()
    {
        $result = $this->db->fetch($this->table, $this->filter, $this->selectFields, $this->limit, $this->order);
        $this->initialize();
        return $result;
    }

    public function fetchAll()
    {
        $result = $this->db->fetchAll($this->table, $this->filter, $this->selectFields, $this->limit, $this->order);
        $this->initialize();
        return $result;
    }

    public function update(array $data)
    {
        $result = $this->db->update($this->table, $data, $this->filter);
        $this->initialize();
        return $result;
    }

    public function insert(array $data)
    {
        $result = $this->db->insert($this->table, $data);
        return $result;
    }

    private function initialize()
    {
        $this->selectFields = [];
        $this->filter = [];
        $this->limit = [];
        $this->order = [];
    }
}