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
 * @property-read \Slim\Container
 * @property-read \App\lib\Mydb $db
 */
abstract class Model {

    protected $container;
    protected $db;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->db = $this->container->get('db');
    }
}