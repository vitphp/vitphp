<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace vitphp\admin\traits;

use think\exception\HttpResponseException;
use think\Response;

trait Halt
{
    protected function show($message, $code = 0, $header = [])
    {
        if (request()->isAjax()){
            $result = [
                'code' => 0,
                'msg' => $message,
            ];
            $response = Response::create($result, 'json')->header($header);
        }else{
            $response = Response::create(dirname(__DIR__) . '/view/halt_show.html', 'view')->assign(compact('message','code'))->header($header);
        }
        throw new HttpResponseException($response);
    }
}