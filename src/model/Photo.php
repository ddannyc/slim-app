<?php
/**
 * Created by wayne.
 * Date: 2015/12/19
 * Time: 16:23
 */

namespace App\model;


use App\lib\Model;
use App\thirdparty\SomeFun;

class Photo extends Model
{
    const ARCHIVE_CLASSES = 1;
    private static $table = 'photos';

    public function all()
    {
        return $this->db->fetchAll(self::$table);
    }

    public function save($data)
    {
        $pathStatic = $this->container->settings['path_static'];
        list($year, $month) = explode('-', date('Y-m'));

        $pathForDb = 'data/'. $year. '/'. $month. '/';
        if (!$this->createPath($pathStatic . $pathForDb)) {
            return false;
        }

        // Create archive
        $archive = $this->container->model->load('Archive');
        $archive->create(self::ARCHIVE_CLASSES, $year, $month);

        $filename = sprintf('%s.%s', SomeFun::guidv4(), pathinfo($data['file']->getClientFilename(), PATHINFO_EXTENSION));
        $pathForDb = $pathForDb. $filename;
        $data['file']->moveTo($pathStatic. $pathForDb);

        $dataSave = [
            'user_id' => $_SESSION['user']['id'],
            'photo' => $pathForDb,
            'description' => $data['description']
        ];
        return $this->db->save(self::$table, $dataSave);
    }

    private function createPath($path)
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, 0777, true);
    }
}