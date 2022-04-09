<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------


namespace app\index\controller;

use think\db\Where;
use think\facade\Log;
use vitphp\admin\controller\BaseController;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;
use vitphp\admin\model\SystemMenu;
use vitphp\admin\Tree;

/**
 * @title 系统管理
 * Class System
 * @package app\vitphp\controller
 */
class System extends BaseController
{
    /**
     * @title 站点设置
     */
    public function site(){
        $uid = session('admin.id');
        $site = Db::name('system_site')->where('uid',$uid)->find();
        if($site){
            $site['hdp'] =empty($site['index_hdp']) ? "" : unserialize($site['index_hdp']);
        }else{
            $site['hdp'] = [];
        }
        //查询首页菜单管理
        $queryIndex = SystemMenu::order('sort', 'asc')
            ->where(['type'=>'index','uid'=>$uid])
            ->order('id', 'asc')->select();
        $queryIndexMenu = $this->_index_before($queryIndex);

        $pcAddonsIds = Db::name('addons')->whereFindInSet('addons_type', 'template')->column('id');
        $uid = session('admin.id');
        if($uid==1){
            $pcApp = Db::name('app')->where('app_id', 'in', $pcAddonsIds)->select();
        }else{
            $pcApp = Db::name('app')->where('app_id', 'in', $pcAddonsIds)->where('uid', $uid)->select();
        }

        $pcApp = $pcApp->isEmpty() ? [] : $pcApp->toArray();

        View::assign([
            'site'=>$site,
            'pcApp' =>$pcApp,
            'indexList'=>$queryIndexMenu
        ]);
        return View::fetch();
    }
    /**
     * @title 整理首页树形菜单
     */
    protected function _index_before($list){
        /**
         * 整理树形菜单
         */
        foreach ($list as $key => $value) {
            $list[$key] = $value->toArray();
        }
        /**
         * @param $tree
         * @return array
         * 匿名函数 整理子菜单IDs 用于删除时全部子菜单同时删除
         */
        $func = function (&$tree) use (&$func) {
            $aids = [];
            foreach ($tree as &$item) {
                $ids = [];
                $ids[] = $item['id'];
                if (isset($item['_child'])) {
                    $ids = array_merge($ids, $func($item['_child']));
                }
                $aids = array_merge($aids, $ids);
                $item['son_ids'] = implode(',', $ids);
            }
            return $aids;
        };
        $tree = Tree::array2tree($list);
        $func($tree);
        $list = Tree::tree2list($tree);
        return $list;
    }
    /**
     * @title 添加菜单
     */
    public function editmenu(){
        $type = input('type');
        $uid = session('admin.id');
        $query = (new SystemMenu())->db();
        View::assign('uid',$uid);
        View::assign('type',$type);
        return $this->_form($query, 'editmenu');
    }
    protected function _form_before($data)
    {
        $type = input('type');

        if ($this->request->isGet()) {
            $id = $this->request->get('id', false);
            $map = [];
            $id && $map['id'] = ['<>', $id];

            $parents = SystemMenu::where(new Where($map))
                ->order('sort asc,id asc')
                ->where("type='{$type}'")
                ->select();
            foreach ($parents as $key => $value) {
                $parents[$key] = $value->toArray();
            }
            $parents = Tree::array2list($parents, 'id', 'pid', '_child');
            $pid = $this->request->get('pid', false);
            if ($pid) {
                View::assign('pid', $pid);
            }
            View::assign('parents', $parents);
        }
    }
    /**
     * @title 删除菜单
     * @throws \Exception
     */

    public function del()
    {
        $ids = $this->request->post('ids');
        $query = SystemMenu::whereIn('id', $ids);
        $this->_del($query, $ids);
    }
    
