<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace vitphp\admin\storage;

use think\App;
use think\Container;

/**
 * 文件存储管理
 * Class Storage
 * @package vitphp\admin
 * @method array info($name) static 获取文件的信息
 * @method array upload($uploadPath, $file) 上传文件
 * @method string url($name) static 获取文件完整链接
 * @method string path($name) static 获取文件存储路径
 * @method boolean del($name) static 删除存储文件
 * @method boolean has($name) static 检查是否存在
 * @method string uploadUrl() static 获取上传地址
 */
abstract class Storage
{
    protected $app;
    public $attaType;

    /**
     * 定义单例模式的变量
     * @var null
     */
    protected static $_instance = null;

    /**
     * Storage constructor.
     * @param App $app
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct(App $app, $config = [], $attaType = null)
    {
        $this->app = $app;
        $this->attaType = $attaType;
        $this->init($config);
    }

    /**
     * 各驱动配置自定义配置信息
     * @return mixed
     */
    abstract protected function init($config = []);

    public static function __callStatic(string $method, array $arguments)
    {
        if (method_exists($class = static::instance(), $method)) {
            return call_user_func_array([$class, $method], $arguments);
        } else {
            throw new \Exception("method not exists: " . get_class($class) . "->{$method}()");
        }
    }

    /**
     * 单例模式
     * @return mixed|Storage
     * @throws \Exception
     */
    public static function instance(?int $attaType = null, $config = [])
    {
        $configAttaType = $config['type'] ?? 0;
        $attaType = $configAttaType ?: $attaType;

        $attaType = $attaType ?: getSetting('atta_type', 'setup');
        if (empty(self::$_instance)) {
            switch ($attaType) {
                case 1:
                    $class = 'Local';
                    self::$_instance = new LocalStorage(app(), $config, $attaType);
                    break;
                case 2:
                    $class = 'Qiniu';
                    self::$_instance = new QiniuStorage(app(), $config, $attaType);
                    break;
                case 3:
                    $class = 'Tencentcos';
                    self::$_instance = new TencentcosStorage(app(), $config, $attaType);
                    break;
                case 4:
                    $class = 'Alioss';
                    self::$_instance = new AliossStorage(app(), $config, $attaType);
                    break;
                case 5:
                    $class = 'Ftp';
                    self::$_instance = new FtpStorage(app(), $config, $attaType);
                    break;
                default:
                    throw new \Exception('未知的存储位置');
                    break;
            }
        }
        return self::$_instance;

        $configAttaType = $config['type'] ?? 0;
        $attaType = $configAttaType ?: $attaType;
        if ($attaType) {
            $attaTypeRange = ['', 'Local', 'Qiniu', 'Tencentcos', 'Alioss', 'Ftp'];
            $class = $attaTypeRange[$attaType] ?? '';
        } else {
            $attaType = getSetting('atta_type', 'setup');
            $class = '';
            switch ($attaType) {
                case 1:
                    $class = 'Local';
                    break;
                case 2:
                    $class = 'Qiniu';
                    break;
                case 3:
                    $class = 'Tencentcos';
                    break;
                case 4:
                    $class = 'Alioss';
                    break;
                case 5:
                    $class = 'Ftp';
                    break;
                default:
                    throw new \Exception('未知的存储位置');
                    break;
            }
        }

        if (class_exists($object = "vitphp\\admin\\storage\\{$class}Storage")) {
            return Container::getInstance()->make($object, $config);
        } else {
            throw new \Exception("[{$class}] 驱动文件不存在");
        }
    }

    /**
     * 生成文件新名称
     * @param string $path
     * @param string $extension
     * @param string $fun
     * @return string
     */
    public static function name(string $path, string $extension = '', string $fun = 'md5'): string
    {
        return $fun($path . $extension . uniqid() . redom(24));
    }

    /**
     * 获取文件的基础名称
     * 去掉后面的参数
     * @param string $name
     * @return string
     */
    protected function getBaseName(string $name): string
    {
        if (strpos($name, '?') !== false) {
            return strstr($name, '?', true);
        }
        return $name;
    }

    /**
     * 生成新的文件名，不包含后缀
     * @return string
     */
    protected function getNewKey(): string
    {
       return uniqidDate(18);
    }

    /**
     * 如果文件名中有域名，则将协议与域名全都去掉
     * @param $name
     */
    protected function getNamePath($name)
    {
        $preg = "/http[s]?:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is";
        if (preg_match($preg, $name)) {
            $nameParseUrl = parse_url($name);
            return $nameParseUrl['path'];
        }else{
            return $name;
        }
    }

