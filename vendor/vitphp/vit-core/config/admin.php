<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

return [
    "app"=>"vitphp",
    "cost" => 11,
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => app()->getRootPath().'/vendor/vitphp/vit-core/src/tpl/dispatch_jump.tpl',
    'dispatch_error_tmpl'   => app()->getRootPath().'/vendor/vitphp/vit-core/src/tpl/dispatch_jump.tpl',
];