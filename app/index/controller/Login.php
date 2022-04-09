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
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
use vitphp\admin\controller\BaseController;
use vitphp\admin\model\SystemAdmin;
use vitphp\admin\WeChat\WeUserInfo;

/**
 * @title 登陆操作
 * Class Login
 * @package app\index\controller
 */
class Login extends BaseController
{
    /**
     * @title 登录页
     * @login 0
     * @auth 0
     */
    public function index()
    {
        $xieyi = Db::name('settings')->where([
            'addons'=>'setup',
            'name'=>'xieyi'
        ])->find();
        View::assign([
            'xieyi'=>!empty($xieyi['value']) ? ($xieyi['value']) : '',
        ]);
        if (!!session('admin.id')){
            # 如果有设置PID，则跳转
            $userPID = Db::name('users')->where('id',session('admin.id'))->value('pid');
            if(!empty($userPID)){
                $url = '/index/addons?pid='.$userPID;
            }else{
                $url = '../../index/admin/index';
            }
            return redirect($url);
        } else {
            return View::fetch('');
        }
    }
    /**
     * @title 用户协议
     * @auth 0
     * @login 0
     */
    public function xieyi()
    {
        $xieyi = Db::name('settings')->where([
            'addons'=>'setup',
            'name'=>'xieyi'
        ])->find();
        View::assign([
            'xieyi'=>!empty($xieyi) ? $xieyi : '',
        ]);
        return View::fetch('');
    }

    /**
     * @title 执行登陆
     * @auth 0
     * @login 0
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function dologin()
    {
        $username = $this->request->post(SystemAdmin::username(), false);
        $password = $this->request->post('password', false);
        $verify = $this->request->post('verify', false);

        (!$username || !$password || !$verify) && $this->error("缺少参数！");

        if (strlen($username) < 5 || strlen($username) > 20)
            $this->error("用户名在5-20字符之间！");
        if (strlen($password) < 5 || strlen($password) > 20)
            $this->error("用户名在5-20字符之间！");
        if (!captcha_check($verify)) {
            $this->error("验证码错误！");
        }

        $user = SystemAdmin::where(SystemAdmin::username(), '=', $username)->where('is_deleted', 0)->find();
        if (empty($user))
            $this->error("用户名输入不正确！");
        if (!password_verify($password, $user['password']))
            $this->error("密码输入不正确！");
        if ($user['state'] !== 1)
            $this->error("该用户已被禁用！");

        $options = [
            'cost' => config('admin.cost')
        ];
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT, $options)) {
            // 如果是这样，则创建新散列，替换旧散列
            $newpassword = password_hash($password, PASSWORD_DEFAULT, $options);
            $update_data['password'] = $newpassword;
        }
        // 最后登录时间
        $update_data['last_login'] = $this->request->time();
        $update_data['login_ip'] = $this->request->ip();
        $update_data['login_num'] = $user->login_num + 1;
        SystemAdmin::where(SystemAdmin::username(), '=', $username)->update($update_data);
        $user = SystemAdmin::where(SystemAdmin::username(), '=', $username)->find();
        Session::set('admin', $user);
        # 如果有设置PID，则跳转
        $userPID = Db::name('users')->where('id', $user->id)->value('pid');
        if(!empty($userPID)){
            $url = '/index/addons?pid='.$userPID;
        }else{
            $url = '/index/admin/index';
        }
        save_sys_log('账号登录', null, $username);
        $this->success("登录成功！", $url);
    }
    /**
     * @title 退出操作
     * @auth 0
     * @login 1
     */
    public function logout()
    {
        save_sys_log('退出登录', null, session('admin.username'));
        $this->app->session->clear();
        $this->app->session->destroy();

        return redirect('../../index/login/index');
    }

    /**
     * @login  0
     * @auth 0
     * @title 生成登录二维码
     */
    public function createQrcode(){
        $url = url("/index/login/saoma");
        $setSqDomain = getSetting('wx_sqdomain', 'setup');
        $domain = $setSqDomain ?: request()->domain();

        $data = array();
        $data['sid'] = redom(8);
        $data['createtime'] = time();
        $res = $this->app->db->name("qrcode")->insertGetId($data);

        $qrcode = $domain.url("/index/common/Cqrcode")."?url=".$domain.$url."?wesid=".$data['sid'];

        if($qrcode){
            echo json_encode(['code'=>200,'msg'=>$qrcode,'sid'=>$data['sid']]);exit;
        }else{
            echo json_encode(['code'=>100,'msg'=>'生成二维码失败']);exit;
        }
    }
    /**
     * @title 扫码登录
     * @auth 0
     * @login 0
     */
    public function saoma(){
        $sid = input("wesid");
        $res = Db::name("qrcode")->where(['sid'=>$sid])->find();
        $error = 0;
        $tip = '';
        if(empty($res)){
            $error = 1;
            $tip = '扫码失败，请重新生成';
        }
        if($res['openid']){
            $error = 1;
            $tip = '二维码已使用，请重新生成';
        }
        if(time() - $res['createtime'] > 1800){
            //删除失效的二维码
            Db::name("qrcode")->where('id',$res['id'])->delete();
            $error = 1;
            $tip = '二维码已失效，请重新生成';
        }

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
        }

