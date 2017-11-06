<?php
/**
 * Description:渠道管理文章相关
 * Author: gradydong
 * Date: 2017/4/19
 * Time: 16:02
 * Copyright: Joyme.com
 */
if (!defined('IN')) die('bad request');
include_once(AROOT . 'controller' . DS . 'app.class.php');
use Joyme\core\Request;
use Joyme\qiniu\Qiniu_Utils;
use Joyme\net\Curl;

class jchanneldataController extends appController
{
    public function __construct()
    {
        parent::__construct();
    }

    //文章列表
    public function alist()
    {
        $conditions = array();
        $where = array('source' => 1);
        $where['isblock'] = 0;
        //创建时间开始
        $begintime = Request::getParam('begintime');
        //创建时间结束
        $endtime = Request::getParam('endtime');
        if ($begintime && empty($endtime)) {
            $gbegintime = strtotime($begintime);
            $where['pubdate'] = array('gt', $gbegintime);
            $conditions['begintime'] = $begintime;
        } elseif (empty($begintime) && $endtime) {
            $gendtime = strtotime("$endtime +1 days");
            $where['pubdate'] = array('lt', $gendtime);
            $conditions['endtime'] = $endtime;
        } elseif ($begintime && $endtime) {
            $gbegintime = strtotime($begintime);
            $gendtime = strtotime("$endtime +1 days");
            $where['pubdate'] = array(array('gt',$gbegintime),array('lt',$gendtime),'and');
            $conditions['begintime'] = $begintime;
            $conditions['endtime'] = $endtime;
        }

        $game_cids = '';
        $jchannelmodel = new jChannelModel();
        $jchannellists = $jchannelmodel->select("cid,ckey,channel_name", array(
            'isshow'=>1
        ), 'cid ASC', '', '');
        //合作渠道
        $cid = Request::getParam('cid');
        if ($cid) {
            $game_cids = $cid;
            $where['cid'] = array('in', $cid);
            $conditions['pcid'] = implode(",",$cid);
            if ($jchannellists) {
                foreach ($jchannellists as $k => $jchannellist) {
                    if (in_array($jchannellist['cid'], $cid)) {
                        $jchannellists[$k]['checked'] = 'checked="checked"';
                    } else {
                        $jchannellists[$k]['checked'] = '';
                    }
                }
            }
        } else {
            $pcid = Request::getParam('pcid');
            if($pcid){
                $cid = explode(',',$pcid);
                $game_cids = $cid;
                $where['cid'] = array('in', $cid);
                $conditions['pcid'] = implode(",",$cid);
                if ($jchannellists) {
                    foreach ($jchannellists as $k => $jchannellist) {
                        if (in_array($jchannellist['cid'], $cid)) {
                            $jchannellists[$k]['checked'] = 'checked="checked"';
                        } else {
                            $jchannellists[$k]['checked'] = '';
                        }
                    }
                }
            }else{
                $cids = array_column($jchannellists, 'cid');
                if ($cids) {
                    $where['cid'] = array('in', $cids);
                }
                if ($jchannellists) {
                    foreach ($jchannellists as $k => $jchannellist) {
                        $jchannellists[$k]['checked'] = '';
                    }
                }
                $where['isblock'] = array('in', array(0, 1));
            }
        }

        if ($game_cids) {
            $jchannelgame = new jChannelGameModel();
            $jchannelgamelists = $jchannelgame->select('gid', array(
                'cid' => array('in', $game_cids)
            ), '', '', '');
            if ($jchannelgamelists) {
                $gids = array_column($jchannelgamelists, 'gid');
                $gids = array_unique($gids);
                $where['gid'] = array('in', $gids);
            }
        }

        //标签类型,标签名称
        $group = 'aid';
        $labeltype = (int)Request::getParam('labeltype','1');
        $labelname = Request::getParam('labelname','');
        $conditions['labeltype'] = $labeltype;
        $conditions['labelname'] = $labelname;
        if($labeltype&&$labelname){
            if($labeltype==1){
                $where['datatype'] = 1;
                $group = 'aid';
                $jgamemodel = new jGameModel();
                $games = $jgamemodel->getsearchgame($labelname);
                if ($games) {
                    $gameIds = array_column($games, 'gameId');
                    $where['gid'] = array('in', $gameIds);
                    $jcgwhere['gid'] = array('in', $gameIds);
                }
            }elseif ($labeltype==2){
                $where['datatype'] = 2;
                $group = 'aid,gid';
                $jchannelgame = new jChannelGameModel();
                $jchannelgamelists = $jchannelgame->select('gid,gamename',array(
                    'datatype' => 2,
                    'gamename' => array('like','%'.$labelname.'%')
                ),'','','');
                if($jchannelgamelists){
                    $labelids = array_column($jchannelgamelists,'gid');
                    $where['gid'] = array('in', $labelids);
                    $jcgwhere['gid'] = array('in', $labelids);
                }else{
                    $where[1]=0;
                }
            }else{
                $where[1]=0;
            }
        }elseif ($labeltype&&empty($labelname)){
            $where['datatype'] = $labeltype;
            if($labeltype==1){
                $group = 'aid';
            }elseif ($labeltype==2){
                $group = 'aid,gid';
            }
        }elseif (empty($labeltype)&&$labelname){
            $where[1]=0;
        }else{
            $where['datatype'] = 1;
            $group = 'aid';
        }

        //文章标题
        $arctitle = Request::getParam('arctitle');
        if ($arctitle) {
            $articleinfo = $this->getarticleinfo($arctitle);
            if ($articleinfo) {
                $aids = array_column($articleinfo, 'id');
                $where['aid'] = array('in', $aids);
                $conditions['arctitle'] = $arctitle;
            } else {
                $where[1] = 0;
            }
        }

        $allatypes = jChannelDataModel::getAllAtypes();
        //文章类型
        $atype = Request::getParam('atype');
        if ($atype) {
            $where['atype'] = array('in', $atype);
            $conditions['patype'] = implode(",",$atype);
            foreach ($allatypes as $k => $allatype) {
                if (in_array($k, $atype)) {
                    $allatypes[$k]['checked'] = 'checked="checked"';
                }
            }
        }else{
            $patype = Request::getParam('patype');
            if($patype){
                $atype = explode(',',$patype);
                $where['atype'] = array('in', $atype);
                $conditions['patype'] = implode(",",$atype);
                foreach ($allatypes as $k => $allatype) {
                    if (in_array($k, $atype)) {
                        $allatypes[$k]['checked'] = 'checked="checked"';
                    }
                }
            }
        }
        $jchanneldatamodel = new jChannelDataModel();
        $psize = 20; //每页显示条数
        $pno = Request::get('pb_page', 1);
        $skip = 0;
        if ($pno) {
            $skip = (intval($pno) - 1) * $psize;
        }
        $constr = "";
        if($conditions){
            foreach ($conditions as $ck => $condition){
                $constr.="&".$ck."=".$condition;
            }
        }
        if($where['datatype'] == 1){
            $lists = $jchanneldatamodel->getGameLists($where,$psize, $skip,$jchannellists,$constr);
        }elseif ($where['datatype'] == 2){
            $lists = $jchanneldatamodel->getLabelLists($where,$psize, $skip,$jchannellists,$constr);
        }else{
            $lists = array();
        }
        $jchanneldatacount = $jchanneldatamodel->select('count(*) as num',$where,'','','',$group);
        $total = count($jchanneldatacount);
        $page = new pageModel();
        $page->mainPage(array('total' => $total, 'perpage' => $psize, 'nowindex' => $pno, 'pagebarnum' => 10));
        $phtml = $page->show(2, $conditions);
        $data = array(
            'begintime' => $begintime,
            'endtime' => $endtime,
            'arctitle' => $arctitle,
            'labeltype' => $labeltype,
            'labelname' => $labelname,
            'allatypes' => $allatypes,
            'jchannellists' => $jchannellists,
            'list' => $lists,
            'total' => $total,
            'phtml' => $phtml,
            'pno' => $pno,
            'constr' => $constr
        );
        render($data, 'web', 'jchanneldata/alist');
    }

