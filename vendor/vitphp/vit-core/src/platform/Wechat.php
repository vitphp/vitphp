<?php

namespace vitphp\admin\platform;

use think\facade\Request;

class Wechat
{
    protected static $wechatSessionKey = '';
    protected static $wechatInfo = [];

    protected static $wechatConfig = [];
    protected static $isSnsapiInfo = 1;

    protected static function getConfig($appid = '', $appsecret = '', $authdomain = '', $isSnsapiInfo = 1)
    {
        global $_VIT;
        $wechatConfig = $_VIT['platform']['wechat'] ?? [];
        self::$wechatConfig = [
            'appid' => $appid ?: ($wechatConfig['appid'] ?? ''),
            'appsecret' => $appsecret ?: ($wechatConfig['appsecret'] ?? ''),
            'authdomain' => $authdomain ?: ($wechatConfig['authdomain'] ?? ''),
            'is_snsapi_info' => $isSnsapiInfo ?: ($wechatConfig['isSnsapiInfo'] ?? 1),
        ];
    }

    /**
     * 获取微信信息
     * @return mixed
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public static function getWechatInfo($appid = '', $appsecret = '', $authdomain = '', $isSnsapiInfo = 1)
    {
        global $_VIT;
        self::getConfig($appid, $appsecret, $authdomain, $isSnsapiInfo);
        self::$wechatSessionKey = md5('vit_wechat_info' . $appid . '_' . self::$wechatConfig['is_snsapi_info']);
        $wechatInfo = vit_session(self::$wechatSessionKey);
        if ($wechatInfo) {
            $_VIT['wechat_user'] = $wechatInfo;
            return $wechatInfo;
        } else {
            $wechatInfo = self::getUserInfo();
        }

        if (!empty($wechatInfo['nickname'])) {
            $wechatInfo['nickname'] = self::removeEmojiChar($wechatInfo['nickname']);
        }

        return $wechatInfo;
    }

    protected static function removeEmojiChar($str)
    {
        $mbLen = mb_strlen($str);

        $strArr = [];
        for ($i = 0; $i < $mbLen; $i++) {
            $mbSubstr = mb_substr($str, $i, 1, 'utf-8');
            if (strlen($mbSubstr) >= 4) {
                continue;
            }
            $strArr[] = $mbSubstr;
        }

        return implode('', $strArr);
    }

    protected static function getUserInfo()
    {
        global $_VIT;
        $snsapi = self::$wechatConfig['is_snsapi_info'] == 1 ? 'snsapi_userinfo' : 'snsapi_base';
        $authdomain = self::$wechatConfig['authdomain'];
        $thisUrl = Request::domain() . Request::url();
        $config = [
            'appid' => self::$wechatConfig['appid'],
            'appsecret' => self::$wechatConfig['appsecret']
        ];
        $wechat = new \WeChat\Oauth($config);
        if (!input('code')) {
            $state = '123';
            $jumpUrl = $thisUrl;
            if ($authdomain) {
                $jumpUrl = $authdomain;
                $stateKey = md5(session_id() . $_VIT['pid'] . md5(time()) . $thisUrl . time());
                $state = 'vitphp-' . $stateKey;
                cache($state, $thisUrl, 60);
            }
            $redirectUrl = $wechat->getOauthRedirect($jumpUrl, $state, $snsapi);
            redirect($redirectUrl)->send();
        } else {
            $result = $wechat->getOauthAccessToken();
            if ($snsapi == 'snsapi_userinfo') {
                $userInfo = $wechat->getUserInfo($result['access_token'], $result['openid']);
                self::$wechatInfo = array_merge($result, $userInfo);
            } else {
                self::$wechatInfo = $result;
            }
            self::$wechatInfo['code'] = input('code');
            self::$wechatInfo['state'] = input('state');
            $_VIT['wechat_user'] = self::$wechatInfo;
            vit_session(self::$wechatSessionKey, self::$wechatInfo);
            \think\facade\Session::save();
            self::removeCode();

        }
        return self::$wechatInfo;
    }

    /**
     * 剔除code与state
     */
    protected static function removeCode()
    {
        if (!Request::isGet() || Request::isAjax()) return;
        if (!isset($_GET['code']) && !isset($_GET['state'])) return;
        if (!self::$wechatInfo) return;
        $baseUrl = Request::domain() . Request::baseUrl();
        $url = $baseUrl;

        unset($_GET['code']);
        unset($_GET['state']);
        if ($_GET) {
            $url = $baseUrl . '?' . http_build_query($_GET);
        }
        echo "<script>window.location.href='{$url}'</script>";
    }
}