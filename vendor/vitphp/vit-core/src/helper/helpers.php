<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

define('IA_ROOT', str_replace("\\", '/', dirname(dirname(__FILE__))));

use think\facade\Cache;
use think\facade\Db;
use think\facade\Session;

//返回json
function jsonErrCode($msg)
{
    header('Content-Type: application/json');
    $result = [
        'code' => 0,
        'msg' => $msg,
    ];
    echo json_encode($result, 256);
    exit;
}

function jsonSucCode($msg, $data = "")
{
    header('Content-Type: application/json');
    $result = [
        'code' => 1,
        'msg' => $msg,
        'data' => $data
    ];
    echo json_encode($result);
    exit;
}

//xml验证
function check_appconfig($config)
{
    if (is_string($config)) return [1, '应用配置错误'];

    $name = $config['addons']['name'] ?? '';
    if (!$name) return [1, '应用名称定义错误或未定义，请检查config.xml文件. '];

    $version = $config['addons']['version'] ?? '';
    if (!$version || !preg_match('/^([1-9]\d|[1-9])(.([1-9]\d|\d)){2}$/', $version)) return [1, '应用版本号未定义，请检查config.xml文件. '];

    $logo = $config['addons']['logo'] ?? '';
    if (!$logo) return [1, '应用logo未定义，请检查config.xml文件. '];

    $author = $config['addons']['author'] ?? '';
    if (!$author) return [1, '应用作者未定义，请检查config.xml文件. '];

    $menu = $config['menu'] ?? '';
    if (!$menu) return [1, '应用菜单项不存在，请检查config.xml文件. '];

    return [0, ''];
}

function addons_config_array($addons_name)
{
    $filename = base_path() . $addons_name . '/config.xml';
    if (!file_exists($filename)) return [];

    $config = file_get_contents($filename);
    $config = xml2array($config);
    return parse_addons_config($config);
}

function parse_addons_config($config)
{
    if (empty($config)) return [];

    $menus = $config['menu'] ?? [];
    if($menus){
        $menusTmp = [];
        foreach($menus as $item){
            if(count($item)>1){
                foreach($item as $itemSon){
                    $menusTmp[] = [
                        'name' => $itemSon['@attributes']['name'] ?? '',
                        'icon' => $itemSon['@attributes']['icon'] ?? '',
                        'url' => $itemSon['@attributes']['url'] ?? ''
                    ];
                }
            }else{
                $menusTmp[] = [
                    'name' => $item['@attributes']['name'] ?? '',
                    'icon' => $item['@attributes']['icon'] ?? '',
                    'url' => $item['@attributes']['url'] ?? ''
                ];
            }
        }
        $menus = $menusTmp;
    }
    $goodsId = $config['addons']['goodsid'] ?? [];
    $vitphp = [
        'addons' => [
            'name' => $config['addons']['name'] ?? '',
            'version' => $config['addons']['version'] ?? '',
            'logo' => $config['addons']['logo'] ?? '',
            'goodsid' => $goodsId ?: '',
            'author' => $config['addons']['author'] ?? '',
            'addons_type' => $config['addons']['addons_type'] ?? ''
        ],
        'menu' => $menus,
        'install' => $config['install'] ?? '',
        'upgrade' => $config['upgrade'] ?? '',
        'uninstall' => $config['uninstall'] ?? ''
    ];

    return $vitphp;
}

/**
 * 将xml内容转成数组
 * @param $xml
 * @param false $isnormal
 * @return mixed
 */
function xml2array($xml)
{
    $obj = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
    $array = json_decode(json_encode($obj), true);

    return $array;
}

