<?php
/**
 * Created by WayneChen.
 * Date: 2015/12/30
 * Time: 11:07
 */

namespace App\views;


use App\models\Photo;
use Interop\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class Home
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
        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        if ($this->user['id'] <= 0) {
            $filter = ['is_public' => Photo::PUBLIC_YES];
        } else {
            $filter = ['user_id' => $this->user['id']];
        }
        $output['datas'] = $photo->filter($filter)
            ->orderBy('id', 'desc')
            ->limit(50)
            ->fetchAll();

        $output['flash'] = $this->flash->show('home');
        return $this->renderer->render($response, 'home.html', $output);
    }

    public function p(Request $request, Response $response, $args)
    {
        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        $output = $photo
            ->filter(['id' => $args['id'], 'user_id' => $this->user['id']])
            ->fetch();
        if (!$output) {
            $this->flash->addError('home', 'Photo not exists.');
            return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('home'));
        }
        return $this->renderer->render($response, 'p.html', $output);
    }
}