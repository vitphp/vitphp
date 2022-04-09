<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace app\index\controller\api;

use app\index\controller\ValidationController;
use think\facade\Db;
use think\facade\Session;
use vitphp\admin\controller\BaseController;
use vitphp\admin\storage\Storage;
use vitphp\admin\traits\Jump;

/**
 * @title 附件管理
 * Class Attachment
 * @package app\index\controller\api
 */
//class Attachment extends BaseController
//class Attachment extends \app\BaseController
class Attachment extends ValidationController
{
    protected $typePath = [
        1 => 'images',
        2 => 'music',
        3 => 'video',
    ];

    /**
     * @title 上传图片
     * @login 1
     * @auth 0
     */
    public function upload()
    {
        if (!$this->request->isPost()) $this->error('请使用post提交');

        $files = $this->request->file();
        $data = [];
        $type = input('type', 1);
        $saveDb = input('db', 1);
        $storageId = input('storage_id', '');
        $session = input('session', null);
        $sessionId = session($session . '.id');
        $storageSetup = null;
        if($storageId){
            $storageSetup = Db::name('storage_setup')->where('id', $storageId)->find();
        }
        $typePath = $this->typePath[$type] ?? $this->typePath[1];

        $uid = $session ? $sessionId : session('admin.id');

        foreach ($files as $file) {
            $attachment = new \vitphp\admin\model\Attachment();
            if($session){
                $uploadPath =  $session . '/' . $uid . '/' . $typePath . '/' . date('Y/m/d');
            }else{
                $uploadPath = $uid . '/' . $typePath . '/' . date('Y/m/d');
            }

            $storage = Storage::instance(null, $storageSetup);

            $res = $storage->upload($uploadPath, $file);

            $dataRes['uid'] = $session ? $sessionId : session('admin.id');
            $dataRes['filename'] = $res['filename'];
            $dataRes['fileurl'] = $storage->attaType == 1 ? $res['fileurl'] : $res['src'];
//            $dataRes['fileurl'] = $res['fileurl'];
            $dataRes['size'] = $res['size'];
            $dataRes['type'] = $type;
            $dataRes['storage'] = $res['storage'];
            $dataRes['session'] = $session;
            $dataRes['storage_id'] = $storageId ?? null;
            $dataRes['pid'] = input('pid', null);
            $dataRes['createtime'] = $this->request->time();
            if($saveDb) $attachment->data($dataRes)->save();

            $data[] = [
                'id' => $attachment->id,
                'size' => $dataRes['size'],
                'name' => $res['filename'],
                'src' => $res['src'],
                'fileurl' => $res['storage']==1 ? $res['fileurl'] : $res['src']
            ];
        }

        header('Content-Type: application/json');
        $result = [
            'code' => 0,
            'data' => $data,
        ];
        echo json_encode($result, 256);
        exit();
    }

    /**
     * @title 图片列表
     * @login 1
     * @auth 0
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function imagelist()
    {
        $type = input('type', 1);
        $session = input('session', null);
        $sessionId = session($session . '.id');
        $storageSetup = null;
        if($session){
            $where = [
                'uid' => $sessionId,
                'session' => $session
            ];
        }else{
            $where = [
                'uid' => session('admin.id')
            ];
        }
        $result = \vitphp\admin\model\Attachment::where('type', $type)
            ->where($where)
//            ->where('uid', \session('admin.id'))
            ->order('id', 'desc')
            ->paginate(8);
        if ($result) {
            foreach ($result as $k => $v) {
                $result[$k]['imgUrl'] = media($v['fileurl'], $v['storage']);
                $result[$k]['imgUrl'] = $v['storage'] == 1 ? $this->request->domain().$result[$k]['imgUrl'] : $result[$k]['imgUrl'];
//                $result[$k]['fileurl'] = $v['storage'] == 1 ? $this->request->domain().$result[$k]['imgUrl'] : $result[$k]['imgUrl'];
                $result[$k]['fileurl'] = $v['storage'] == 1 ? $v['fileurl'] : media($v['fileurl'], $v['storage']);
            }
        }
        return json($result);
    }

    public function deleteFile($id)
    {
        $attachment = \vitphp\admin\model\Attachment::where('id', $id)->find();
        if (!$attachment) {
            return false;
        }

        $attachment->delete();

        if($attachment['storage_id']){
            $storageSetup = Db::name('storage_setup')->where('id', $attachment['storage_id'])->find();
            Storage::instance($attachment['storage'], $storageSetup)->del($attachment['fileurl']);
        }else{
            Storage::instance($attachment['storage'])->del($attachment['fileurl']);
        }

        return true;
    }

    /**
     * @title 删除图片
     * @login 1
     * @auth 0
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delimage()
    {
        if (!$this->request->isPost()) {
            $this->error('请post访问');
        }
        $id = $this->request->post('id', false);
        !$id && $this->error('参数错误,请传入附件ID');
        $ids = array_filter(explode(',', $id));
        foreach ($ids as $id) {
            try {
                $this->deleteFile($id);
            } catch (\Exception $e) {
                throw $e;
            }
        }
        $this->success('删除成功！');
    }
}