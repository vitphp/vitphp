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
 * @title 财务中心
 */
class Property extends BaseController
{
    /**
     * @title 订单列表
     * @login 1
     * @auth 1
     */
    public function index(){
        $pageNum = 15;
        $uid = session('admin.id');
        $orderid = input('orderid');
        $user_id = input('user_id');

        $users = [];
        $where = [];
        if($orderid) $where[] = ['p.orderid', 'like', '%'.$orderid.'%'];
        if($uid==1){
            if($user_id) $where[] = ['p.uid', '=', $user_id];
            $users = Db::name('users')->where('is_deleted', 0)->whereNull('pid')->select();
        }else{
            $where[] = ['p.uid', '=', $uid];
        }
        $where = $where ? $where : 1;
        $order = Db::name('property')->alias('p')
            ->leftJoin('vit_users u', 'p.uid=u.id')
            ->field('p.*,u.username')
            ->where($where)->paginate($pageNum);

        $data = $order->isEmpty() ? [] : $order->items();
        foreach($data as &$item){
            $item['snapshot'] = $item['snapshot'] ? json_decode($item['snapshot'], true) : [];
        }

        View::assign([
            'users' => $users,
            'order' => $data,
            'page' => $order->render(),
        ]);
        return View::fetch();
    }
}