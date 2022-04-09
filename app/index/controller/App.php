<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace app\index\controller;

use think\facade\Db;
use think\facade\View;
use vitphp\admin\controller\BaseController;

/**
 * @title 平台管理
 * Class App
 * @package app\index\controller
 */
class App extends BaseController
{
    /**
     * @title 平台列表
     */
    public function index(){
        $uid = session('admin.id');
        $pageNum = 20;
        $keyword = input('keyword');
        if($uid==1){
            $where = [];
            if($keyword){
                $where[] = ['name' ,'like', '%'.$keyword.'%'];
            }
            $applist = Db::name('app')->where($where)
                ->alias('a')->join('vit_users b','b.id = a.uid')
                ->field('a.*,b.username')->order('id','DESC')->paginate($pageNum);
        }else{
            $where = [
                ['uid', '=', session('admin.id')]
            ];
            if($keyword){
                $where[] = ['name' ,'like', '%'.$keyword.'%'];
            }
            $applist = Db::name('app')->where($where)->paginate($pageNum);
        }
        //分页查询
        // 渲染模板输出
        View::assign([
            'applist'=>!empty($applist) ? $applist:'',
            'addons'=>!empty($addons)?$addons:'',
            'uid'=>$uid,
            'page' => $applist->render(),
        ]);
        return View::fetch('index');
    }
    /**
     * @title 购买应用
     */
    public function shop(){
        $app = Db::name('addons')->where('is_install',1)->order('id desc')->select()->all();
        View::assign([
           'app' => isset($app)? $app:""
        ]);
        return View::fetch('shop');
    }
    /**
     * @title 购买应用提交
     */
    public function save_project(){
        $param = input("param");
        $addons_id = $param['addons_id'];
        if(empty($addons_id)){
            echo json_encode(['code'=>100,'msg'=>'发生错误,请选择购买的应用']);exit;
        }else{
            $app = Db::name('addons')->where(['is_install'=>1,'id'=>$addons_id])->find();
            if($app){
                    $data['uid'] = session('admin.id');
                    $data['name'] = $app['name'];
                    $data['logo'] = $app['logo'];
                    $data['addons'] = $app['identifie'];
                    $data['app_id'] = $app['id'];
                    $po = Db::name('app')->insertGetId($data);
                    if($po){
                        save_sys_log('安装项目', "应用标识：{$app['identifie']}", session('admin.username'));
                        echo json_encode(['code'=>200,'msg'=>'购买成功']);exit;
                    }else{
                        echo json_encode(['code'=>100,'msg'=>'发生错误']);exit;
                    }

            }else{
                echo json_encode(['code'=>100,'msg'=>'发生错误，无法购买']);exit;
            }
        }
    }
    /**
     * @title  编辑平台资料
     */
    public function edit(){
        $id = input("id");
        $newUid = input('uid');
        $uid = session('admin.id');
        $isVitAdmin = $uid == 1;
        $app = Db::name('app')->where('id',$id)->find();
        if(!$app) $this->error('未知的项目');
        if($app){
            $app['dq_time'] = date("Y-m-d H:i:s",$app['dq_time']);
        }
        $user = Db::name("users")->where("is_deleted=0 and state=1 and pid is null")->select()->toArray();
        if(!$isVitAdmin){
            if($app['uid'] != $uid) $this->error('非法的项目');
        }

        if(request()->isPost()){
            $param = [
                'name'=>input('name'),
                'logo'=>input('logo'),
            ];
            if($isVitAdmin){
                $param['dq_time'] = !empty(input('dq_time')) ? strtotime(input('dq_time')) : "";
                $param['uid'] = $newUid;
            }else{
                $param['uid'] = $uid;
            }

            Db::name("app")->where('id',$id)->update($param);
            save_sys_log('修改项目', "项目id：{$id}", session('admin.username'));
            $this->success("配置成功！", 'index/app/index');
        }
        View::assign([
            'app'=>isset($app)?$app:'',
            'user'=>isset($user)?$user:''
        ]);
        return View::fetch();
    }

    /**
     * @title 删除平台
     */
    public function del()
    {
        $id = input('id');
        Db::name('app')->where('id',$id)->delete();
        save_sys_log('删除项目', null, session('admin.username'));
        $this->success('删除成功');
    }
}