<?php
if (!defined('IN')) die('bad request');
include_once(CROOT . 'controller' . DS . 'core.class.php');
/**
 * Created by JetBrains PhpStorm.
 * User: xinshi
 * Date: 15-4-15
 * Time: 下午2:51
 * To change this template use File | Settings | File Templates.
 * //这里是对一些应用的固定判断，如果你需要这些判断，则请继承appController
 */
use Joyme\core\JoymeToolsUser;

class appController extends coreController
{
    function __construct()
    {
        // 载入默认的
        if ($GLOBALS['config']['checklogin']) {
            $this->loginCheck();
        }
        parent::__construct();
    }

    //判断判断登录
    function loginCheck()
    {

        //必须指定回跳到 joyme域名下，否则获取不到cookie！
        JoymeToolsUser::init($GLOBALS['domain'], $this->redirect_url());
        JoymeToolsUser::check(101, 102);
        if (!$_COOKIE['joume_username']) {
            setcookie("joume_username", JoymeToolsUser::getUsername(), time() + 3600 * 24 * 7);
        }
    }

    //退出
    function logout()
    {
        //具体退出调用Java接口
        setcookie("joume_username", $_COOKIE['joume_username'], time() - 1);
        $url = "http://tools.joyme." . $GLOBALS['domain'] . "/logout?reurl=" . $this->redirect_url();
        header("location:$url");
    }

    function redirect_url()
    {

        $redirect_url = "http://" . $_SERVER['SERVER_NAME'];
        return $redirect_url;
    }
}
