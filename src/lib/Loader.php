<?php
/**
 * Created by PhpStorm.
 * User: KTE
 * Date: 2015/12/19
 * Time: 17:48
 */

namespace App\lib;


use Slim\Container;


/**
 * Class Loader
 * @package App\lib
 *
 * @property-read \Slim\Container
 */
class Loader {

    private $container;
    private $values = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function load($modelName)
    {
        if (!isset($this->values[$modelName])) {
            $this->container['model_name'] = $modelName;
            $this->values[$modelName] = $this->container->get('actual_model');
        }

        return $this->values[$modelName];
    }
}