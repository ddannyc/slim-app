<?php
/**
 * Created by wayne.
 * Date: 2015/12/19
 * Time: 16:23
 */

namespace App\models;


use App\lib\Model;
use App\thirdparty\SomeFun;
use Psr\Http\Message\UploadedFileInterface;

class Photo extends Model
{
    protected $table = 'photos';
    const MAX_WIDTH = 1920;
    const MAX_HEIGHT = 1080;
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

    public function save($userId, $uploadFile, $description, $pathForDb)
    {
        /* @var \Slim\Http\UploadedFile $uploadFile */
        if (!($uploadFile && $uploadFile instanceof UploadedFileInterface)) {
            return false;
        }

        $pathStatic = $this->settings['path_static'];
        $extName = pathinfo($uploadFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = SomeFun::guidv4();
        $pathMoveTo = $pathStatic . $pathForDb . $filename . ".$extName";

        try {
            $uploadFile->moveTo($pathMoveTo);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            return false;
        }

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
                'user_id' => $userId,
                'name' => $uploadFile->getClientFilename(),
                'photo' => $pathForDb . $filename . ".$extName",
                'thumbnail' => $pathForDb . $filename . "_thumbnail.$extName",
                'description' => $description,
                'created' => date('Y-m-d H:i:s')
            ];
            return $this->insert($dataSave);
        }
        return false;
    }

    public function initialPath($year, $month)
    {
        $pathForDb = 'data/' . $year . '/' . $month . '/';
        if (!$this->createPath($this->settings['path_static'] . $pathForDb)) {
            return false;
        }
        return $pathForDb;
    }

    private function copyResize($src, $dst, $resize_width, $resize_height)
    {
        if (!is_file($src)) {
            return false;
        }

        list($width, $height, $imageType) = getimagesize($src);
        if (!in_array($imageType, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
            $this->logger->info('Invalid file was uploaded.');
            $this->flash->addError('admin_index', 'Invalid file was uploaded.');
            return false;
        }
        if ($width * $height <= $resize_width * $resize_height) {
            // Do not resize when the source file smaller than the new size
            return true;
        }
        if ($height > $resize_height) {
            $resize_width = floor(($resize_height / $height) * $width);
        }
        if ($width > $resize_width) {
            $resize_height = floor(($resize_width / $width) * $height);
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
            $this->logger->info($e->getMessage());
            return false;
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