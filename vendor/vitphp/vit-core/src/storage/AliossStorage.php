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

use think\File;

class AliossStorage extends Storage
{
    private $point;
    private $bucket;
    private $accessKey;
    private $secretKey;
    private $domain;

    /**
     * 初始化配置信息
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function init($config = [])
    {
        $this->point = $config['al_region'] ?? getSetting('al_region');
        $this->bucket = $config['al_space'] ?? getSetting('al_space');
        $this->accessKey = $config['al_accesskey'] ?? getSetting('al_accesskey');
        $this->secretKey = $config['al_secretkey'] ?? getSetting('al_secretkey');
        $this->domain = $config['al_domain'] ?? getSetting('al_domain');

        if (!$this->point) throw new \Exception('未配置阿里云访问区域');
        if (!$this->bucket) throw new \Exception('未配置阿里云空间名称');
        if (!$this->accessKey) throw new \Exception('未配置阿里云AccessKey');
        if (!$this->secretKey) throw new \Exception('未配置阿里云SecretKey');
        if (!$this->domain) throw new \Exception('未配置阿里云访问域名');
    }

    public static function instance(?int $attaType = null, $config = [])
    {
        return parent::instance(4, $config);
    }

    /**
     * 上传文件
     */
    public function upload(string $uploadPath, File $file, $newFileName = ''): array
    {
        $fileInfo = $this->getFileInfo($file);

        $name = $newFileName ?: $uploadPath . "/" . $this->getNewKey() . "." . $fileInfo['orgin'];

        $token = $this->getUploadToken($name, 3600);
        $data = [
            'key' => $name,
            'policy' => $token['policy'],
            'Signature' => $token['signature'],
            'OSSAccessKeyId' => $this->accessKey,
            'success_action_status' => '200'
        ];

        $url = $this->uploadUrl();
        $fileBody = $this->getFileBody((string)$file);

        list($code, $duration, $headers, $body) = $this->multipartPost($url, $data, 'file', $name, $fileBody);
        $body = json_decode($body, true);
        if ($code != 200) throw new \Exception($body['error'] ?? '上传失败');

        return [
            'storage' => 4,
            'filename' => $fileInfo['original_name'], // 这是原文件名
            'fileurl' => '/' . $name, // 这是除了域名外的，也就是key
            'size' => intval($fileInfo['size'] / 1024),
            'src' => $this->domain . '/' . $name, // 可访问的http地址
        ];
    }

    /**
     * 删除文件
     * 上传的时候开头没加/，所以这里要处理一下
     * @param string $name
     * @return bool|void
     * @throws \Exception
     */
    public function del(string $name)
    {
        $name = $this->getNamePath($name);
        $queryUrl = 'http://' . $this->bucket . '.' . $this->point . '.aliyuncs.com' . $name;
        $headers = $this->headerSign('DELETE', $name);
        list($code, $duration, $headers, $body) = $this->sendRequest('DELETE', $queryUrl, $headers);

        if ($code != 204) throw new \Exception($body['error'] ?? '删除失败');
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
        $policy = [
            'conditions' => [['content-length-range', 0, 1048576000]],
            'expiration' => date('Y-m-d\TH:i:s.000\Z', time() + $expires),
        ];
        $policy = base64_encode(json_encode($policy));
        $data = [
            'policy' => $policy,
            'keyid' => $this->accessKey,
            'signature' => base64_encode(hash_hmac('sha1', $policy, $this->secretKey, true))
        ];

        return $data;
    }

    /**
     * 操作请求头信息签名
     * @param string $method 请求方式
     * @param string $soruce 资源名称
     * @param array $header 请求头信息
     * @return array
     */
    private function headerSign(string $method, string $soruce, array $header = []): array
    {
        if (!isset($header['Data'])) $header['Date'] = gmdate('D, d M Y H:i:s \G\M\T');
        if (!isset($header['Content-Type'])) $header['Content-Type'] = 'application/xml';
        uksort($header, 'strnatcasecmp');

        $content = $method . "\n\n";
        foreach ($header as $key => $value) {
            $value = str_replace(["\r", "\n"], '', $value);
            if (in_array(strtolower($key), ['content-md5', 'content-type', 'date'])) {
                $content .= $value . "\n";
            } elseif (stripos($key, 'x-oss-') === 0) {
                $content .= strtolower($key) . ':' . $value . "\n";
            }
        }
        $content = rawurldecode($content) . '/' . $this->bucket . $soruce;
        $signature = base64_encode(hash_hmac('sha1', $content, $this->secretKey, true));
        $header['Authorization'] = 'OSS ' . $this->accessKey . ':' . $signature;

        return $header;
    }

    /**
     * 获取上传url
     * @return mixed|string
     * @throws \Exception
     */
    private function uploadUrl()
    {
        return 'http://' . $this->bucket . '.' . $this->point . '.aliyuncs.com';
    }
}