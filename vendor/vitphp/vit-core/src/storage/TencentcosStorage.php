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

use think\facade\Filesystem;
use think\facade\Session;
use think\File;

class TencentcosStorage extends Storage
{
    private $bucket;
    private $accessKey;
    private $secretKey;
    private $region;
    private $domain;
    private $regionDomain = [
        'tj' => 'ap-beijing-1',
        'bj' => 'ap-beijing',
        'nj' => 'ap-nanjing',
        'sh' => 'ap-shanghai',
        'gz' => 'ap-guangzhou',
        'cd' => 'ap-chengdu',
        'cq' => 'ap-chongqing',
        'sz' => 'ap-shengzheng'
    ];

    /**
     * 初始化配置信息
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function init($config = [])
    {
        $this->region = $config['tx_region'] ?? (getSetting("tx_region") ?: 'bj');
        $this->accessKey = $config['tx_accesskey'] ?? getSetting("tx_accesskey");
        $this->secretKey = $config['tx_secretkey'] ?? getSetting("tx_secretkey");
        $this->bucket = $config['tx_space'] ?? getSetting("tx_space");
        $this->domain = $config['tx_domain'] ?? getSetting("tx_domain");

        if (!$this->bucket) throw new \Exception('未配置腾讯云空间名称');
        if (!$this->accessKey) throw new \Exception('未配置腾讯云AccessKey');
        if (!$this->secretKey) throw new \Exception('未配置腾讯云SecretKey');
        if (!$this->domain) throw new \Exception('未配置腾讯云访问域名');
    }

    public static function instance(?int $attaType = null, $config = [])
    {
        return parent::instance(3, $config);
    }

    /**
     * 上传文件
     */
    public function upload(string $uploadPath, File $file, $newFileName = ''): array
    {
        $fileInfo = $this->getFileInfo($file);
        $name = $newFileName ?: $uploadPath . "/" . $this->getNewKey() . "." . $fileInfo['orgin'];

        $params = $this->getUploadToken($name, 3600);
        $params['key'] = $name;
        $params['success_action_status'] = 200;

        $url = $this->uploadUrl();
        $fileBody = $this->getFileBody((string)$file);

        list($code, $duration, $headers, $body) = $this->multipartPost($url, $params, 'file', $name, $fileBody);
        if ($code != 200) throw new \Exception('上传失败');

        return [
            'storage' => 3,
            'filename' => $fileInfo['original_name'], // 这是原文件名
            'fileurl' => '/' . $name, // 这是除了域名外的，也就是key
            'size' => intval($fileInfo['size'] / 1024),
            'src' => $this->domain . '/' . $name, // 可访问的http地址
        ];
    }

    /**
     * 删除文件
     * @param string $name
     * @return bool|void
     * @throws \Exception
     */
    public function del(string $name)
    {
        $name = $this->getNamePath($name);
        $queryUrl = 'http://' . $this->bucket . '.cos.' . $this->regionDomain[$this->region] . '.myqcloud.com' . $name;
        $headers = $this->headerSign('DELETE', $name);
        
        list($code, $duration, $headers, $body) = $this->sendRequest('DELETE', $queryUrl, $headers);
        if ($code != 204) throw new \Exception('删除失败');
    }

    /**
     * 获取上传凭证
     * @param string|null $name
     * @param int $expires
     * @param string|null $attname
     * @return string
     */
    public function getUploadToken(?string $name = null, int $expires = 3600): array
    {
        $time = time();
        $endTimestamp = $time + $expires;
        $keyTime = $time . ';' . $endTimestamp;
        $policy = [
            'expiration' => date('Y-m-d\TH:i:s.000\Z', $endTimestamp),
            'conditions' => [
                ['q-ak' => $this->accessKey],
                ['q-sign-time' => $keyTime],
                ['q-sign-algorithm' => 'sha1']
            ]
        ];
        $policy = json_encode($policy);

        return [
            'policy' => base64_encode($policy),
            'q-ak' => $this->accessKey,
            'q-key-time' => $keyTime,
            'q-sign-algorithm' => 'sha1',
            'q-signature' => hash_hmac('sha1', sha1($policy), hash_hmac('sha1', $keyTime, $this->secretKey)),
        ];
    }

    /**
     * 获取头部签名
     * @param string $method
     * @param string $key
     * @param array $header
     * @return array
     */
    private function headerSign(string $method, string $key, array $header = []): array
    {
        $str1 = substr($key, 0, 1);
        if ($str1 == '/') $key = substr($key, 1);

        $startTimestamp = time();
        $endTimestamp = $startTimestamp + 3600;
        $keyTime = $startTimestamp . ';' . $endTimestamp;

        $parseUrl = parse_url($key);
        $urlParamList = $httpParameters = '';
        if (!empty($parseUrl['query'])) {
            parse_str($parseUrl['query'], $params);
            uksort($params, 'strnatcasecmp');
            $urlParamList = join(';', array_keys($params));
            $httpParameters = http_build_query($params);
        }

        $headerList = $httpHeaders = '';
        if (!empty($header)) {
            uksort($header, 'strnatcasecmp');
            $headerList = join(';', array_keys($header));
            $httpHeaders = http_build_query($header);
        }

        $httpString = strtolower($method) . "\n/" . $parseUrl['path'] . "\n" . $httpParameters . "\n" . $httpHeaders . "\n";
        $httpStringSha1 = sha1($httpString);
        $stringToSign = "sha1\n" . $keyTime . "\n" . $httpStringSha1 . "\n";

        $signKey = hash_hmac('sha1', $keyTime, $this->secretKey);
        $signature = hash_hmac('sha1', $stringToSign, $signKey);
        $signArray = [
            'q-sign-algorithm' => 'sha1',
            'q-ak' => $this->accessKey,
            'q-sign-time' => $keyTime,
            'q-key-time' => $keyTime,
            'q-header-list' => $headerList,
            'q-url-param-list' => $urlParamList,
            'q-signature' => $signature,
        ];
        $header['Authorization'] = urldecode(http_build_query($signArray));

        return $header;
    }

    /**
     * 获取上传的url地址
     * @return string
     */
    private function uploadUrl(): string
    {
        return 'https://' . $this->bucket . '.cos.' . $this->regionDomain[$this->region] . '.myqcloud.com';
    }
}