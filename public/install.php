<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2022 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------
if (is_file('./install.lock')) {
    header("location:/index.php");
    exit;
}
define('DS', DIRECTORY_SEPARATOR);

/**
 * 密码加密
 * @param $pass
 * @return false|string|null
 */
function pass_en($pass)
{
    $options = [
        "cost" => 11
    ];

    return password_hash($pass, PASSWORD_DEFAULT, $options);
}

class SQL
{
    /**
     * 从sql文件获取纯sql语句
     * @param string $sql_file sql文件路径
     * @param bool $string 如果为真，则只返回一条sql语句，默认以数组形式返回
     * @param array $replace 替换前缀，如：['my_' => 'me_']，表示将表前缀"my_"替换成"me_"
     *         这种前缀替换方法不一定准确，比如正常内容内有跟前缀相同的字符，也会被替换
     *         todo 表前缀替换功能/请谨慎使用 (!!!最好不要使用)
     * @return mixed
     */
    public static function getSqlFromFile($sql_file, $string = false, $replace = [])
    {
        if (!file_exists($sql_file)) {
            return false;
        }
        // 读取sql文件内容
        $handle = file_get_contents($sql_file);

        // 分割语句
        $handle = self::sqlParse($handle, $string, $replace);

        return $handle;
    }

    /**
     * 分割sql语句
     * @param string $content sql内容
     * @param bool $string 如果为真，则只返回一条sql语句，默认以数组形式返回
     * @param array $replace 替换前缀，如：['my_' => 'me_']，表示将表前缀my_替换成me_
     * @return array|string 除去注释之后的sql语句数组或一条语句
     */
    public static function sqlParse($content = '', $string = false, $replace = [])
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
     * 获取数据库配置模板
     * @return string
     */
    public static function get_database_config_tpl()
    {
        $cfg = <<<EOF
<?php
return [
    // 默认使用的数据库连接配置
    'default'         => 'mysql',
    // 自定义时间查询规则
    'time_query_rule' => [],
    // 自动写入时间戳字段
    'auto_timestamp'  => true,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 数据库连接配置信息
    'connections'     => [
        'mysql' => [
            // 数据库类型
            'type'            => '{#type}',
            // 服务器地址
            'hostname'        => '{#hostname}',
            // 数据库名
            'database'        => '{#database}',
            // 用户名
            'username'        => '{#username}',
            // 密码
            'password'        => '{#password}',
            // 端口
            'hostport'        => '{#hostport}',
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用 utf8
            'charset'         => 'utf8mb4',
            // 数据库表前缀
            'prefix'          => '{#prefix}',
            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 是否需要断线重连
            'break_reconnect' => false,
            // 监听SQL执行日志
            'trigger_sql'     => true,
            // 开启字段类型缓存
            'fields_cache'    => !app()->isDebug(),
        ],
    ], 
];

EOF;
        return trim($cfg);
    }
}

