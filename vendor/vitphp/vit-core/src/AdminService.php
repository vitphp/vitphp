<?php// +----------------------------------------------------------------------// | VitPHP// +----------------------------------------------------------------------// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]// +----------------------------------------------------------------------// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。// +----------------------------------------------------------------------namespace vitphp\admin;use think\App;use think\facade\Db;use think\facade\Request;use think\facade\Route;use think\Service;use vitphp\admin\variable\Variable;use vitphp\admin\command\NodeRefresh;use vitphp\admin\middleware\SeoFetch;class AdminService extends Service{    protected $middlewares = [        // 全局请求缓存        // \think\middleware\CheckRequestCache::class,        // 多语言加载        // \think\middleware\LoadLangPack::class,        // Session初始化        \think\middleware\SessionInit::class    ];    public function register()    {        $subDomain = request()->subDomain();        $routing = Db::table('vit_routing')->where('domain', $subDomain)->find();        if ($routing) {            $s = $_GET['s'] ?? '';            if (Db::table('vit_settings')->where([                'addons' => 'routing',                'name' => 'special',                'value' => $s            ])->find()) {            } else if (count(array_filter(explode('/', $s))) < 3) {                $s = parse_url($routing['url']);                $q = empty($s['query']) ? '' : $s['query'];                $p = empty($s['path']) ? '' : $s['path'];//                dump($p);                $p = $p . ($_GET['s'] ?? '');                if (substr($p, -5, 5) === '.html') {                    $p = substr($p, 0, -5);                }//                dump(strpos($p,'.'));                if (strpos($p, '.') === false) {                    $p = implode('/', array_slice(explode('/', $p), -3));                }//                dump($p);                $s = '';                if ($routing['directory']) {                    $s = '/' . $routing['directory'];                }                if ($p) {                    $s .= '/' . $p;                }                $_GET['s'] = $s . ($_GET['s'] ?? '');                parse_str($q, $query_arr);                $_GET = array_merge($query_arr, $_GET);//                dump($_GET['s']);            }        }        $this->changeTemplate();        Variable::init();        // 注册命令        $this->commands([            NodeRefresh::class        ]);        foreach ($this->middlewares as $middleware) {            $this->app->middleware->add($middleware);        }        // 注册容器        $this->app->bind(include __DIR__ . '/../extend/app/provider.php');        // 注册事件        $this->app->loadEvent(include __DIR__ . '/../extend/app/event.php');        // 注册服务        $services = include __DIR__ . '/../extend/app/service.php';        foreach ($services as $service) {            $this->app->register($service);        }    }    /**     * 处理默认模板     * @throws \ReflectionException     * @throws \think\db\exception\DataNotFoundException     * @throws \think\db\exception\DbException     * @throws \think\db\exception\ModelNotFoundException     */    private function changeTemplate()    {        $appName = '';        $appController = '';        $appActive = '';        $sPath = $_GET['s'] ?? '';        if (substr($sPath, -5, 5) === '.html') {            $sPath = substr($sPath, 0, -5);        }        $sPathArr = $sPath ? explode('/', $sPath) : [];        $appName = $sPathArr[1] ?? 'index';        $appController = $sPathArr[2] ?? 'index';        $appActive = $sPathArr[3] ?? 'index';        if($appName!='index') return '';        $appController = ucfirst($appController);        $templateAppName = '';        // 找到数组库中配置的默认模板对应的应用名称        $domain = Request::host();        $site = Db::name('system_site')->where('domain', $domain)->find();        if (!$site) {            $site = Db::name('system_site')->where('uid', '1')->find();        }        $templateId = $site['template'];        $appInfo = Db::name('app')->where('id', '=', $templateId)->find();        $templateAppName = $appInfo['addons'] ?? '';        if (!$templateAppName) return;        $classString = str_replace('/', '\\', 'app/' . $templateAppName . '/controller/' . DIRECTORY_SEPARATOR . $appController);        $classString = str_replace('\\\\', '\\', $classString);        if (!class_exists($classString)) return;        $reflection = new \ReflectionClass($classString);        if (!$reflection->hasMethod($appActive)) return;        if (!isset($_GET['s'])) {            $_GET['s'] = '/' . $templateAppName . '/' . $appController . '/' . $appActive;        } else {            $sPathArr = $sPath ? explode('/', $sPath) : [];            $sPathArr[1] = $templateAppName;            $sPathArr[3] = $appActive;            $_GET['s'] = implode('/', $sPathArr);        }    }    public function boot()    {        $this->app->middleware->add(SeoFetch::class);    }}