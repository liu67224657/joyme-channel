<?php

/**
 * Description:渠道管理游戏相关
 * Author: gradydong
 * Date: 2017/4/19
 * Time: 16:20
 * Copyright: Joyme.com
 */
if (!defined('IN')) die('bad request');
include_once(AROOT . 'controller' . DS . 'app.class.php');
use Joyme\core\Request;
use Joyme\qiniu\Qiniu_Utils;
use Joyme\net\Curl;

class jgameController extends appController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $conditions = $jcgwhere = $where = array();

        $jchannelmodel = new jChannelModel();
        //标签类型
        $labeltype = (int)Request::getParam('labeltype','1');
        $labelname = Request::getParam('labelname');
        if($labeltype&&$labelname){
            $conditions['labeltype'] = $labeltype;
            $conditions['labelname'] = $labelname;
            if($labeltype==1){
                $jcgwhere['datatype'] = $where['datatype'] = 1;
                $jgamemodel = new jGameModel();
                $games = $jgamemodel->getsearchgame($labelname);
                if ($games) {
                    $gameIds = array_column($games, 'gameId');
                    $where['gid'] = array('in', $gameIds);
                    $jcgwhere['gid'] = array('in', $gameIds);
                }else{
                    $jcgwhere[1]=$where[1]=0;
                }
            }elseif ($labeltype==2){
                $jcgwhere['datatype'] = $where['datatype'] = 2;
                $jcgwhere['gamename'] = $where['gamename'] = array('like','%'.$labelname.'%');
            }else{
                $where[1]=0;
            }
        }elseif ($labeltype&&empty($labelname)){
            $conditions['labeltype'] = $labeltype;
            $jcgwhere['datatype'] = $where['datatype'] = $labeltype;
        }elseif (empty($labeltype)&&$labelname){
            $jcgwhere[1]=$where[1]=0;
        }

        //渠道id
        $cid = (int)Request::getParam('cid');
        if ($cid) {
            $where['cid'] = $cid;
            $conditions['cid'] = $cid;
        } else {
            $jchannellist = $jchannelmodel->selectRow("cid,ckey,channel_name",array(
                'isshow'=>1
            ));
            if ($jchannellist) {
                $cid = $where['cid'] = $jchannellist['cid'];
            }
        }

        $lists = array();
        $jchannelgamemodel = new jChannelGameModel();
        $total = $jchannelgamemodel->count($where);
        $psize = 20; //每页显示条数
        $pno = Request::get('pb_page', 1);
        $skip = 0;
        if ($pno) {
            $skip = (intval($pno) - 1) * $psize;
        }
        $jchannelgamelists = $jchannelgamemodel->select("cid,gid,gamestatus,gamename,datatype", $where, 'id DESC', $psize, $skip);
        if ($jchannelgamelists) {
            $gids = array();
            foreach ($jchannelgamelists as $k => $v){
                if($v['datatype']==1){
                    $gids[] = $v['gid'];
                }
            }
            $extras = array();
            if($gids){
                $jgamemodel = new jGameModel();
                $jgamelists = $jgamemodel->select('gid,extra', array(
                    'gid' => array('in', $gids)
                ));
                if ($jgamelists) {
                    $extras = array_column($jgamelists, 'extra', 'gid');
                }
            }
            $lists = array();
            foreach ($jchannelgamelists as $jk => $jchannelgamelist) {
                $lists[$jk]['gid'] = $jchannelgamelist['gid'];
                if($jchannelgamelist['datatype']==1){
                    $lists[$jk]['datatype'] = '游戏';
                    if ($extras[$jchannelgamelist['gid']]) {
                        $extra = json_decode($extras[$jchannelgamelist['gid']], true);
                        if($extra['gameName']){
                            $lists[$jk]['gameName'] = $extra['gameName'];
                        }else{
                            $lists[$jk]['gameName'] = "";
                        }
                        $validstatus = $extra['validStatus']['code'];
                        if ($validstatus == 'invalid') {
                            $lists[$jk]['validstatus'] = '审核中';
                        } elseif ($validstatus == 'valid') {
                            $lists[$jk]['validstatus'] = '审核通过';
                        } elseif ($validstatus == 'notvalid') {
                            $lists[$jk]['validstatus'] = '审核未通过';
                        } elseif ($validstatus == 'removed') {
                            $lists[$jk]['validstatus'] = '删除';
                        } else {
                            $lists[$jk]['validstatus'] = '审核中';
                        }
                    }
                }elseif ($jchannelgamelist['datatype']==2){
                    $lists[$jk]['datatype'] = '栏目';
                    $lists[$jk]['gameName'] = $jchannelgamelist['gamename'];
                    $lists[$jk]['validstatus'] = '';
                }
                $op_desc = '';
                if ($jchannelgamelist['gamestatus'] == 1) {
                    $op_desc .= '<a href="?c=jgame&a=edit&gid=' . $jchannelgamelist['gid'] . '&datatype='.$jchannelgamelist['datatype'].'">编辑</a><br><a href="javascript:;" class="status" data-gid="' . $jchannelgamelist['gid'] . '" data-status="0">禁用</a><br>';
                } else {
                    $op_desc .= '<span>编辑</span><br><a href="javascript:;" class="status" data-gid="' . $jchannelgamelist['gid'] . '" data-status="1">启用</a><br>';
                }
                $lists[$jk]['op_desc'] = $op_desc;
            }
        }
        $page = new pageModel();
        $page->mainPage(array('total' => $total, 'perpage' => $psize, 'nowindex' => $pno, 'pagebarnum' => 10));
        $phtml = $page->show(2, $conditions);

        $jchannellists = $jchannelmodel->select("cid,ckey,channel_name", array(
            'isshow'=>1
        ), '', '');
        if ($jchannellists) {
            $jchannelgamecount = $jchannelgamemodel->select("cid,count(*) as num", $jcgwhere, '', '', '', 'cid');
            if ($jchannelgamecount) {
                $nums = array_column($jchannelgamecount, 'num', 'cid');
                foreach ($jchannellists as $jk => $jchannellist) {
                    if ($nums[$jchannellist['cid']]) {
                        $jchannellists[$jk]['num'] = $nums[$jchannellist['cid']];
                    } else {
                        $jchannellists[$jk]['num'] = 0;
                    }
                }
            }
        }

        $data = array(
            'cid' => $cid,
            'labeltype' => $labeltype,
            'labelname' => $labelname,
            'jchannellists' => $jchannellists,
            'list' => $lists,
            'total' => $total,
            'phtml' => $phtml,
            'pno' => $pno
        );
        render($data, 'web', 'jgame/list');
    }


    public function add()
    {
        $jchannelmodel = new jChannelModel();
        $jchannellists = $jchannelmodel->select("cid,ckey,channel_name", array(
            'isshow'=>1
        ), '', '', '');
        $uptoken = $this->getUptoken();
        $data = array(
            'jchannellists' => $jchannellists,
            'uptoken' => $uptoken
        );
        render($data, 'web', 'jgame/add');
    }

    public function addPro()
    {
        $cids = Request::getParam('cids');
        $datatype = (int)Request::getParam('datatype');
        if (!empty($cids) && !empty($datatype)) {
            if($datatype==1){
                $gid = (int)Request::getParam('gid');
                $jchannelmodel = new jChannelModel();
                $jchannellists = $jchannelmodel->select("cid,ckey", array(
                    'isshow'=>1
                ), '', '', '');
                $ckeys = array();
                if ($jchannellists) {
                    $ckeys = array_column($jchannellists, 'ckey', 'cid');
                }
                //添加渠道游戏数据
                $jchannelgame = new jChannelGameModel();
                foreach ($cids as $cid) {
                    $cgdata = array(
                        'cid' => $cid,
                        'gid' => $gid,
                        'datatype' => $datatype
                    );
                    if (isset($ckeys[$cid])) {
                        $keygamename = Request::getParam($ckeys[$cid] . 'gamename');
                        if ($keygamename) {
                            $cgdata['gamename'] = $keygamename;
                        }
                    }
                    $jchannelgame->addData($cgdata);
                }


                //添加游戏数据
                $jgame = new jGameModel();
                $gdata = array();
                //缩略图地址
                $litpic = Request::getParam('litpic');
                if (!empty($litpic)) {
                    $gdata['litpic'] = $litpic;
                }
                //游戏分类地址
                $gametypeurl = Request::getParam('gametypeurl');
                if (!empty($gametypeurl)) {
                    $gdata['gametypeurl'] = $gametypeurl;
                }
                //资讯地址
                $listnewsurl = Request::getParam('listnewsurl');
                if (!empty($listnewsurl)) {
                    $gdata['listnewsurl'] = $listnewsurl;
                }
                //攻略地址
                $liststrategyurl = Request::getParam('liststrategyurl');
                if (!empty($liststrategyurl)) {
                    $gdata['liststrategyurl'] = $liststrategyurl;
                }
                //视频地址
                $listvideourl = Request::getParam('listvideourl');
                if (!empty($listvideourl)) {
                    $gdata['listvideourl'] = $listvideourl;
                }
                //礼包地址
                $listgifturl = Request::getParam('listgifturl');
                if (!empty($listgifturl)) {
                    $gdata['listgifturl'] = $listgifturl;
                }
                //排行
                $rank = Request::getParam('rank');
                if (!empty($rank)) {
                    $gdata['rank'] = $rank;
                }
                //排行链接
                $rankurl = Request::getParam('rankurl');
                if (!empty($rankurl)) {
                    $gdata['rankurl'] = $rankurl;
                }
                //人气
                $follow = Request::getParam('follow');
                if (!empty($follow)) {
                    $gdata['follow'] = $follow;
                }

                //游戏类型
                $sougoupcgametype = Request::getParam('sougoupcgametype');
                if (!empty($sougoupcgametype)) {
                    //游戏附加字段json存储
                    $extra = array();
                    $jgamelist = $jgame->selectRow('extra', array('gid' => $gid));
                    if ($jgamelist) {
                        $extra = json_decode($jgamelist['extra'], true);
                    }
                    $extra['sougoupcgametype'] = $sougoupcgametype;
                    $gdata['extra'] = json_encode($extra);
                }
                $res = $jgame->update($gdata, array(
                    'gid' => $gid
                ));
                if ($res !== false) {
                    echo '添加成功 <a href="?c=jgame&a=index">返回列表</a>';
                    exit();
                } else {
                    echo '添加失败 <a href="?c=jgame&a=index">返回列表</a>';
                    exit();
                }
            }elseif ($datatype==2){
                $gid = Request::getParam('labelid');
                $gamename = Request::getParam('labelname');
                //添加渠道游戏数据
                $jchannelgame = new jChannelGameModel();
                $addflag = true;
                foreach ($cids as $cid) {
                    $cgdata = array(
                        'cid' => $cid,
                        'gid' => $gid,
                        'gamename' => $gamename,
                        'datatype' => $datatype
                    );
                    $res = $jchannelgame->addData($cgdata);
                    if($res===false){
                        $addflag =false;
                        break;
                    }
                }
                if ($addflag) {
                    echo '添加成功 <a href="?c=jgame&a=index">返回列表</a>';
                    exit();
                } else {
                    echo '添加失败 <a href="?c=jgame&a=index">返回列表</a>';
                    exit();
                }
            }else{
                echo '参数错误 <a href="?c=jgame&a=index">返回列表</a>';
                exit();
            }
        } else {
            echo '参数错误 <a href="?c=jgame&a=index">返回列表</a>';
            exit();
        }
    }

    public function edit()
    {
        $datatype = (int)Request::getParam('datatype');
        $gid = (int)Request::getParam('gid');
        if ($gid && $datatype) {
            $jchannelmodel = new jChannelModel();
            $jchannellists = $jchannelmodel->select("cid,ckey,channel_name", array(
                'isshow'=>1
            ), 'cid DESC', '', '');
            $jchannelgamemodel = new jChannelGameModel();
            $jchannelgamelists = $jchannelgamemodel->select('cid,gamename', array(
                'gid' => $gid,
                'datatype' => $datatype
            ));
            $jcgcids = array();
            $jcggamenames = array();
            $labelname = '';
            if ($jchannelgamelists) {
                $labelname = $jchannelgamelists[0]['gamename'];
                $jcgcids = array_column($jchannelgamelists, 'cid');
                $jcggamenames = array_column($jchannelgamelists, 'gamename', 'cid');
            }
            $baiduglbgroup = "none";
            $sougoupcgroup = "none";
            $sougouwapgroup = "none";
            $sgcomgroup = "none";
            $baiduglbgamename = "";
            $sougoupcgamename = "";
            $sougouwapgamename = "";
            foreach ($jchannellists as $jk => $jchannellist) {
                if (in_array($jchannellist['cid'], $jcgcids)) {
                    $jchannellists[$jk]['checked'] = 'checked="checked"';
                    if ($jchannellist['ckey'] == "baiduglb") {
                        $baiduglbgroup = "block";
                        if ($jcggamenames[$jchannellist['cid']]) {
                            $baiduglbgamename = $jcggamenames[$jchannellist['cid']];
                        }
                    }
                    if ($jchannellist['ckey'] == "sougoupc") {
                        $sougoupcgroup = "block";
                        if ($jcggamenames[$jchannellist['cid']]) {
                            $sougoupcgamename = $jcggamenames[$jchannellist['cid']];
                        }
                    }
                    if ($jchannellist['ckey'] == "sougouwap") {
                        $sougouwapgroup = "block";
                        if ($jcggamenames[$jchannellist['cid']]) {
                            $sougouwapgamename = $jcggamenames[$jchannellist['cid']];
                        }
                    }
                } else {
                    $jchannellists[$jk]['checked'] = '';
                }
            }
            if ($sougoupcgroup == "block") {
                $sgcomgroup = "block";
            } else {
                if ($sougouwapgroup == "block") {
                    $sgcomgroup = "block";
                }
            }
            $gamename = "";
            $wikikey = "";
            $jgamemodel = new jGameModel();
            $jgamelist = $jgamemodel->selectRow('*', array(
                'gid' => $gid
            ));
            $gtypechecked = "ONLINEGAME";
            if ($jgamelist) {
                $extra = json_decode($jgamelist['extra'], true);
                if ($extra['sougoupcgametype']) {
                    if ($extra['sougoupcgametype'] == "PCGAME") {
                        $gtypechecked = "PCGAME";
                    } else {
                        $gtypechecked = "ONLINEGAME";
                    }
                }
                $gamename = $extra['gameName'];
                $wikikey = $extra['wikiKey'];
            }
            $uptoken = $this->getUptoken();
            $data = array(
                'gid' => $gid,
                'datatype' => $datatype,
                'jchannellists' => $jchannellists,
                'jgamelist' => $jgamelist,
                'baiduglbgroup' => $baiduglbgroup,
                'sougoupcgroup' => $sougoupcgroup,
                'sougouwapgroup' => $sougouwapgroup,
                'sgcomgroup' => $sgcomgroup,
                'baiduglbgamename' => $baiduglbgamename,
                'sougoupcgamename' => $sougoupcgamename,
                'sougouwapgamename' => $sougouwapgamename,
                'uptoken' => $uptoken,
                'labelname' => $labelname,
                'gamename' => $gamename,
                'wikikey' => $wikikey,
                'gtypechecked' => $gtypechecked,
            );
            render($data, 'web', 'jgame/edit');
        } else {
            echo '参数错误 <a href="?c=jgame&a=index">返回列表</a>';
            exit();
        }
    }

    public function editPro()
    {
        $cids = Request::getParam('cids');
        $gid = Request::getParam('gid');
        $datatype = Request::getParam('datatype');
        if (!empty($cids) && !empty($gid) && !empty($datatype)) {
            $jchannelmodel = new jChannelModel();
            $jchannellists = $jchannelmodel->select("cid,ckey", array(
                'isshow'=>1
            ), '', '', '');
            $ckeys = array();
            if ($jchannellists) {
                $ckeys = array_column($jchannellists, 'ckey', 'cid');
            }
            //添加渠道游戏数据
            $jchannelgame = new jChannelGameModel();
            $jchannelgamelists = $jchannelgame->select('cid',array(
                'gid' => $gid
            ), '', '', '');
            if($jchannelgamelists){
                foreach ($jchannelgamelists as $jchannelgamelist){
                    if(!in_array($jchannelgamelist['cid'],$cids)){
                        $jchannelgame->delete(array(
                            'cid' => $jchannelgamelist['cid'],
                            'gid' => $gid,
                            'datatype' => $datatype
                        ));
                    }
                }
            }
            if($datatype==1){
                foreach ($cids as $cid) {
                    $cgdata = array(
                        'cid' => $cid,
                        'gid' => $gid,
                        'datatype' => $datatype
                    );
                    if (isset($ckeys[$cid])) {
                        $keygamename = Request::getParam($ckeys[$cid] . 'gamename');
                        if ($keygamename) {
                            $cgdata['gamename'] = $keygamename;
                        }
                    }
                    $jchannelgame->addData($cgdata);
                }
                //添加游戏数据
                $jgame = new jGameModel();
                $gdata = array();
                //缩略图地址
                $litpic = Request::getParam('litpic');
                if (!empty($litpic)) {
                    $gdata['litpic'] = $litpic;
                }
                //游戏分类地址
                $gametypeurl = Request::getParam('gametypeurl');
                if (!empty($gametypeurl)) {
                    $gdata['gametypeurl'] = $gametypeurl;
                }
                //资讯地址
                $listnewsurl = Request::getParam('listnewsurl');
                if (!empty($listnewsurl)) {
                    $gdata['listnewsurl'] = $listnewsurl;
                }
                //攻略地址
                $liststrategyurl = Request::getParam('liststrategyurl');
                if (!empty($liststrategyurl)) {
                    $gdata['liststrategyurl'] = $liststrategyurl;
                }
                //视频地址
                $listvideourl = Request::getParam('listvideourl');
                if (!empty($listvideourl)) {
                    $gdata['listvideourl'] = $listvideourl;
                }
                //礼包地址
                $listgifturl = Request::getParam('listgifturl');
                if (!empty($listgifturl)) {
                    $gdata['listgifturl'] = $listgifturl;
                }
                //排行
                $rank = Request::getParam('rank');
                if (!empty($rank)) {
                    $gdata['rank'] = $rank;
                }
                //排行链接
                $rankurl = Request::getParam('rankurl');
                if (!empty($rankurl)) {
                    $gdata['rankurl'] = $rankurl;
                }
                //人气
                $follow = Request::getParam('follow');
                if (!empty($follow)) {
                    $gdata['follow'] = $follow;
                }
                //游戏附加字段json存储
                $extra = array();
                $jgamelist = $jgame->selectRow('extra', array('gid' => $gid));
                if ($jgamelist) {
                    $extra = json_decode($jgamelist['extra'], true);
                }
                //游戏类型
                $sougoupcgametype = Request::getParam('sougoupcgametype');
                if (!empty($sougoupcgametype)) {
                    $extra['sougoupcgametype'] = $sougoupcgametype;
                }
                if ($extra) {
                    $gdata['extra'] = json_encode($extra);
                }
                $res = $jgame->update($gdata, array(
                    'gid' => $gid
                ));
                if ($res !== false) {
                    echo '修改成功 <a href="?c=jgame&a=index">返回列表</a>';
                } else {
                    echo '修改失败 <a href="?c=jgame&a=index">返回列表</a>';
                }
            }
            elseif ($datatype==2){
                $labelname = Request::getParam('labelname');
                $editflag = true;
                foreach ($cids as $cid) {
                    $cgdata = array(
                        'cid' => $cid,
                        'gid' => $gid,
                        'gamename' => $labelname,
                        'datatype' => $datatype
                    );
                    $res = $jchannelgame->addData($cgdata);
                    if($res===false){
                        $editflag = false;
                        break;
                    }
                }
                if ($editflag) {
                    echo '修改成功 <a href="?c=jgame&a=index">返回列表</a>';
                } else {
                    echo '修改失败 <a href="?c=jgame&a=index">返回列表</a>';
                }
            }
        } else {
            echo '参数错误 <a href="?c=jgame&a=index">返回列表</a>';
        }
        exit();
    }


    public function editgamestatus()
    {
        $gid = (int)Request::getParam('gid');
        $cid = (int)Request::getParam('cid');
        $status = (int)Request::getParam('status');
        if ($gid && $cid) {
            $jchannelgamemodel = new jChannelGameModel();
            $errno = $jchannelgamemodel->update(
                array(
                    'gamestatus' => $status
                ),
                array(
                    'gid' => $gid,
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

    public function searchgame()
    {
        $searchtext = Request::getParam('searchtext');
        if ($searchtext) {
            $jgamemodel = new jGameModel();
            $result = $jgamemodel->getsearchgame($searchtext);
            if ($result) {
                $res = array('rs' => 1, 'msg' => $result);
            } else {
                $res = array('rs' => 2, 'msg' => '查询失败');
            }
        } else {
            $res = array('rs' => 2, 'msg' => '参数不能为空');
        }
        jsonEncode($res);
    }


    //获取文章栏目
    public function getarctype()
    {
        global $GLOBALS;
        $typeid = Request::getParam('typeid');
        if ($typeid) {
            $jchannelgame = new jChannelGameModel();
            $jchannelgamecount = $jchannelgame->count(array(
                'gid' => $typeid,
                'datatype' => 2
            ));
            if($jchannelgamecount){
                $res = array('rs' => 3, 'msg' => '该栏目已添加');
            }else{
                $url = "http://article.joyme." . $GLOBALS['domain'] . "/plus/channelapi.php";
                $curl = new Curl();
                $result = $curl->Get($url, array(
                    'action' => 'searcharctype',
                    'typeid' => $typeid
                ));
                $result = json_decode($result, true);
                if ($result['rs'] == '1') {
                    if($result['result']){
                        $res = array('rs' => 1, 'msg' => $result['result']);
                    }else{
                        $res = array('rs' => 2, 'msg' => '查询结果为空');
                    }
                } else {
                    $res = array('rs' => 2, 'msg' => '查询失败');
                }
            }
        } else {
            $res = array('rs' => 2, 'msg' => '参数不能为空');
        }
        jsonEncode($res);
    }


    private function getUptoken()
    {
        $bucket = $GLOBALS['config']['qiniu']['bucket'];
        return Qiniu_Utils::Qiniu_UploadToken($bucket);
    }

}