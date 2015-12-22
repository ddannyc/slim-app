<?php
/**
 * Created by wayne.
 * Date: 2015/12/19
 * Time: 16:29
 */

namespace App\lib;


abstract class Model {

    protected $db;

    public function __construct(MyDb $db)
    {
        $this->db = $db;
    }
}