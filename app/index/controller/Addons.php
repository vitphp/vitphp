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
use vitphp\admin\model\SystemMenu;

/**
 * @title 应用管理
 */
class Addons extends BaseController
{
    private $table_addons = "addons";
    private $table_menu = "menu";
    private $table_project = "app";
    private $addons;
    private $config;

    /**
     * @title 应用跳转
     * @login  1
     * @auth 0
     */
    public function index()
    {
        $pid = input('pid');
        $project = Db::name($this->table_project)->where(['id' => $pid])->find();
        if ($project) {
            $login_id = session('admin.id');
            $uid = $project['uid'];
            if ($login_id == $uid) {
                $menu = SystemMenu::order('sort', 'asc')
                    ->where(['status' => '1', 'type' => $project['addons']])
                    ->select()->each(function ($d) {
                        $d['url'] = '/' . $d['type'] . '/' . $d['url'];
                        return $d;
                    })->toArray();
            } else {
                $menu = AnnotationAuth::getMenu($login_id, $project['addons']);
            }
            if (count($menu) === 0) {
                $this->error("应用无菜单");
            }
            $query = request()->domain();
            $url = $menu[0]['url'];
            $this->redirect($query . (substr($url, 1, 1) == '/' ? substr($url, 1) : $url) . '?pid=' . $pid);
        }

        View::assign([
            'menu' => isset($menu) ? $menu : ""
        ]);
        return View::fetch();
    }

    /**
     * @title 应用列表
     */
    public function list()
    {
        $this->delAddonsDir();
        $this->checkRecord();
        $vitDev = getSetting('vit_dev') ?: 2;

        View::assign([
            'vit_dev' => $vitDev
        ]);
        return View::fetch('list');
    }

    /**
     * 删除不存在的目录记录
     */
    public function delAddonsDir()
    {
        $dir_list = Db::name($this->table_addons)->select()->toArray();
        //获取app 目录
        $dir = base_path();
        $id_s = [];
        foreach ($dir_list as $item) {
            $path_dir = $dir . $item['identifie'];
            if (!file_exists($path_dir)) {
                $id_s[] = $item['id'];
            }
        }
        Db::name($this->table_addons)->delete($id_s);
    }

