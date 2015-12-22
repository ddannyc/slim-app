<?php
/**
 * Created by PhpStorm.
 * User: KTE
 * Date: 2015/12/17
 * Time: 17:51
 */

namespace App\middleware;



use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Router;

class Permission {

    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }
    /**
     * middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('login'));
        }

        if ($next) {
            $response = $next($request, $response);
        }
        return $response;
    }
}