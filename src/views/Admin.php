<?php
/**
 * Created by WayneChen.
 * Date: 2015/12/30
 * Time: 15:36
 */

namespace App\views;

use App\models\Album;
use App\models\Photo;
use App\thirdparty\SomeFun;
use Interop\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class Admin
{
    const ACTION_ADD = 'add';
    const ACTION_EDIT = 'edit';
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __get($key)
    {
        return $this->container->get($key);
    }

    public function index(Request $request, Response $response)
    {
        if ($this->user['id'] > 0) {
            $filters = ['user_id' => $this->user['id']];
            /* @var \App\models\Photo $photo */
            $photo = $this->model->load('Photo');
            $totalNums = $photo->filter($filters)->rowCount();
            $queryParams = $request->getQueryParams();
            $currentPage = isset($queryParams['p']) ? $queryParams['p'] : 1;
            $pagination = $this->pagination->show($totalNums, $currentPage);
            $output['paginator']['items'] = array_values($pagination);
            $output['paginator']['current'] = $currentPage;

            $perNums = $this->settings['pagination']['per_nums'];
            $output['datas'] = $photo->filter(['user_id' => $this->user['id']])
                ->orderBy('id', 'desc')
                ->limit($perNums, ($currentPage - 1) * $perNums)
                ->fetchAll();
        }

        $output['flash'] = $this->flash->show('admin_index');
        return $this->renderer->render($response, 'admin/index.html', $output);
    }

    public function scan(Request $request, Response $response)
    {
        $dateToday = date('Y-m-d');
        list($year, $month,) = explode('-', $dateToday);
        // Create archive
        /* @var \App\models\Archive $archive */
        $archive = $this->model->load('Archive');
        $archive->create(Photo::ARCHIVE_CLASSES, $year, $month, $this->user['id']);

        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        $pathForDb = $photo->initialPath($this->user['id'], $year, $month);
        if (!$pathForDb) {
            $this->flash->addError('admin_index', 'Initial upload path failed.');
            return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('admin_index'));
        }

        // Create album
        /* @var \App\models\Album $album */
        $album = $this->model->load('Album');

        foreach ($photo->scanPath($this->settings['path_scan']) as $dirName => $data) {
            if (!is_numeric($dirName)) {

                $newAlbum = [
                    'user_id' => $this->user['id'],
                    'name' => $dirName,
                    'created' => date('Y-m-d H:i:s'),
                    'is_public' => Album::PUBLIC_YES
                ];
                $albumId = $album->insert($newAlbum);
                if ($albumId > 0) {
                    $coverId = $photo->processScan($pathForDb, $albumId, $data);
                    if ($coverId > 0) {
                        $album->filter(['id' => $albumId])->update(['cover' => $coverId]);
                    }
                }
            }
        }

        $this->flash->addSuccess('admin_index', 'Scan completed.');
        return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('admin_index'));
    }

    public function albumAction(Request $request, Response $response, $args)
    {
        if (!$request->isPost()) {

            if ($args['action'] == self::ACTION_ADD) {
                return $this->addAlbum($request, $response);
            } else {
                return $this->editAlbum($request, $response);
            }
        } else {

            if ($args['action'] == self::ACTION_ADD) {
                return $this->submitAddAlbum($request, $response);
            } else {
                return $this->submitEditAlbum($request, $response);
            }
        }
    }

    public function photoAction(Request $request, Response $response, $args)
    {
        if (!$request->isPost()) {

            if ($args['action'] == self::ACTION_ADD) {
                return $this->addPhoto($request, $response);
            } else {
                return $this->editPhoto($request, $response);
            }
        } else {

            if ($args['action'] == self::ACTION_ADD) {
                return $this->submitAddPhoto($request, $response);
            } else {
                return $this->submitEditPhoto($request, $response);
            }
        }
    }

    private function addPhoto(Request $request, Response $response)
    {
        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        $output['action'] = self::ACTION_ADD;
        $output['isPublicOptions'] = $photo->getIsPublicOptions();
        $output['is_public'] = Photo::PUBLIC_YES;

        /* @var \App\models\Album $album */
        $album = $this->model->load('Album');
        $output['albums'] = $album->getOptions();
        $output['album_id'] = 0;
        return $this->renderer->render($response, 'admin/edit_photo.html', $output);
    }

    private function addAlbum(Request $request, Response $response)
    {
        /* @var \App\models\Album $album */
        $album = $this->model->load('Album');
        $output['action'] = self::ACTION_ADD;
        $output['isPublicOptions'] = $album->getIsPublicOptions();
        $output['is_public'] = Album::PUBLIC_YES;

        return $this->renderer->render($response, 'admin/edit_album.html', $output);
    }

    private function editPhoto(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['id'])) {

            /* @var \App\models\Photo $photo */
            $photo = $this->model->load('Photo');
            $output = $photo->filter(['id' => $queryParams['id'], 'user_id' => $this->user['id']])->fetch();
            if ($output) {
                $output['action'] = self::ACTION_EDIT;
                $output['isPublicOptions'] = $photo->getIsPublicOptions();

                /* @var \App\models\Album $album */
                $album = $this->model->load('Album');
                $output['albums'] = $album->getOptions();
                return $this->renderer->render($response, 'admin/edit_photo.html', $output);
            } else {
                return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
            }

        } else {
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
        }
    }

    private function editAlbum(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['id'])) {

            /* @var \App\models\Album $album */
            $album = $this->model->load('Album');
            $output = $album->filter(['id' => $queryParams['id'], 'user_id' => $this->user['id']])->fetch();
            if ($output) {
                $output['action'] = self::ACTION_EDIT;
                $output['isPublicOptions'] = $album->getIsPublicOptions();

                return $this->renderer->render($response, 'admin/edit_album.html', $output);
            } else {
                return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
            }

        } else {
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
        }
    }

    private function submitAddPhoto(Request $request, Response $response)
    {
        $albumId = $request->getParsedBody()['album'];
        /* @var \App\models\Album $album */
        $album = $this->model->load('Album');
        if ($album->filter(['id' => $albumId])->rowCount() <= 0) {
            $this->flash->addError('admin_index', 'Please chose an album.');
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
        }

        list($year, $month) = explode('-', date('Y-m'));
        // Create archive
        /* @var \App\models\Archive $archive */
        $archive = $this->model->load('Archive');
        $archive->create(Photo::ARCHIVE_CLASSES, $year, $month, $this->user['id']);

        /* @var \Slim\Http\UploadedFile $uploadedFile */
        $uploadedFile = $request->getUploadedFiles()['photo'];
        $fileInfo = [
            'name' => $uploadedFile->getClientFilename(),
            'fullName' => $uploadedFile->getClientFilename(),
        ];
        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        if ($pathInfo = $photo->getPathInfo($this->user['id'], $year, $month, $fileInfo)) {

            try {
                $uploadedFile->moveTo($pathInfo['moveTo']);
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
                $this->flash->addError('admin_index', 'Uploaded fail.');
                return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
            }

            if ($photo->save(
                $this->user['id'],
                $pathInfo,
                $request->getParsedBody()['description'],
                $request->getParsedBody()['is_public'],
                $albumId
            )
            ) {
                $this->flash->addSuccess('admin_index', 'Photo uploaded success.');
            } else {
                $this->flash->addError('admin_index', 'Photo uploaded falil.');
            }
        } else {
            $this->logger->error('Fail to initial path.');
            $this->flash->addError('admin_index', 'Uploaded fail.');
        }
        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
    }

    private function submitAddAlbum(Request $request, Response $response)
    {
        /* @var \App\models\Album $album */
        $album = $this->model->load('Album');
        $newAlbum = [
            'user_id' => $this->user['id'],
            'name' => $request->getParsedBody()['name'],
            'description' => $request->getParsedBody()['description'],
            'is_public' => $request->getParsedBody()['is_public']
        ];
        $albumId = $album->insert($newAlbum);

        list($year, $month) = explode('-', date('Y-m'));
        // Create archive
        /* @var \App\models\Archive $archive */
        $archive = $this->model->load('Archive');
        $archive->create(Photo::ARCHIVE_CLASSES, $year, $month, $this->user['id']);

        /* @var \Slim\Http\UploadedFile $uploadedFile */
        $uploadedFile = $request->getUploadedFiles()['cover'];
        $fileInfo = [
            'name' => $uploadedFile->getClientFilename(),
            'fullName' => $uploadedFile->getClientFilename(),
        ];
        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        if ($pathInfo = $photo->getPathInfo($this->user['id'], $year, $month, $fileInfo)) {

            try {
                $uploadedFile->moveTo($pathInfo['moveTo']);
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
                $this->flash->addError('admin_index', 'Uploaded fail.');
                return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
            }

            if ($photoId = $photo->save(
                $this->user['id'],
                $pathInfo,
                $request->getParsedBody()['description'],
                $request->getParsedBody()['is_public'],
                $albumId
            )
            ) {
                $album->filter(['id' => $albumId])->update(['cover' => $photoId]);
                $this->flash->addSuccess('admin_index', 'Photo uploaded success.');
            } else {
                $this->flash->addError('admin_index', 'Photo uploaded falil.');
            }
        } else {
            $this->logger->error('Fail to initial path.');
            $this->flash->addError('admin_index', 'Uploaded fail.');
        }
        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
    }

    private function submitEditPhoto(Request $request, Response $response)
    {
        $albumId = $request->getParsedBody()['album'];
        /* @var \App\models\Album $album */
        $album = $this->model->load('Album');
        if ($album->filter(['id' => $albumId])->rowCount() <= 0) {
            $this->flash->addError('admin_index', 'Please chose an album.');
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
        }

        $parsePost = $request->getParsedBody();
        $input = [
            'description' => $parsePost['description'],
            'edited' => date('Y-m-d H:i:s'),
            'is_public' => $parsePost['is_public'],
            'album_id' => $albumId
        ];
        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        $updateStatus = $photo->updateById($parsePost['id'], $this->user['id'], $input);
        if ($updateStatus) {
            $this->flash->addSuccess('admin_index', 'Edited success.');
        } else {
            $this->flash->addError('admin_index', 'Edited falil.');
        }
        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
    }

    private function submitEditAlbum(Request $request, Response $response)
    {
        $albumId = $request->getParsedBody()['id'];
        /* @var \App\models\Album $album */
        $album = $this->model->load('Album');
        if ($album->filter(['id' => $albumId])->rowCount() <= 0) {
            $this->flash->addError('admin_index', 'Invalid data posted.');
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
        }

        $parsePost = $request->getParsedBody();
        $input = [
            'name' => $parsePost['name'],
            'description' => $parsePost['description'],
            'is_public' => $parsePost['is_public'],
        ];
        $filter = [
            'user_id' => $this->user['id'],
            'id' => $albumId
        ];
        $updateStatus = $album->filter($filter)->update($input);
        if ($updateStatus) {
            $this->flash->addSuccess('admin_index', 'Edited success.');
        } else {
            $this->flash->addError('admin_index', 'Edited falil.');
        }
        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
    }
}