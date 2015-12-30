<?php
/**
 * Created by WayneChen.
 * Date: 2015/12/30
 * Time: 15:11
 */

namespace App\views;


use Interop\Container\ContainerInterface;

class Archive
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

    public function index($request, $response)
    {

        /* @var \App\models\Archive $archive */
        $archive = $this->model->load('Archive');
        $srcArchives = $archive->filter(['user_id' => $this->user['id'], 'classes' => 1])->fetchAll();

        $output['archives'] = $srcArchives;
        $output['datas'] = [];

        return $this->renderer->render($response, 'archives.html', $output);
    }

    public function detail($request, $response, $args)
    {
        /* @var \App\models\Archive $archive */
        $archive = $this->model->load('Archive');
        $srcArchives = $archive->filter(['user_id' => $this->user['id'], 'classes' => 1])->fetchAll();
        $output['archives'] = $srcArchives;

        $timeFrom = mktime(0, 0, 0, $args['month'], 1, $args['year']);
        $timeTo = mktime(0, 0, 0, $args['month'] + 1, 1, $args['year']);
        $filter = [
            'user_id' => $this->user['id'],
            'created >=' => date('Y-m-d H:i:s', $timeFrom),
            'created <' => date('Y-m-d H:i:s', $timeTo)
        ];

        /* @var \App\models\Photo $photo */
        $photo = $this->model->load('Photo');
        $output['datas'] = $photo->filter($filter)->fetchAll();

        return $this->renderer->render($response, 'archives.html', $output);
    }
}