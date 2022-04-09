<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------


namespace vitphp\admin\middleware;


use think\facade\Db;
use think\facade\View;
use think\Request;
use app\BaseController;
use vitphp\admin\Tree;

class SeoFetch
{
    public function handle(Request $request,\Closure $next)
    {
        $uid = session('admin.id');
        $domain = $request->host();
        $site = Db::name('system_site')->where('domain',$domain)->find();
        if (!$site){
//            header("HTTP/1.1 404 Not Found");exit;
            $site = Db::name('system_site')->where('uid','1')->find();
        }
        $menu = Db::name('menu')->where(['type'=>'index','status'=>'1','uid'=>$site['uid']])->order('sort', 'asc')->select()->toArray();
        $menus = "";
        if($menu){
            $menus = Tree::array2tree($menu);
        }
        $site = $site?$site:[];

        $index_hdp = unserialize($site['index_hdp']);
        if(!$index_hdp || count($index_hdp)<=1){
            $img = $index_hdp[0]['hdp_img'] ?? '';
            if(!$img) $index_hdp = [];
        }
        $code = getSiteCode();
        $url = config('vitphp.market').'api/cloud/index';
        $post = [
            'code' => $code
        ];
        $result = app_http_request($url, $post);
        $result = json_str_to_array($result);
        $copyrightTime = $result['data']['copyright_time'] ?? 0;
        if($copyrightTime > time()? 0 : 1){
            $site['title'] = $site['title'].' - Powered by Vitphp';
            $site['foot_top'] = 'Copyright © 2022 VitPhp <a href="https://www.vitphp.cn" target="_blank">vitphp.cn</a> All Rights Reserved';
            $site['foot_bottom'] = '免费开源的saas管理系统';
        }
        View::assign([
            'system_site'=>$site,
            'menus'=>$menus,
            'index_hdp'=>$index_hdp,
            'is_show_copy' => $copyrightTime > time() ? 0 : 1,
            'copyright_time' => $copyrightTime
        ]);
        return $next($request);
    }
}