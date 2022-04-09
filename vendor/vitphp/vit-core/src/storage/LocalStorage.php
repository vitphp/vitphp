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

class LocalStorage extends Storage
{
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
        $this->domain = $this->app->request->domain();
    }

    public static function instance(?int $attaType = null, $config = [])
    {
        return parent::instance(1, $config);
    }

    /**
     * 上传文件
     */
    public function upload(string $uploadPath, File $file, $newFileName = ''): array
    {
        $fileInfo = $this->getFileInfo($file);

        if($newFileName){
            $path = Filesystem::disk('public')->putFileAs($uploadPath, $file, $newFileName);
        }else{
            $path = Filesystem::disk('public')->putFile($uploadPath, $file, 'sha1');
        }

        $prefix = Filesystem::getConfig('disks.public.url');

        return [
            'storage' => 1,
            'filename' => $fileInfo['original_name'], // 这是原文件名
            'fileurl' => '/' . $prefix . '/' . $path, // 这是除了域名外的，也就是key
            'size' => intval($fileInfo['size'] / 1024),
            'src' => $this->domain . '/' . $prefix . '/' . $path, // 可访问的http地址
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
        $filePath = $this->app->getRootPath() . DIRECTORY_SEPARATOR . 'public' . $name;
        @unlink($filePath);
    }
}