    /**
     * @title 禁用/启用菜单
     */
    public function change()
    {
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        $query = (new SystemMenu())->db();
        $this->_change($query, $id, ['status' => $status]);
    }
    /**
     * @title 系统设置修改提交
     */
    public function setup_save(){
        foreach ($this->request->post() as $name =>$value){
            setSetting($name,$value);
        }
        echo json_encode(['code'=>200,'msg'=>"提交成功"]); exit;
    }
    /**
     * @title 站点设置修改提交
     */
    public function site_save(){
        $uid = session('admin.id');
        $domain = input('domain');
        $name = input('name');
        $title = input('title');
        $keywords = input('keywords');
        $description = input('description');
        $index_logo = input('index_logo');
        $admin_logo = input('admin_logo');
        $kf_qq = input('kf_qq');
        $index_content = input('index_content');
        $icpbeian = input('icpbeian');
        $foot_top = input('foot_top');
        $foot_bottom = input('foot_bottom');
        $template = input('template');
        $user = Session::get("admin");
        //幻灯片提交
        $hdp = input("hdp");
        $data = [
            'uid'=>$uid,
            'domain'=>$domain,
            'name'=>$name,
            'title'=>$title,
            'keywords'=>$keywords,
            'description'=>$description,
            'index_logo'=>$index_logo,
            'admin_logo'=>$admin_logo,
            'kf_qq'=>$kf_qq,
            'index_content'=>$index_content,
            'icpbeian'=>$icpbeian,
            'foot_top'=>$foot_top,
            'foot_bottom'=>$foot_bottom,
            'template'=>$template,
            'index_hdp'=>!empty($hdp) ? serialize($hdp) : ""
        ];
        $site = Db::name('system_site')->where( 'uid',$user['id'])->select()->toArray();
        if ($site){
        $res = Db::name('system_site')->where([ 'uid'=>$user['id']])->update($data);
        }else{
            $res = Db::name('system_site')->save($data);
        }
        $this->success('设置成功');
    }
    /**
     * @title 系统设置
     */
    public function setup(){
       $role =  DB::name('role')->where('status=1')->order('id desc ')->select();

        View::assign([
            'role'=>!empty($role) ? $role->toArray() : ""
        ]);
        return view();
    }

    public function vitphp_update()
    {
        $isUpdate = false;
        $url = 'https://www.vitphp.cn/api/vitphp';
        $result = app_http_request($url);
        $result = is_null(json_decode($result)) ? [] : json_decode($result, true);

        if ($result['data']) {
            $vitphpVersion = config('vitphp.version');
            $cloudVersion = $result['data']['version'];
            $isUpdate = version_compare($vitphpVersion, $cloudVersion, '<');
            if($isUpdate){
                // 下载文件
                $file = down_file($result['data']['fileurl'], root_path());
                if(!$file['file']) $this->error('文件下载失败');

                $sqls = sql_parse($result['data']['sql']);
                foreach ($sqls as $sql){
                    try {
                        Db::execute($sql);
                    }catch (\Exception $e){}
                }

                // 解压文件
                $zip = new \ZipArchive();
                $openRes = $zip->open($file['file']);
                if ($openRes === TRUE) {
                    $zip->extractTo(root_path());
                    $zip->close();
                }
                $this->handleUpdate();
                // 删除文件
                if(file_exists($file['file'])){
                    unlink($file['file']);
                }
            }
        }
        if(!$isUpdate) $this->error('暂无新版本');

        (new Admin(app()))->clear(0);
        $this->success('更新成功');
    }

    /**
     * @title 系统更新
     */
    public function info(){
        $this->handleUpdate();

        $isUpdate = false;
        $url = 'https://www.vitphp.cn/api/vitphp';
        $result = app_http_request($url);
        $result = is_null(json_decode($result)) ? [] : json_decode($result, true);
        if (!empty($result['data'])) {
            $vitphpVersion = config('vitphp.version');
            $cloudVersion = $result['data']['version'];
            $isUpdate = version_compare($vitphpVersion, $cloudVersion, '<');
        }
        $this->updateAppId();

        $info = array(
            'os' => php_uname(),
            'php' => PHP_VERSION,
            'path'=> $_SERVER['DOCUMENT_ROOT'],
            'filesize'=>ini_get('upload_max_filesize'),
            'remaining'=>round((disk_free_space(".")/(1024*1024)),2).'M',
            'server' => $_SERVER['SERVER_SOFTWARE'] ? $_SERVER['SERVER_SOFTWARE'] : php_sapi_name(),
        );
        $type = config('vitphp.type');
        $auth = [
            'url' => $this->request->scheme() . '://' . $this->request->host() .  '/index/system/auth'
        ];
        $auth = json_encode($auth);
        $vitDev = getSetting('vit_dev');
        View::assign([
            'type'=>$type,
            'isUpdate'=> $isUpdate,
            'cloudVersion'=> $cloudVersion ?? '',
            'info'=> $info,
            'auth' => base64_encode($auth),
            'debug' => env('app_debug') ? 1 : 0,
            'vit_dev' => $vitDev ?: 2 // 1开，2关，
        ]);
        return View::fetch();
    }

