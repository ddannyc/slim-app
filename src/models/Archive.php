<?php
/**
 * Created by wayne.
 * Date: 2015/12/19
 * Time: 16:23
 */

namespace App\models;


use App\lib\Model;

class Archive extends Model
{
    protected $table = 'archives';

    public function all()
    {
        return $this->fetchAll();
    }

    public function create($classes, $year, $month, $userId)
    {
        $data = [
            'year' => $year,
            'month' => $month,
            'classes' => $classes,
            'user_id' => $userId
        ];
        return $this->insert($data);
    }
}