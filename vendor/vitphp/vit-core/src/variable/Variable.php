<?php

namespace vitphp\admin\variable;

use think\facade\Db;
use think\facade\Request;

class Variable
{
    private static $pid;

    public static function init()
    {
        global $_VIT;

        self::$pid = input('pid', 0);

        $_VIT['pid'] = self::$pid;
        $_VIT['platform'] = self::getVitPlatform();
    }

    /**
     * 获取平台配置
     */
    public static function getVitPlatform()
    {
        $getS = explode('/', $_GET['s'] ?? '');
        $addons = $getS[1] ?? '';
        if(!$addons) return [];
        $platform = Db::name('platform')->where('pid', self::$pid)->where('addons', $addons)->find();
        if(!$platform) return [];

        $platform['wechat'] = json_str_to_array($platform['wechat'] ?? '');
        $platform['wxapp'] = json_str_to_array($platform['wxapp'] ?? '');
        $platform['workwechat'] = json_str_to_array($platform['workwechat'] ?? '');
        $platform['ksapp'] = json_str_to_array($platform['ksapp'] ?? '');
        $platform['androidapp'] = json_str_to_array($platform['androidapp'] ?? '');
        $platform['iosapp'] = json_str_to_array($platform['iosapp'] ?? '');
        $platform['dyapp'] = json_str_to_array($platform['dyapp'] ?? '');
        $platform['bdapp'] = json_str_to_array($platform['bdapp'] ?? '');
        $platform['alpayapp'] = json_str_to_array($platform['alpayapp'] ?? '');
        $platform['template'] = json_str_to_array($platform['template'] ?? '');
        $platform['web'] = json_str_to_array($platform['web'] ?? '');
        $platform['other'] = json_str_to_array($platform['other'] ?? '');
        return $platform;
    }
}