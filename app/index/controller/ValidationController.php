<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace app\index\controller;


use app\BaseController;
use think\App;
use think\facade\Db;
use think\facade\View;
use vitphp\admin\auth\AnnotationAuth;
use vitphp\admin\middleware\CheckAccess;
use vitphp\admin\traits\Jump;

class ValidationController extends BaseController
{
    use Jump;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $sessionKey = input('session', '');
        $session = $sessionKey ? session($sessionKey) : session('admin');
        $action = $this->request->action();
        if($action!='Cqrcode'){
            if(!$session) $this->error('当前请求没有登录~！','index/login/index');

            if($sessionKey){
                // 如果是自定义session的一些操作
            }else{
                $this->validateAdminAuth($session['id'] ?? 0);
            }
        }
    }

    protected function validateAdminAuth($login_id)
    {
        # 模块
        $module = $this->app->http->getName();
        # 控制器
        $controller = parse_name($this->request->controller(),0);
        # 方法
        $action = $this->request->action();

        // 当前控制器
        $classuri = $this->app->http->getName().'/'.$this->request->controller();
        $name = $this->app->http->getName();

        if($name != 'index'){
            # 如果模块不是index模块，判断是否传有pid参数
            $pid = input('pid');
            global $_PID_;
            $_PID_ = $pid;
            if(empty($pid)){
                $this->error("项目不存在");
            }
            # 查询项目信息
            $app = Db::name('app')->where(['id'=>$pid])->find();
            if($app){
                $ok = false;
                if($login_id == 1){
                    $ok = true;
                }else if($app['uid'] == $login_id){
                    $ok = true;
                }else{
                    if($pid == Db::name('users')->where('id', $login_id)->value('pid')){
                        $ok = true;
                    }
                }
                if($ok === false){
                    $this->error("项目不存在!");
                }
                # 如果不是超级管理员
                if($login_id != 1){
                    # 判断项目是否过期
                    $dq_time = $app['dq_time'];
                    if(time() > $dq_time){
                        $this->error("项目时间已到期");
                    }
                }
                # 获取记录模块名
                $addons = $app['addons'];
                # 如果记录模块名称和当前模块名称不一致
                if($addons != $name){
                    $this->error("项目不匹配");
                }

                if($app){

                    # 如果当前用户是项目的超级管理员
                    if($app['uid'] === $login_id){
                        # 是管理员，查询全部菜单权限
                        $menu = Db::name("menu")->where(['type'=>$name])->select()->toArray();
                        foreach ($menu as $i=>$v){
                            $v['url'] = url(strtolower($v['url']))->build();
                            $menu[$i] = $v;
                        }
                    }else{
                        # 不是该项目的管理员,进行权限匹配查询 (如果是项目成员(并非管理员)则验证权限)
                        $menu = AnnotationAuth::getMenu($login_id,$addons);
                    }
                }else{
                    # 项目信息不存在，返回一个空菜单
                    $menu = [];
//                    $this->error("项目不存在!");
                }
                # 菜单数据处理
                if($menu){
                    foreach ($menu as $k=>$v){
                        $name = $v['name'] ?? '';
                        $title = $v['title'] ?? '';
                        $menu[$k]['pathUrl'] = $v['url'].'?pid='.$pid;
                        $menu[$k]['title'] = empty($title) ? $name : $title;
                    }
                }

                View::assign(['menu'=>$menu,'app'=>$app]);
            }else{
                if($login_id){
                    $this->error("项目不存在!");
                }else{
                    $this->error('当前请求没有登录!!','index/login/index');
                }
            }

        }
    }
}