$m = isset($_GET['m']) ? $_GET['m'] : '';
switch ($m) {
    # 校验数据库配置
    case 'check_config':
        $d = $_POST;
        try {
            $msg = '';
            $mysqli = new mysqli($d['hostname'], $d['username'], $d['password'], $d['database'], $d['hostport']);
            if (!$mysqli) {
                $msg = "数据库配置错误";
            }
            $check = $mysqli->query("show tables");
            $t = 0;
            foreach ($check as $i=>$v){
                $t++;
            }
            if ($t !== 0) {
                $msg =  '数据库已存在';
            }
            $mysqli->close();
            if($msg){
                echo $msg;
                exit;
            }
        } catch (Throwable $e) {
            echo $e->getMessage();
            exit;
        }
        // 连接成功
        echo '1';
        exit;
    # 保存数据
    case 'save_data':
        $d = $_POST;
        if(strlen($d['user_username']) < 5){
            echo "管理员用户名最少5位";exit;
        }
        if(strlen($d['user_password']) < 6){
            echo "管理员密码最少6位";exit;
        }
        $d['type'] = 'mysql';
        $s = SQL::get_database_config_tpl(); //file_get_contents(__DIR__ . '/database.php');
        foreach ([
                     '{#type}' => $d['type'],
                     '{#hostname}' => $d['hostname'],
                     '{#database}' => $d['database'],
                     '{#username}' => $d['username'],
                     '{#password}' => $d['password'],
                     '{#hostport}' => $d['hostport'],
                     '{#prefix}' => $d['prefix'],
                 ] as $f => $v
        ) {
            $s = str_replace($f, $v, $s);
        }
        # 替换config下的database文件
        file_put_contents(__DIR__ . '../../config/database.php', $s);
        # 开始解析sql语句
        $data = SQL::getSqlFromFile(__DIR__ . '/database.sql', false, ['vit_' => $d['prefix']]);
        $mysqli = new mysqli($d['hostname'], $d['username'], $d['password'], $d['database'], $d['hostport']);
        
        try {
            if (!$mysqli) {
                $mysqli->close();
                echo "数据库配置错误";exit;
            }
            $mysqli->autocommit(FALSE);
            foreach ($data as $sql) {
                $mysqli->query($sql);
            }
            $d['user_password'] = pass_en($d['user_password']);
            $d['create_time'] = time();
            # 创建用户
            $mysqli->query("INSERT INTO {$d['prefix']}users (id,username,password,create_time) VALUES ('1','{$d['user_username']}','{$d['user_password']}','{$d['create_time']}');");
            #创建站点信息
//            $mysqli->query("INSERT INTO {$d['prefix']}system_site ('uid','domain','name','title') VALUES ('1', '{$_SERVER['HTTP_HOST']}','VitPhp','VitPhp');");
            $mysqli->commit();
            $mysqli->autocommit(TRUE);
            $mysqli->close();
        } catch (Throwable $e) {
            $mysqli->rollback();
            $mysqli->close();
            echo $e->getMessage();
            exit;
        }
        echo "1";
        # 添加lock
        file_put_contents('./install.lock', "1");
        exit;
    default:
        function isReadWrite($file)
        {
            if (DIRECTORY_SEPARATOR == '\\') {
                return true;
            }
            if (DIRECTORY_SEPARATOR == '/' && @ini_get("safe_mode") === false) {
                return is_writable($file);
            }
            if (!is_file($file) || ($fp = @fopen($file, "r+")) === false) {
                return false;
            }
            fclose($fp);
            return true;
        }

        function checkPhpVersion($version)
        {
            $php_version = explode('-', phpversion());
            $check = strnatcasecmp($php_version[0], $version) >= 0 ? true : false;
            return $check;
        }

        $os = php_uname('s') . ' ' . php_uname('n') . ' ' . php_uname('r');
        $server = $_SERVER["SERVER_SOFTWARE"];
        $php = PHP_VERSION;
        $errorInfo = null;
        $pathAuth = [true, true, true, true];
        $ok = true;
        $php_bg = "#39b54a";
        if (is_file('./public/install.lock')) {
            $ok = false;
            $errorInfo = '已安装系统，如需重新安装请删除文件：/public/install.lock';
        }
        if (!isReadWrite(__DIR__ . DS .'..'. DS . 'config' . DS)) {
            $pathAuth[0] = false;
            $ok = false;
            $errorInfo = __DIR__ . 'config' . DS . '：读写权限不足';
        }
        if (!isReadWrite(__DIR__ . DS .'..'. DS  . 'runtime' . DS)) {
            $pathAuth[1] = false;
            $ok = false;
            $errorInfo = __DIR__ . 'runtime' . DS . '：读写权限不足';
        }
        if (!isReadWrite(__DIR__ . DS .'..'. DS  . 'public' . DS)) {
            $pathAuth[2] = false;
            $ok = false;
            $errorInfo = __DIR__ . 'public' . DS . '：读写权限不足';
        }
        if (!isReadWrite(__DIR__ . DS .'..'. DS  . 'app' . DS)) {
            $pathAuth[3] = false;
            $ok = false;
            $errorInfo = __DIR__ . 'app' . DS . '：读写权限不足';
        }
        if (!checkPhpVersion('7.1.0')) {
            $ok = false;
            $php_bg = "#ff4d4d";
            $errorInfo = 'PHP版本不能小于7.2.0';
        }
        if (!extension_loaded("PDO")) {
            $ok = false;
            $pdo_bg = "#ff4d4d";
            $errorInfo = '当前未开启PDO，无法进行安装';
        }

        if ($pathAuth[0] && $pathAuth[1] && $pathAuth[2] && $pathAuth[3]) {
            $auth_bg = "#39b54a";
            $auth_msg = '正常';
        } else {
            $ok = false;
            $auth_bg = "#ff4d4d";
            $auth_msg = "";
            if (!$pathAuth[0]) {
                $auth_msg .= "config无权限";
            }
            if (!$pathAuth[1]) {
                $auth_msg = $auth_msg ? $auth_msg . ", runtime无权限" : " runtime无权限";
            }
            if (!$pathAuth[2]) {
                $auth_msg = $auth_msg ? $auth_msg . ", public无权限" : " public无权限";
            }
            if (!$pathAuth[3]) {
                $auth_msg = $auth_msg ? $auth_msg . ", app无权限" : " app无权限";
            }
        }
        function get_http_type()
        {
            $http_type = ( ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) )
                        ? 'https://' : 'http://';
            return $http_type;
        }
        // 检测伪静态
        function geturl($url)
        {
            $headerArray = array("Content-type:application/json;", "Accept:application/json");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
            $output = curl_exec($ch);
            curl_close($ch);
            return $output;
        }

        $s_status = geturl( get_http_type() . $_SERVER['HTTP_HOST'] . '/_s_s?name=伪静态测试') === "1";
        $s_status_bg = $s_status ? "#39b54a" : "#ff4d4d";
        $s_status_msg = $s_status ? '正常' : '未开启 &nbsp;<a href="https://www.vitphp.cn/doc/11/44.html" target="_blank" >查看详情</a>';
        if(!$s_status) $ok = false;
        $ok_status =  $ok ? 'true' : 'false';
        break;
}

