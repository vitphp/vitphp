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
use think\facade\Session;
use think\facade\View;
use vitphp\admin\auth\AnnotationAuth;
use vitphp\admin\controller\BaseController;
use vitphp\admin\model\SystemMenu;
use vitphp\admin\Tree;

/**
 * @title 管理员后台
 * Class Admin
 * @package app\index\controller
 */
class Admin extends BaseController
{
    /**
     * @title 后台全局
     * @auth 0
     * @login 1
     * @return string
     * @throws \throwable
     */
    public function index()
    {
        // 查询全部菜单

        $model = SystemMenu::order('sort', 'asc')
            ->where(['status' => '1', 'type' => 'admin'])
            ->select();
        if (\session('admin.id') != 1) {
            $role_nodes = Db::table('vit_auth_nodes')
                ->whereIn('rule_id', Db::table('vit_auth_map')
                    ->where('admin_id', \session('admin.id'))
                    ->column('role_id'))
                ->group('node')
                ->select()->column('node');
            $user_nodes = Db::table('vit_auth_nodes')
                ->where('uid', \session('admin.id'))
                ->select()->column('node');
            $nodes = array_map(function ($d) {
                return strtolower($d);
            }, array_merge($role_nodes, $user_nodes));

            $menus = $model->toArray();
            $data = [];
            $s = []; // $s = AnnotationAuth::getAddonsAuth("index",false,false)[0]['list'];
            $nodes_codes = [];
            $nodes_keys = [];
            foreach ($s as $v) {
                $node = strtolower($v['node']);
                $nodes_codes[] = $node;
                $nodes_keys[$node] = $v;
            }
            foreach ($menus as $v) {
                if (isset($nodes_keys[$v['url']])) {
                    $n = $nodes_keys[$v['url']];
                    if (!isset($n['meta']['auth'])) {
                        $n['meta']['auth'] = 0;
                    }
                    if ($n['meta']['auth'] === 1) {
                        if (in_array($v['url'], $nodes)) {
                            $data[] = $v;
                            continue;
                        } else {
                            continue;
                        }
                    }
                }
                if (in_array($v['url'], $nodes) || $v['url'] === "#") {
                    $data[] = $v;
                }
            }
            $menus = $data;
        } else {
            $menus = $model->toArray();
        }
        foreach ($menus as $i => $v) {
            $v['path'] = $v['url'];
            $v['url'] = url(strtolower($v['url']))->build();
            $menus[$i] = $v;
        }
        $menus = Tree::array2tree($menus);

        $data = [];
        foreach ($menus as $i => $item) {
            if ($item['path'] == "#" || $item['url'] == '/index/Admin/index.html') {
                if (empty($item['_child']) || count($item['_child']) === 0) {
                    continue;
                }
            }
            $data[] = $item;
        }
        $menus = $data;
//            halt($data);
        View::assign("menus", $menus);
        return View::fetch();
    }

    /**
     * @title 后台首页
     * @auth 0
     * @login 1
     * @return string
     */
    public function main()
    {
        $uid = session('admin.id');
        $data['usersum'] = Db::name('users')->where(['is_deleted' => '0'])->count();
        $data['addonssum'] = Db::name('addons')->where(['is_install' => '1'])->count();
        $data['appsum'] = Db::name('app')->count();
        $data['newssum'] = Db::name('news')->count();
        $newTid = getSetting('tid', 'mainset');
        $news = Db::name('news')->where(['tid' => $newTid])->select();

        View::assign([
            'data' => $data,
            'news' => $news,
            'uid' => $uid
        ]);
        return View::fetch();
    }

    /**
     * @title 配置公告
     * @login 1
     * @auth 1
     */
    public function setnews()
    {
        $newstype = DB::name('news_type')->where('uid', Session::get("admin.id"))->order('id desc ')->select();

        if (request()->isPost()) {
            foreach ($this->request->post() as $name => $value) {
                setSetting($name, $value, 'mainset');
            }
            $this->success("配置成功！", 'index/admin/main');
        }

        View::assign([
            'newstype' => $newstype
        ]);
        return View::fetch();
    }

    public function tuijian()
    {
        $url = config('vitphp.market') . 'api/goods';
        $post = [
            //'code' => $this->getCode(),
            'mark' => 'vitphp',
            'did' => ''
        ];

        $result = app_http_request($url, $post);
        $result = is_null(json_decode($result)) ? [] : json_decode($result, true);

        if (!$result || $result['code'] != 1) {
            $msg = $result['msg'] ?? '请求错误';
            return json(['code' => -1, 'msg' => $msg]);
        }

        return json(['code' => 1, 'data' => $result['data']]);
    }

    /**
     * @title 清理缓存
     * @login 1
     */
    public function clear($showTitle = 1)
    {
        $runtimePath = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;

        $path = $runtimePath . 'cache';
        Common::rmdirs($path);

        if($showTitle){
            $this->success('清理完成！');
        }
    }
}
