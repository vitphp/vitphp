<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace vitphp\admin\auth;

use \ReflectionClass;
use \ReflectionMethod;
use think\facade\Db;
use vitphp\admin\model\SystemMenu;

class AnnotationAuth
{
    /**
     * 获取注解中指定的键值
     * @param $flag
     * @param $comment
     * @return false|mixed
     */
    public static function getAnnotation($flag, $comment)
    {
        preg_match("/@{$flag}\s*([^\s]*)/i", $comment, $matches);
        return isset($matches[1]) ? $matches[1] : false;
    }

    #  注解权限类
    public static function scandir($dir, $depth = false)
    {
        $files = [];
        $dirs = [];
        $handle = opendir($dir);

        while (($file = readdir($handle)) !== false) {
            if (in_array($file, ['..', '.'])) continue;

            if (is_dir($dir . "/" . $file)) {
                $dirs[] = $file;
                if ($depth) {
                    // 递归想找
                    $files[$file] = self::scandir($dir . "/" . $file, $depth);
                }
            } else {
                $files[] = $file;
            }
        }
        closedir($handle);
        return ['dirs' => $dirs, 'files' => $files];
    }

    public static function getControllerAuth($class, $delTitle = false)
    {
        $reflection = new ReflectionClass($class);
        $controllerDoc = $reflection->getDocComment();
        $parase_result = [
            'title' => self::getAnnotation('title', $controllerDoc),
            'description' => self::getAnnotation('description', $controllerDoc) ?: '',
        ];

        //获取类中的方法，设置获取public,protected类型方法
        $methods = $reflection->getMethods();
        $data = [];
        $sysMethods = [
            'initialize', '__construct', 'validate', 'redirect', 'getResponseType', 'success', 'error', 'result',
            '_callback', '_list', '_form', '_del', '_change', '_form_before', '_index_list_before'
        ];
        foreach ($methods as $method) {
            if (substr($method->getName(), 0, 1) == '_') continue;
            if (in_array($method->getName(), $sysMethods)) continue;
            //获取方法的注释
            $doc = $method->getDocComment();
            //获取方法的类型
            $method_flag = $method->isProtected(); //还可能是public,protected类型的

            if ($doc === false) {
                if ($delTitle) {
                    continue;
                }
                $call = [
                    'class' => $class,
                    'name' => $method->getName(),
                    'meta' => [
                        'name' => '',
                        'auth' => 1,
                        'login' => 1
                    ],
                    'flag' => $method_flag
                ];
                $data[] = $call;
                continue;
            }
            //解析注释
            $metadata = [
                'title' => self::getAnnotation('title', $doc),
                'description' => self::getAnnotation('description', $doc) ?: '',
                'auth' => self::getAnnotation('auth', $doc),
                'login' => self::getAnnotation('login', $doc),
                'return' => self::getAnnotation('return', $doc),
                'throws' => self::getAnnotation('throws', $doc),
            ];
            foreach ($metadata as $mk => &$mv) {
                if ($mv === false) {
                    unset($metadata[$mk]);
                }
            }

            if ($delTitle && empty($metadata['title'])) continue;
            $call = [
                'class' => $class,
                'name' => $method->getName(),
                'meta' => $metadata,
                'flag' => $method_flag
            ];
            $data[] = $call;
        }
        foreach ($data as $i => $v) {
            $v['_id'] = "{$v['class']}@{$v['name']}";
            $v['_cs'] = array_merge(['title' => '', 'description' => ''], $parase_result, [
                'title' => empty($parase_result['title']) ? '' : $parase_result['title'],
            ]);
            $data[$i] = $v;
        }

        return $data;
    }

    public static function getAnnotationData($auth, $app)
    {
        $_path = "app\\$app\controller\\";
        $_dir = str_replace($_path, "", $auth['class']);

        return [
            '_app' => $app,
            '_id' => $auth['_id'],
            '_api' => [$auth['class'], $auth['name']],
            'title' => isset($auth['meta']['title']) ? $auth['meta']['title'] : '',
            'auth' => isset($auth['meta']['auth']) ? $auth['meta']['auth'] : '1',
            'login' => isset($auth['meta']['login']) ? $auth['meta']['login'] : '1',
            'node' => "$app/" . implode('.', explode('\\', $_dir)) . "/" . $auth['name'],
            // 方法@class注解
            '_cs' => isset($auth['_cs']) ? $auth['_cs'] : []
        ];
    }

    public static function getPathAuth($controllerPath, $delTitle = false)
    {
        $data = [];

        foreach (self::scandir(root_path() . $controllerPath, false)['files'] as $file) {
            $className = basename($file, '.php');

            $classString = str_replace('/', '\\', $controllerPath . DIRECTORY_SEPARATOR . $className);
            $classString = str_replace('\\\\', '\\', $classString);
            if (class_exists($classString)) {
                $data[] = self::getControllerAuth($classString, $delTitle);
            }
        }

        foreach (self::scandir(root_path() . $controllerPath, false)['dirs'] as $dir) {
            $mDir = self::getPathAuth($controllerPath . $dir, $delTitle);
            $data[] = array_merge(...$mDir);
        }
        return $data;
    }

    public static function getControllerDirAuth($controllerPath, $app, $delTitle)
    {
        $auths = self::getPathAuth($controllerPath, $delTitle);
        $auths = array_merge(...$auths);
        foreach ($auths as $i => $auth) {
            $auths[$i] = self::getAnnotationData($auth, $app);
        }
        return $auths;
    }

