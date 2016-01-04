<?php
/**
 * Created by wayne.
 * Date: 2015/12/19
 * Time: 16:23
 */

namespace App\models;


use App\lib\Model;
use App\thirdparty\SomeFun;

class Photo extends Model
{
    protected $table = 'photos';
    const MAX_WIDTH = 800;
    const MAX_HEIGHT = 600;
    const MIN_WIDTH = 200;
    const MIN_HEIGHT = 200;
    const ARCHIVE_CLASSES = 1;

    public function all()
    {
        return $this->fetchAll();
    }

    public function updateById($photoId, $userId, $data)
    {
        $this->filter(['id' => $photoId, 'user_id' => $userId]);
        return $this->update($data);
    }

    public function save(array $data)
    {
        $pathStatic = $this->container->settings['path_static'];
        list($year, $month) = explode('-', date('Y-m'));

        $pathForDb = 'data/'. $year. '/'. $month. '/';
        if (!$this->createPath($pathStatic . $pathForDb)) {
            return false;
        }

        // Create archive
        $archive = $this->container->model->load('Archive');
        $archive->create(self::ARCHIVE_CLASSES, $year, $month, $data['user_id']);

        $extName = pathinfo($data['file']->getClientFilename(), PATHINFO_EXTENSION);
        $filename = SomeFun::guidv4();
        $pathMoveTo = $pathStatic . $pathForDb . $filename . ".$extName";
        $data['file']->moveTo($pathMoveTo);

        // Resize photo
        $imgSave = $this->copyResize($pathMoveTo, $pathMoveTo, self::MAX_WIDTH, self::MAX_HEIGHT);

        if ($imgSave) {
            // Create thumbnail
            $thumbMoveTo = $pathStatic . $pathForDb . $filename . "_thumbnail.$extName";
            $imgSave = $this->copyResize($pathMoveTo, $thumbMoveTo, self::MIN_WIDTH, self::MIN_HEIGHT);
        } else {
            // unlink invalid file
            unlink($pathMoveTo);
        }

        if ($imgSave) {
            $dataSave = [
                'user_id' => $data['user_id'],
                'photo' => $pathForDb . $filename . ".$extName",
                'thumbnail' => $pathForDb . $filename . "_thumbnail.$extName",
                'description' => $data['description']
            ];
            return $this->insert($dataSave);
        }
        return false;
    }

    private function copyResize($src, $dst, $resize_width, $resize_height)
    {
        if (!is_file($src)) {
            return false;
        }

        list($width, $height, $imageType) = getimagesize($src);
        if (!in_array($imageType, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
            $this->container->logger->info('Invalid file was uploaded.');
            return false;
        }
        if ($width * $height <= $resize_width * $resize_height) {
            // Do not resize when the source file smaller than the new size
            return true;
        }
        if ($height > $resize_height) {
            $resize_width = ceil(($resize_height / $height) * $width);
        }
        if ($width > $resize_width) {
            $resize_height = ceil(($resize_width / $width) * $height);
        }

        try {

            switch ($imageType) {
                case IMAGETYPE_GIF:
                    $image = imagecreatefromgif($src);
                    break;
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($src);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($src);
                    break;
                default:
                    return false;
            }

            $imResize = imagecreatetruecolor($resize_width, $resize_height);
            imagecopyresampled($imResize, $image, 0, 0, 0, 0, $resize_width, $resize_height, $width, $height);
            imagejpeg($imResize, $dst);
            imagedestroy($image);
            imagedestroy($imResize);
        } catch (\Exception $e) {
            $this->container->logger->info($e->getMessage());
        }

        return true;
    }

    private function createPath($path)
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, 0777, true);
    }
}