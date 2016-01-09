<?php
/**
 * Created by wayne.
 * Date: 2015/12/17
 * Time: 17:51
 */

namespace App\middleware;


use Interop\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class Permission
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * middleware invokable class
     *
     * @param RequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @param  callable $next Next middleware
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $user = $this->container->get('user');
        if ($user['id'] === 0) {
            $this->container->flash->addError('admin_index', 'Please login first.');
            return $response->withStatus(302)->withHeader('Location ', $this->container->get('router')->pathFor('admin_index'));
        }

        if ($next) {
            $response = $next($request, $response);
        }
        return $response;
    }
}