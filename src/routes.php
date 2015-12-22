<?php
// Routes
$app->get('/test', function($request, $response) {

    var_dump($this->settings['path_static']);
    $photo = $this->model->load('Photo');
    $archive = $this->model->load('Archive');

    var_dump($photo->all(), $archive->all());die;
    //$this->logger->info("Slim-Skeleton '/' route");
    $body = $response->getBody();
    $body->write();
    return $response;
});

$app->get('/', function ($request, $response) {

    $this->db->orderBy('id', 'desc');
    $this->db->limit(30);
    $output['datas'] = $this->db->fetchAll('photos');

    return $this->renderer->render($response, 'home.html', $output);
})->setName('home')->add(new \App\middleware\Permission($app->getContainer()['router']));

$app->group('/archives', function() {

    $this->get('', function($request, $response){

        $srcArchives = $this->db->fetchAll('archives', ['classes'=>1]);

        $output['archives'] = $srcArchives;
        $output['datas'] = [];

        return $this->renderer->render($response, 'archives.html', $output);
    })->setName('archive_list');

    $this->get('/{year}/{month}', function($request, $response, $args){

        $srcArchives = $this->db->fetchAll('archives', ['classes'=>1]);
        $output['archives'] = $srcArchives;

        $timeFrom = mktime(0, 0, 0, $args['month'], 1, $args['year']);
        $timeTo = mktime(0, 0, 0, $args['month'] + 1, 1, $args['year']);
        $filter = [
            'created >=' => date('Y-m-d H:i:s', $timeFrom),
            'created <' => date('Y-m-d H:i:s', $timeTo)
        ];
        $output['datas'] = $this->db->fetchAll('photos', $filter);

        return $this->renderer->render($response, 'archives.html', $output);
    })->setName('archives');
})->add(new \App\middleware\Permission($app->getContainer()['router']));

$app->group('/login', function() {
    $this->get('', function($request, $response) {

        return $this->renderer->render($response, 'admin/login.html');
    })->setName('login');

    $this->post('/in', function($request, $response) {

        $post = $request->getParsedBody();
        $user = $this->db->fetch('users', ['name'=>$post['username']], ['id', 'name', 'salt', 'password']);

        if ($user) {

            $checkPasswd = md5($user['salt']. $post['password']);
            if ($checkPasswd == $user['password']) {
                $_SESSION['user'] = ['id'=>$user['id'], 'name'=>$user['name']];
                return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('home'));
            }
        }

        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('login'));

    })->setName('sign_in');

    $this->get('/out', function($request, $response) {

        unset($_SESSION['user']);
        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('login'));
    })->setName('sign_out');
});

$app->map(['GET', 'POST'], '/register', function($request, $response) {

    if (!$request->isPost()) {
        return $this->renderer->render($response, 'admin/register.html');
    } else {

        $post = $request->getParsedBody();
        $user = $this->db->fetch('users', ['name'=>$post['username']], 'name');

        if (!$user) {
            $salt = time();
            $newUser = [
                'name' => $post['username'],
                'salt' => $salt,
                'password' => md5($salt. $post['password'])
            ];
            $this->db->save('users', $newUser);
        }
        $response = $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('login'));
        return $response;
    }
})->setName('register');

$app->group('/admintxy', function() {

    $this->get('', function($request, $response) {

        $totalNums = $this->db->getRowCount('photos');
        $queryParams = $request->getQueryParams();
        $currentPage = isset($queryParams['p'])? $queryParams['p']: 1;
        $perNums = 3;
        $pagination = $this->pagination->show($totalNums, $perNums, $currentPage);
        foreach ($pagination as $val) {
            $output['paginator']['items'][] = $val;
        }
        $output['paginator']['current'] = $currentPage;

        $this->db->orderBy('id', 'desc');
        $this->db->limit(['offset' => ($currentPage-1)*$perNums, 'count'=>$perNums]);
        $output['datas'] = $this->db->fetchAll('photos');

        $output['username'] = $_SESSION['user']['name'];
        return $this->renderer->render($response, 'admin/index.html', $output);
    })->setName('admin_index');

    $this->map(['GET', 'POST'], '/photo/{action}', function(\Slim\Http\Request $request, $response, $args) {

        if ($request->isGet()) {

            if ($args['action'] == 'add') {
                $output['action'] = $args['action'];
                return $this->renderer->render($response, 'admin/edit_photo.html', $output);
            } else {

                $queryParams = $request->getQueryParams();
                if (isset($queryParams['id'])) {
                    $output = $this->db->fetch('photos', ['id'=>$queryParams['id']]);
                    if ($output) {
                        $output['action'] = $args['action'];
                        return $this->renderer->render($response, 'admin/edit_photo.html', $output);
                    } else {
                        return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
                    }

                } else {
                    return $response->withStatus(302)->withHeader('Location ', $this->router->pathFor('admin_index'));
                }
            }
        } else {

            if ($args['action'] == 'add') {

                list($year, $month) = explode('-', date('Y-m'));
                $photo = $this->model->load('Photo');
                $savePath = $photo->initialSavePath($this->settings['path_static'], $year, $month);
                $file = $request->getUploadedFiles()['photo'];
                $parsedBody = $request->getParsedBody();
                var_dump($file, $parsedBody);die;
            } else {}
        }
    })->setName('photo_action');
})->add(new \App\middleware\Permission($app->getContainer()['router']));