if (!function_exists('app_http_request')) {
    /**
     * http/https请求
     * @param string $url 请求的链接，若为get则将参数拼接而成
     * @param string $data 请求的参数，json格式
     * @param array $header 请未头，数组
     * @param array $extra 其他参数，用于扩展curl
     * @return array|bool|string
     */
    function app_http_request($url, $data = null, $header = [], $extra = [], $isJson = false, $method=null)
    {
        if($isJson){
            $header['Content-Type'] = 'application/json; charset=utf-8';
            $data = $data ? json_encode($data, 256) : '{}';
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if(empty($data) && $method=='post'){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        foreach ($extra as $opt => $value) {
            if (strpos($opt, 'CURLOPT_') !== false) {
                curl_setopt($curl, constant($opt), $value);
            } else if (is_numeric($opt)) {
                curl_setopt($curl, $opt, $value);
            }
        }
        if (!empty($header)) {
            foreach ($header as $key => $value) {
                $header[$key] = ucfirst($key) . ':' . $value;
            }
            $headers = array_values($header);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');

        $output = curl_exec($curl);
        curl_close($curl);

        return $output;
    }
}

/**
 * @param $path
 * @param array $params
 * @return string|\think\route\Url
 */
function ToUrl($path, $params = array())
{
    $url = url($path);
    $pid = input('pid');

    if (!empty($pid)) {
        $url .= "?pid={$pid}&";
    } else {
        $url .= "?pid=";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params);

        $url .= $queryString;
    }
    return $url;
}

/**
 * 获取ip地址
 * @return mixed|string
 */
function getip()
{
    if (isset($_SERVER['HTTP_CDN_SRC_IP']) && $_SERVER['HTTP_CDN_SRC_IP'] && strcasecmp($_SERVER['HTTP_CDN_SRC_IP'], "unknown")) {
        $ip = $_SERVER['HTTP_CDN_SRC_IP'];
    } elseif (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
        $ip = getenv("REMOTE_ADDR");
    } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = '';
    }
    return $ip;
}

/**
 * 创建二维码
 */
function createQrcode($url)
{
    if ($url) {
        require root_path() . 'extend/qrcode/phpqrcode.php';
        $errorCorrectionLevel = 'L';
        $matrixPointSize = '6';
        QRcode::png($url, false, $errorCorrectionLevel, $matrixPointSize);
        die;
    }
}

/**
 * 保存设置
 * @param $name
 * @param string $value
 * @return int|string
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 */
function setSetting($name, $value = '', $addons = '')
{
    $data = ['name' => $name, 'value' => $value, 'addons' => !empty($addons) ? $addons : 'setup'];
    $get = Db::name('settings')->where(['name' => $data['name'], 'addons' => $data['addons']])->find();
    if ($get) {
        $res = Db::name('settings')->where(['id' => $get['id']])->update($data);
    } else {
        $res = Db::name('settings')->insert($data);
    }
    return $res;
}

/**
 * 获取设置
 * @param $name
 * @return mixed
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 */
function getSetting($name, $addons = "")
{
    global $_SETTING_;

    if (!$_SETTING_) {
        $sett = DB::name('settings')->where('1=1')->order('id desc ')->select();
        $_SETTING_ = $sett->isEmpty() ? [] : $sett->toArray();
    }

    $data = [];
    if ($_SETTING_) {
        foreach ($_SETTING_ as $k => $v) {
            $data[$v['addons']][$v['name']] = $v['value'];
        }
        if ($addons) {
            return isset($data[$addons][$name]) ? $data[$addons][$name] : "";
        } else {
            return isset($data["setup"][$name]) ? $data["setup"][$name] : "";
        }
    } else {
        return '';
    }
}

/**
 * 生成字符串
 * @param int $length 要生成的长度
 * @param int $type 生成字符串内容的范围
 * @return string|null
 */
function redom($length = 8, $type = 0)
{
    $strPol = [
        "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz",
        "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
        "abcdefghijklmnopqrstuvwxyz0123456789",
        "0123456789",
        "abcdefghijklmnopqrstuvwxyz",
        "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*()_+~?",
    ];
    $str = null;
    $max = strlen($strPol[$type]) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[$type][rand(0, $max)];
    }

    return $str;
}

