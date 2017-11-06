<?php

/**
 * Description:
 * Author: gradydong
 * Date: 2017/4/19
 * Time: 14:20
 * Copyright: Joyme.com
 */
if (!defined('IN')) {
    die('bad request');
}
use Joyme\db\JoymeModel;

class jChannelDataModel extends JoymeModel
{
    public $tableName = 'channeldata';

    public static $atypes = array(
        "0" => "资讯类",
        "1" => "视频类",
        "4" => "攻略类",
        "5" => "资料类",
    );

    public function __construct()
    {
        $this->db_config = array(
            'hostname' => $GLOBALS['config']['db']['db_host'],
            'username' => $GLOBALS['config']['db']['db_user'],
            'password' => $GLOBALS['config']['db']['db_password'],
            'database' => $GLOBALS['config']['db']['channel_db_name']
        );
        parent::__construct();
    }

    public function getData($where, $limit = 10, $skip = 0)
    {
        return $this->select('*', $where, 'pubdate desc', $limit, $skip);
    }

    public function addData($data)
    {
        return $this->insert($data);
    }

    public static function getAtype($key)
    {
        if (self::$atypes[$key]) {
            return self::$atypes[$key];
        } else {
            return '';
        }
    }

    public function getCount($where){
        return $this->count($where);
    }

    public static function getAllAtypes()
    {
        $atypes = self::$atypes;
        $allatypes = array();
        foreach ($atypes as $k => $v) {
            $allatypes[$k]['atype_desc'] = $v;
            $allatypes[$k]['checked'] = '';
        }
        return $allatypes;
    }

    //获取游戏相关文章列表
    public function getGameLists($where,$psize,$skip,$jchannellists,$constr)
    {
        $lists = $this->select("aid,gid,atype,isblock,url,datatype", $where, 'pubdate DESC', $psize, $skip, 'aid');
        if ($lists) {
            $cids = array_column($jchannellists, 'cid');
            //数据id
            $aids = array_column($lists, 'aid');
            $jsourcedatamodel = new jSourceDataModel();
            $jsourcedatalists = $jsourcedatamodel->select('aid,data', array(
                'source' => 1,
                'aid' => array('in', $aids)
            ));
            $datas = array();
            if ($jsourcedatalists) {
                $datas = array_column($jsourcedatalists, 'data', 'aid');
            }
            //游戏id
            $gids = array_column($lists, 'gid');
            $jgamemodel = new jGameModel();
            $jgamelists = $jgamemodel->select('gid,extra', array(
                'gid' => array('in', $gids)
            ));
            $gameinfos = array();
            if ($jgamelists) {
                $gameinfos = array_column($jgamelists, 'extra', 'gid');
            }
            //查询游戏添加的渠道
            $jchannelgamemodel = new jChannelGameModel();
            $jchannelgamelists = $jchannelgamemodel->select("cid,gid", array(
                'gid' => array('in', $gids),
                'gamestatus' => 1,
                'datatype' => 1
            ));
            $jchannelgamecids = array();
            if ($jchannelgamelists) {
                foreach ($jchannelgamelists as $jk => $jchannelgamelist) {
                    $jchannelgamecids[$jchannelgamelist['gid']][] = $jchannelgamelist['cid'];
                }
            }
            //判断渠道是否已选
            $cidlists = $this->select("aid,cid", array(
                'source' => 1,
                'aid' => array('in', $aids),
                'datatype' => 1
            ), 'pubdate DESC', '', '');
            $jchannelcids = array();
            if ($cidlists) {
                foreach ($cidlists as $ck => $cidlist) {
                    $jchannelcids[$cidlist['aid']][] = $cidlist['cid'];
                }
            }
            //判断状态是启用还是禁用
            $isblocklists = $this->select("aid,cid,isblock", array(
                'source' => 1,
                'aid' => array('in', $aids),
                'datatype' => 1
            ), 'pubdate DESC', '', '');
            $jchannelisblocks = array();
            if ($isblocklists) {
                foreach ($isblocklists as $ik => $isblocklist) {
                    $jchannelisblocks[$isblocklist['aid']][$isblocklist['cid']] = $isblocklist['isblock'];
                }
            }
            foreach ($lists as $k => $list) {
                if($list['datatype']==1){
                    $lists[$k]['datatype'] = '游戏';
                }
                elseif ($list['datatype']==2){
                    $lists[$k]['datatype'] = '栏目';
                }
                if ($datas[$list['aid']]) {
                    $data = json_decode($datas[$list['aid']], true);
                    $lists[$k]['title'] = $data['title'];
                    $lists[$k]['ctime'] = date("Y-m-d H:i:s", $data['pubdate']);
                }
                $lists[$k]['atype_desc'] = self::getAtype($list['atype']);
                if ($gameinfos[$list['gid']]) {
                    $gameinfo = json_decode($gameinfos[$list['gid']], true);
                    $lists[$k]['gameName'] = $gameinfo['gameName'];
                }
                $channels = array();
                foreach ($cids as $cid) {
                    if ($jchannelcids[$list['aid']]) {
                        if (in_array($cid, $jchannelcids[$list['aid']])) {
                            if ($jchannelgamecids[$list['gid']]) {
                                if (in_array($cid, $jchannelgamecids[$list['gid']])) {
                                    if ($jchannelisblocks[$list['aid']][$cid] == '1') {
                                        $channels[$cid] = "&#10006";
                                    } else {
                                        $channels[$cid] = "&#10004";
                                    }
                                } else {
                                    $channels[$cid] = "&#10006";
                                }
                            } else {
                                $channels[$cid] = "&#10006";
                            }
                        } else {
                            $channels[$cid] = "&#10006";
                        }
                    }
                }
                $lists[$k]['channels'] = $channels;
                if ($list['atype'] == 5) {
                    if ($list['isblock'] == '1') {
                        $lists[$k]['op'] = '<a href="javascript:;" class="status" data-aid="' . $list['aid'] . '" data-isblock="0">启用</a><br>';
                    } else {
                        $lists[$k]['op'] = '<a href="javascript:;" class="status" data-aid="' . $list['aid'] . '" data-isblock="1">禁用</a><br>';
                    }
                } else {
                    $lists[$k]['op'] = '<a href="?c=jchanneldata&a=aedit&aid=' . $list['aid'] . '&atype=' . $list['atype'] .$constr. '">编辑</a><br>';
                }
            }
        }
        return $lists;
    }