    public function bindCloud()
    {
        if($this->request->isPost()){
            $param = [
                'appid' => input('appid'),
                'appkey' => input('appkey'),
                'domain' => $this->request->host()
            ];
            if(!$param['appid']) $this->error('请填写appid');
            if(!$param['appkey']) $this->error('请填写appkey');

            $url = 'https://www.mzapp.cn/api/index/index';
            $result = app_http_request($url, $param);
            $result = is_null(json_decode($result)) ? [] : json_decode($result, true);
            if ($result['code']!=1) $this->error($result['msg'] ?: '绑定失败');

            setSetting('appid', $param['appid'], 'cloud');
            setSetting('appkey', $param['appkey'], 'cloud');
            $this->success('绑定成功');
        }
        return View::fetch();
    }

    private function updateAppId()
    {
        $url = 'http://www.mzapp.cn/api/index/index';
        $result = app_http_request($url, ['domain'=>$this->request->host()]);
        $result = is_null(json_decode($result)) ? [] : json_decode($result, true);
        if (!empty($result['data'])) {
            $appid = $result['data']['appid'] ?? '';
            $appkey = $result['data']['appkey'] ?? '';
            if(!$appid || !$appkey) return;

            setSetting('appid', $appid, 'info');
            setSetting('appkey', $appkey, 'info');
        }
    }

    /**
     * 如果更新文件存在，则执行update文件
     */
    private function handleUpdate()
    {
        $file = root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'update.php';
        if(!file_exists($file)) return;
        require_once $file;
    }

    /**
     * 配置debug开关，重写.env内容
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function debug()
    {
        $debug = input('debug', 1);
        $envContent = $debug == 1 ? 'APP_DEBUG = true' : 'APP_DEBUG = false';

        $file = $this->app->getRootPath() . '.env';
        file_put_contents($file, $envContent);

        $tip = $debug == 1 ? '调试模式已打开' : '调试模式已关闭';
        $this->success($tip);
    }

    /**
     * 配置debug开关，重写.env内容
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function vitDev()
    {
        $vitDev = input('vit_dev');
        setSetting('vit_dev', $vitDev);

        $this->success($vitDev == 1 ? '开发模式已打开' : '开发模式已关闭');
    }

    /**
     * @login 0
     */
    public function auth()
    {
        $post = $this->request->post();
        Log::info(json_encode($post));

        $username = Db::name('settings')->where(['addons'=>'info','name'=>'username'])->find();
        if($username){
            Db::name('settings')->where(['addons'=>'info','name'=>'username'])->update(['value'=>$post['username']]);
        }else{
            Db::name('settings')->insert(['addons'=>'info','name'=>'username','value'=>$post['username']]);
        }
        $appid = Db::name('settings')->where(['addons'=>'info','name'=>'appid'])->find();
        if($appid){
            Db::name('settings')->where(['addons'=>'info','name'=>'appid'])->update(['value'=>$post['appid']]);
        }else{
            Db::name('settings')->insert(['addons'=>'info','name'=>'appid','value'=>$post['appid']]);
        }
        $appkey = Db::name('settings')->where(['addons'=>'info','name'=>'appkey'])->find();
        if($appkey){
            Db::name('settings')->where(['addons'=>'info','name'=>'appkey'])->update(['value'=>$post['appkey']]);
        }else{
            Db::name('settings')->insert(['addons'=>'info','name'=>'appkey','value'=>$post['appkey']]);
        }

        return json(['code'=>1, 'msg'=>'ok']);
    }
    /**
     * @title 系统日志
     */
    public function log(){
        $pageNum = 20;
        $log = Db::name('sys_log')->order('id','desc')->paginate($pageNum);
        View::assign([
           'log' => $log,
           'page'=>$log->render(),
        ]);
        return View::fetch();
    }
}