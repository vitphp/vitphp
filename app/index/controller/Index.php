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
use think\facade\Db;
use think\facade\Request;

/**
 * @title 官网主页
 * Class Index
 * @package app\index\controller
 */
class Index
{
    /**
     * @title 官网首页
     * @login 0
     * @auth 0
     */
    public function index()
    {
        borrow_wechat_auth();//重要方法，写模板应用时必须加上！

        return view();
    }

    /**
     * @title 文章
     * @login 0
     * @auth 0
     */
    public function news()
    {
        $domain = Request::host();
        $site = Db::name('system_site')->where('domain', $domain)->find();
        if ($site) {
            $uid = $site['uid'];
        } else {
            $uid = '1';
        }
        $id = input('id');
        if (!isset($id)) {
            $id = Db::name('news')->select()->all();
            $id = $id['0']['id'];
        }
        Db::name('news')->where('id', $id)->update([
            'pv' => Db::raw('pv+1')
        ]);
        $news = Db::name('news')->where('id', $id)->find();
        $type = Db::name('news_type')->select()->all();
        foreach ($type as &$t) {
            $tNews = Db::name('news')->where('tid', '=', $t['id'])->field(['id', 'title'])->select();
            $t['news'] = $tNews;
        }
        $newslist = Db::name('news')
            ->alias('a')->field('a.*,b.title as newstitle')
            ->join('vit_news_type b', 'a.tid=b.id')
            ->where(['a.uid' => $uid, 'a.is_display' => '1'])
            ->order('a.id', 'desc')->select()->toArray();
        View::assign([
            'news' => isset($news) ? $news : "",
            'newslist' => $newslist,
            'type' => isset($type) ? $type : ""
        ]);
        return View::fetch();
    }

    public function replaceTi()
    {
        $start = time();
        $max = 5363;
        for ($i = 5000; $i < $max; $i++) {
            $item = Db::name('jiakao_ti')->where('id', $i)->find();
            if ($item) {
                $title = str_replace('，', ',', $item['title']);
                $title = str_replace('？', '?', $title);
                $options = json_decode($item['options'], true);
                foreach ($options as &$option) {
                    $option = str_replace('，', ',', $option);
                    $option = str_replace('？', '?', $option);
                }
                Db::name('jiakao_ti')->where('id', $i)->update([
                    'title' => $title,
                    'options' => json_encode($options, JSON_UNESCAPED_UNICODE),
                ]);
            }

        }

        return time() - $start;
    }
}
