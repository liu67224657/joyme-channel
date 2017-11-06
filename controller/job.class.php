<?php

if (!defined('IN')) die('bad request');
/**
 * Created by PhpStorm.
 * User: pengzhang
 * Date: 2017/4/12
 * Time: 下午4:05
 */
include_once(CROOT . 'controller' . DS . 'core.class.php');

use Joyme\core\Request;
use Joyme\core\Log;

class jobController extends coreController
{
    public function index()
    {
        $this->jobprint('开始导入');
        //查询渠道信息
        $this->jobprint('开始查询渠道信息');
        $jchannelmodel = new jChannelModel();
        $tplArr = $jchannelmodel->select("cid,template,size", array());

        $tplCidArr = array_column($tplArr, 'cid');
        $tplArr = array_combine($tplCidArr, $tplArr);

        //查询栏目信息
        $this->jobprint('开始查询栏目信息');
        $lanmuArr = array(1339,417,1334);
        $arctypeModel = M('cmsArctypeModel');
        $cidlist = $arctypeModel->select('id,typedir,namerule,channelcids',array('id'=>array('in',$lanmuArr)));

        $cids = array_column($cidlist,'id');
        $cidlist = array_combine($cids,$cidlist);
        $oknum = $failnum = 0;

        //查询所属的栏目列表
        $channelGameModel = new jChannelGameModel();
        $channelgameRs = $channelGameModel->select("cid,gid", array('cid' => 6, 'datatype' => 2));

        $channelgameArr = array();
        foreach($channelgameRs as $v){
            $channelgameArr[$v['cid']][] = $v['gid'];
        }

        /*
        //查询游戏库的文章列表
        $this->jobprint('开始查询游戏库文章');
        $atype = 0;
        $cmsArcModel = M('cmsArcModel');
        $sql = $this->getsql();
        $taglistrs = $cmsArcModel->excuteSql($sql);

        $aids = array_column($taglistrs,'aid');
        $taglist = array_combine($aids,$taglistrs);

        //查询cms基础信息
        $cmsDataList = $cmsArcModel->select('*',array('id'=>array('in',$aids),'typeid'=>array('in',$lanmuArr)),'',10000);

        foreach($cmsDataList as $v){
            $gid = empty($taglist[$v['id']]['gid'])?'':$taglist[$v['id']]['gid'];
            $cid = 5;
            $extra = json_encode($v);
            $columninfo = $cidlist[$v['typeid']];
            $aurl = $this->getUrl($v['id'],$v['senddate'],$columninfo);
            $rs = $this->savedata($v['id'], $cid, $gid, $atype, $extra, $v['pubdate'], $aurl, $tplArr,$channelgameArr);
            if($rs){
                $oknum+=1;
            }else{
                $failnum+=1;
                $this->jobprint('ID: '.$v['id'].'导入失败');
            }
        }
        $this->jobprint('导入游戏库文章完毕，成功了'.$oknum.'条，失败了'.$failnum.'条。');
        */
        //查询栏目的文章列表
        $atype = 0;
        $cmsArcModel = M('cmsArcModel');
        $this->jobprint('开始查询栏目文章');
        $sql1 = $this->getlanmusql(1339);
        $sql2 = $this->getlanmusql(417);
        $sql3 = $this->getlanmusql(1334);
        $taglist1 = $cmsArcModel->excuteSql($sql1);
        $taglist2 = $cmsArcModel->excuteSql($sql2);
        $taglist3 = $cmsArcModel->excuteSql($sql3);

        $taglistall = array_merge($taglist1,$taglist2,$taglist3);

        $aids = array_column($taglistall,'aid');

        //查询cms基础信息
        $cmsDataList = $cmsArcModel->select('*',array('id'=>array('in',$aids)),'',10000);

        foreach($cmsDataList as $v){
            $gid = '';
            $cid = 6;
            $extra = json_encode($v);
            $columninfo = $cidlist[$v['typeid']];
            $aurl = $this->getUrl($v['id'],$v['senddate'],$columninfo);
            $rs = $this->savedata($v['id'], $cid, $gid, $atype, $extra, $v['pubdate'], $aurl, $tplArr,$channelgameArr);
            if($rs){
                $oknum+=1;
            }else{
                $failnum+=1;
                $this->jobprint('ID: '.$v['id'].'导入失败');
            }
        }

        $this->jobprint('全部导入完毕，成功了'.$oknum.'条，失败了'.$failnum.'条。');


    }
    public function getUrl($aid, $pubdate,$columninfo){
        if(!empty($columninfo['namerule']) && !empty($columninfo['typedir'])){
            $namerule = $columninfo['namerule'];
            $typedir = $columninfo['typedir'];
        }else{
            return '';
        }
        $url = $namerule;
        $Y = date('Y', $pubdate);
        $M = date('m', $pubdate);
        $D = date('d', $pubdate);

        $url = str_replace('{typedir}', $typedir, $url);
        $url = str_replace('{Y}', $Y, $url);
        $url = str_replace('{M}', $M, $url);
        $url = str_replace('{D}', $D, $url);
        $url = str_replace('{aid}', $aid, $url);
        $url = str_replace('{cmspath}', '', $url);
        return $url;
    }
    public function jobprint($str){
        echo $str." \n\n";
    }
    public function getsql(){
        //tagid 游戏库ID
        $sql = 'SELECT  r1.dede_archives_id as aid,r1.tagid as gid
FROM joymeapp.tag_dede_archives r1       
WHERE tagid in(72045,100665) and relation_type=1 and dede_archives_pubdate>=1488297600000;
';
        return $sql;
    }
    public function getlanmusql($typeids){
        $sql = 'select id as aid,typeid as gid from dede_archives where arcrank>-1 and ismake=1 and typeid in( '.$typeids.' ) order by id desc limit 800;';
        return $sql;
    }

