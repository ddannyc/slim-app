<?php
/**
 * Created by WayneChen.
 * Date: 2016/1/18
 * Time: 11:51
 */

namespace App\models;


use App\lib\Model;

class Album extends Model
{
    protected $table = 'albums';

    public function all()
    {
        $datas = $this->filter(['user_id' => $this->user['id']])
            ->orderBy(['weight', 'id'], 'desc')
            ->fetchAll();

        if ($datas) {
            /* @var \App\models\Photo $photo */
            $photo = $this->model->load('Photo');
            foreach ($datas as $key => $val) {
                $datas[$key]['cover'] = $photo
                    ->filter(['id' => $val['cover']])
                    ->select(['thumbnail'])
                    ->fetch()['thumbnail'];
            }
        }
        return $datas;
    }
}