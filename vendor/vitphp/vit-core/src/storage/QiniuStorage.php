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

class QiniuStorage extends Storage
{
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
        $this->bucket = $config['space'] ?? getSetting('space');
        $this->accessKey = $config['access_key'] ?? getSetting('access_key');
        $this->secretKey = $config['secret_key'] ?? getSetting('secret_key');
        $this->domain = $config['domain'] ?? getSetting('domain');

        if (!$this->bucket) throw new \Exception('未配置七牛云空间名称');
        if (!$this->accessKey) throw new \Exception('未配置七牛云AccessKey');
        if (!$this->secretKey) throw new \Exception('未配置七牛云SecretKey');
        if (!$this->domain) throw new \Exception('未配置七牛云访问域名');
    }

    public static function instance(?int $attaType = null, $config = [])
    {
        return parent::instance(2, $config);
    }

    /**
     * 上传文件
     */
    public function upload(string $uploadPath, File $file, $newFileName = ''): array
    {
        $fileInfo = $this->getFileInfo($file);
        $name = $newFileName ?: $uploadPath . "/" . $this->getNewKey() . "." . $fileInfo['orgin'];

        $token = $this->getUploadToken($name, 3600);
        $data = ['key' => $name, 'token' => $token];

        $url = $this->uploadUrl();
        $fileBody = $this->getFileBody((string)$file);

        list($code, $duration, $headers, $body) = $this->multipartPost($url, $data, 'file', $name, $fileBody);
        $body = json_decode($body, true);
        if ($code != 200) throw new \Exception($body['error'] ?? '上传失败');

        return [
            'storage' => 2,
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
        $str1 = substr($name, 0, 1);
        if ($str1 == '/') $name = substr($name, 1);
        list($encodedEntryURI, $accessToken) = $this->getAccessToken($name, 'delete');

        $url = 'http://rs.qiniu.com/delete/' . $encodedEntryURI;
        $headers = ["Authorization" => "QBox {$accessToken}"];

        list($code, $duration, $headers, $body) = $this->sendRequest('POST', $url, $headers);
        $body = json_decode($body, true);
        if ($code != 200) throw new \Exception($body['error'] ?? '删除失败');
    }

    /**
     * 获取对象管理凭证
     * @param string $name 文件名称
     * @param string $type 操作类型
     * @return array
     */
    private function getAccessToken(string $name, string $type = 'stat'): array
    {
        $entry = $this->base64urlEncode($this->bucket . ':' . $name);
        $sign = hash_hmac('sha1', "/{$type}/{$entry}\n", $this->secretKey, true);
        return [$entry, $this->accessKey . ':' . $this->base64urlEncode($sign)];
    }

    /**
     * 获取上传凭证
     * @param string|null $name
     * @param int $expires
     * @param string|null $attname
     * @return string
     */
    public function getUploadToken(?string $name = null, int $expires = 3600): string
    {
        $scope = $this->bucket . ':' . $name;
        $deadline = time() + $expires;
        $returnBody = [
            'key' => $name,
            'file' => $name,
        ];
        $policy = [
            'scope' => $scope,
            'deadline' => $deadline,
            'returnBody' => json_encode($returnBody, JSON_UNESCAPED_UNICODE)
        ];
        $policy = $this->base64urlEncode(json_encode($policy, JSON_UNESCAPED_UNICODE));

        $sign = hash_hmac('sha1', $policy, $this->secretKey, true);
        $encodedSign = $this->base64urlEncode($sign);

        return $this->accessKey . ':' . $encodedSign . ':' . $policy;
    }

    /**
     * 获取上传域名，根据七牛的accessKey与bucket获取七牛的上传域名
     * @return mixed|string
     * @throws \Exception
     */
    private function uploadUrl()
    {
        $queryUrl = 'http://uc.qbox.me/v1/query' . "?ak=" . $this->accessKey . "&bucket=" . $this->bucket;
        list($code, $duration, $headers, $body) = $this->sendRequest('GET', $queryUrl);
        if ($code != 200) throw new \Exception('获取地址域名失败');
        $result = json_decode($body, true, 512);
        return $result['https']['up'][0] ?? '';
    }

    /**
     * 将内容转为base64之后，再转为url安全的格式
     * @param string $str
     * @return string
     */
    private function base64urlEncode(string $str): string
    {
        $base64 = base64_encode($str);
        $base64url = strtr($base64, '+/', '-_');
        return $base64url;
    }
}