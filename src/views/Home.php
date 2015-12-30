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
        $this->db->orderBy('id', 'desc');
        $this->db->limit(30);
        $output['datas'] = $this->db->fetchAll('photos', ['user_id' => $this->user['id']]);

        return $this->renderer->render($response, 'home.html', $output);
    }
}