echo <<<EOF
        <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VitPhp安装程序</title>
    <link rel="stylesheet" href="/static/plugs/layui/css/layui.css" media="all"> 
    <link rel="stylesheet" href="https://at.alicdn.com/t/font_2526691_rldun5e8bat.css">
    <link rel="stylesheet" href="/static/theme/css/common.css?v={:date('mdhms')}" media="all">
    <link rel="stylesheet" href="/static/theme/css/vitstyle.css?v={:date('mdhms')}" media="all">
</head>
<body>
<style>
    .layui-form-pane .layui-input-block {
        margin-left: 120px;
    }
    .layui-form-pane .layui-form-label {
        width: 120px;
    }
</style>
<div style="background-color: #fff">
  <div class="layui-layout layui-layout-admin layui-bg-black" style="overflow: hidden;">
      <div class="layui-header layui-bg-black" style="width: 960px;margin: 0 auto;position: relative;">
          <div class="layui-logo layui-hide-xs layui-bg-black">VitPhp</div>
          <ul class="layui-nav layui-layout-right">
              <li class="layui-nav-item layui-hide-xs"><a href="http://www.vitphp.cn" target="_blank">官网</a></li>
              <li class="layui-nav-item layui-hide-xs"><a href="http://www.vitphp.cn//ke/pc.html?id=1" target="_blank">安装教程</a></li>
              <li class="layui-nav-item layui-hide-xs"><a href="https://www.vitphp.cn/doc/19/75.html" target="_blank">开发文档</a></li>
			  <li class="layui-nav-item layui-hide-xs"><a href="https://qm.qq.com/cgi-bin/qm/qr?k=U4M1Gcv5Cmxv39KDPAUtpR_dmGDrHWNx&jump_from=webapi" target="_blank">QQ群</a></li>
          </ul>
      </div>
  </div>
    <div style="background-color: #f5f7f9!important;">
        <div style="padding: 30px;width: 960px;margin: 0 auto;">
            <div class="layui-tab layui-tab-brief" readonly   lay-filter="docDemoTabBrief">
                <ul class="layui-tab-title" style="text-align: center;pointer-events: none;" id="layui-tab-title">
                    <li class="layui-this">许可协议</li>
                    <li>检查环境</li>
                    <li>参数配置</li>
                    <li>安装成功</li>
                </ul>
                <div class="layui-tab-content" style="height: 600px;" id="layui-tab-content">
                <div class="layui-tab-item layui-show">
                        <div class="layui-col-md12">
                            <div class="layui-card">
                                <div class="layui-card-header">Vitphp用户授权许可协议</div>
                                <div class="layui-card-body" style="overflow-y:scroll;max-height:460px;line-height:20px;">
                                    <p style="text-align:center"><span style="font-size:20px"><strong>Vitphp用户授权许可协议</strong></span></p>
								  <p><span style="font-size:16px">版权所有 (c)2022，石家庄萌折科技有限公司保留所有权利。</span></p>
								  <p><span style="font-size:16px">感谢您选择VITPHP多应用管理系统（以下简称Vitphp），Vitphp基于thinkphp6+layui开发，系统免费开源。<br />
								本《Vitphp用户授权许可协议》（以下简称&ldquo;协议&rdquo;）是你（自然人、法人或其他组织）与石家庄萌折科技有限公司（以下简称&ldquo;萌折&rdquo;）之间有关复制、下载、安装、使用Vitphp系统的协议，同时本协议适用于任何有关Vitphp的后期更新和升级。一旦复制、下载、安装或以其他方式使用Vitphp，即表明你同意接受本协议各项条款的约束。</span></p>
								  <p><span style="font-size:16px"><span style="color:#c0392b">如果你不同意本协议中的条款，请勿复制、下载、安装或以其他方式使用Vitphp。</span></span></p>
								  <p><span style="font-size:16px">一、许可您的权利</span></p>
								  <p><span style="font-size:16px"><span style="color:#c0392b">1.你可以在完全遵守本协议的基础上，将Vitphp应用于各类网站，而不必支付软件版权授权费用。</span><br />
								2.你可以在协议规定的约束和限制范围内根据需要对Vitphp进行必要的修改和美化，以适应你的网站要求。<br />
								3.您拥有使用Vitphp构建的网站中的全部内容的所有权，并独立承担与内容相关的法律义务。</span></p>
								  <p><span style="font-size:16px">二、约束和限制</span></p>
								  <p><span style="font-size:16px">未经萌折官方许可，不得对Vitphp或与之关联的商业授权进行出租、出售、抵押或发放子许可证。</span></p>
								  <p><span style="font-size:16px">1.在使用Vitphp系统时必须保留所有版权信息。<br />
								2.禁止在vitphp的整体或任何部分基础上以发展任何派生版本、修改版本或第三方版本用于重新分发。<br />
								3.如果你未能遵守本协议的条款，你的授权将被终止，所被许可的权利将被收回，并承担相应法律责任。本协议适用中华人民共和国法律。如你与Vitphp就本协议的相关问题发生争议，双方均有权向石家庄萌折科技有限公司所在地管辖法院提起诉讼。</span></p>
								  <p><span style="font-size:16px">三、有限担保和免责声明</span></p>
								  <p><span style="font-size:16px">1.Vitphp及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的。<br />
								2.用户出于自愿而使用Vitphp，你必须了解使用Vitphp的风险，在尚未购买产品技术服务之前，我们不承诺提供任何形式的技术支持、使用担保，也不承担任何因使用Vitphp而产生问题的相关责任。<br />
								3.Vitphp不对使用Vitphp构建的网站的任何信息内容以及导致的任何版权纠纷和法律争议及后果承担责任。<br />
								4.为了优化Vitphp运行所需要的服务器环境，萌折会在用户安装Vitphp的过程中获取网站域名、PHP版本、数据库版本等信息。<br />
								5.电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和等同的法律效力。您一旦开始确认本协议并安装vitphp，即被视为完全理解并接受本协议的各项条款，在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。</span></p>
								  <p><span style="font-size:16px">四、协议修订</span></p>
								  <p><span style="font-size:16px">&nbsp; &nbsp;萌折有权随时对本合约的条款进行修订，并在修订生效日前一个工作日更新在vitphp官网。<br />
								&nbsp; &nbsp;修订的条款始终公开在「https://www.vitphp.cn/license.html」。</span></p>
								  <p style="text-align:right"><span style="font-size:16px">石家庄萌折科技有限公司</span></p>
								  <p style="text-align:right"><span style="font-size:16px">修订于2021年10月20日</span></p>
                                </div>
                            </div>
                            <form class="layui-form" action="">
                                <div class="layui-form-item" pane="">
                                    <div style="text-align: center;">
                                        <input type="checkbox" id="xieyiId" name="xieyi[read]" lay-skin="primary" title=" 我已经阅读并同意此协议">
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" onclick="jixu('1')">继续安装</button>
                                    </div>
                                </div> 
                            </form>
                        </div>
                    </div>
                    <div class="layui-tab-item">
                        <div class="layui-row layui-col-space15">
                            <div class="layui-col-md12">
                                <div class="layui-card">
                                    <div class="layui-card-header">系统环境</div>
                                    <div class="">
                                        <table class="layui-table" >
                                            <thead>
                                            <tr style="background-color: #39b54a;color: #ffffff;">
                                                <td>服务器系统</td>
                                                <td>{$os}</td>
                                            </tr>
                                            <tr style="background-color: #39b54a;color: #ffffff;">
                                                <td>服务器环境</td>
                                                <td>{$server}</td>
                                            </tr>
                                            <tr style="background-color: {$auth_bg};color: #ffffff;">
                                                <td>读写权限</td>
                                                <td>{$auth_msg}</td>
                                            </tr>
                                            <tr style="background-color: {$s_status_bg};color: #ffffff;">
                                                <td>伪静态配置</td>
                                                <td>{$s_status_msg}</td>
                                            </tr>
                                            <tr style="background-color: {$php_bg};color: #ffffff;">
                                                <td>php版本</td>
                                                <td>{$php}, 运行环境要求PHP7.1+</td>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                            </div>
                            <div style="text-align: center;">
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" onclick="fanhui('0')">返回</button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" onclick="jixu('2',{$ok_status})">继续</button>
                            </div>
                        </div>
                    </div>
                    <div class="layui-tab-item">
                        <div class="layui-row layui-col-space15">
                            <div class="layui-col-md12">
                                <div class="layui-card">
                                    <div class="layui-card-header">填写数据库</div>
                                    <div class="layui-card-body">
                                        <form class="layui-form layui-form-pane">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">数据库主机</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="hostname" value="127.0.0.1" class="layui-input">
                                                </div>
                                            </div>
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">数据库端口</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="hostport" value="3306" class="layui-input">
                                                </div>
                                            </div>
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">数据库名称</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="database" value="" class="layui-input">
                                                </div>
                                            </div>
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">数据库用户名</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="username" value="" class="layui-input">
                                                </div>
                                            </div>
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">数据库密码</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="password" value="" class="layui-input">
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="layui-row layui-col-space15">
                            <div class="layui-col-md12">
                                <div class="layui-card">
                                    <div class="layui-card-header">配置管理用户</div>
                                    <div class="layui-card-body">
                                        <form class="layui-form layui-form-pane">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">管理员账号</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="user_username" value="" class="layui-input">
                                                </div>
                                            </div>
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">管理员密码</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="user_password" class="layui-input">
                                                </div>
                                            </div>
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">重复密码</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="user_password2" class="layui-input">
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" onclick="fanhui('1')">返回</button>
                            <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" onclick="jixu('3')">安装</button>
                        </div>
                    </div>
                    <div class="layui-tab-item">
                        <div style="padding:20% 30%;text-align: center">
                            <div><i class="viticon vit-icon-dui text-green" style="font-size: 72px"></i></div>
                            <div style="font-size: 36px">安装成功</div>
                            <div class="padding-top">
                                <a href="/index.php" class="layui-btn layui-btn-primary layui-btn-lg">进入系统</a>
                                <a href="http://www.vitphp.cn/" target="_blank" class="layui-btn layui-btn-primary layui-btn-lg">访问官网</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="vit-layout-footer" style="position: fixed;">
            <div>
                <p> Copyright © 2022 石家庄萌折科技有限公司</p>
                <a style="padding-right: 10px;" href="http://www.vitphp.cn">www.vitphp.cn</a>All Rights Reserved
            </div>
        </div>
    </div>
