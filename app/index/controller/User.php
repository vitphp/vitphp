<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace app\index\controller;

use think\db\exception\PDOException;
use think\facade\Db;
use think\facade\View;
use vitphp\admin\auth\AnnotationAuth;
use vitphp\admin\controller\BaseController;
use vitphp\admin\model\SystemAdmin;
use vitphp\admin\model\SystemAuthMap;
use vitphp\admin\model\SystemRole;

/**
 * @title 后台管理员
 * Class User
 * @package app\vitphp\controller
 */
class User extends BaseController
{
    /**
     * @title 授权权限
     * @auth 1
     * @login 1
     */
    public function access()
    {
        if($this->request->isGet()){
            $query = (new SystemAdmin())->db();
            $apps = AnnotationAuth::getAddonsAuth('index');
            View::assign('auth_ids', []);
            View::assign('apps', $apps);
            return $this->_form($query, 'access');
        }
        if($this->request->isPost()){
            $uid = $this->request->param('id');
            $node = $this->request->param('node', []);

            Db::table('vit_auth_nodes')->where('uid', $uid)->delete();
            $dataSet = [];
            foreach ($node as $i=>$v){
                $dataSet[] = ['rule_id'=>0,'uid'=>$uid,'node'=>$v];
            }
            Db::table('vit_auth_nodes')->insertAll($dataSet);
            $this->success('恭喜, 数据保存成功!', '');
        }
    }
    protected function _access_form_before(&$data)
    {
        if ($this->request->isGet()) {
            $role_nodes = Db::table('vit_auth_nodes')
                ->whereIn('rule_id',  Db::table('vit_auth_map')
                    ->where('admin_id', input('id'))
                    ->column('role_id'))
                ->group('node')
                ->select()->column('node');

            $user_nodes = Db::table('vit_auth_nodes')
                ->where('uid', input('id'))
                ->select()->column('node');
            View::assign('role_nodes', $role_nodes);
            View::assign('nodes', array_merge($role_nodes,$user_nodes));
        }
    }
    /**
     * @title 列表页
     * @return mixed
     */
    public function index()
    {
        if (session('admin.id')==1){
        $query = SystemAdmin::where('is_deleted',0);
        }else{
            $query = SystemAdmin::where(['is_deleted'=>0]);
        }
        $search = $this->request->get();
        foreach (['username'] as $field){
            if (isset($search[$field]) && $search[$field] !==''){
                $query->where($field, $search[$field]);
            }

        }

        return $this->_list($query);
    }

    /**
     * @title 添加用户
     * @return array|mixed
     */
    public function add()
    {
        $roles = SystemRole::where('status',1)->select();
        View::assign([
            'roles' => $roles,
            'access' => [],
        ]);
        $query = (new SystemAdmin())->db();
        return $this->_form($query, 'form');
    }
    public function create_user()
    {
        if ($this->request->isPost()){
            $data = $this->request->post();
            if(!isset($data['id'])){
                (strlen($data['password']) < 5 || strlen($data['password']) > 25) && $this->error('密码长度必须5-25位之间');
                !preg_match('/^[a-zA-Z0-9]+$/', $data['password']) && $this->error('只能使用字母数字');
                ($data['password']!=$data['repassword']) && $this->error('两次密码输入不一致');
                unset($data['repassword']);
                $data['password'] = pass_en($data['password']);
            }else{
                unset($data['password']);
                unset($data['repassword']);
            }

            if(!isset($data['id'])){
                (strlen($data[SystemAdmin::username()])<6)&&$this->error('用户名长度必须大于6位');
                $has = SystemAdmin::where(SystemAdmin::username(), $data[SystemAdmin::username()])->count();
                $has&&$this->error("已经存在的用户名");
                !preg_match('/^[a-zA-Z0-9]+$/',$data[SystemAdmin::username()])&&$this->error('只能使用字母数字');
                $data['create_time'] = $this->request->time();
            }

            $roles = $data['role'] ?? [];
            $userId = $data['id'] ?? 0;
            Db::startTrans();
            try {
                unset($data['role']);
                if(isset($data['id']) && $data['id']){
                    unset($data['id']);
                }else{
                    $userId = Db::name('users')->insertGetId($data);
                }

                SystemAuthMap::where('admin_id', $userId)->delete();
                foreach ($roles as $key => $role) {
                    $insert[] = ['admin_id' => $userId, 'role_id' => $key];
                }
                if(isset($insert)){
                    (new SystemAuthMap)->saveAll($insert);
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error("添加失败！", '', $e->getMessage());
            }
            $this->success("添加成功！", '');
        }
    }
    protected function _add_form_before(&$data){
        if ($this->request->isPost()){
//            if(!isset($data['id'])){
//                (strlen($data['password']) < 5 || strlen($data['password']) > 25) && $this->error('密码长度必须5-25位之间');
//                !preg_match('/^[a-zA-Z0-9]+$/', $data['password']) && $this->error('只能使用字母数字');
//                ($data['password']!=$data['repassword']) && $this->error('两次密码输入不一致');
//                unset($data['repassword']);
//                $data['password'] = pass_en($data['password']);
//            }
////            dump($data);
//            (strlen($data[SystemAdmin::username()])<6)&&$this->error('用户名长度必须大于6位');
//            $has = SystemAdmin::where(SystemAdmin::username(), $data[SystemAdmin::username()])->count();
//            $has&&$this->error("已经存在的用户名");
//            !preg_match('/^[a-zA-Z0-9]+$/',$data[SystemAdmin::username()])&&$this->error('只能使用字母数字');
//            $data['create_time'] = $this->request->time();
//            exit();
        }
    }

    /**
     * @title 修改用户
     * @return array|mixed
     */
    public function edit()
    {
        $query = (new SystemAdmin())->db();
        $roles = SystemRole::where('status',1)->select();
        $access = SystemAuthMap::where('admin_id', input('id'))->column('role_id');
        View::assign([
            'roles' => $roles,
            'access' => $access,
        ]);
        return $this->_form($query, 'form');
    }
    protected function _edit_form_before(&$data)
    {
        (intval($data['id']) === 1) && $this->error("超级用户禁止修改！");
    }
    /**
     * @title 删除用户
     * @throws PDOException
     * @throws \think\Exception
     */
    public function del()
    {
        $ids = $this->request->post('ids');
        $idarr = explode(',',$ids);
        if(in_array('1',$idarr)){
            $this->error("超级用户禁止删除！");
        }
        $query = (new SystemAdmin())->db();
        $this->_del($query, $ids);
    }
    /**
     * @title 修改密码
     * @return array|mixed
     */
    public function password()
    {
        $query = (new SystemAdmin())->db();
        return $this->_form($query, 'password');
    }
    protected function _password_form_before(&$data)
    {
        if ($this->request->isPost()) {
            $password = $data['password'];
            $repassword = $data['repassword'];

            (strlen($password) < 5 || strlen($password) > 25) && $this->error('密码长度必须5-25位之间');
            !preg_match('/^[a-zA-Z0-9]+$/', $password) && $this->error('只能使用字母数字');
            ($password !== $repassword) && $this->error('两次密码输入不一致');
            unset($data['repassword']);
            $data['password'] = pass_en($data['password']);
        }
    }
    /**
     * @title 禁用/启用
     */
    public function change()
    {
        $id = $this->request->post('id');
        (intval($id) === 1) && $this->error("超级用户禁止禁用！");
        $state = $this->request->post('state');
        $query = (new SystemAdmin)->db();
        $this->_change($query, $id, ['state' => $state]);
    }
}