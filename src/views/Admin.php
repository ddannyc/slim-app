<?php
/**
 * Created by WayneChen.
 * Date: 2015/12/30
 * Time: 15:36
 */

namespace App\views;

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

                $input = [
                    'file' => $request->getUploadedFiles()['photo'],
                    'description' => $request->getParsedBody()['description'],
                    'user_id' => $this->user['id'],
                    'created' => date('Y-m-d H:i:s'),
                ];
                if ($photo->save($input)) {
                    $this->flash->addSuccess('admin_index', 'Photo uploaded success.');
                } else {
                    $this->flash->addError('admin_index', 'Photo uploaded falil.');
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