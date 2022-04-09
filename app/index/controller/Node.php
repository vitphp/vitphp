<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace app\index\controller;

use think\facade\View;
use vitphp\admin\controller\BaseController;

/**
 * @title 权限节点
 * Class Node
 * @package app\vitphp\controller
 */
class Node extends BaseController
{
    /**
     * @title 列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        View::assign("list", []);
        return View::fetch();
    }

    /**
     * @title 刷新节点
     * @throws \ReflectionException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function clear()
    {
        set_time_limit(0);
        session_write_close();
        $this->success('操作成功', '');
    }
}