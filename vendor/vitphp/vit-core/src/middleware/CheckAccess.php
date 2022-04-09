<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace vitphp\admin\middleware;


use think\facade\Session;
use think\Request;
use vitphp\admin\Auth;
use vitphp\admin\traits\Halt;
use vitphp\admin\traits\Jump;
use vitphp\admin\model\SystemAdmin;

/**
 * 权限校验中间件
 * Class CheckAccess
 * @package app\common\middleware
 */
class CheckAccess
{
    use Jump;
    use Halt;

    public function handle(Request $request,\Closure $next)
    {
        $admin_id = session('admin.id');
        if (!!$admin_id){
            $admin = SystemAdmin::where('id',$admin_id)->find();
            if ($admin->getData('state') !== 1){
                $this->show('当前账户已被禁用');
            }
        }

        $module = $this->app->http->getName();
        $controller = parse_name($request->controller(),0);
        $action = $request->action();

        $path = "{$module}/{$controller}/{$action}";

        if($admin_id != 1){
            if (!Auth::auth([$module,$controller,$action])){
                if (Session::has('admin')){
                    $this->show("当前请求没有权限");
                }
                $this->error('当前请求没有登录~~','index/login/index');
            }
        }

        $response = $next($request);

        return $response;
    }
}