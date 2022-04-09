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
use vitphp\admin\auth\AnnotationAuth;
use vitphp\admin\controller\BaseController;
use vitphp\admin\model\SystemRole;

/**
 * @title 角色管理
 * Class Role
 * @package app\vitphp\controller
 */
class Role extends BaseController
{
    /**
     * @title 列表页
     * @auth 1
     * @return mixed
     */
    public function index()
    {
        $query = (new SystemRole())->db();
        return $this->_list($query);
    }
    /**
     * @title 添加
     * @return array|mixed
     */
    public function add()
    {
        $query = (new SystemRole())->db();
        return $this->_form($query, 'form');
    }
    protected function _add_form_before(&$data){
        $data['create_time'] = request()->time();
    }

    /**
     * @title 编辑
     * @return array|mixed
     */
    public function edit()
    {
        $query = (new SystemRole())->db();
        return $this->_form($query, 'form');
    }
    /**
     * @title 授权
     * @return array|mixed
     */
    public function access()
    {
        if($this->request->isGet()){
            $query = (new SystemRole())->db();
            $apps = AnnotationAuth::getAddonsAuth('index');
            View::assign('auth_ids', []);
            View::assign('apps', $apps);
            return $this->_form($query, 'access');
        }
        if($this->request->isPost()){
            $rule_id = $this->request->param('id');
            $node = $this->request->param('node', []);

            Db::table('vit_auth_nodes')->where('rule_id', $rule_id)->delete();
            $dataSet = [];
            foreach ($node as $i=>$v){
                $dataSet[] = ['uid'=>0,'rule_id'=>$rule_id,'node'=>$v];
            }
            Db::table('vit_auth_nodes')->insertAll($dataSet);

            $this->success('恭喜, 数据保存成功!', '');
        }
    }
    protected function _access_form_before(&$data)
    {
        if ($this->request->isGet()) {
            $nodes = Db::table('vit_auth_nodes')
                ->where('rule_id', input('id'))
                ->select()->column('node');

            View::assign('nodes', $nodes);
        }
    }
    /**
     * @title 删除及批量删除
     * @throws \think\Exception
     * @throws \think\db\exception\PDOException
     */
    public function del()
    {
        $ids = $this->request->post('ids');
        $query = (new SystemRole())->db();
        save_sys_log('删除权限', null, session('admin.username'));
        $this->_del($query, $ids);
    }
    /**
     * @title 禁用/启用
     */
    public function change()
    {
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        $query = (new SystemRole())->db();
        save_sys_log('禁用/启用权限', null, session('admin.username'));
        $this->_change($query, $id, ['status' => $status]);
    }
}