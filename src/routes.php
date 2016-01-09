<?php
/* @var Slim\App $app */
// Routes
$app->get('/test', 'App\\views\\Test:index')->setName('test');

$app->get('/', 'App\\views\\Home:index')->setName('home');
$app->get('/p/{id:\d+}', 'App\\views\\Home:p')->setName('photoDetail');
$app->group('/archives', function() {

    $this->get('', 'App\\views\\Archive:index')->setName('archive_list');
    $this->get('/{year}/{month}', 'App\\views\\Archive:detail')->setName('archives');
})->add('App\middleware\Permission');

$app->group('/login', function() {
    $this->map(['GET', 'POST'], '', 'App\\views\\Auth:login')->setName('login');
    $this->get('/out', 'App\\views\\Auth:logout')->setName('sign_out');
});

$app->map(['GET', 'POST'], '/registry', 'App\\views\\Auth:registry')->setName('registry');

$app->group('/admin', function () {

    $this->get('', 'App\\views\\Admin:index')->setName('admin_index');
    $this->map(['GET', 'POST'], '/photo/{action}', 'App\\views\\Admin:photoAction')->setName('photo_action')->add('App\middleware\Permission');
    $this->get('/scan', 'App\\views\\Admin:scan')->setName('scan')->add('App\middleware\Permission');
});