<?php
/**
 * Created by WayneChen.
 * Date: 2015/12/30
 * Time: 15:21
 */

namespace App\views;


use Interop\Container\ContainerInterface;

class Auth
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

    public function login($request, $response)
    {
        if (!$request->isPost()) {
            return $this->renderer->render($response, 'admin/login.html');
        } else {

            $post = $request->getParsedBody();
            $user = $this->db->fetch('users', ['name' => $post['username']], ['id', 'name', 'salt', 'password']);

            if ($user) {

                $checkPasswd = md5($user['salt'] . $post['password']);
                if ($checkPasswd == $user['password']) {
                    $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name']];
                    return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
                }
            }

            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('login'));
        }
    }

    public function logout($request, $response)
    {
        unset($_SESSION['user']);
        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('login'));
    }

    public function registry($request, $response)
    {

        if (!$request->isPost()) {
            return $this->renderer->render($response, 'admin/registry.html');
        } else {

            $post = $request->getParsedBody();
            $user = $this->db->fetch('users', ['name' => $post['username']], 'name');

            if (!$user) {
                $salt = time();
                $newUser = [
                    'name' => $post['username'],
                    'salt' => $salt,
                    'password' => md5($salt . $post['password'])
                ];
                $this->db->save('users', $newUser);
            }
            $response = $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('login'));
            return $response;
        }
    }
}