    /**
     * @title 检查目录下是否有xml文件
     */
    public function checkRecord()
    {
        $isUpdateAddons = [];
        //获取app 目录
        $dir = base_path();
        //打开目录
        $handle = opendir($dir);
        if (!empty($handle)) {
            while ($dir = readdir($handle)) {
                if (strpos($dir, '.') == false && '.' != $dir && '..' != $dir && $dir !== 'index') {
                    //解析xml
                    $res = addons_config_array($dir);
                    if ($res) {
                        $module['name'] = $res['addons']['name'];
                        $module['identifie'] = $dir;
                        $module['goods_id'] = $res['addons']['goodsid'] ?? null;
                        $module['version'] = $res['addons']['version'];
                        $module['logo'] = $res['addons']['logo'];
                        $module['author'] = $res['addons']['author'];
                        $module['addons_type'] = $res['addons']['addons_type'];
                        $module['is_install'] = 0;
                        $module['create_time'] = time();

                        // 如果只有应用标识，商品id为空，则补商品Id
                        $identifieInfo = Db::name($this->table_addons)->where('identifie', $module['identifie'])->find();
                        $goodsInfo = $module['goods_id'] ? Db::name($this->table_addons)->where('goods_id', $module['goods_id'])->find() : [];
                        if ($identifieInfo && !$identifieInfo['goods_id']) {
                            unset($module['is_install']);
                            unset($module['version']);
                            unset($module['logo']);
                            Db::name($this->table_addons)->where('id', $identifieInfo['id'])->update($module);
                        }
                        if ($goodsInfo && !$goodsInfo['identifie']) {
                            unset($module['is_install']);
                            Db::name($this->table_addons)->where('id', $goodsInfo['id'])->update($module);
                        }
                        if (!$identifieInfo && !$goodsInfo) {
                            Db::name($this->table_addons)->insert($module);
                        }
                        $isUpdateAddons[$module['identifie']] = $res['addons']['version'];
                    } else {
                        Db::name($this->table_addons)->where(['identifie' => $dir, 'is_install' => 0])->delete();
                    }
                }
            }
        }
        Db::name($this->table_addons)->whereNull('is_install')->update(['is_install' => 0]);
        // 请求安装
        $code = $this->getCode();
        if ($code) {
            $url = config('vitphp.market') . 'api/install/getList';
            $post = [
                'code' => $code,
                'type' => 'vitphp'
            ];
            $result = app_http_request($url, $post);
            $result = is_null(json_decode($result)) ? [] : json_decode($result, true);
            if (!empty($result['data'])) {
                foreach ($result['data'] as $k => $banben) {
                    $addons = Db::name($this->table_addons)->where('goods_id', '=', $banben['gid'])->find();
                    if (!$addons) {
                        Db::name($this->table_addons)->where('goods_id', '=', $banben['gid'])->insert([
                            'name' => $banben['title'],
                            'version' => $banben['number'],
                            'yun_version' => $banben['number'],
                            'goods_id' => $banben['gid'],
                            'state' => 0,
                            'is_install' => 0,
                            'describe' => $banben['describe'],
                            'up_time' => $banben['up_time']
                        ]);
                    }
                }
            }
        }

        $notInstallList = Db::name($this->table_addons)->where('is_install=0')->select()->toArray();
        $installedList = Db::name($this->table_addons)->where('is_install=1')->order('id desc')->paginate(20);
        $page = $installedList->render();
        $installedList = $installedList->toArray()['data'];
        $goodsIds = [];
        foreach ($installedList as $i => $d) {
            $d['waitUpdate'] = false;
            if (isset($isUpdateAddons[$d['identifie']])) {
                if ($d['version'] !== $isUpdateAddons[$d['identifie']]) {
                    $d['waitUpdate'] = true;
                    $d['waitUpdateVersion'] = $isUpdateAddons[$d['identifie']];
                }
            }
            $installedList[$i] = $d;
            if ($d['goods_id']) {
                array_push($goodsIds, $d['goods_id']);
            }
        }
        // 比对版本号，如果大于的，则在$installedList中添加一个字段以及版本号与下载地址
        $code = $this->getCode();
        if ($code) {
            if ($goodsIds) {
                $url = config('vitphp.market') . 'api/check';
                $post = [
                    'code' => $code,
                    'goods' => implode(',', $goodsIds)
                ];
                $result = app_http_request($url, $post);
                $result = is_null(json_decode($result)) ? [] : json_decode($result, true);

                if (!empty($result['data'])) {
                    foreach ($result['data'] as $k => $banben) {
                        if (isset($banben['gid'])) {
                            Db::name($this->table_addons)->where('goods_id', '=', $banben['gid'])->update([
                                'yun_version' => $banben['number'],
                                'describe' => $banben['describe'],
                                'up_time' => $banben['up_time']
                            ]);
                        }
                    }
                }
            }
        }
        $replace = [
            'wechat' => '微信公众号',
            'wxapp' => '微信小程序',
            'template' => 'pc模板应用',
            'web' => '网页应用',
            'workwechat' => '企业微信',
            'ksapp' => '快手小程序',
            'androidapp' => '安卓app',
            'iosapp' => '苹果app',
            'dyapp' => '字节跳动小程序',
            'bdapp' => '百度小程序',
            'alpayapp' => '支付宝小程序',
            'other' => '其他应用'
        ];

        foreach ($installedList as &$item) {
            if (isset($result['data'][$item['goods_id']])) {
                $banben = $result['data'][$item['goods_id']];
                if (!isset($banben['gid'])) {
                    $item['is_update'] = 0;
                } else {
                    if (version_compare($item['version'], $banben['number'], '<')) {
                        $item['is_update'] = 1;
                        $item['yun_version'] = $banben['number'];
                    } else {
                        $item['is_update'] = 0;
                    }
                }
            } else {
                $item['is_update'] = 0;
            }
            $addonsType = explode(',', $item['addons_type']);
            foreach($addonsType as &$type){
                foreach($replace as $repK =>$rep){
                    if($type==$repK) $type = $rep;
                }
            }

            $item['addons_type'] = implode(',', $addonsType);
        }
        foreach ($notInstallList as &$notItem) {
            $addonsType = explode(',', $notItem['addons_type']);
            foreach($addonsType as &$type){
                foreach($replace as $repK =>$rep){
                    if($type==$repK) $type = $rep;
                }
            }

            $notItem['addons_type'] = implode(',', $addonsType);
        }

        View::assign(
            [
                'notInstallList' => $notInstallList,
                'installedList' => $installedList,
                'page' => $page
            ]
        );
    }

