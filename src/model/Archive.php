<?php
/**
 * Created by wayne.
 * Date: 2015/12/19
 * Time: 16:23
 */

namespace App\model;


use App\lib\Model;

class Archive extends Model
{
    private static $table = 'archives';

    public function all()
    {
        return $this->db->fetchAll(self::$table);
    }
}