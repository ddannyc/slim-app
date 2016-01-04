<?php
/**
 * Created by WayneChen.
 * Date: 2016/1/4
 * Time: 17:03
 */

namespace App\lib;


class Flash
{
    const KEY = 'flash';
    private $session;
    private $values;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->values = isset($this->session[self::KEY]) ? $this->session[self::KEY] : [];
    }

    public function get($key)
    {
        if (isset($this->values[$key])) {
            $result = $this->values[$key];
            unset($this->values[$key]);
            $this->session[self::KEY] = $this->values;
        } else {
            $result = '';
        }
        return $result;
    }

    public function set($key, $value)
    {
        if ($key) {
            $this->values[$key] = $value;
            $this->session[self::KEY] = $this->values;
        }
    }
}