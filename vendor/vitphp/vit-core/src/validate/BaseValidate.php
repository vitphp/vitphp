<?php

namespace vitphp\admin\validate;

use think\facade\Request;

abstract class BaseValidate extends \think\Validate
{
    abstract public function goCheck();

    protected function getParamForMethod($method)
    {
        switch ($method) {
            case 1:
                $params = Request::post();
                break;
            case 2:
                $params = Request::get();
                break;
            default:
                $params = Request::post();
        }

        return $params;
    }

    public function getDataByRule($array)
    {
        $newArray = [];
        foreach ($this->rule as $k => $v) {
            $newArray[$k] = $array[$k];
        }

        return $newArray;
    }

    public function getDataByScene($array, $scene)
    {
        $newArray = [];
        foreach ($this->scene[$scene] as $k => $v) {
            if (isset($array[$k])) {
                $newArray[$k] = $array[$k];
            } else {
                if (isset($array[$v])) $newArray[$v] = $array[$v];
            }
        }

        return $newArray;
    }

    protected function isPositiveInteger($value, $rule = '')
    {
        if (is_numeric($value) && is_int($value + 0) && ($value + 0) > 0) {
            return true;
        }

        return $rule ? $rule . '必须是正整数' : false;
    }

    protected function isMobile($value, $rule = '')
    {
        $rule = '^1(3|4|5|6|7|8|9)[0-9]\d{8}$^';
        $result = preg_match($rule, $value);
        return $result ? true : false;
    }

    protected function isMoney($value)
    {
        if (preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value)) {
            return true;
        } else {
            return false;
        }
    }

    protected function returnEmpty()
    {
        return true;
    }
}