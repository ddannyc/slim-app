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
        $totalNums = $this->db->getRowCount('photos', ['user_id' => $this->user['id']]);
        $queryParams = $request->getQueryParams();
        $currentPage = isset($queryParams['p']) ? $queryParams['p'] : 1;
        $pagination = $this->pagination->show($totalNums, $currentPage);
        foreach ($pagination as $val) {
            $output['paginator']['items'][] = $val;
        }
        $output['paginator']['current'] = $currentPage;

        $this->db->orderBy('id', 'desc');
        $perNums = $this->settings['pagination']['per_nums'];
        $this->db->limit(['offset' => ($currentPage - 1) * $perNums, 'count' => $perNums]);
        $output['datas'] = $this->db->fetchAll('photos', ['user_id' => $this->user['id']]);

        $output['user'] = $this->user;
        return $this->renderer->render($response, 'admin/index.html', $output);
    }

    public function photoAction(Request $request, Response $response, $args)
    {
        if (!$request->isPost()) {

            if ($args['action'] == 'add') {
                $output['action'] = $args['action'];
                return $this->renderer->render($response, 'admin/edit_photo.html', $output);
            } else {

                $queryParams = $request->getQueryParams();
                if (isset($queryParams['id'])) {
                    $output = $this->db->fetch('photos', ['id' => $queryParams['id'], 'user_id' => $this->user['id']]);
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

            $photo = $this->model->load('Photo');
            if ($args['action'] == 'add') {

                $input = [
                    'file' => $request->getUploadedFiles()['photo'],
                    'description' => $request->getParsedBody()['description'],
                    'user_id' => $this->user['id']
                ];
                $photo->save($input);
            } else {

                $parsePost = $request->getParsedBody();
                $input = [
                    'description' => $parsePost['description']
                ];
                $photo->updateById($parsePost['id'], $this->user['id'], $input);
            }
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
        }
    }
}