    //获取栏目相关文章列表
    public function getLabelLists($where,$psize,$skip,$jchannellists,$constr)
    {
        $lists = $this->select("aid,gid,atype,isblock,url,datatype", $where, 'pubdate DESC', $psize, $skip, 'aid,gid');
        if ($lists) {
            $cids = array_column($jchannellists, 'cid');
            //数据id
            $aids = array_column($lists, 'aid');
            $jsourcedatamodel = new jSourceDataModel();
            $jsourcedatalists = $jsourcedatamodel->select('aid,data', array(
                'source' => 1,
                'aid' => array('in', $aids)
            ));
            $datas = array();
            if ($jsourcedatalists) {
                $datas = array_column($jsourcedatalists, 'data', 'aid');
            }
            //栏目id
            $gids = array_column($lists, 'gid');
            $jchannelgamemodel = new jChannelGameModel();
            $jchannelgamelists = $jchannelgamemodel->select('gid,gamename',array(
                'gid' => array('in', $gids),
                'datatype' => 2
            ),'','','','gid');
            $gamenames = array();
            if($jchannelgamelists){
                $gamenames = array_column($jchannelgamelists,'gamename','gid');
            }

            //查询游戏添加的渠道
            $jchannelgamelists = $jchannelgamemodel->select("cid,gid", array(
                'gid' => array('in', $gids),
                'gamestatus' => 1,
                'datatype' => 2
            ));
            $jchannelgamecids = array();
            if ($jchannelgamelists) {
                foreach ($jchannelgamelists as $jk => $jchannelgamelist) {
                    $jchannelgamecids[$jchannelgamelist['gid']][] = $jchannelgamelist['cid'];
                }
            }
            //判断渠道是否已选
            $cidlists = $this->select("aid,cid", array(
                'source' => 1,
                'aid' => array('in', $aids),
                'datatype' => 2
            ), 'pubdate DESC', '', '');
            $jchannelcids = array();
            if ($cidlists) {
                foreach ($cidlists as $ck => $cidlist) {
                    $jchannelcids[$cidlist['aid']][] = $cidlist['cid'];
                }
            }
            //判断状态是启用还是禁用
            $isblocklists = $this->select("aid,cid,isblock", array(
                'source' => 1,
                'aid' => array('in', $aids),
                'datatype' => 2
            ), 'pubdate DESC', '', '');
            $jchannelisblocks = array();
            if ($isblocklists) {
                foreach ($isblocklists as $ik => $isblocklist) {
                    $jchannelisblocks[$isblocklist['aid']][$isblocklist['cid']] = $isblocklist['isblock'];
                }
            }
            foreach ($lists as $k => $list) {
                if($list['datatype']==1){
                    $lists[$k]['datatype'] = '游戏';
                }
                elseif ($list['datatype']==2){
                    $lists[$k]['datatype'] = '栏目';
                }
                if ($datas[$list['aid']]) {
                    $data = json_decode($datas[$list['aid']], true);
                    $lists[$k]['title'] = $data['title'];
                    $lists[$k]['ctime'] = date("Y-m-d H:i:s", $data['pubdate']);
                }
                $lists[$k]['atype_desc'] = self::getAtype($list['atype']);
                if ($gamenames[$list['gid']]) {
                    $lists[$k]['gameName'] = $gamenames[$list['gid']];
                }
                $channels = array();
                foreach ($cids as $cid) {
                    if ($jchannelcids[$list['aid']]) {
                        if (in_array($cid, $jchannelcids[$list['aid']])) {
                            if ($jchannelgamecids[$list['gid']]) {
                                if (in_array($cid, $jchannelgamecids[$list['gid']])) {
                                    if ($jchannelisblocks[$list['aid']][$cid] == '1') {
                                        $channels[$cid] = "&#10006";
                                    } else {
                                        $channels[$cid] = "&#10004";
                                    }
                                } else {
                                    $channels[$cid] = "&#10006";
                                }
                            } else {
                                $channels[$cid] = "&#10006";
                            }
                        } else {
                            $channels[$cid] = "&#10006";
                        }
                    }
                }
                $lists[$k]['channels'] = $channels;
                if ($list['atype'] == 5) {
                    if ($list['isblock'] == '1') {
                        $lists[$k]['op'] = '<a href="javascript:;" class="status" data-aid="' . $list['aid'] . '" data-isblock="0">启用</a><br>';
                    } else {
                        $lists[$k]['op'] = '<a href="javascript:;" class="status" data-aid="' . $list['aid'] . '" data-isblock="1">禁用</a><br>';
                    }
                } else {
                    $lists[$k]['op'] = '<a href="?c=jchanneldata&a=aedit&aid=' . $list['aid'] . '&atype=' . $list['atype'] . $constr.'">编辑</a><br>';
                }
            }
        }
        return $lists;
    }

}