    /**
     * @title 编辑应用
     */
    public function edit()
    {
        $id = input('id');
        $addons = Db::name('addons')->where('id', $id)->find();
        if ($addons) {
            $price = unserialize($addons['price']);
            $addons['prices'] = json_decode($price, true);
        } else {
            $addons['prices'] = [];
        }
        //判断是否post提交
        if (request()->isPost()) {
            $param = [
                'name' => input('name'),
                'logo' => input('logo'),
            ];
            $res = Db::name("addons")->where(['id' => $id])->update($param);
            if ($res) {
                $this->success("配置成功！", 'index/addons/list');
            } else {
                $this->error("配置失败！");
            }
        }
        View::assign([
            'addons' => isset($addons) ? $addons : ''

        ]);
        return View::fetch();
    }

    public function install()
    {
        $id = input('id');
        $modules = Db::name('addons')->where('id', $id)->find();
        if ($modules['is_install'] == 1) $this->error('应用已安装，不可重复操作');

        $config = addons_config_array($modules['identifie']);
        [$code, $msg] = check_appconfig($config);
        if ($code) $this->error($msg);

        $this->config = $config;
        $this->addons = $modules['identifie'];

        Db::startTrans();
        try {
            $this->installMoveStatic();
            $this->handle($this->config['install']);
            $this->updateMenu();
            Db::name('addons')->where('id', $modules['id'])->update(['is_install' => 1]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
            $this->error('安装失败：' . $e->getMessage());
        }

        (new Admin(app()))->clear(0);
        save_sys_log('安装应用', "应用标识：{$modules['identifie']}", session('admin.username'));
        $this->success('安装成功');
    }

    public function uninstall()
    {
        $id = input('id');
        $modules = Db::name('addons')->where('id', $id)->find();
        if (!$modules['is_install']) $this->error('卸载失败');

        $config = addons_config_array($modules['identifie']);
        [$code, $msg] = check_appconfig($config);
        if ($code) $this->error($msg);

        $this->config = $config;
        $this->addons = $modules['identifie'];

        Db::startTrans();
        try {
            $this->uninstallMoveStatic();
            $this->handle($this->config['uninstall']);
            $this->deleteMenu();
            Db::name('addons')->where('id', $modules['id'])->delete();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
            $this->error('卸载失败：' . $e->getMessage());
        }

        (new Admin(app()))->clear(0);
        save_sys_log('卸载应用', "应用标识：{$modules['identifie']}", session('admin.username'));
        $this->success('卸载成功');
    }

    public function update()
    {
        $id = input('id');
        $modules = Db::name('addons')->where('id', $id)->find();
        if (!$modules) jsonErrCode('升级失败');

        $config = addons_config_array($modules['identifie']);
        [$code, $msg] = check_appconfig($config);
        if ($code) $this->error($msg);

        $this->config = $config;
        $this->addons = $modules['identifie'];

        Db::startTrans();
        try {
            $this->installMoveStatic();
            $this->handle($this->config['upgrade']);
            $this->updateMenu();
            Db::name('addons')->where('id', $modules['id'])->update([
                'version' => $this->config['addons']['version'],
                'addons_type' => $this->config['addons']['addons_type']
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
            $this->error('升级失败：' . $e->getMessage());
        }

        (new Admin(app()))->clear(0);
        $this->success('升级成功');
    }

    public function cloud_down()
    {
        $id = input('id');
        $addons = Db::name('addons')->where('id', $id)->find();

        $url = config('vitphp.market') . 'api/update/detail';
        $post = [
            'code' => $this->getCode(),
            'goods' => $addons['goods_id']
        ];
        $result = app_http_request($url, $post);
        $result = is_null(json_decode($result)) ? [] : json_decode($result, true);
        if (!$result || $result['code'] != 1) {
            $msg = $result['msg'] ?? '请求错误';
            return json(['code' => -1, 'msg' => $msg]);
        }
        $fileUrl = $result['data']['fileurl'] ?? '';
        if ($fileUrl) {
            $pathInfo = pathinfo($fileUrl);
            $filename = $pathInfo['basename'];
            $file = down_file($fileUrl, app()->getRootPath() . 'app', $filename);
            $zip = new \ZipArchive();
            $openRes = $zip->open($file['file']);
            if ($openRes === TRUE) {
                $zip->extractTo(app()->getRootPath() . 'app');
                $zip->close();
            }
            @unlink($file['file']);
        }

        save_sys_log('下载远程源码', "应用iD：{$id}", session('admin.username'));
        return json(['code' => 1, 'msg' => '下载成功']);
    }

    public function yunUpdate()
    {
        $id = input('id');
        $addons = Db::name('addons')->where('id', $id)->find();

        //判断是否post提交
        if (request()->isPost()) {
            $url = config('vitphp.market') . 'api/update/detail';
            $post = [
                'code' => $this->getCode(),
                'goods' => $addons['goods_id']
            ];
            $result = app_http_request($url, $post);
            $result = is_null(json_decode($result)) ? [] : json_decode($result, true);

            if (!$result || $result['code'] != 1) {
                $msg = $result['msg'] ?? '请求错误';
                return json(['code' => -1, 'msg' => $msg]);
            }
            $fileUrl = $result['data']['fileurl'] ?? '';
            if ($fileUrl) {
                $pathInfo = pathinfo($fileUrl);
                $filename = $pathInfo['basename'];
                $file = down_file($fileUrl, app()->getRootPath() . 'app', $filename);
                $zip = new \ZipArchive();
                $openRes = $zip->open($file['file']);
                if ($openRes === TRUE) {
                    $zip->extractTo(app()->getRootPath() . 'app');
                    $zip->close();
                    @unlink($file['file']);
                    $this->update();
                }
            }
            return json(['code' => 1, 'msg' => '升级成功']);
        }
        View::assign([
            'addons' => isset($addons) ? $addons : ''

        ]);
        return View::fetch();
    }

    public function handle($handle)
    {
        if (!$handle) return;
        $extensions = substr($handle, -4);
        if ($extensions == '.php') {
            $handleFile = root_path() . 'app' . DIRECTORY_SEPARATOR . $this->addons . DIRECTORY_SEPARATOR . $handle;
            if (file_exists($handleFile)) {
                require_once $handleFile;
            }
        } else {
            $sqls = sql_parse($handle);
            foreach ($sqls as $sql) {
                try {
                    Db::execute($sql);
                } catch (\Exception $e) {
                }
            }
        }
    }

    /**
     * 安装与更新的时候移动静态文件
     */
    public function installMoveStatic()
    {
        $addonStaticDir = root_path() . 'app' . DIRECTORY_SEPARATOR . $this->addons . DIRECTORY_SEPARATOR . $this->addons . DIRECTORY_SEPARATOR;
        $newStaticDir = root_path() . 'public' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $this->addons . DIRECTORY_SEPARATOR;
        try {
            Common::copydirs($addonStaticDir, $newStaticDir);
        } catch (\Exception $e) {
        }

        Common::rmdirs($addonStaticDir);
    }

    /**
     * 卸载的时候移动静态文件
     */
    public function uninstallMoveStatic()
    {
        $oldDir = root_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $this->addons . DIRECTORY_SEPARATOR;
        $appAddonDir = root_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $this->addons . DIRECTORY_SEPARATOR . $this->addons . DIRECTORY_SEPARATOR;
        try {
            Common::copydirs($oldDir, $appAddonDir);
        } catch (\Exception $e) {
        }

        Common::rmdirs($oldDir);
    }

    /**
     * 更新菜单
     */
    public function updateMenu()
    {
        $getmenus = Db::name('menu')->where(['type' => $this->addons])->select();

        // 如果没有$this->config['menu']，就不处理
        // 如果$this->config['menu']有，但库中没有，才添加
        if (!isset($this->config['menu'])) return;

        // 找到xml文件中有，但是数据库中没有的
        $addMenus = [];
        foreach ($this->config['menu'] as $menu) {
            if (!$menu) continue;
            $addFlag = true;
            foreach ($getmenus as $getmenu) {
                if ($getmenu['url'] == $menu['url']) $addFlag = false;
            }
            if (!$addFlag) continue;
            array_push($addMenus, $menu);
        }
        $updateMenus = [];
        foreach ($this->config['menu'] as $menu) {
            if (!$menu) continue;
            $updateFlag = false;
            foreach ($getmenus as $getmenu) {
                if ($getmenu['url'] == $menu['url']) {
                    // url一样但是title或icon不一样，才添加
                    if ($getmenu['title'] != $menu['name'] || $getmenu['icon'] != $menu['icon']) {
                        $updateFlag = true;
                    }
                }
            }
            if (!$updateFlag) continue;
            array_push($updateMenus, $menu);
        }
        foreach ($addMenus as $k => $v) {
            $data = [];
            $data['title'] = $v['name'];
            $data['icon'] = $v['icon'];
            $data['url'] = $v['url'];
            $data['type'] = $this->addons;
            $data['pid'] = 0;
            $data['status'] = 1;
            Db::name('menu')->insertGetId($data);
        }
        foreach ($updateMenus as $k => $v) {
            $data = [];
            $data['title'] = $v['name'];
            $data['icon'] = $v['icon'];
            $data['url'] = $v['url'];
            $data['type'] = $this->addons;
            $data['pid'] = 0;
            $data['status'] = 1;
            Db::name('menu')->where('url', $v['url'])->update($data);
        }

        // 菜单去重
        $this->removeRepeatMenu();
    }

    protected function removeRepeatMenu()
    {
        $getmenus = Db::name('menu')->where(['type' => $this->addons])->select();
        $menus = [];
        $removeIds = [];
        foreach($getmenus as $menu){
            if(isset($menus[$menu['url']])){
                array_push($removeIds, $menu['id']);
            }else{
                array_push($menus, $menu['url']);
            }
        }

        if(!$removeIds) return;
        Db::name('menu')->where('id', 'in', $removeIds)->delete();
    }

    /**
     * 删除菜单
     */
    public function deleteMenu()
    {
        Db::name('menu')->where(['type' => $this->addons])->delete();
    }
    private function downFile($url)
    {
        $pathInfo = pathinfo($url);
        $filename = $pathInfo['basename'];
        $saveDir = app()->getRootPath().'app';
        if (!file_exists($saveDir)) {
            mkdir($saveDir, 0755);
            @chmod($saveDir, 0755);
        }
        $localPath = $saveDir . DIRECTORY_SEPARATOR . $filename;
        if(file_exists($saveDir . DIRECTORY_SEPARATOR . $filename)){
            return [
                'name' => $filename,
                'path' => $saveDir,
                'file' => $saveDir . DIRECTORY_SEPARATOR . $filename
            ];
        }
        ob_start(); //打开输出
        readfile($url); //输出图片文件
        $file = ob_get_contents(); //得到浏览器输出
        ob_end_clean(); //清除输出并关闭
        file_put_contents($localPath, $file);
        return [
            'name' => $filename,
            'path' => $saveDir,
            'file' => $saveDir . DIRECTORY_SEPARATOR . $filename
        ];
    }

    public function cut_url($url){
        if(strpos($url,'.')!==false){
            $m_url = explode('.',$url);
            $result['m_url'] = $m_url[0];
            if(strpos($url,'/')!==false){
                $c_url = explode('/',$m_url[1]);
                $result['c_url'] = $c_url[0];
                $result['a_url'] = $c_url[1];
            }else{
                return false;
            }
        }else{
            if(strpos($url,'/')!==false){
                $m_url = explode('/',$url);
                $result['c_url'] = $m_url[0];
                $result['a_url'] = $m_url[1];
            }else{
                $result['a_url'] = $url;
            }
        }
        return $result;
    }

    public function del_dir($dir,$addons){
        foreach ($dir as $item) {
            unlink($item, 0777, true);
        }
        $rootPath = root_path();
        $addonsPath = $rootPath.'app'.DIRECTORY_SEPARATOR.$addons;
        unlink($addonsPath.DIRECTORY_SEPARATOR.'static', 0777, true);
        return true;
    }

    private function checkMenuUrl($url)
    {
        $re = '^[\.a-zA-Z\/]$^';
        $menuTip = 'URL格式为 xxx/xxx 或 xxx.xxx/xxx';
        if (!preg_match($re, $url)){
            $this->error($menuTip);
        }
        $dianCount = substr_count($url, '.');
        $backslashCount = substr_count($url, '/');
        if($dianCount>1 || $backslashCount>1 ) $this->error($menuTip);

        $len = strlen($url);
        if(strpos($url,'.')!==false){
            if(!$backslashCount) $this->error($menuTip.'!');
            if($url[0]=='.') $this->error($menuTip.'..');
            if($url[$len-1]=='.') $this->error($menuTip.'-');

            $backslashIndex =  strpos($url,'/');
            if(!$backslashIndex) $this->error($menuTip.'/');

            if(strpos($url,'.') > $backslashIndex)  $this->error($menuTip.'aa');
        }else{
            if($backslashCount>1) $this->error($menuTip);
            if($url[0]=='/') $this->error($menuTip);
            if($url[$len-1]=='/') $this->error($menuTip);
        }
    }
    /**
     * 创建应用
     * @param $name
     * @param $addons
     * @param null $logo
     * @param null $author
     * @param null $menus
     */
    public function save($name,$addons,$logo = null,$author = null,$menus = null){
        $rootPath = root_path();
//        halt($this->request->post());
        $goodsId = input('goods_id', '');
        if($goodsId){
            $dbAddons = Db::name($this->table_addons)->where('goods_id', $goodsId)->find();
            if($dbAddons) $this->error('商品ID已存在，请更换');
        }

        $addonsTypes = input('addons_type');
        $addonsTypes = $addonsTypes ? implode(',', $addonsTypes) : '';
        $addonsPath = $rootPath.'app'.DIRECTORY_SEPARATOR.$addons;
        if(file_exists($addonsPath)){
            $this->error("该插件标识不可用！");
        }
        if (!preg_match('^[_a-zA-Z]$^',$addons)){
            $this->error("该插件标识只能是小写字母与下划线！");
        }
        foreach ($menus as $item){
            $this->checkMenuUrl($item['menu_url']);

            $path_check = $this->cut_url($item['menu_url']);
            if(!$path_check){
                $this->error("URL格式不正确！");
            }
            if(!isset($path_check['c_url'])){
                $this->error("URL格式不正确！");
            }
        }
        $addonsDirs = [
            $addonsPath.DIRECTORY_SEPARATOR.'controller',
            $addonsPath.DIRECTORY_SEPARATOR.'view',
            $rootPath.'app'.DIRECTORY_SEPARATOR.$addons.DIRECTORY_SEPARATOR.$addons.DIRECTORY_SEPARATOR.'images'
        ];
        foreach ($addonsDirs as $dir){
            mkdir($dir,0777,true);
        }
        $config_menu = [];
        foreach ($menus as $item){
            $path_res = $this->cut_url($item['menu_url']);

            $create_con_path = $addonsPath.DIRECTORY_SEPARATOR.'controller';
            $create_con_path .= isset($path_res['m_url']) ? '/'.$path_res['m_url'] : '';
            if(!file_exists($create_con_path)){
                mkdir($create_con_path,0777,true);
            }
            if(!isset($path_res['m_url'])){
                $namespace = 'namespace app\\'.$addons.'\controller;';
            }else{
                $namespace = 'namespace app\\'.$addons.'\controller\\'.$path_res["m_url"].';';
            }
            $FirstBig = ucfirst($path_res['c_url']);
            $controller_content = <<<EOT
<?php
$namespace
use think\\facade\Db;
use think\\facade\View;
use vitphp\admin\controller\BaseController;
class $FirstBig extends BaseController
{
    public function {$path_res['a_url']}(){
	 return View::fetch();
	}	
}
EOT;
            file_put_contents($create_con_path.'/'.$FirstBig.'.php', $controller_content);//写入PHP
            $create_view_path = $addonsPath.DIRECTORY_SEPARATOR.'view';
            $create_view_path .= isset($path_res['m_url']) ? '/'.$path_res['m_url'] : '';
            $create_view_path .= isset($path_res['c_url']) ? '/'.$path_res['c_url'] : '';
            mkdir($create_view_path,0777,true);
            $config_menu[] = "<id name='{$item["menu_name"]}' icon='{$item['menu_icon']}' url='{$item['menu_url']}'/>\r\n";
            //写入H5文件
            $view_content = "{extend name='../../index/view/addons'}
{block name='content'}
<div>{$item['menu_name']}</div>
{/block}";
            file_put_contents($create_view_path.'/'.$path_res['a_url'].'.html', $view_content);

        }

        $suffix = $this->get_suffix($logo);
        $app_logo = '/app/'.$addons.'/images/logo.'.$suffix;
        //写入XML文件
        $config_menu = implode("",$config_menu);
        $config_body=<<<___
<?xml version="1.0" encoding="utf-8"?>
<vitphp version="1.0.0">
    <addons>
        <name><![CDATA[$name]]></name>
        <identifie><![CDATA[$addons]]></identifie>
        <version><![CDATA[1.0.0]]></version>
        <goodsid><![CDATA[$goodsId]]></goodsid>
        <logo><![CDATA[$app_logo]]></logo>
        <author><![CDATA[$author]]></author>
        <addons_type><![CDATA[$addonsTypes]]></addons_type>
    </addons>
    <menu>
        $config_menu
    </menu>
    <install><![CDATA[]]></install>
    <uninstall><![CDATA[]]></uninstall>
    <upgrade><![CDATA[]]></upgrade>
</vitphp>
___;
        file_put_contents($addonsPath.DIRECTORY_SEPARATOR.'config.xml', $config_body);
        try{
            $preg = "/http[s]?:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is";
            if (!preg_match($preg, $logo)) {
                $logo = $rootPath . 'public' . $logo;
            }
            file_put_contents($rootPath.'app'.DIRECTORY_SEPARATOR.$addons.DIRECTORY_SEPARATOR.$addons.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'logo'.'.'.$suffix, file_get_contents($logo));
        }catch (\Exception $e){
            $this->error('图片地址异常');
        }
        $module['name']= $name;
        $module['identifie'] = $addons;
        $module['version'] = "1.0.0";
        $module['logo'] = $app_logo;
        $module['author'] = $author;
        $module['goods_id'] = $goodsId ?: null;
        $module['addons_type'] = $addonsTypes;
        $module['is_install'] = 0;
        $module['create_time'] = time();
        $Id = Db::name($this->table_addons)->insertGetId($module);
        $this->success('新建成功','', $Id);
    }
    // 字符串切割
    public function get_suffix($url){
        return substr($url,strrpos($url,'.')+1);
    }
    /**
     * @title 应用菜单管理
     */
    public function menu(){
        $ide = input('ide');
        $menu = Db::name('menu')->where('type',$ide)->select()->toArray();
        View::assign([
            'menu'=>$menu
        ]);
        return View::fetch();
    }

    /**
     * @title 编辑应用菜单
     */
    public function editmenu(){
        $id = input('id');
        $ide = input('ide');
        $menu = Db::name('menu')->where(['id'=>$id,'type'=>$ide])->find();
        if(request()->isPost()){
            $param = [
                'title'=>input('title'),
                'url'=>input('url'),
                'icon'=>input('icon'),
                'type'=>$ide,
            ];
            if ($id){
                Db::name('menu')->where('id',$id)->update($param);
            }else{
                Db::name("menu")->save($param);
            }
            $this->success("保存成功！");
        }

        View::assign([
            'menu'=>$menu
        ]);
        return View::fetch();
    }
    /**
     * @title 删除菜单
     */
    public function del(){
        $ids = $this->request->post('ids');
        $query = Db::name('menu')->where('id',$ids)->delete();
        if ($query){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
}