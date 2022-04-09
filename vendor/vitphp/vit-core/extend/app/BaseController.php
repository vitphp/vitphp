<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\facade\Cache;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 获取站点code
     * @param false $refresh
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSiteCode($refresh = false )
    {
        if($refresh){
            return $this->getRefreshSiteCode();
        }

        $code = Cache::get('vit_site_code');
        if(!$code){
            return $this->getRefreshSiteCode();
        }
        return $code;
    }

    /**
     * 重新获取新的站点code值
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getRefreshSiteCode()
    {
        $domain = $this->request->host();
        $appid = getSetting('appid', 'cloud');
        $appkey = getSetting('appkey', 'cloud');
        $time = time();
        $sign = sha1($appid.$appkey.$domain.$time);

        $url = config('vitphp.market').'api/code/get';
        $post = [
            'domain' => $domain,
            'appid' => $appid,
            'sign' => $sign,
            'time' => $time
        ];
        $result = app_http_request($url, $post);
        $result = is_null(json_decode($result)) ? [] : json_decode($result, true);
        if(!$result || $result['code']!=1){
            return '';
        }
        Cache::set('vit_site_code', $result['data']['code'], '600');
        return $result['data']['code'];
    }

    /**
     * 获取code值
     * @param false $refresh
     * @return mixed|string
     */
    public function getCode($refresh = false )
    {
        $code = $this->getSiteCode($refresh);
        if($code) return $code;

        if($refresh){
            return $this->getRefreshCode();
        }

        $code = Cache::get('vit_code');
        if(!$code){
            return $this->getRefreshCode();
        }
        return $code;
    }

    /**
     * 重新获取新的code值
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getRefreshCode()
    {
        $username = getSetting('username', 'info');
        $appid = getSetting('appid', 'info');
        $appkey = getSetting('appkey', 'info');
        $time = time();
        $sign = sha1($username.$appkey.$time);

        $url = config('vitphp.market').'api/code';
        $post = [
            'appid' => $appid,
            'sign' => $sign,
            'time' => $time
        ];
        $result = app_http_request($url, $post);
        $result = is_null(json_decode($result)) ? [] : json_decode($result, true);
        if(!$result || $result['code']!=1){
            return '';
        }
        Cache::set('vit_code', $result['data']['code'], '600');
        return $result['data']['code'];
    }
}