        $openid = isset($param['openid']) ? $param['openid'] : "";
        if(empty($openid)){
            $error = 1;
            $tip = '获取用户信息失败';
        }

        $data['openid']= $openid;
        $data['avatar'] = $param['headimgurl'];
        $data['nickname'] = $param['nickname'];
        $data['is_login'] = 1;
        Db::name("qrcode")->where('sid',$sid)->update($data);

        View::assign([
            'error' => $error,
            'tip' => $tip
        ]);
        return View::fetch();
    }
    /**
     * @title 扫码登录
     * @auth 0
     * @login 0
     */
    public function scan_login(){
        $op  = input("op");
        $sid = input('wesid');
        $res = Db::name("qrcode")->where(['sid'=>$sid])->find();
        if($op == "doLogin"){
            $users = Db::name("users")->where('openid',$res['openid'])->where('is_deleted', 0)->find();
            if($users){
                if ($users['state']!==1) {
                    Db::name("qrcode")->where('id',$sid)->delete();
                    echo json_encode(['code'=>100,'msg'=>'账号已经被禁用，请联系管理员!']);exit;
                }
                $data_code['is_login'] =2;
                $res = Db::name("qrcode")->where('sid',$sid)->update($data_code);

                echo json_encode(['code'=>200,'msg'=>'登录成功!']);exit;
            }else{
                Db::name("qrcode")->where('id',$sid)->delete();
                echo json_encode(['code'=>100,'msg'=>'暂未绑定微信']);exit;
            }
        }
    }
    /**
     * @title 扫码登录
     * @auth 0
     * @login 0
     */
    public function querylogin(){
        $sid = input("sid");
        $res = Db::name("qrcode")->where(['sid'=>$sid])->find();

        if($res['is_login'] == 2){
            $user = Db::name('users')->where('openid',$res['openid'])->where('is_deleted', 0)->find();
            if(!$user){
                echo json_encode(array('code'=>-200,'msg'=>'登录失败'.$res['openid'].'不存在!'));exit;
            }
            if(!$res['openid']){
                echo json_encode(array('code'=>800,'msg'=>'登录失败'.$res['openid'].'不存在!'));exit;
            }
            Session::set('admin', $user);
            Session::save();//BUG 这句这句不能少
            # 如果有设置PID，则跳转
            $userPID = Db::name('users')->where('id', $user['id'])->value('pid');
            if(!empty($userPID)){
                $url = '/index/addons?pid='.$userPID;
            }else{
                $url = '/index/admin/index';
            }
            echo json_encode(array('code'=>200,'msg'=>'登录成功','url'=>url('@'.$url)->build()));exit;

        }else if($res['is_login'] == 1){
            echo json_encode(array('code'=>400,'msg'=>'扫码成功'));exit;
        }else{
            echo json_encode(array('code'=>800,'msg'=>'扫码登录中'));exit;
        }
    }
    /**
     * @title 用户注册
     * @auth 0
     * @login 0
     */
    public function reg_save(){
        $domain = Request::host();
        $site = Db::name('system_site')->where('domain',$domain)->find();
        if ($site){
            $sid = $site['uid'];
        }else{
            $sid = '';
        }
        $verify = $this->request->post('verify', false);
        $username = input('username');
        $password = input('password');
        $repassword = input('repassword');
        //注册默认权限id
        $auth = getSetting('interest');
        if (strlen($username) < 5 || strlen($username) > 20){
            $this->error("用户名在5-20字符之间！");
        }
        if (strlen($password) < 5 || strlen($password) > 20){
            $this->error("密码在5-20字符之间！");
        }
        if(!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
            $this->error('密码只能使用字母数字');
        }
        if(($password !== $repassword)){
            $this->error('两次密码输入不一致');
        }
        if (!captcha_check($verify)) {
            $this->error("验证码错误！");
        }
        $getUser = Db::name("users")->where("username",$username)->find();
        if($getUser){
            $this->error("用户已存在！");
        }
        $password = pass_en($password);
        $param = [
            'username'=>input('username'),
            'password'=> $password,
            'create_time'=>time(),
            'sid'=>$sid,
        ];

        $res = Db::name("users")->insertGetId($param);

        if($res){
            $data['admin_id'] = $res;
            $data['role_id'] = $auth;
            Db::name("auth_map")->insertGetId($data);
            save_sys_log('注册', null, input('username'));
            $this->success("注册成功！",'index');
        }else{
            $this->error("注册失败！");
        }
    }
}