    /**
     * @param string $addons 默认*，获取所有应用下权限，单个应用则只填写应用名，多个应用用,号隔开
     * @param bool $group 默认true，会将权限进行分组显示
     * @param bool $delTitle 默认删除未配置title的方法
     * @return array
     */
    public static function getAddonsAuth($addons = "*", $group = true, $delTitle = true)
    {
        $dirs = self::scandir(root_path('app'), false)['dirs'];
        $ads = [];
        $list = explode(',', $addons);
        foreach ($dirs as $v) {
            if ($addons === "*" || in_array($v, $list)) {
                $ads[] = $v;
            }
        }
        $apps = [];
        foreach ($ads as $dir) {
            $data = self::getControllerDirAuth("app/{$dir}/controller/", $dir, $delTitle);

            foreach ($data as $i => $v) {
                $v['level'] = 3;

                if (empty($v['name'])) {
                    $v['name'] = $v['_api'][1];
                }
                $v['title'] = empty($v['title']) ? $v['name'] : $v['title'];
                $data[$i] = $v;
            }

            if ($group) {
                // 聚类分组
                $new_data = [];
                $dir_data = [];
                foreach ($data as $i => $v) {
                    $_app = $v['_app'];
                    $_path = "app\\$_app\controller\\";
                    $_dir = str_replace($_path, "", $v['_api'][0]);
                    if (!isset($dir_data[$_dir])) {
                        $dir_data[$_dir] = [
                            'path' => $_dir,
                            'list' => [],
                            'level' => 2,
                            'title' => $v['_cs']['title'] ?: $_dir
                        ];
                    }
                    $dir_data[$_dir]['list'][] = $v;
                }
                $new_data = array_values($dir_data);
                foreach ($new_data as $i => $v) {
                    $v['list'] = self::arraySort($v['list'], 'name');
                    $new_data[$i] = $v;
                }
                $data = $new_data;
            }
            $data = self::arraySort($data, 'path');
            $apps[] = [
                'path' => $dir,
                'level' => 1,
                'list' => $data
            ];
        }
        $apps = self::arraySort($apps, 'path');
        return $apps;
    }

    public static function arraySort($array, $keys, $sort = 'asc')
    {
        $newArr = $valArr = array();

        foreach ($array as $key => $value) {
            $valArr[$key] = isset($value[$keys]) ? $value[$keys] : '';
        }
        ($sort == 'asc') ? asort($valArr) : arsort($valArr);//先利用keys对数组排序，目的是把目标数组的key排好序
        reset($valArr); //指针指向数组第一个值
        foreach ($valArr as $key => $value) {
            $newArr[$key] = $array[$key];

        }
        return $newArr;
    }

    public static function checkAuth($class, $path)
    {
        $class = explode('\\', $class);
        foreach ($class as $i => $v) {
            $v = explode('.', $v);
            if (count($v) == 2) {
                $v[1] = substr($v[1], 0, 1) == '_' ? substr($v[1], 1) : $v[1];
            }
            $class[$i] = implode('\\', $v);
        }
        $class = implode("\\", $class);
        if (!class_exists($class)) {
            return false;
        }
        $auths = self::getControllerAuth($class);
        // 找到当前的方法
        $activeAuth = [];
        foreach ($auths as $item) {
            if ($item['name'] == $path[2]) {
                $app = $path[0];
                $item['node'] = "$app/" . implode('.', explode('\\', $path[1])) . "/" . $item['name'];
                $activeAuth = $item;
                break;
            }
        }
        return $activeAuth;
    }

    public static function getMenu($userId, $app)
    {
        $model = SystemMenu::order('sort', 'asc')
            ->where(['status' => '1', 'type' => $app])
            ->select();

        if ($userId != 1) {
            $role_nodes = Db::table('vit_auth_nodes')
                ->whereIn('rule_id', Db::table('vit_auth_map')
                    ->where('admin_id', $userId)
                    ->column('role_id'))
                ->group('node')
                ->select()->column('node');
            $user_nodes = Db::table('vit_auth_nodes')
                ->where('uid', $userId)
                ->select()->column('node');
            $nodes = array_map(function ($d) {
                return strtolower($d);
            }, array_merge($role_nodes, $user_nodes));

            $menus = $model->toArray();
            $data = [];
            $s = AnnotationAuth::getAddonsAuth($app, false)[0]['list'];
            $nodes_codes = [];
            $nodes_keys = [];
            foreach ($s as $v) {
                $node = strtolower($v['node']);
                $nodes_codes[] = $node;
                $nodes_keys[$node] = $v;
            }
            foreach ($menus as $v) {
                $v['url'] = $app . "/" . $v['url'];
                if (isset($nodes_keys[$v['url']])) {
                    $n = $nodes_keys[$v['url']];
                    if (!isset($n['auth'])) {
                        $n['auth'] = 0;
                    }
                    if ($n['auth'] == 1) {
                        if (in_array(strtolower($v['url']), $nodes)) {
                            $data[] = $v;
                            continue;
                        } else {
                            continue;
                        }
                    }
                }
                $data[] = $v;
            }
            $menus = $data;
        } else {
            $menus = $model->toArray();
        }
        foreach ($menus as $i => $v) {
            $v['url'] = url($app . '/' . strtolower($v['url']))->build();
            $menus[$i] = $v;
        }
        return $menus;
    }
}