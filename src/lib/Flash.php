<?php
/**
 * Created by WayneChen.
 * Date: 2016/1/4
 * Time: 17:03
 */

namespace App\lib;

class FlashType
{
    const SUCCESS = 'success';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
}
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

    public function addSuccess($key, $value)
    {
        $this->set(FlashType::SUCCESS, $key, $value);
    }

    public function addInfo($key, $value)
    {
        $this->set(FlashType::INFO, $key, $value);
    }

    public function addWarning($key, $value)
    {
        $this->set(FlashType::WARNING, $key, $value);
    }

    public function addError($key, $value)
    {
        $this->set(FlashType::ERROR, $key, $value);
    }

    public function show($key)
    {
        if (isset($this->values[$key])) {
            $result = $this->values[$key];
            unset($this->values[$key]);
            $this->session[self::KEY] = $this->values;
        } else {
            $result = [];
        }
        return $this->toHtml($result);
    }

    private function toHtml(array $datas)
    {
        $result = '';
        foreach ($datas as $type => $contents) {

            if ($contents && is_array($contents)) {
                foreach ($contents as $msg) {
                    $result = $result . '<p class="box ' . $type . '">' . $msg . '</p>' . "\n";
                }
            }
        }
        return $result;
    }

    private function set($type, $key, $value)
    {
        if ($key) {
            $this->values[$key][$type][] = $value;
            $this->session[self::KEY] = $this->values;
        }
    }
}