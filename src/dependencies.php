<?php
// DIC configuration
$container = $app->getContainer();
// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    $view = new \Slim\Views\Twig($settings['template_path'], [
        'cache' => $settings['cache_path']
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $c['router'],
        $c['request']->getUri()
    ));

    return $view;
};
// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], Monolog\Logger::DEBUG));
    return $logger;
};

// my db
$container['db'] = function($c) {
    $settings = $c->get('settings')['db_config'];

    return \App\lib\MyDb::getInstance($settings['dsn'], $settings['user'], $settings['password'], $settings['debug']);
};

// model
$container['model'] = function($c) {
    return new \App\lib\Loader($c);
};
$container['actual_model'] = $container->factory(function($c) {

    $modelName = $c->get('model_name');
    if ($modelName) {

        $modelFullName = 'App\\model\\'. $modelName;
        return new $modelFullName($c->get('db'));
    }
    return false;
});

// pagination
$container['pagination'] = function($c) {
    return new \App\lib\Pagination();
};