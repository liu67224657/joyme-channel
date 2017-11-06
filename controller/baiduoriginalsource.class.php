<?php

/**
 * Description:百度原创接口提交
 * Author: gradydong
 * Date: 2017/6/30
 * Time: 16:46
 * Copyright: Joyme.com
 */

if (!defined('IN')) die('bad request');
include_once(CROOT . 'controller' . DS . 'core.class.php');

use Joyme\core\Request;
use Joyme\core\Log;

class baiduoriginalsourceController extends coreController
{

    public function savedata()
    {
        $source = Request::getParam('source', 1);
        $title = Request::getParam('title');
        $url = Request::getParam('url');
        $result = empty($_REQUEST['result']) ? '' : $_REQUEST['result'];
        $addtime = Request::getParam('addtime');
        if(empty($title)||
            empty($url)||
            empty($result)||
            empty($addtime)
        ){
            $data = array(
                'rs' => '0',
                'msg'=>'参数不能为空'
            );
        }else{
            $data = array(
                'source' => $source,
                'title' => $title,
                'url' => $url,
                'result' => $result,
                'addtime' => $addtime
            );
            $baiduoriginalmodel = new baiduOriginalModel();
            $ret = $baiduoriginalmodel->insert($data);
            if($ret){
                $data = array(
                    'rs' => '1',
                    'msg'=>'提交成功'
                );
            }else{
                $data = array(
                    'rs' => '0',
                    'msg'=>'提交失败'
                );
            }
        }
        echo json_encode($data);
        exit;
    }
}