    public function aedit()
    {
        $aid = Request::getParam('aid');
        $atype = (int)Request::getParam('atype');
        if ($aid && $atype >= 0) {
            $jchanneldata = new jChannelDataModel();
            $jchanneldatalists = $jchanneldata->select('cid,gid,atype,litpic,isblock', array(
                'aid' => $aid,
                'atype' => $atype,
                'source' => 1
            ));
            $litpics = array();
            $isblock = array();
            $gid = array();
            if ($jchanneldatalists) {
                $litpics = array_column($jchanneldatalists, 'litpic', 'cid');
                $isblock = array_column($jchanneldatalists, 'isblock', 'cid');
                $gid = array_column($jchanneldatalists, 'gid');
            }
            $jchannelgame = new jChannelGameModel();
            $jchannelgamelists = $jchannelgame->select('cid', array(
                'gid' => array('in', $gid)
            ), '', '', '');
            $jcgcids = array();
            if ($jchannelgamelists) {
                $jcgcids = array_column($jchannelgamelists, 'cid');
            }

            $jchannel = new jChannelModel();
            $jchannellists = $jchannel->select('cid,ckey,channel_name', array(
                'isshow'=>1
            ), '', '');
            if ($jchannellists) {
                foreach ($jchannellists as $jk => $jchannellist) {
                    if (isset($litpics[$jchannellist['cid']])) {
                        $jchannellists[$jk]['litpic'] = $litpics[$jchannellist['cid']];
                    }
                    if (in_array($jchannellist['cid'], $jcgcids)) {
                        if (isset($isblock[$jchannellist['cid']])&&$isblock[$jchannellist['cid']] === '1') {
                            $jchannellists[$jk]['isblock_desc'] = "禁用";
                            $jchannellists[$jk]['isblock_op'] = '<a href="javascript:;" class="status" data-cid="' . $jchannellist['cid'] . '" data-isblock="0">启用</a><br>';
                            $jchannellists[$jk]['litpic_desc'] = '<i id="channel_' . $jchannellist['cid'] . '_' . $aid . '" class="table-in-upload" data-ckey="' . $jchannellist['ckey'] . '" style="width: 80px;height: 34px;display: inline-block; cursor: pointer; text-align: center;line-height: 34px;border-radius: 4px;">选择图片</i>';
                        } elseif (isset($isblock[$jchannellist['cid']])&&$isblock[$jchannellist['cid']] === '0') {
                            $jchannellists[$jk]['isblock_desc'] = "启用";
                            $jchannellists[$jk]['isblock_op'] = '<a href="javascript:;" class="status" data-cid="' . $jchannellist['cid'] . '" data-isblock="1">禁用</a><br>';
                            $jchannellists[$jk]['litpic_desc'] = '<i id="channel_' . $jchannellist['cid'] . '_' . $aid . '" class="table-in-upload"  data-ckey="' . $jchannellist['ckey'] . '"  style="width: 80px;height: 34px;display: inline-block; cursor: pointer; text-align: center;line-height: 34px;border-radius: 4px;">选择图片</i>';
                        } else {
                            $jchannellists[$jk]['isblock_desc'] = "未开通";
                            $jchannellists[$jk]['isblock_op'] = '<span>未开通</span>';
                            $jchannellists[$jk]['litpic_desc'] = '<span>选择图片</span>';
                        }
                    } else {
                        $jchannellists[$jk]['isblock_desc'] = "未开通";
                        $jchannellists[$jk]['isblock_op'] = '<span>未开通</span>';
                        $jchannellists[$jk]['litpic_desc'] = '<span>选择图片</span>';
                    }
                    $jchannellists[$jk]['litpic_rule'] = $this->getlitpicrule($jchannellist['ckey'], $atype);
                }
            }
            $jsourcedata = new jSourceDataModel();
            $jsourcedatalist = $jsourcedata->selectRow('data', array(
                'aid' => $aid,
                'source' => 1
            ));
            $alist = array();
            if ($jsourcedatalist) {
                $alist = json_decode($jsourcedatalist['data'], true);
            }
            $uptoken = $this->getUptoken();
            $data = array(
                'aid' => $aid,
                'atype' => $atype,
                'atype_desc' => jChannelDataModel::getAtype($atype),
                'jchannellists' => $jchannellists,
                'alist' => $alist,
                'uptoken' => $uptoken,
                'begintime' => Request::getParam('begintime',''),
                'endtime' => Request::getParam('endtime',''),
                'pcid' => Request::getParam('pcid',''),
                'labeltype' => Request::getParam('labeltype',1),
                'labelname' => Request::getParam('labelname',''),
                'arctitle' => Request::getParam('arctitle',''),
                'patype' => Request::getParam('patype','')
            );
            render($data, 'web', 'jchanneldata/aedit');
        } else {
            echo '参数错误 <a href="?c=jchanneldata&a=alist">返回列表</a>';
        }
    }

