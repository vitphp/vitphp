<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace vitphp\admin\controller;
use think\facade\Db;
use app\BaseController as ThinkController;

use think\App;
use think\db\exception\PDOException;
use think\db\Query;
use think\db\Where;
use think\facade\Cache;
use think\facade\View;
use vitphp\admin\auth\AnnotationAuth;
use vitphp\admin\middleware\CheckAccess;
use vitphp\admin\traits\Jump;

class BaseController extends ThinkController
{
    use Jump;

    protected $middleware = [
        CheckAccess::class
    ];

    /**
     * 构造函数
     * BaseAdmin constructor.
     * @param App|null $app
     */
    public function __construct(App $app = null)
    {
        parent::__construct($app);
        # 获取登录用户的id
        $login_id = session('admin.id');
        // 面包屑数据
        # 模块
        $module = $this->app->http->getName();
        # 控制器
        $controller = parse_name($this->request->controller(),0);
        # 方法
        $action = $this->request->action();

        // 当前控制器
        $classuri = $this->app->http->getName().'/'.$this->request->controller();
        $name = $this->app->http->getName();

        if($name != 'index'){
            # 如果模块不是index模块，判断是否传有pid参数
            $pid = input('pid');
            global $_PID_;
            $_PID_ = $pid;
            if(empty($pid)){
                $this->error("项目不存在");
            }
            # 查询项目信息
            $app = Db::name('app')->where(['id'=>$pid])->find();
            if($app){
                $ok = false;
                if($login_id == 1){
                    $ok = true;
                }else if($app['uid'] == $login_id){
                    $ok = true;
                }else{
                   if($pid == Db::name('users')->where('id', $login_id)->value('pid')){
                       $ok = true;
                   }
                }
                if($ok === false){
                    $this->error("项目不存在!");
                    return false;
                }
                # 如果不是超级管理员
                if($login_id != 1){
                    # 判断项目是否过期
                    $dq_time = $app['dq_time'];
                    if(time() > $dq_time){
                        $this->error("项目时间已到期");
                    }
                }
                # 获取记录模块名
                $addons = $app['addons'];
                # 如果记录模块名称和当前模块名称不一致
                if($addons != $name){
                    $this->error("项目不匹配");
                }

                if($app){

                    # 如果当前用户是项目的超级管理员
                    if($app['uid'] === $login_id){
                        # 是管理员，查询全部菜单权限
                        $menu = Db::name("menu")->where(['type'=>$name])->select()->toArray();
                        foreach ($menu as $i=>$v){
                            $v['url'] = url(strtolower($v['url']))->build();
                            $menu[$i] = $v;
                        }
                    }else{
                        # 不是该项目的管理员,进行权限匹配查询 (如果是项目成员(并非管理员)则验证权限)
                        $menu = AnnotationAuth::getMenu($login_id,$addons);
                    }
                }else{
                    # 项目信息不存在，返回一个空菜单
                    $menu = [];
//                    $this->error("项目不存在!");
                }
                # 菜单数据处理
                if($menu){
                    foreach ($menu as $k=>$v){
                        $name = $v['name'] ?? '';
                        $title = $v['title'] ?? '';
                        $menu[$k]['pathUrl'] = $v['url'].'?pid='.$pid;
                        $menu[$k]['title'] = empty($title) ? $name : $title;
                    }
                }

                View::assign(['menu'=>$menu,'app'=>$app]);
            }else{
                if($login_id){
                    $this->error("项目不存在!");
                }else{
                    $this->error('当前请求没有登录~','index/login/index');
                }
            }

        }

        View::assign('classuri',$classuri);
    }

    /**
     * 万能列表方法
     * @param $query
     * @param bool $multipage
     * @param array $param
     * @return mixed
     */
    protected function _list($query,$multipage = true,$pageParam = [])
    {
        if ($this->request->isGet()){
            if ($multipage){
                $pageResult = $query->paginate(null,false,['query'=>$pageParam]);
                View::assign('page',$pageResult->render());
                $result = $pageResult->all();
            }else{
                $result = $query->select();
            }
            if (false !== $this->_callback('_list_before', $result, [])) {
               
                View::assign('list',$result);
                return View::fetch();
            }
            return $result;
        }
    }

    /**
     * 表单万能方法
     * @param $query
     * @param string $tpl
     * @param string $pk
     * @param array $where
     * @return array|mixed
     */
    protected function _form(Query $query, $tpl = '', $pk='', $where = []) {
        $pk = $pk?:($query->getPk()?:'id');
        $defaultPkValue = isset($where[$pk])?$where[$pk]:null;
        $pkValue = $this->request->get($pk,$defaultPkValue);

        if ($this->request->isGet()){
            $vo = ($pkValue !== null) ? $query->where($pk,$pkValue)->where(new Where($where))->find():[];
            if (false !== $this->_callback('_form_before', $vo)) {
                return View::fetch($tpl,['vo'=>$vo]);
            }
            return $vo;
        }
        $data = $this->request->post();
        if (false !== $this->_callback('_form_before', $data)) {
            try{
                if (isset($data[$pk])){
                    $where[$pk] = ['=',$data[$pk]];
                    $result = $query->where(new Where($where))->update($data);
                    $last_id = $data[$pk];
                }else{
                    $result = $query->insert($data);
                    $last_id = $query->getLastInsID();
                }
            }catch (PDOException $e){
                $this->error($e->getMessage());
            }
            //手动释放所有查询条件（此处TP有bug  导致数据库链接对象拿到错误的表名）
//            $query->removeOption();
            // 重置查询对象
            $query = $query->newQuery();
            $last_data = $query->find($last_id);
            if (false !== $this->_callback('_form_after',  $last_data)) {
                if ($result !== false) {
                    $this->success('恭喜, 数据保存成功!', '');
                }
                $this->error('数据保存失败, 请稍候再试!');
            }else{
                $this->error("表单后置操作失败，请检查数据！");
            }
        }
    }

    /**
     * @param $ids
     * @throws PDOException
     * @throws \think\Exception
     */
    protected function _del($query, $ids)
    {
        $fields = $query->getTableFields();
        if (in_array('is_deleted',$fields)){
            $res = $query->whereIn('id', $ids)
                ->update(['is_deleted' => 1]);
        }else{
            $res = $query->whereIn('id', $ids)
                ->delete();
        }
        if ($res) {
            $this->success('删除成功！', '');
        } else {
            $this->error("删除失败");
        }
    }

    protected function _change($query, $id, $data)
    {
        $res = $query->where('id', $id)->update($data);
        if ($res) {
            $this->success('切换状态操作成功！');
        } else {
            $this->error('切换状态操作失败！');
        }
    }

    /**
     * 回调唤起
     * @param $method
     * @param $data1
     * @param $data2
     * @return bool
     */
    protected function _callback($method, &$data)
    {
        foreach ([$method, "_" . $this->request->action() . "{$method}"] as $_method) {
            if (method_exists($this, $_method) && false === $this->$_method($data)) {
                return false;
            }
        }
        return true;
    }

}