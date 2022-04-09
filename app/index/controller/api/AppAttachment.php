<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace app\index\controller\api;

use app\BaseController;
use think\facade\Session;
use vitphp\admin\storage\Storage;

/**
 * @title 前端附件管理
 * @package app\index\controller\api
 */
class AppAttachment extends BaseController
{
    protected $typePath = [
        1 => 'images',
        2 => 'music',
        3 => 'video',
    ];

    /**
     * 前端上传图片
     */
    public function upload($type = 1, $attaType = null)
    {
        if (!$this->request->isPost()) {
            return [
                'code' => -1,
                'msg' => '请使用post提交'
            ];
        }

        $files = $this->request->file();
        $typePath = $this->typePath[$type] ?? $this->typePath[1];

        foreach ($files as $file) {
            $uploadPath = 'app' . DIRECTORY_SEPARATOR . app('http')->getName() . DIRECTORY_SEPARATOR . $typePath . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d');
            try {
                $res = Storage::instance($attaType)->upload($uploadPath, $file);
            } catch (\Exception $e) {
                return [
                    'code' => -1,
                    'msg' => $e->getMessage()
                ];
            }

            $res['name'] = $res['filename'];
            return [
                'code' => 1,
                'msg' => '上传成功',
                'data' => $res
            ];
        }

        return [
            'code' => -1,
            'msg' => '未发现文件'
        ];
    }
}