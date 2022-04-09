<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace vitphp\admin;

use think\facade\Db;
use think\facade\Session;
use vitphp\admin\auth\AnnotationAuth;
use vitphp\admin\model\SystemAdmin;

/**
 * 权限验证类
 * Class Auth
 * @package LiteAdmin
 */
class Auth
{

    /**
     * 执行验证
     * @param $path
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function auth($path)
    {
        $sp_path = $path;
        $username = Session::get('admin.username');
        $admin_id = Session::get('admin.id');
        if($admin_id==1) return true;

        if(is_string($sp_path)){
            $paths =  explode('/', $path);
            $path = [];
            $path[2] = array_pop($paths);
            $path[0] = array_shift($paths);
            $path[1] = implode('.', $paths);
        }

        $node = AnnotationAuth::checkAuth("app\\" . $path[0] . "\\controller\\" . $path[1], $path);
        $appAuth = Db::table('vit_app')->where(['uid'=>$admin_id,'addons'=>$path[0]])->find();
        if($appAuth){
            return true;
        }

        if (!$node) {
            return false;
//            halt('当前PATH（'.$path.'）没有加入权限管理列表');
        }

        $meta = $node['meta'];
        # 如果没有设置，则设置默认
        if(!isset($meta['login'])){
            $meta['login'] = 1;
        }
        if(!isset($meta['auth'])){
            if($meta['login'] == 0){
                $meta['auth'] = 0;
            }else{
                $meta['auth'] = 1;
            }
        }
        # 如果要校验权限，那么必须登录
        if ($meta['auth'] == '1') {
            $meta['login'] = 1;
        }

        # login为0，不需要登录
        if($meta['login'] == 0){
            return true;
        }

        if ($meta['login'] == '1') {
            if ($admin_id) {
                
                if ($meta['auth'] == '1') {
                    # 需要权限校验
                    $role_nodes = Db::table('vit_auth_nodes')
                        ->whereIn('rule_id', Db::table('vit_auth_map')
                            ->where('admin_id', $admin_id)
                            ->column('role_id'))
                        ->group('node')
                        ->select()->column('node');
                       
                    $user_nodes = Db::table('vit_auth_nodes')
                        ->where('uid', $admin_id)
                        ->select()->column('node');
                    $access = array_merge($role_nodes, $user_nodes);
   
                    $access = array_map(function ($d){
                        return strtolower($d);
                    },$access);

                    return in_array(strtolower($node['node']), $access);
                }
                return true;
            } else {
                return false;
            }
        }

        return false;
    }
}