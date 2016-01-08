<?php
/**
 * Created by WayneChen.
 * Date: 2015/12/30
 * Time: 15:36
 */

namespace App\views;

use App\models\Photo;
use App\thirdparty\SomeFun;
use Interop\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class Admin
{
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

        $output['user'] = $this->user;
        $output['flash'] = $this->flash->show('admin_index');
        return $this->renderer->render($response, 'admin/index.html', $output);
    }

    public function scan(Request $request, Response $response)
    {
        list($year, $month) = explode('-', date('Y-m'));
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
        $pathStatic = $this->settings['path_static'];
        foreach ($photo->scanPath($this->settings['path_scan']) as $file) {
            $extName = pathinfo($file['fullName'], PATHINFO_EXTENSION);
            $filename = SomeFun::guidv4();
            $pathInfo = [
                'path_static' => $pathStatic,
                'tmpName' => $file['name'],
                'ext' => $extName,
                'filename' => $filename,
                'db' => $pathForDb,
                'moveTo' => $pathStatic . $pathForDb . $filename . ".$extName"
            ];

            try {
                rename($file['fullName'], $pathInfo['moveTo']);
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }

            $photo->save(
                $this->user['id'],
                $pathInfo,
                $request->getParsedBody()['description']
            );
        }

        $this->flash->addSuccess('admin_index', 'Scan completed.');
        return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('admin_index'));
    }

    public function photoAction(Request $request, Response $response, $args)
    {
        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        if (!$request->isPost()) {

            if ($args['action'] == 'add') {
                $output['action'] = $args['action'];
                return $this->renderer->render($response, 'admin/edit_photo.html', $output);
            } else {

                $queryParams = $request->getQueryParams();
                if (isset($queryParams['id'])) {

                    $output = $photo->filter(['id' => $queryParams['id'], 'user_id' => $this->user['id']])->fetch();
                    if ($output) {
                        $output['action'] = $args['action'];
                        return $this->renderer->render($response, 'admin/edit_photo.html', $output);
                    } else {
                        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
                    }

                } else {
                    return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
                }
            }
        } else {

            if ($args['action'] == 'add') {

                list($year, $month) = explode('-', date('Y-m'));
                // Create archive
                /* @var \App\models\Archive $archive */
                $archive = $this->model->load('Archive');
                $archive->create(Photo::ARCHIVE_CLASSES, $year, $month, $this->user['id']);

                if ($pathForDb = $photo->initialPath($this->user['id'], $year, $month)) {

                    /* @var \Slim\Http\UploadedFile $uploadedFile */
                    $uploadedFile = $request->getUploadedFiles()['photo'];
                    $pathStatic = $this->settings['path_static'];
                    $extName = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $filename = SomeFun::guidv4();
                    $pathInfo = [
                        'path_static' => $pathStatic,
                        'tmpName' => $uploadedFile->getClientFilename(),
                        'ext' => $extName,
                        'filename' => $filename,
                        'db' => $pathForDb,
                        'moveTo' => $pathStatic . $pathForDb . $filename . ".$extName"
                    ];

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
                        $request->getParsedBody()['description']
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
            } else {

                $parsePost = $request->getParsedBody();
                $input = [
                    'description' => $parsePost['description'],
                    'edited' => date('Y-m-d H:i:s'),
                ];
                $updateStatus = $photo->updateById($parsePost['id'], $this->user['id'], $input);
                if ($updateStatus) {
                    $this->flash->addSuccess('admin_index', 'Edited success.');
                } else {
                    $this->flash->addError('admin_index', 'Edited falil.');
                }
            }
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
        }
    }
}