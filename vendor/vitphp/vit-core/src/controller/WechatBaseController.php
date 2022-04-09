<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace vitphp\admin\controller;

use app\BaseController as ThinkController;
use think\App;
use think\facade\Request;

class WechatBaseController extends ThinkController
{
    protected $res = [
        'code' => 0,
        'msg' => 'ok',
        'data' => ''
    ];

    /**
     * Base constructor.
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        // 判断是否
    }

    protected function json()
    {
        return json($this->res);
    }

    /**
     * 获取第几页
     * @param string $key post中页码的key值
     * @return int
     */
    protected function getPage($key='page')
    {
        $page = $this->request->post($key) ?? 0;
        $page = (int)$page;
        return $page ?: 1;
    }

    /**
     * 获取显示条数
     * @param string $key post中显示条数的key值
     * @param int $detail
     * @return int
     */
    protected function getNum($key='num', $detail = 0)
    {
        $detail = $detail ?: 15;
        $num = $this->request->post($key) ?? 0;
        $num = (int)$num;
        $num = $num > 100 ? 100 : $num;
        return $num ?: $detail;
    }

    /**
     * 根据附件，返回可直接查看的url，这个应该放在公共方法里
     * @param $media
     * @return array|string
     */
    protected function getFullMediaUrl($media)
    {
        if (!$media) return '';
        $preg = "/http[s]?:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is";
        $mediaUrl = media($media, 'act');
        if (!preg_match($preg, $mediaUrl)) {
            $mediaUrl = Request::domain() . $mediaUrl;
        }

        return $mediaUrl;
    }
}