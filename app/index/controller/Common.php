<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------


namespace app\index\controller;

use think\facade\Cache;
use think\facade\View;
use vitphp\admin\controller\BaseController;
use vitphp\admin\traits\Jump;

///**
// * @title 全局
// * Class Common
// * @package app\index\controller
// */
//class Common extends BaseController
class Common extends ValidationController
{
    protected $mime_types = array(
        'gif' => 'image/gif',

        'jpg' => 'image/jpg',

        'jpeg' => 'image/jpeg',

        'jpe' => 'image/jpeg',

        'bmp' => 'image/bmp',

        'png' => 'image/png',

        'tif' => 'image/tiff',

        'tiff' => 'image/tiff',

        'pict' => 'image/x-pict',

        'pic' => 'image/x-pict',

        'pct' => 'image/x-pict',

        'psd' => 'image/x-photoshop',

        'swf' => 'application/x-shockwave-flash',

        'js' => 'application/x-javascript',

        'pdf' => 'application/pdf',

        'ps' => 'application/postscript',

        'eps' => 'application/postscript',

        'ai' => 'application/postscript',

        'wmf' => 'application/x-msmetafile',

        'css' => 'text/css',

        'htm' => 'text/html',

        'html' => 'text/html',

        'txt' => 'text/plain',

        'xml' => 'text/xml',

        'wml' => 'text/wml',

        'wbmp' => 'image/vnd.wap.wbmp',

        'mid' => 'audio/midi',

        'wav' => 'audio/wav',

        'mp3' => 'audio/mpeg',

        'mp2' => 'audio/mpeg',

        'avi' => 'video/x-msvideo',

        'mpeg' => 'video/mpeg',

        'mpg' => 'video/mpeg',

        'qt' => 'video/quicktime',

        'mov' => 'video/quicktime',

        'lha' => 'application/x-lha',

        'lzh' => 'application/x-lha',

        'z' => 'application/x-compress',

        'gtar' => 'application/x-gtar',

        'gz' => 'application/x-gzip',

        'gzip' => 'application/x-gzip',

        'tgz' => 'application/x-gzip',

        'tar' => 'application/x-tar',

        'bz2' => 'application/bzip2',

        'zip' => 'application/zip',

        'arj' => 'application/x-arj',

        'rar' => 'application/x-rar-compressed',

        'hqx' => 'application/mac-binhex40',
        'sit' => 'application/x-stuffit',
        'bin' => 'application/x-macbinary',
        'uu' => 'text/x-uuencode',
        'uue' => 'text/x-uuencode',
        'latex'=> 'application/x-latex',
        'ltx' => 'application/x-latex',
        'tcl' => 'application/x-tcl',
        'pgp' => 'application/pgp',
        'asc' => 'application/pgp',
        'exe' => 'application/x-msdownload',
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'mdb' => 'application/x-msaccess',
        'wri' => 'application/x-mswrite',
        'mp4' => 'audio/mp4,video/mp4,MPEG-4 Audio/Video',
    );
    /**
     * @title 上传
     * @auth 0
     */
    public function upload()
    {
        $imgtype = getSetting('imgtype','setup');
        $imgtype = explode(',', $imgtype);
        $imgAcceptMime = [];
        foreach ($imgtype as $type){
            if(isset($this->mime_types[$type])){
                $mime = $this->mime_types[$type] ?? '';
                if(!in_array($mime, $imgAcceptMime)){
                    $imgAcceptMime[] = $mime;
                }
            }
        }
        $imgAcceptMime = implode(',', $imgAcceptMime);

        return view('',['imgAcceptMime'=>$imgAcceptMime]);
    }
    /**
     * @title 选择图标
     * @auth 0
     */
    public function icon(){
        return view();
    }
    /**
     * @title 创建二维码
     * @login 0
     * @auth 0
     * @return string
     */
    public function Cqrcode(){
        $url = input('url');
        if($url){
            createQrcode($url);
        }
    }

    /**
     * 获取code值
     * @param false $refresh
     * @return mixed|string
     */
    public static function getVitCode($refresh = false )
    {
        if($refresh){
            return self::getRefreshVitCode();
        }

        $code = Cache::get('vit_code');
        if(!$code){
            return self::getRefreshVitCode();
        }
        return $code;
    }

    /**
     * 重新获取新的code值
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function getRefreshVitCode()
    {
        $username = getSetting('username', 'info');
        $appid = getSetting('appid', 'info');
        $appkey = getSetting('appkey', 'info');
        $time = time();
        $sign = sha1($username.$appkey.$time);

        $url = config('vitphp.market').'api/code';
        $post = [
            'appid' => $appid,
            'sign' => $sign,
            'time' => $time
        ];
        $result = app_http_request($url, $post);
        $result = is_null(json_decode($result)) ? [] : json_decode($result, true);
        if(!$result || $result['code']!=1){
            return '';
        }
        Cache::set('vit_code', $result['data']['code'], '7200');
        return $result['data']['code'];
    }

    public static function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    public static function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirname, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }

    /**
     * 上传音乐
     * @auth 0
     */
    public function music(){
        $musicType = getSetting('mp3type','setup');
        $musicType = explode(',', $musicType);
        $musicAcceptMime = [];
        foreach ($musicType as $type){
            if(isset($this->mime_types[$type])){
                $mime = $this->mime_types[$type] ?? '';
                if(!in_array($mime, $musicAcceptMime)){
                    $musicAcceptMime[] = $mime;
                }
            }
        }
        $musicAcceptMime = implode(',', $musicAcceptMime);

        View::assign([
            'musicAcceptMime' => $musicAcceptMime
        ]);
        return View::fetch();
    }
    /**
     * 上传视频
     * @auth 0
     */
    public function video(){
        $videoType = getSetting('mp4type','setup');
        $videoType = explode(',', $videoType);
        $videoAcceptMime = [];
        foreach ($videoType as $type){
            if(isset($this->mime_types[$type])){
                $mime = $this->mime_types[$type] ?? '';
                if(!in_array($mime, $videoAcceptMime)){
                    $videoAcceptMime[] = $mime;
                }
            }
        }
        $videoAcceptMime = implode(',', $videoAcceptMime);

        View::assign([
            'videoAcceptMime' => $videoAcceptMime
        ]);
        return View::fetch();
    }
}