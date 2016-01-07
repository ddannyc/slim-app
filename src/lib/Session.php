<?php
/**
 * Created by WayneChen.
 * Date: 2016/1/4
 * Time: 17:17
 */

namespace App\lib;


class Session implements \ArrayAccess
{
    private $id;
    private $datas;

    public function __construct()
    {
        $this->id = session_id();
        $this->datas = &$_SESSION;
    }

    public function id()
    {
        return $this->id;
    }

    public function get($offset)
    {
        return $this->offsetGet($offset);
    }

    public function set($offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    public function del($offset)
    {
        $this->offsetUnset($offset);
    }

    public function offsetExists($offset)
    {
        return isset($this->datas[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->datas[$offset])) {
            return $this->datas[$offset];
        } else {
            return null;
        }
    }

    public function offsetSet($offset, $value)
    {
        if ($offset) {
            $this->datas[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->datas[$offset]);
    }
}