/**
 * 根据文件名，获取文件的访问链接
 * @param $fileUrl
 * @param null $storage
 * @param false $domain
 * @return array|string
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 */
function media($fileUrl, $storage = null, $domain = false)
{
    if (substr($fileUrl, 0, 1) == '/' && !is_numeric(substr($fileUrl, 1, 1))) {
        // 如果是/开头，并且第二位不是数字，直接返回
        return $fileUrl;
    } else if (substr($fileUrl, 0, 1) !== '/') {
        return $fileUrl;
    }
    // 如果是https://,http://,//开头直接返回
    if (strpos($fileUrl, "http://") !== false
        || strpos($fileUrl, "https://") !== false
        || strpos($fileUrl, "//") !== false
    ) {
        return $fileUrl;
    }
    // 如果$storage 不为空
    if (!is_null($storage)) {
        // 如果 $storage == 'act'则取当前默认$storage
        if ($storage == 'act') {
            $storage = getSetting("atta_type");
        }
        $storageMap = [
            '2' => 'domain',
            '3' => 'tx_domain',
            '4' => 'al_domain',
            '5' => 'ftp_domain'
        ];
        $name = $storageMap[$storage] ?? '';
        if ($name) {
            $domainStr = getSetting($name, 'setup');
            // 如果有设置domain，则返回数组
            if ($domain) {
                return [$domainStr, $fileUrl];
            }
            // 如果域名是/结尾直接拼接
            if (substr($domainStr, strlen($domainStr) - 1, 1) == '/') {
                $fileSrc = $domainStr . $fileUrl;
            } else {
                // 否则加上斜杠再拼接
                $fileSrc = $domainStr . str_replace("//", "/", '/' . $fileUrl);
            }
            return $fileSrc;
        }
    }
    // 如果是https://,http://,//开头直接返回
    if (strpos($fileUrl, "http://") !== false
        || strpos($fileUrl, "https://") !== false
        || strpos($fileUrl, "//") !== false
    ) {
        return $fileUrl;
    } else {
        // 如果是/app/开头的直接返回
        if (substr($fileUrl, 0, 5) === '/app/') {
            return $fileUrl;
        }
        // 否则拼接绝对路径
        return ROOT_PATH . $fileUrl;
    }

    return $fileUrl;
}

/**
 * 判断用户是否是应用的管理员
 * @return bool
 */
function is_admin()
{
    $uid = session('admin.id');
    if (\request()->root() === '/index') {
        if (!is_null($uid) && (int)$uid === 1) {
            return true;
        } else {
            return false;
        }
    }
    // 超管无惧
    if (!is_null($uid) && (int)$uid === 1) {
        return true;
    }
    $app_uid = Db::table('vit_app')
        ->where('id', \request()->get('pid'))
        ->value('uid');
    if (!is_null($uid) && !is_null($app_uid) && (int)$app_uid === (int)$uid) {
        return true;
    } else {
        return false;
    }
}
/**
 * 查询项目到期时间
 */
function app_dqtime()
{
    $pid = input('pid');
    if(!$pid) return '缺少参数pid';
    $app = Db::name('app')->where('id', $pid)->find();
    if(!$app) return '无数据';
    if($app['uid']==1){
        return '无限期';
    }else{
        $dqTime = $app['dq_time'];
        return !empty($dqTime) ? date('Y-m-d', $dqTime) : '已过期';
    }
}
/**
 * 判断当前项目是否到期
 * 通过pid找app表，如果uid为1，不操作
 * 若为其他，则判断表的dq_time，若到期返回false
 */
function is_appdue($pid)
{
    if(!$pid) return false;

    $app = Db::name('app')->where('id', $pid)->find();
    if(!$app) return false;
    if($app['uid']==1) return true;
    if(!$app['dq_time']) return false;
    return $app['dq_time'] >= time();
}

/**
 * 密码加密
 * @param $pass
 * @return false|string|null
 */
function pass_en($pass)
{
    $options = [
        "cost" => config('admin.cost')
    ];

    return password_hash($pass, PASSWORD_DEFAULT, $options);
}

/**
 * 密码校验
 * @param $pass
 * @param $hash
 * @return bool
 */
function pass_compare($pass, $hash)
{
    return password_verify($pass, $hash);
}

/**
 * 唯一日期编码
 * @param integer $size
 * @param string $prefix
 * @return string
 */
function uniqidDate($size = 16, $prefix = '')
{
    if ($size < 14) $size = 14;
    $string = $prefix . date('Ymd') . (date('H') + date('i')) . date('s');
    while (strlen($string) < $size) $string .= rand(0, 9);
    return $string;
}

/**
 * 权限校验
 * @param $path
 * @return mixed
 */
function auth($path)
{
    return \vitphp\admin\Auth::auth($path);
}