    //上报cms
    public function savedata($aid, $cid, $gid, $atype, $extra, $pubdate, $aurl, $tplArr,$channelgameArr)
    {
        $source = 1;
        $extraArr = json_decode($extra, true);

        $litpic = empty($extraArr['litpic']) ? '' : $extraArr['litpic'];

        $sourcedata = array(
            'aid' => $aid,
            'data' => $extra,
            'source' => $source
        );


        $sourceDataModel = new jSourceDataModel();
        $sourceDataRs = $sourceDataModel->getRowData(array('aid' => $aid, 'source' => $source));
        if ($sourceDataRs) {
            $rs = $sourceDataModel->update(array('data' => $extra), array('aid' => $aid, 'source' => $source));
        } else {
            $rs = $sourceDataModel->addData($sourcedata);
        }

        //判断是否删除
        $isblock = 0;


        if ($rs !== false) {
            $channelDataModel = new jChannelDataModel();
            $url = '';
            $channeldata = array(
                'cid' => '',
                'gid' => $gid,
                'aid' => $aid,
                'atype' => $atype,
                'litpic' => $litpic,
                'source' => $source,
                'url' => $url,
                'pubdate' => $pubdate,
                'isblock' => $isblock
            );
            $cidArr = explode(',', $cid);


            foreach ($cidArr as $v) {
                if ($tplArr[$v]['template'] == 'pc') {
                    $urlpre = 'http://www.joyme.' . $GLOBALS['domain'];
                } else if ($tplArr[$v]['template'] == 'wap') {
                    $urlpre = 'http://m.joyme.' . $GLOBALS['domain'];
                } else {
                    $urlpre = 'http://www.joyme.' . $GLOBALS['domain'] . '/' . $tplArr[$v]['template'];
                }

                if ($atype == 5) {
                    $channeldata['url'] = $aurl;
                } else {
                    $channeldata['url'] = $urlpre . str_replace('article/pc/', '', $aurl);
                }
                $size = json_decode($tplArr[$v]['size'], true);
                if ($size && !empty($size[$atype])) {
                    $channeldata['litpic'] = $litpic . '?imageView2/1/w/' . $size[$atype]['w'] . '/h/' . $size[$atype]['h'];
                }

                $channeldata['cid'] = $v;

                $typeid = empty($extraArr['typeid']) ? 0 : $extraArr['typeid'];

                //判断渠道是否开启游戏或栏目
                if ($gid) {
                    $channeldata['datatype'] = 1;
                    $channeldata['gid'] = $gid;
                    $channelDataModel->addData($channeldata);
                }else if ($channelgameArr[$v] && in_array($typeid, $channelgameArr[$v])) {
                    $channeldata['datatype'] = 2;

                    $channeldata['gid'] = $typeid;
                    $channelDataModel->addData($channeldata);

                    $typeid2 = empty($extraArr['typeid2']) ? '' : $extraArr['typeid2'];
                    $typeid2Arr = explode(',', $typeid2);
                    if (!empty($typeid2Arr)) {
                        foreach ($typeid2Arr as $v2) {
                            if (in_array($v2, $channelgameArr[$v])) {
                                $channeldata['gid'] = $v2;
                                $channelDataModel->addData($channeldata);
                            }
                        }

                    }

                }
            }
            return true;
        } else {
            return false;
        }
    }

}