    /**
     * 获取文件信息
     * @param $file
     * @return array
     */
    public function getFileInfo($file)
    {
        return [
            'orgin' => $file->getOriginalExtension(),
            'original_name' => $file->getOriginalName(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * 读取文件内容
     * @param string $filePath
     * @return string
     * @throws \Exception
     */
    public function getFileBody(string $filePath)
    {
        $file = fopen($filePath, 'rb');

        if ($file === false) {
            throw new \Exception("file can not open", 1);
        }
        $stat = fstat($file);
        $size = $stat['size'];
        $fileBody = fread($file, $size);
        fclose($file);
        if ($fileBody === false) {
            throw new \Exception("file can not read", 1);
        }

        return $fileBody;
    }

    /**
     * 使用formData提交文件
     * @param $url
     * @param $fields
     * @param $name
     * @param $fileName
     * @param $fileBody
     * @param null $mimeType
     * @param array $headers
     * @return array
     */
    protected function multipartPost(
        $url,
        $fields,
        $name,
        $fileName,
        $fileBody,
        $mimeType = null,
        array $headers = array()
    )
    {
        $data = array();
        $mimeBoundary = md5(microtime());

        foreach ($fields as $key => $val) {
            array_push($data, '--' . $mimeBoundary);
            array_push($data, "Content-Disposition: form-data; name=\"$key\"");
            array_push($data, '');
            array_push($data, $val);
        }

        array_push($data, '--' . $mimeBoundary);
        $mimeType = empty($mimeType) ? 'application/octet-stream' : $mimeType;
        $fileName = self::escapeQuotes($fileName);
        array_push($data, "Content-Disposition: form-data; name=\"$name\"; filename=\"$fileName\"");
        array_push($data, "Content-Type: $mimeType");
        array_push($data, '');
        array_push($data, $fileBody);

        array_push($data, '--' . $mimeBoundary . '--');
        array_push($data, '');
        $body = implode("\r\n", $data);
        $contentType = 'multipart/form-data; boundary=' . $mimeBoundary;
        $headers['Content-Type'] = $contentType;

        return $this->sendRequest('POST', $url, $headers, $body);
    }

    protected function userAgent()
    {
        $sdkInfo = "QiniuPHP/";

        $systemInfo = php_uname("s");
        $machineInfo = php_uname("m");

        $envInfo = "($systemInfo/$machineInfo)";

        $phpVer = phpversion();

        $ua = "$sdkInfo $envInfo PHP/$phpVer";
        return $ua;
    }

    /**
     * 发送htt请求
     * @param $method
     * @param $url
     * @param array $headersArr
     * @param null $body
     * @param null $request
     * @return array
     */
    protected function sendRequest($method, $url, $headersArr = [], $body = null, $request = null)
    {
        $t1 = microtime(true);
        $ch = curl_init();
        $options = array(
            CURLOPT_USERAGENT => $this->userAgent(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => $url
        );

        // Handle open_basedir & safe mode
        if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
            $options[CURLOPT_FOLLOWLOCATION] = true;
        }

        if (!empty($headersArr)) {
            $headers = array();
            foreach ($headersArr as $key => $val) {
                array_push($headers, "$key: $val");
            }
            $options[CURLOPT_HTTPHEADER] = $headers;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));

        if (!empty($body)) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $t2 = microtime(true);
        $duration = round($t2 - $t1, 3);
        $ret = curl_errno($ch);
        if ($ret !== 0) {
            $r = [-1, $duration, array(), null, curl_error($ch)];
            curl_close($ch);
            return $r;
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = $this->parseHeaders(substr($result, 0, $header_size));
        $body = substr($result, $header_size);
        curl_close($ch);
        return [$code, $duration, $headers, $body];
    }

    /**
     * 获取响应头信息
     * @param $raw
     * @return array
     */
    protected function parseHeaders($raw)
    {
        $headers = array();
        $headerLines = explode("\r\n", $raw);
        foreach ($headerLines as $line) {
            $headerLine = trim($line);
            $kv = explode(':', $headerLine);
            if (count($kv) > 1) {
                $headers[$kv[0]] = trim($kv[1]);
            }
        }
        return $headers;
    }

    protected function escapeQuotes($str)
    {
        $find = array("\\", "\"");
        $replace = array("\\\\", "\\\"");
        return str_replace($find, $replace, $str);
    }
}