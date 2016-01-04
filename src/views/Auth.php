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

            $flash = $this->container['flash']->get('login');
            return $this->renderer->render($response, 'admin/login.html', ['flash' => $flash]);
        } else {

            $post = $request->getParsedBody();
            $user = $this->user->select(['id', 'name', 'salt', 'password'])
                ->filter(['name' => $post['username']])
                ->fetch();

            if ($user) {

                $checkPasswd = md5($user['salt'] . $post['password']);
                if ($checkPasswd == $user['password']) {
                    $this->container['session']['user'] = ['id' => $user['id'], 'name' => $user['name']];
                    return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
                }
            }

            $this->container->flash->set('admin_index', 'Invalid username or password input.');
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
        }
    }

    public function logout($request, $response)
    {
        unset($this->container['session']['user']);
        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
    }

    public function registry($request, $response)
    {

        if (!$request->isPost()) {
            return $this->renderer->render($response, 'admin/registry.html');
        } else {

            $post = $request->getParsedBody();
            if (!($post['username'] && $post['password'])) {
                $this->container->flash->set('admin_index', 'Username and password are required.');
                $response = $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
                return $response;
            }

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
            $response = $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
            return $response;
        }
    }
}