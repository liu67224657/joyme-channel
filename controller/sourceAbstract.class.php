<?php
if (!defined('IN')) die('bad request');
/**
 * Created by PhpStorm.
 * User: pengzhang
 * Date: 2017/4/13
 * Time: 上午10:14
 */
include_once(CROOT . 'controller' . DS . 'core.class.php');

use Joyme\core\Request;

abstract class sourceAbstractController extends coreController
{
    //渠道字段
    public $fields = array();

    /**
     * 接口查询
     */
    abstract public function query($cid);

}