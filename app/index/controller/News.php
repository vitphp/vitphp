<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace app\index\controller;

use vitphp\admin\controller\BaseController;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;
/**
 * @title 文章管理
 */

class News extends BaseController
{
    /**
     * @title 文章列表
     * @auth 1
     * @login 1
     * @return string
     * @throws \think\db\exception\DbException
     */
    public function index(){
        $user = Session::get("admin");
        $newslist=Db::name('news')->alias('n')->field('n.*,b.title as newstitle')
            ->join('vit_news_type b','n.tid=b.id')
            ->paginate(10,false,
            [
                'type'=>'BootstrapDetail'
            ]);
//        $newslist = $newslist['data'];
        $list = Db::name('news_type')->where('uid','1')->paginate(10,false,
            [
                'type'=>'BootstrapDetail'
            ]);
        $list_copy = $list->toArray();
        $listdata = $list_copy['data'];
        View::assign([
            'newslist'=>!empty($newslist) ? $newslist : '',
            'listdata'=>!empty($listdata) ? $listdata : '',
            'list'=>!empty($list) ? $list : '',
            'page' => $newslist->render(),
        ]);
        return View::fetch();
    }
    /**
     * @title 添加/修改文章分类
     */
    public function addtype(){
        $user = Session::get("admin");
        $id = input("id");
        $data = Db::name('news_type')->where(['id'=>$id])->find();
        //判断是否post提交
        if(request()->isPost()){
            $param = [
                'uid'=> $user['id'],
                'title'=>input('title')
            ];
            if ($id){
                $res = Db::name('news_type')->where('id',$id)->update($param);
                save_sys_log('修改文章分类', "文章分类ID：{$id}", session('admin.username'));
            }else{
                $res = Db::name("news_type")->insertGetId($param);
                save_sys_log('添加文章分类', "文章分类ID：{$res}", session('admin.username'));
            }

            if($res){
                $this->success("配置成功！", 'index/news/index');
            }else{
                $this->error("配置失败！");
            }
        }
        View::assign([
            'data'=>$data
        ]);
        return View::fetch();
    }
    /**
     * @title 删除文章分类
     */
    public function deltype(){
        $ids = $this->request->post('ids');
        $newsList = Db::name('news')->where('tid', '=', $ids)->find();
        if($newsList) $this->error('分类下有文章，请先删除文章');

        Db::name('news_type')->where('id',$ids)->delete();
        save_sys_log('删除文章分类', "文章分类ID：{$ids}", session('admin.username'));
        $this->success('删除成功');
    }
    /**
     * @title 编辑/添加文章
     */
    public function editnew(){
        $user = Session::get("admin");
        $id = input("id");
        $data = Db::name('news')->where(['id'=>$id])->find();
        $newstype =  DB::name('news_type')->where('uid',$user['id'])->order('id desc ')->select();
        //判断是否post提交
        if(request()->isPost()){
            $param = [
                'uid'=> $user['id'],
                'tid'=>input('tid'),
                'title'=>input('title'),
                'cover'=>input('cover'),
                'content'=>input('content'),
                'author'=>$user['username'],
                'is_display'=>input('is_display'),
                'createtime'=>time()
            ];
            if ($id){
                $res = Db::name('news')->where('id',$id)->update($param);
                save_sys_log('修改文章', "文章ID：{$id}", session('admin.username'));
            }else{
                $res = Db::name("news")->insertGetId($param);
                save_sys_log('添加文章', "文章ID：{$res}", session('admin.username'));
            }

            if($res){
                $this->success("配置成功！", 'index/news/index');
            }else{
                $this->error("配置失败！");
            }
        }
        View::assign([
            'newstype'=>!empty($newstype) ? $newstype->toArray() : "",
            'data'=>$data
        ]);
        return View::fetch();
    }
    /**
     * @title 删除文章
     */
    public function delnews(){
        $ids = $this->request->post('ids');
        $query = Db::name('news')->where('id',$ids)->delete();
        if ($query){
            save_sys_log('删除文章', "文章ID：{$ids}", session('admin.username'));
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
}