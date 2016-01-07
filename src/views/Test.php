<?php
/**
 * Created by WayneChen.
 * Date: 2015/12/30
 * Time: 11:01
 */

namespace App\views;


use Interop\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class Test
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
        return $response;
    }

    public function __invoke(Request $request, Response $response)
    {
        $this->logger->info('log info');
        $response->getBody()->write('Hello world');
        return $response;
    }
}