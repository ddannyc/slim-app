<?php
/**
 * Created by WayneChen.
 * Date: 2015/12/30
 * Time: 15:21
 */

namespace App\views;


use Interop\Container\ContainerInterface;

/**
 * Class Auth
 * @package App\views
 *
 * @property-read \App\models\User $user
 */
class Auth
{
    private $container;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->user = $this->model->load('User');
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
            $user = $this->user->select(['id', 'name', 'salt', 'password'])
                ->filter(['name' => $post['username']])
                ->fetch();

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
            $user = $this->user->select('name')->filter(['name' => $post['username']])->fetch();

            if (!$user) {
                $salt = time();
                $newUser = [
                    'name' => $post['username'],
                    'salt' => $salt,
                    'password' => md5($salt . $post['password'])
                ];
                $this->user->insert($newUser);
            }
            $response = $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('login'));
            return $response;
        }
    }
}