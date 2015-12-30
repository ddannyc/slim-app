<?php
// Routes
$app->get('/test', '\\App\\views\\Test')->setName('test')
    ->add(function ($req, $res, $next) {
        $res->getBody()->write('<p>middlewareA</p>');
        $res = $next($req, $res);
        $res->getBody()->write('<p>middlewareA</p>');
        return $res;
    })
    ->add(function ($req, $res, $next) {
        $res->getBody()->write('<p>middlewareB</p>');
        $res = $next($req, $res);
        $res->getBody()->write('<p>middlewareB</p>');
        return $res;
    })
    ->add(function ($req, $res, $next) {
        $res->getBody()->write('<p>middlewareC</p>');
        $res = $next($req, $res);
        $res->getBody()->write('<p>middlewareC</p>');
        return $res;
    });

$app->get('/', '\\App\\views\\Home:index')->setName('home');
$app->group('/archives', function() {

    $this->get('', '\\App\\views\\Archive:index')->setName('archive_list');
    $this->get('/{year}/{month}', '\\App\\views\\Archive:detail')->setName('archives');
});

$app->group('/login', function() {
    $this->map(['GET', 'POST'], '', '\\App\\views\\Auth:login')->setName('login');
    $this->get('/out', '\\App\\views\\Auth:logout')->setName('sign_out');
});

$app->map(['GET', 'POST'], '/registry', '\\App\\views\\Auth:registry')->setName('registry');

$app->group('/admin', function () {

    $this->get('', '\\App\\views\\Admin:index')->setName('admin_index');
    $this->map(['GET', 'POST'], '/photo/{action}', '\\App\\views\\Admin:photoAction')->setName('photo_action');
});