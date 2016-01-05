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
    private $userModel;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->userModel = $this->model->load('User');
    }

    public function __get($key)
    {
        return $this->container->get($key);
    }

    public function login($request, $response)
    {
        if (!$request->isPost()) {

            $flash = $this->flash->get('login');
            return $this->renderer->render($response, 'admin/login.html', ['flash' => $flash]);
        } else {

            $post = $request->getParsedBody();
            $user = $this->userModel->select(['id', 'name', 'salt', 'password'])
                ->filter(['name' => $post['username']])
                ->fetch();

            if ($user) {
                $checkPasswd = md5($user['salt'] . $post['password']);
                if ($checkPasswd == $user['password']) {
                    $this->session['user'] = ['id' => $user['id'], 'name' => $user['name']];
                    return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
                }
            }

            $this->flash->addError('admin_index', 'Invalid username or password.');
            return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
        }
    }

    public function logout($request, $response)
    {
        unset($this->session['user']);
        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
    }

    public function registry($request, $response)
    {

        if (!$request->isPost()) {
            $output['flash'] = $this->flash->show('registry');
            return $this->renderer->render($response, 'admin/registry.html', $output);
        } else {

            $post = $request->getParsedBody();
            if (!($post['username'] && $post['password'])) {
                $this->flash->addError('admin_index', 'Username and password are required.');
                $response = $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
                return $response;
            }

            $user = $this->userModel->select('name')->filter(['name' => $post['username']])->fetch();

            if (!$user) {
                $salt = time();
                $newUser = [
                    'name' => $post['username'],
                    'salt' => $salt,
                    'password' => md5($salt . $post['password'])
                ];

                if ($this->userModel->insert($newUser)) {
                    $this->session['user'] = ['id' => $this->userModel->lastInsertId(), 'name' => $post['username']];
                    $this->flash->addSuccess('admin_index', 'Registry success.');
                } else {
                    $this->flash->addError('registry', 'Registry fail.');
                    return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('registry'));
                }
            } else {
                $this->flash->addError('registry', 'Sorry, username is already exists, please try other username.');
                return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('registry'));
            }
            $response = $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
            return $response;
        }
    }
}