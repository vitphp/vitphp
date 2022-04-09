<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------
return [
    "app" => "vitphp",
    "cost" => 11,
    "dispatch_success_tmpl" => app()->getRootPath() . "vendor/vitphp/vit-core/src/tpl/dispatch_jump.tpl",
    "dispatch_error_tmpl" => app()->getRootPath() . "vendor/vitphp/vit-core/src/tpl/dispatch_jump.tpl",
    'jwt' => [
        'iss' => 'http://domain.org', //iss: jwt签发者
        'aud' => 'http://domain.org', //aud: 接收jwt的一方
        'sub' => 'http://domain.org', //sub: jwt所面向的用户
        'expire' => 604800 * 4,
        'key' => 'fp88aWM3yfejPrS46f3cfjac14'
    ]
];