    //获取
    public function getlitpicrule($ckey, $atype)
    {
        $litpicrule = '';
        if ($ckey == 'baiduglb') {
            if ($atype == '0') {
                $litpicrule = '尺寸要求：比例1:1，不低于120*120';
            } elseif ($atype == '1') {
                $litpicrule = '尺寸要求：比例4:3，不低于180*135，建议200*150以上';
            } elseif ($atype == '4') {
                $litpicrule = '尺寸要求：比例1:1，不低于120*120';
            }
        }
        elseif ($ckey == 'sougoupc') {
            $litpicrule = '尺寸要求：202*152';
        }
        elseif ($ckey == 'sougouwap') {
            $litpicrule = '尺寸要求：120*90';
        }
        elseif ($ckey == 'baidugl') {
            $litpicrule = '尺寸要求：90*65';
        }
        elseif ($ckey == 'aliyunos'){
            $litpicrule = '建议尺寸：宽高比26:19，尺寸312x228';
        }
        elseif ($ckey == 'sougouclient'){
            $litpicrule = '建议尺寸：宽度大于等于 720px，文件限 300kb';
        }
        return $litpicrule;
    }


    //修改资料类文章状态
    public function articlestatus()
    {
        $aid = Request::getParam('aid');
        $isblock = Request::getParam('isblock');
        if ($aid && is_numeric($aid)
            && is_numeric($isblock)
        ) {
            $jchanneldatamodel = new jChannelDataModel();
            $errno = $jchanneldatamodel->update(
                array(
                    'isblock' => $isblock
                ),
                array(
                    'aid' => (int)$aid,
                    'source' => 1,
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


    //修改其他类型文章状态
    public function aeditisblock()
    {
        $aid = Request::getParam('aid');
        $cid = Request::getParam('cid');
        $isblock = (int)Request::getParam('isblock');
        if ($cid && $isblock >= 0) {
            $jchanneldatamodel = new jChannelDataModel();
            $errno = $jchanneldatamodel->update(
                array(
                    'isblock' => $isblock
                ),
                array(
                    'aid' => (int)$aid,
                    'cid' => (int)$cid,
                    'source' => 1,
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

    //修改渠道缩略图地址
    public function aeditlitpic()
    {
        $cid = (int)Request::getParam('cid');
        $aid = (int)Request::getParam('aid');
        $litpic = Request::getParam('litpic');
        if ($cid && $aid && $litpic) {
            $jchanneldatamodel = new jChannelDataModel();
            $errno = $jchanneldatamodel->update(
                array(
                    'litpic' => $litpic
                ),
                array(
                    'cid' => $cid,
                    'aid' => $aid,
                    'source' => 1,
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


    //礼包列表
    public function giftlist()
    {
        global $GLOBALS;
        $conditions = $where = array('source' => 2);
        //创建时间开始
        $begintime = Request::getParam('begintime');
        //创建时间结束
        $endtime = Request::getParam('endtime');
        if ($begintime && empty($endtime)) {
            $gbegintime = strtotime($begintime);
            $where['pubdate'] = array('gt', $gbegintime);
            $conditions['begintime'] = $begintime;
        } elseif (empty($begintime) && $endtime) {
            $gendtime = strtotime("$endtime +1 days");
            $where['pubdate'] = array('lt', $gendtime);
            $conditions['endtime'] = $endtime;
        } elseif ($begintime && $endtime) {
            $gbegintime = strtotime($begintime);
            $gendtime = strtotime("$endtime +1 days");
            $where['pubdate'] = array(array('gt',$gbegintime),array('lt',$gendtime),'and');
            $conditions['begintime'] = $begintime;
            $conditions['endtime'] = $endtime;
        }
        //游戏名称
        $gamename = Request::getParam('gamename');
        $gid = (int)Request::getParam('gid');
        if ($gid) {
            $where['gid'] = $gid;
            $conditions['gid'] = $gid;
        } else {
            if ($gamename) {
                $jgamemodel = new jGameModel();
                $games = $jgamemodel->getsearchgame($gamename);
                if ($games) {
                    $gameids = array_column($games, 'gameId');
                    $where['gid'] = array('in', $gameids);
                    $conditions['gamename'] = $gamename;
                } else {
                    $where['1'] = 0;
                }
            }
        }
        $jchanneldatamodel = new jChannelDataModel();
        $total = $jchanneldatamodel->count($where);
        $psize = 20; //每页显示条数
        $pno = Request::get('pb_page', 1);
        $skip = 0;
        if ($pno) {
            $skip = (intval($pno) - 1) * $psize;
        }
        $lists = $jchanneldatamodel->select("aid,gid,isblock", $where, 'pubdate DESC', $psize, $skip);
        if ($lists) {
            //礼包内容
            $aids = array_column($lists, 'aid');
            $jsourcedatamodel = new jSourceDataModel();
            $jsourcedatalists = $jsourcedatamodel->select('aid,data', array(
                'source' => 2,
                'aid' => array('in', $aids)
            ));
            $datas = array();
            if ($jsourcedatalists) {
                $datas = array_column($jsourcedatalists, 'data', 'aid');
            }
            //游戏内容
            $gids = array_column($lists, 'gid');
            $jgamemodel = new jGameModel();
            $jgamelists = $jgamemodel->select('gid,extra', array(
                'gid' => array('in', $gids)
            ));
            $gameinfos = array();
            if ($jgamelists) {
                $gameinfos = array_column($jgamelists, 'extra', 'gid');
            }
            //搜狗wap对应的游戏是否禁用
            $jchannelmodel = new jChannelModel();
            $jchannellist = $jchannelmodel->selectRow('cid',array(
                'ckey' => 'sougouwap'
            ));
            $cid = 0;
            if($jchannellist){
                $cid = $jchannellist['cid'];
            }
            $jchannelgame = new jChannelGameModel();
            $jchannelgamelists = $jchannelgame->select('gid', array(
                'gid' => array('in', $gids),
                'cid' => $cid,
                'gamestatus' => 1
            ),'','','');
            $jchannelgids = array();
            if($jchannelgamelists){
                $jchannelgids = array_column($jchannelgamelists,'gid');
            }
            foreach ($lists as $k => $list) {
                if ($datas[$list['aid']]) {
                    $data = json_decode($datas[$list['aid']], true);
                    $lists[$k]['activityUrl'] = "http://www.joyme." . $GLOBALS['domain'] . "/gift/" . $data['activityGoodsId'];
                    $lists[$k]['activitySubject'] = $data['activitySubject'];
                    $lists[$k]['start_time'] = date("Y-m-d H:i:s", strtotime($data['startTime']));
                    $endTime = strtotime($data['endTime']);
                    $lists[$k]['end_time'] = date("Y-m-d H:i:s", $endTime);
                    if ($data['actStatus']['code'] == 'y') {
                        $lists[$k]['remove_status'] = '可用';
                        if ($list['isblock'] == 1) {
                            $lists[$k]['op_desc'] = '<a href="javascript:;" class="status" data-aid="' . $list['aid'] . '" data-isblock="0">启用</a><br>';
                            $lists[$k]['sougouwap_desc'] = '&#10006';
                        } else {
                            $lists[$k]['op_desc'] = '<a href="javascript:;" class="status" data-aid="' . $list['aid'] . '" data-isblock="1">禁用</a><br>';
                            if(in_array($list['gid'],$jchannelgids)){
                                $lists[$k]['sougouwap_desc'] = '&#10004';
                            }else{
                                $lists[$k]['sougouwap_desc'] = '&#10006';
                            }
                        }
                    } else {
                        $lists[$k]['remove_status'] = '不可用';
                        $lists[$k]['op_desc'] = '<span>---</span>';
                        $lists[$k]['sougouwap_desc'] = '&#10006';
                    }
                }
                if ($gameinfos[$list['gid']]) {
                    $gameinfo = json_decode($gameinfos[$list['gid']], true);
                    $lists[$k]['wikiKey'] = $gameinfo['wikiKey'];
                    $lists[$k]['gameName'] = $gameinfo['gameName'];
                }
            }
        }
        $page = new pageModel();
        $page->mainPage(array('total' => $total, 'perpage' => $psize, 'nowindex' => $pno, 'pagebarnum' => 10));
        $phtml = $page->show(2, $conditions);
        $data = array(
            'begintime' => $begintime,
            'endtime' => $endtime,
            'gamename' => $gamename,
            'total' => $total,
            'list' => $lists,
            'phtml' => $phtml,
            'pno' => $pno
        );
        render($data, 'web', 'jchanneldata/giftlist');
    }

    //修改礼包状态
    public function giftstatus()
    {
        $aid = Request::getParam('aid');
        $isblock = Request::getParam('isblock');
        if ($aid && is_numeric($aid)
            && is_numeric($isblock)
        ) {
            $jchanneldatamodel = new jChannelDataModel();
            $errno = $jchanneldatamodel->update(
                array(
                    'isblock' => $isblock
                ),
                array(
                    'aid' => (int)$aid,
                    'source' => 2,
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

    //获取文章相关信息
    public function getarticleinfo($title)
    {
        global $GLOBALS;
        if ($title) {
            $url = "http://article.joyme." . $GLOBALS['domain'] . "/plus/channelapi.php";
            $curl = new Curl();
            $result = $curl->Get($url, array(
                'action' => 'searchtitle',
                'title' => $title
            ));
            $result = json_decode($result, true);
            if ($result['rs'] == '1') {
                return $result['result'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    private function getUptoken()
    {
        $bucket = $GLOBALS['config']['qiniu']['bucket'];
        return Qiniu_Utils::Qiniu_UploadToken($bucket);
    }

}