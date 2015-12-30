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

        $srcArchives = $this->db->fetchAll('archives', ['user_id' => $this->user['id'], 'classes' => 1]);

        $output['archives'] = $srcArchives;
        $output['datas'] = [];

        return $this->renderer->render($response, 'archives.html', $output);
    }

    public function detail($request, $response, $args)
    {
        $srcArchives = $this->db->fetchAll('archives', ['user_id' => $this->user['id'], 'classes' => 1]);
        $output['archives'] = $srcArchives;

        $timeFrom = mktime(0, 0, 0, $args['month'], 1, $args['year']);
        $timeTo = mktime(0, 0, 0, $args['month'] + 1, 1, $args['year']);
        $filter = [
            'user_id' => $this->user['id'],
            'created >=' => date('Y-m-d H:i:s', $timeFrom),
            'created <' => date('Y-m-d H:i:s', $timeTo)
        ];
        $output['datas'] = $this->db->fetchAll('photos', $filter);

        return $this->renderer->render($response, 'archives.html', $output);
    }
}