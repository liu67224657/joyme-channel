<?php

if (!defined('IN')) die('bad request');
/**
 * Created by PhpStorm.
 * User: pengzhang
 * Date: 2017/4/12
 * Time: 下午4:05
 */
include_once(AROOT . 'controller' . DS . 'sourceAbstract.class.php');

use Joyme\core\Request;
use Joyme\net\RedisHelper;

class sourceBaiduglbController extends sourceAbstractController
{
    //渠道字段
    public $fields = array('indexData', 'title', 'image', 'url', 'pubtime', 'category');


    /**
     * 接口查询
     */
    public function query($cid)
    {
        //获取参数
        $gameid = Request::getParam('gameid', '');

        if(empty($gameid)){
            return 'no gameid';
        }

        //查询游戏信息
        $jChannelGameModel = new jChannelGameModel();
        $where0 = array('cid' => $cid, 'gid'=>$gameid, 'gamestatus' => 1);
        $gameinfo = $jChannelGameModel->getRowData($where0);

        if(empty($gameinfo)){
            return 'no this game';
        }

        //获取参数
        //atype:类型 0资讯类 1视频类 4攻略类 5资料类
        $atype = Request::getParam('type', '');

        switch ($atype){
            case 'data':
                $atype = 5;
                break;
            case 'news':
                $atype = 0;
                break;
            case 'strategy':
                $atype = 4;
                break;
            case 'video':
                $atype = 1;
                break;
            default:
                return 'no this type';
        }

        //查询渠道数据
        $jChannelDataModel = new jChannelDataModel();
        $where = array('atype' => $atype, 'gid'=>$gameid, 'cid' => $cid, 'source' => 1, 'isblock' => 0);
        $data = $jChannelDataModel->getData($where, 300);

        $aids = '0';
        foreach ($data as $val) {
            $aids .= ',' . $val['aid'];
        }
        //查询附加数据
        $jSourceDataModel = new jSourceDataModel();
        $where2 = array('source' => 1, 'aid' => array('in', $aids));
        $sourcedata = $jSourceDataModel->getData($where2, 300);

        $newdata = "";
        //合并数据
        foreach ($data as $k => $val) {
            foreach ($sourcedata as $row) {
                if ($val['aid'] == $row['aid']) {
                    $tmp = json_decode($row['data'], true);
                    if ($tmp) {
                        $tag = explode(',',$tmp['keywords']);
                        $tmparr = array(
                            'indexData' => $gameinfo['gamename'],
                            'title' => $tmp['title'],
                            'image' => $val['litpic'],
                            'url' => $val['url'],
                            'pubtime' => $val['pubdate'],
                            'category' => $tag[0]
                        );
                        if ($atype == 1) {
                            $tmparr['playTime'] = 180;
                        } else if ($atype == 5) {
                            $tmparr['childCategory'] = '';
                        }
                        $newdata .= json_encode($tmparr)."\n";
                    }
                }
            }
        }

        if(empty($newdata)){
            return 'no data';
        }

        return $newdata;
    }


}