/**
 * 根据键值获取值，
 * 例：
 * $data = ['a'=>['b'=>['c'=>'this is c']]];
 * get_array_val($data, 'a.b.c') 就可以获取this is c
 * @param $array
 * @param $key
 * @param string $default
 * @return array|mixed|string
 */
function get_array_val($array, $key, $default = '')
{
    if (!is_array($array)) return $default;

    $strArr = explode('.', $key);
    $value = $array;
    foreach ($strArr as $v) {
        if (!isset($value[$v])) {
            return $default;
        }
        $value = $value[$v];
    }
    return $value;
}

/**
 * 下载远程文件到本地
 * @param $url
 * @param $saveDir
 * @param string $filename
 * @return array
 */
function down_file($url, $saveDir, $filename = '')
{
    $pathInfo = pathinfo($url);
    $filename = $filename ?: $pathInfo['basename'];

    if (!file_exists($saveDir)) {
        mkdir($saveDir, 0755, true);
        @chmod($saveDir, 0755, true);
    }
    $localPath = $saveDir . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($saveDir . DIRECTORY_SEPARATOR . $filename)) {
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

/**
 * 分割sql文件中的sql语句
 * @param string $content
 * @param false $string
 * @param array $replace
 * @return array|false|string|string[]
 */
function sql_parse($content = '', $string = false, $replace = [])
{
    // 被替换的前缀
    $from = '';
    // 要替换的前缀
    $to = '';

    // 替换表前缀
    if (!empty($replace)) {
        $to = current($replace);
        $from = current(array_flip($replace));
    }

    if ($content != '') {
        // 纯sql内容
        $pure_sql = [];

        // 多行注释标记
        $comment = false;

        // 按行分割，兼容多个平台
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = explode("\n", trim($content));

        // 循环处理每一行
        foreach ($content as $key => $line) {
            // 跳过空行
            if ($line == '') {
                continue;
            }

            // 跳过以#或者--开头的单行注释
            if (preg_match("/^(#|--)/", $line)) {
                continue;
            }

            // 跳过以/**/包裹起来的单行注释
            if (preg_match("/^\/\*(.*?)\*\//", $line)) {
                continue;
            }

            // 多行注释开始
            if (substr($line, 0, 2) == '/*') {
                $comment = true;
                continue;
            }

            // 多行注释结束
            if (substr($line, -2) == '*/') {
                $comment = false;
                continue;
            }

            // 多行注释没有结束，继续跳过
            if ($comment) {
                continue;
            }

            // 替换表前缀
            if ($from != '') {
                $line = str_replace('`' . $from, '`' . $to, $line);
            }

            // sql语句
            array_push($pure_sql, $line);
        }

        // 只返回一条语句
        if ($string) {
            return implode($pure_sql, "");
        }
        // 以数组形式返回sql语句
        $pure_sql = implode("\n", $pure_sql);
        $pure_sql = explode(";\n", $pure_sql);
        return $pure_sql;
    } else {
        return $string == true ? '' : [];
    }
}

/**
 * http/https请求
 * @param url $url 请求的链接，若为get则将参数拼接而成
 * @param data $data 请求的参数
 * @return data
 */
function appa_http_request($url,$data=null)
{
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_URL,$url);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
    if(!empty($data)){
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
    }
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

/**
 * 借用微信公众号授权
 */
function borrow_wechat_auth()
{
    $code = input('code');
    $state = input('state');
    // 拿到state，取信息，有值，重定向，重定向的时候将code值与state值拼接后再跳转
    if(!$code || !$state) return;

    $sessionState = cache($state);
    \think\facade\Cache::delete($state);
    if($sessionState){
        $arr = parse_url($sessionState);
        if(isset($arr['query']) && $arr['query']){
            $query = '?' . $arr['query'] . '&code='.$code.'&state='.$state;
        }else{
            $query = '?code='.$code.'&state='.$state;
        }
        $fragment = $arr['fragment'] ?? '';
        $url = $arr['scheme'] . '://' . $arr['host'] . $arr['path'] . $query;
        if($fragment){
            $url .= '#' . $fragment;
        }

        redirect($url)->send();
    }
}

/**
 * 将字符串转数组
 * 只合法的json格式转数组，否则返回空数组
 * @param $json
 * @param array $default 不合法时默认返回
 */
function json_str_to_array($json, $default = [])
{
    if(!$json) return $default;
    return json_decode($json) ? json_decode($json, true) : $default;
}

if (!function_exists('vit_session')) {
    /**
     * vitphp框架中的session管理
     * 多应用下，相同的session名称会重复，导致权限的跨越
     * 为方便使用，框架自动在name中添加pid
     * @param string $name  session名称
     * @param mixed  $value session值
     * @return mixed
     */
    function vit_session($name = '', $value = '')
    {
        $pid = input('pid', '');
        if($name && $pid){
            $name = "vit_{$pid}_{$name}";
        }
        if (is_null($name)) {
            // 清除
            Session::clear();
        } elseif ('' === $name) {
            return Session::all();
        } elseif (is_null($value)) {
            // 删除
            Session::delete($name);
        } elseif ('' === $value) {
            // 判断或获取
            return 0 === strpos($name, '?') ? Session::has(substr($name, 1)) : Session::get($name);
        } else {
            // 设置
            Session::set($name, $value);
        }
    }
}

if (!function_exists('vit_cache')) {
    /**
     * 缓存管理
     * 多应用下，相同的名称会重复，导致意想不到的问题
     * 为方便使用，框架自动在name中添加pid
     * @param string $name    缓存名称
     * @param mixed  $value   缓存值
     * @param mixed  $options 缓存参数
     * @param string $tag     缓存标签
     * @return mixed
     */
    function vit_cache(string $name = null, $value = '', $options = null, $tag = null)
    {
        $pid = input('pid', '');
        if($name && $pid){
            $name = "vit_{$pid}_{$name}";
        }
        if (is_null($name)) {
            return app('cache');
        }

        if ('' === $value) {
            // 获取缓存
            return 0 === strpos($name, '?') ? Cache::has(substr($name, 1)) : Cache::get($name);
        } elseif (is_null($value)) {
            // 删除缓存
            return Cache::delete($name);
        }

        // 缓存数据
        if (is_array($options)) {
            $expire = $options['expire'] ?? null; //修复查询缓存无法设置过期时间
        } else {
            $expire = $options;
        }

        if (is_null($tag)) {
            return Cache::set($name, $value, $expire);
        } else {
            return Cache::tag($tag)->set($name, $value, $expire);
        }
    }
}

if (!function_exists('save_sys_log')) {
    /**
     * 写入系统日志
     * @param string $authText 操作行为
     * @param string $description 操作描述
     */
    function save_sys_log($authText, $description=null, $userName=null)
    {
        $request = \think\facade\Request::instance();
        $app = app('http')->getName();
        $controller = $request->controller();
        $action = $request->action();
        $userName = $userName ?? session('admin.username');
        Db::name('sys_log')->insert([
            'user_name' => $userName,
            'auth' => $app . '/' . $controller . '/' . $action,
            'auth_text' => $authText,
            'description' => $description,
            'ip' => getip(),
            'create_time' => time()
        ]);
    }
}

if (!function_exists('vit_platform')) {
    /**
     * 保存平台配置
     * @param array $params 参数
     * @param string $platform 平台类型
     */
    function vit_platform($params, $platform='wechat')
    {
        $pid = input('pid');
        $addons = app('http')->getName();
        $platformInfo = Db::name('platform')->where(['addons'=>$addons ,'pid'=>$pid])->find();
        $data = [];
        $data[$platform] = json_encode($params, 256);

        if($platformInfo){
            $data['id'] = $platformInfo['id'];
            Db::name('platform')->update($data);
        }else{
            $data['pid'] = $pid;
            $data['addons'] = $addons;
            Db::name('platform')->insert($data);
        }
    }
}

/**
 * 获取站点code
 * @param false $refresh
 * @return mixed|string
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 */
function getSiteCode($refresh = false )
{
    if($refresh){
        return getRefreshSiteCode();
    }

    $code = Cache::get('vit_site_code');
    if(!$code){
        return getRefreshSiteCode();
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
function getRefreshSiteCode()
{
    $domain = \think\facade\Request::host();
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