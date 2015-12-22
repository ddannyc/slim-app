<?php
/**
 * Created by PhpStorm.
 * User: KTE
 * Date: 2015/12/19
 * Time: 16:23
 */

namespace App\model;


use App\lib\Model;

class Photo extends Model
{
    private static $table = 'photos';

    public function all()
    {
        return $this->db->fetchAll(self::$table);
    }
}