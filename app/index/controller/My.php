<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace app\index\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;
use vitphp\admin\controller\BaseController;
use vitphp\admin\model\SystemAdmin;
use vitphp\admin\WeChat\WeUserInfo;

/**
 * @title 用户资料修改
 * @auth 0
 * Class Admin
 * @package app\index\controller
 */
class My extends BaseController
{
    /**
     * @title 用户资料
     * @auth 0
     */
    public function index(){
        $my = SystemAdmin::where('id',session('admin.id'))->find();
        $my['last_login']= date("Y-m-d H:i:s");
        View::assign('my',$my);

//        dd(View::engine()->getConfig('taglib_pre_load'));

        return View::fetch('index');

    }
    /**
     * @title 修改用户头像
     * @auth 0
    */
    
    public function updateImg(){
        $headimg = input("headimg");
        if(empty($headimg)){
            jsonErrCode("发生错误，请重新上传！");
        }else{
            $id = session('admin.id');
            $res = Db::name('users')->where('id',$id)->update(['headimg'=>$headimg]);
            $user = Db::name("users")->where('id',$id)->find();
            session("admin",$user);
            Session::save();
            if($res){
                jsonSucCode("头像上传成功！");
            }else{
                jsonErrCode("发生错误，请重新上传！");
            }
        }
    }
    /**
     * @title 修改密码
     * @auth 0
    */
    public function edit_pass(){
        $my = SystemAdmin::where('id',session('admin.id'))->find();
        $a = 123;
        $a = pass_en($a);
        View::assign('a',$a);
        return view();
    }
    /**
     * @title 修改密码提交
     * @auth 0
    */
    public function pass_save(){
        $password = input('password');//原密码
        $newpassword = input('newpassword');//新密码
        $repassword = input('repassword');//重新输入密码
        $id = session('admin.id');
        $user = Db::name('users')->where('id',$id)->find();
        !preg_match('/^[a-zA-Z0-9]+$/', $newpassword) && $this->error('只能使用字母数字');
        if (!password_verify($password, $user['password'])){
            $this->error('原密码不正确');
        }else{
            ($newpassword !== $repassword) && $this->error('两次密码输入不一致');

            $newpassword = pass_en($newpassword);
            $res = Db::name('users')->where('id',$id)->update(['password'=>$newpassword]);
            if ($res){
                save_sys_log('修改密码', null, session('admin.username'));
                $this->success('设置成功');
            }else{
                $this->error('设置失败');
            }
        }
    }
    /**
     * @title 用户修改提交
     * @auth 0
    */
    public function save(){
        $nickname = input('nickname');
        $headimg = input('headimg');
        $user = Session::get("admin");
        $data = [
          'nickname'=>$nickname,
          'headimg'=>$headimg
        ];
        $res = SystemAdmin::where([
            'id'=>$user['id']
        ])->update($data);

        if($res){
            $user = SystemAdmin::where([
                'id'=>$user['id']
            ])->withoutField('password')->find()->toArray();
            Session::set("admin",$user);
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }
    /**
     * @title 跳转绑定微信
     * @auth 0
    */
    public function toweixin(){
        return View::fetch();
    }
    /**
     * @title 查询绑定
     * @auth 0
     */

    public function queryBind(){
        $user = session("admin");
        $getuser = Db::name("users")->where(['id'=>$user['id']])->find();
        if($getuser){
            $openid = $getuser['openid'];
            if($openid){
                echo json_encode(['code'=>200,'msg'=>'绑定成功','data'=>$getuser['nickname']]); exit;
            }else{
                echo json_encode(['code'=>100,'msg'=>'扫码绑定中']); exit;
            }

        }else{
            echo json_encode(['code'=>100,'msg'=>'扫码绑定中']); exit;
        }
    }

    /**
     * @title 解除绑定微信
     * @auth 0
     */
    public function unbind(){
        $uid = session("admin.id");
        $data['openid']= "";
        $data['nickname']= "";
        $res = Db::name("users")->where(['id'=>$uid])->update($data);
        if($res){
            $user = Db::name("users")->where('id',$uid)->find();
            session("admin",$user);
            Session::save();

            jsonSucCode('解除绑定成功！');
        }
        jsonSucCode('解除绑定成功');
    }

    /**
     * @title 绑定微信
     * @auth 0
     * @login 0
     */
    public function bindwe(){
        $error = 0;
        $tip = '';
        $wesid = input('wesid');
        $qrcode = Db::name('qrcode')->where('sid', $wesid)->find();
        if(!$qrcode){
            $error = 1;
            $tip = '不存在的二维码';
        }

        if($qrcode['is_login']==2){
            $error = 1;
            $tip = '二维码已使用，请重新生成';
        }

        if(time() - $qrcode['createtime'] > 180){
            $error = 1;
            $tip = '二维码已过期，请重新生成';
        }

        $userInfo = Db::name("users")->where(['id'=>$qrcode['weid']])->find();
        if($userInfo['openid']){
            $error = 1;
            $tip = '此管理员已绑定微信，请先解绑';
        }

        $param = session('param');
        if(!$param){
            //获取用户信息
            $config = [
                'appid' => getSetting("wx_appid"),
                'appsecret' => getSetting("wx_appsecret")
            ];
            $wechat = new \WeChat\Oauth($config);
            if (!input('code')) {
                $thisUrl = $this->request->domain() . $this->request->url();
                $redirectUrl = $wechat->getOauthRedirect($thisUrl, '123', 'snsapi_userinfo');
                return redirect($redirectUrl);
            } else {
                $result = $wechat->getOauthAccessToken();
                $param = $wechat->getUserInfo($result['access_token'], $result['openid']);
                session('param', $param);
            }
        }

        $openid = isset($param['openid']) ? $param['openid'] : "";
        if(empty($openid)){
            $error = 1;
            $tip = '获取用户信息失败';
        }

        if($this->request->isPost()){
            $info = Db::name("users")->where(['openid'=>$openid])->find();
            if($info) $this->error('此微信已绑定过管理员，不可绑定新管理员');
            $data['openid']= $openid;
            $data['nickname'] = $param['nickname'];
            Db::name("users")->where(['id'=>$qrcode['weid']])->update($data);
            Db::name('qrcode')->where(['id'=>$qrcode['id']])->update(['is_login'=>2]);
            $this->success('绑定成功');
        }

        View::assign([
            'wesid' => $wesid,
            'error' => $error,
            'tip' => $tip
        ]);
        return View::fetch();
    }
    /**
     * @title 绑定用户验证密码
     * @auth 0
     */
    public function validPass(){
        $password = input("password");
        $uid = session("admin.id");
        if(empty($password)){
            jsonErrCode("请输入密码！");
        }else{
            $getUser = session("admin");
            if (!password_verify($password,$getUser['password'])){
                jsonErrCode("密码输入不正确！");
            }else{
                //验证成功，生成二维码
                $url = url("/index/my/bindwe");
                $setSqDomain = getSetting('wx_sqdomain', 'setup');
                $domain = $setSqDomain ?: request()->domain();

                $data = [];
                $data['weid'] = $uid;
                $data['sid'] = redom(8);
                $data['createtime'] = time();
                Db::name("qrcode")->insert($data);

                $qrcode = $domain.url("/index/common/Cqrcode")."?url=".$domain.$url."?wesid=". $data['sid'];
                jsonSucCode("验证成功",['qrcode'=>$qrcode]);
            }

        }
    }
}