</div>
<script src="/static/plugs/jquery/jquery.min.js"></script>
<script src="/static/plugs/layui/layui.js"></script>
</body>
<script>
function  jixu(e,ok = true) {
        if(e == '2' && ok === false){
            layer.msg("请先解决环境问题");
            return false;
        }
        console.log(e);
        if(e == '1'){
            var check = $("#xieyiId").is(":checked");
            if(!check){
                layer.msg("请勾选同意协议");
                return false;
            }
        }
        if(e == '3'){
            let user_username = $("input[name=user_username]").val();
            let user_password = $("input[name=user_password]").val();
            let user_password2 = $("input[name=user_password2]").val();
            if(!user_username){
                layer.msg("请输入管理员账号");
                return false;
            }
            if(user_username.length < 5){
                layer.msg("管理员账号最少为5位");
                return false;
            }
            
             if(!user_password){
                layer.msg("请输入密码");
                return false;
            }
             if(user_password.length < 6){
                layer.msg("密码最少为6位");
                return false;
            }
             if(!user_password2){
                layer.msg("请重复密码");
                return false;
             }
             if(user_password !== user_password2){
                layer.msg("两次密码不一致");
                return false;
             }
            
            jixu3(e);
        }else{
            $("#layui-tab-title li:eq("+e+")").addClass("layui-this").siblings().removeClass("layui-this");
            $("#layui-tab-content .layui-tab-item:eq("+e+")").addClass("layui-show").siblings().removeClass("layui-show");
        }
    }
     function jixu3(e){
         let prefix = "vit_";//设置表前缀
         let loadIndex = layer.load(1, {shade: [0.1, '#fff']});
         $.post("?m=check_config", {
             hostname: $("#layui-tab-content input[name=hostname]").val(),
             username: $("#layui-tab-content input[name=username]").val(),
             password: $("#layui-tab-content input[name=password]").val(), 
             database: $("#layui-tab-content input[name=database]").val(),
             hostport: $("#layui-tab-content input[name=hostport]").val(),
             prefix: prefix
         },function (res){
             if(res === '1'){
                 $.post("?m=save_data", {
                     hostname: $("#layui-tab-content input[name=hostname]").val(),
                     username: $("#layui-tab-content input[name=username]").val(), 
                     password: $("#layui-tab-content input[name=password]").val(), 
                     database: $("#layui-tab-content input[name=database]").val(),
                     hostport: $("#layui-tab-content input[name=hostport]").val(),
                     prefix: prefix,
                     user_username:  $("input[name=user_username]").val(),
                     user_password:  $("input[name=user_password]").val(),
                 },function (res){  
                       layer.close(loadIndex);
                       if(res == '1'){
                            $("#layui-tab-title li:eq("+e+")").addClass("layui-this").siblings().removeClass("layui-this");
                            $("#layui-tab-content .layui-tab-item:eq("+e+")").addClass("layui-show").siblings().removeClass("layui-show");
                       }else{
                            layer.msg(res);
                       }
                 },'html')
             }else{
                 layer.close(loadIndex);
                 layer.msg(res);
             } 
         },'html');
         
     }
    function fanhui(e) {
        $("#layui-tab-title li:eq("+e+")").addClass("layui-this").siblings().removeClass("layui-this");
        $("#layui-tab-content .layui-tab-item:eq("+e+")").addClass("layui-show").siblings().removeClass("layui-show");
    }
    layui.use('element', function(){
        var element = layui.element;
        element.on('tab(docDemoTabBrief)', function(data){
            console.log(data);
        });
    });
</script>
</html>
EOF;
// 结束需要独立一行且前后不能空格