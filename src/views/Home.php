<?php
/**
 * Created by WayneChen.
 * Date: 2015/12/30
 * Time: 11:07
 */

namespace App\views;


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
        $output['datas'] = $photo->filter(['user_id' => $this->user['id']])
            ->orderBy('id', 'desc')
            ->limit(20)
            ->fetchAll();

        return $this->renderer->render($response, 'home.html', $output);
    }
}