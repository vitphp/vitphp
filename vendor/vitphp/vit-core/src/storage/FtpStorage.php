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

use League\Flysystem\Adapter\Ftp;
use think\facade\Filesystem;
use think\facade\Session;
use think\File;

class FtpStorage extends Storage
{
    private $domain; // ftp访问域名
    private $host; // ip
    private $port; // 商品号
    private $username; // 用户名
    private $password; // 密码
    private $path; // ftp上传路径

    /**
     * 初始化配置信息
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function init($config = [])
    {
//        halt($config);
        $this->host = $config['ftp_ip'] ?? getSetting('ftp_ip');
        $this->port = $config['ftp_port'] ?? getSetting('ftp_port');
        $this->username = $config['ftp_account'] ?? getSetting('ftp_account');
        $this->password = $config['ftp_pass'] ?? getSetting('ftp_pass');
        $this->domain = $config['ftp_domain'] ?? getSetting('ftp_domain');
        $this->path = $config['ftp_path'] ?? getSetting('ftp_path');
    }

    public static function instance(?int $attaType = null, $config = [])
    {
        return parent::instance(5, $config);
    }

    /**
     * 上传文件
     */
    public function upload(string $uploadPath, File $file, $newFileName = ''): array
    {
        $fileInfo = $this->getFileInfo($file);

        $ftpSystem = new \League\Flysystem\Filesystem(new Ftp([
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password
        ]));
        $uploadPath = $newFileName ?: $uploadPath . "/" . $this->getNewKey() . "." . $fileInfo['orgin'];

        $stats = $ftpSystem->put('/' . $this->path . '/' . $uploadPath, file_get_contents($file->getPathname()));
        if ($stats) {
            return [
                'storage' => 5,
                'filename' => $fileInfo['original_name'], // 这是原文件名
                'fileurl' => '/' . $uploadPath, // 这是除了域名外的，也就是key
                'size' => intval($fileInfo['size'] / 1024),
                'src' => $this->domain . '/' . $uploadPath, // 可访问的http地址
            ];
        }else{
            throw new \Exception('文件上传失败');
        }
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
        $ftpSystem = new \League\Flysystem\Filesystem(new Ftp([
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password
        ]));
        $path = '/' . $this->path . '/' . $name;
        if ($ftpSystem->has($path)) {
            try {
                $ftpSystem->delete($path);
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }
}