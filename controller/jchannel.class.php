<?php

/**
 * Description:渠道管理渠道相关
 * Author: gradydong
 * Date: 2017/4/19
 * Time: 13:52
 * Copyright: Joyme.com
 */
if (!defined('IN')) die('bad request');
include_once(AROOT . 'controller' . DS . 'app.class.php');
use Joyme\core\Request;
use Joyme\net\RedisHelper;

class jchannelController extends appController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $conditions = $where = array();
        //渠道标示
        $ckey = Request::getParam('ckey', '');
        if ($ckey) {
            $where['ckey'] = array('like', '%' . $ckey . '%');
            $conditions['ckey'] = $ckey;
        }
        //渠道名称
        $channel_name = Request::getParam('channel_name', '');
        if ($channel_name) {
            $where['channel_name'] = array('like', '%' . $channel_name . '%');
            $conditions['channel_name'] = $channel_name;
        }
        //渠道模板
        $template = Request::getParam('template', '');
        if ($template) {
            $where['template'] = array('like', '%' . $template . '%');
            $conditions['template'] = $template;
        }
        //渠道显示状态
        $isshow = Request::getParam('isshow', 'all');
        if (is_numeric($isshow)) {
            $where['isshow'] = (int)$isshow;
            $conditions['isshow'] = $isshow;
        }

        $jchannelmodel = new jChannelModel();
        $total = $jchannelmodel->count($where);
        $psize = 20; //每页显示条数
        $pno = Request::get('pb_page', 1);
        $skip = 0;
        if ($pno) {
            $skip = (intval($pno) - 1) * $psize;
        }
        $lists = $jchannelmodel->select("cid,ckey,channel_name,template,isshow", $where, 'cid DESC', $psize, $skip);
        $page = new pageModel();
        $page->mainPage(array('total' => $total, 'perpage' => $psize, 'nowindex' => $pno, 'pagebarnum' => 10));
        $phtml = $page->show(2, $conditions);
        $data = array(
            'ckey' => $ckey,
            'channel_name' => $channel_name,
            'template' => $template,
            'isshow' => $isshow,
            'total' => $total,
            'list' => $lists,
            'phtml' => $phtml,
            'pno' => $pno
        );
        render($data, 'web', 'jchannel/list');
    }


    public function add()
    {
        render(array(), 'web', 'jchannel/add');
    }

    public function addPro()
    {
        $jchannelmodel = new jChannelModel();
        $data = $this->getPostData();
        $ret = $jchannelmodel->insert($data);
        if ($ret) {
            echo '操作成功 <a href="?c=jchannel&a=index">返回列表</a>';
        } else {
            echo '操作失败 <a href="?c=jchannel&a=index">返回列表</a>';
        }
    }

    public function edit()
    {
        $cid = Request::get('cid', 0);
        $jchannelmodel = new jChannelModel();
        $item = $jchannelmodel->selectRow('cid,ckey,channel_name,template', array(
            'cid' => $cid
        ));
        $data = array(
            'item' => $item
        );
        render($data, 'web', 'jchannel/edit');
    }

    public function editPro()
    {
        $cid = Request::post('cid');
        if (is_numeric($cid)) {
            $jchannelmodel = new jChannelModel();
            $data = $this->getPostData();
            $errno = $jchannelmodel->update($data, array(
                'cid' => $cid
            ));
            if ($errno) {
                echo '操作成功 <a href="?c=jchannel&a=index">返回列表</a>';
            } else {
                echo '操作失败 <a href="?c=jchannel&a=index">返回列表</a>';
            }
        } else {
            echo '参数错误 <a href="?c=jchannel&a=index">返回列表</a>';
        }
    }

    public function del()
    {
        $cid = (int)Request::post('cid');
        if ($cid) {
            $jchannelmodel = new jChannelModel();
            $errno = $jchannelmodel->delete(array(
                'cid' => $cid
            ));
            if ($errno) {
                addlog('joymewiki', 'delete', '添加了id为' . $cid . '的渠道数据');
                $res = array('rs' => 1, 'msg' => '删除成功');
            } else {
                $res = array('rs' => 2, 'msg' => '删除失败');
            }
        } else {
            $res = array('rs' => 2, 'msg' => '参数错误');
        }
        jsonEncode($res);
    }

    private function getPostData()
    {
        $data = array();
        $id = Request::post('id');
        $ckey = Request::post('ckey', '');
        if (empty($ckey)) {
            if ($id) {
                echo '渠道标示不能为空 <a href="?c=jchannel&a=edit&id=' . $id . '">返回</a>';
            } else {
                echo '渠道标示不能为空 <a href="?c=jchannel&a=add">返回</a>';
            }
        } else {
            $data['ckey'] = $ckey;
        }
        $channel_name = Request::post('channel_name', '');
        if (empty($channel_name)) {
            if ($id) {
                echo '渠道名称不能为空 <a href="?c=jchannel&a=edit&id=' . $id . '">返回</a>';
            } else {
                echo '渠道名称不能为空 <a href="?c=jchannel&a=add">返回</a>';
            }
        } else {
            $data['channel_name'] = $channel_name;
        }
        $template = Request::post('template', '');
        if (empty($template)) {
            if ($id) {
                echo '渠道模板不能为空 <a href="?c=jchannel&a=edit&id=' . $id . '">返回</a>';
            } else {
                echo '渠道模板不能为空 <a href="?c=jchannel&a=add">返回</a>';
            }
        } else {
            $data['template'] = $template;
        }
        return $data;
    }

    //修改渠道显示状态
    public function editisshow()
    {
        $cid = (int)Request::getParam('cid');
        $isshow = (int)Request::getParam('isshow');
        if ($cid) {
            $jchannelmodel = new jChannelModel();
            $errno = $jchannelmodel->update(array(
                'isshow' => $isshow
            ),array(
                'cid' => $cid,
            ));
            if ($errno) {
                $res = array('rs' => 1, 'msg' => '修改成功');
            } else {
                $res = array('rs' => 2, 'msg' => '修改失败');
            }
        } else {
            $res = array('rs' => 2, 'msg' => '参数错误');
        }
        jsonEncode($res);
    }


    //获取渠道标示获取渠道ID
    public function getCidByKey($key)
    {
        $redisconfig = $GLOBALS['config']['redis'];

        $mem = new RedisHelper($redisconfig['host'], $redisconfig['port'],0,$redisconfig['password']);

        $channelkey = memckey('channellist', 'key', $key);

        $cid = $mem->get($channelkey);

        if (empty($cid)) {
            $jchannelModel = new jChannelModel();

            $cid = $jchannelModel->getIDByKey($key);

            if (empty($cid)) {
                return 0;
            } else {
                $mem->set($channelkey, $cid);
            }

        }
        return $cid;
    }
}