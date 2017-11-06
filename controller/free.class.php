<?php

if (!defined('IN'))
    die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );

use Joyme\core\Log;

/**
 * Created by JetBrains PhpStorm.
 * User: xinshi
 * Date: 15-4-15
 * Time: 下午2:51
 * To change this template use File | Settings | File Templates.
 */
class freeController extends coreController {

    function __construct() {
        // 载入默认的
        parent::__construct();
//        Log::config(Log::ALL);
    }

    //加密验证
    function Protection($str) {
        //约定秘钥
        $key = "zm^!-tb";
        $token = md5(md5($str . $key));
        return $token;
    }
}
