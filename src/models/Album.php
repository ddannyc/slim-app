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
    const PUBLIC_YES = 1;
    const PUBLIC_NO = 0;
    protected $table = 'albums';

    public static function getIsPublicOptions()
    {
        return [
            'private' => self::PUBLIC_NO,
            'public' => self::PUBLIC_YES
        ];
    }

    public function all()
    {
        if ($this->user['id'] > 0) {
            $filter = ['user_id' => $this->user['id']];
        } else {
            $filter = ['is_public' => Album::PUBLIC_YES];
        }
        $datas = $this->filter($filter)
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

    public function getOptions()
    {
        $filter = ['user_id' => $this->user['id']];
        $datas = $this->select(['id', 'name'])
            ->filter($filter)
            ->orderBy(['weight', 'id'], 'desc')
            ->fetchAll();

        $result[0] = 'chose an album';
        foreach ($datas as $val) {
            $result[$val['id']] = $val['name'];
        }
        return $result;
    }
}