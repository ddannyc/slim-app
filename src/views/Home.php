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
        /* @var \App\models\Album $album */
        $album = $this->model->load('Album');
        $output['datas'] = $album->all();
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

    public function albumDetail(Request $request, Response $response, $args)
    {
        /* @var \App\models\Album $album */
        $album = $this->model->load('Album');
        $output = $album
            ->filter(['id' => $args['id']])
            ->fetch();

        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        $output['datas'] = $photo
            ->filter(['album_id' => intval($args['id'])])
            ->fetchAll();

        return $this->renderer->render($response, 'albumDetail.html', $output);
    }
}