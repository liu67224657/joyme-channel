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

class sourceSougouwapController extends sourceAbstractController
{
    //渠道字段
    public $fields = array('indexData', 'title', 'image', 'url', 'pubtime', 'category');

    /**
     * 接口查询
     */
    public function query($cid)
    {
        global $GLOBALS;
        //查询所有的游戏
        $jChannelGameModel = new jChannelGameModel();
        $where = array('cid' => $cid, 'gamestatus' => 1);
        $channelgamelist = $jChannelGameModel->getData($where, 1000);

        $gids = '0';
        foreach ($channelgamelist as $val) {
            $gids .= ',' . $val['gid'];
        }

        //查询游戏附加数据
        $jGameModel = new jGameModel();
        $where2 = array( 'gid' => array('in', $gids));
        $gamelist = $jGameModel->getData($where2, 1000);

        foreach ($channelgamelist as $o => $p) {
            foreach ($gamelist as $k => $v) {
                if ($p['gid'] == $v['gid']) {
                    $temparr = json_decode($v['extra'],true);
                    $temparr = empty($temparr)?array():$temparr;
                    if(empty($temparr['wikiUrl'])){
                        $temparr['wikiUrl'] = "http://www.joyme." . $GLOBALS['domain'] . "/collection/" . $v['gid'];
                    }
                    $channelgamelist[$o] = array_merge($p, $v, $temparr);
                }
            }
        }
        //重建数组
        $channelgameidlist = array_column($channelgamelist, 'gid');
        $channelgamelist = array_combine($channelgameidlist, $channelgamelist);


        //查询渠道数据
        $jChannelDataModel = new jChannelDataModel();
        $where3 = array('cid' => $cid, 'isblock' => 0);
        $data = $jChannelDataModel->getData($where3, 10000);

        $aids = '0';
        foreach ($data as $val) {
            $aids .= ',' . $val['aid'];
        }
        $jSourceDataModel = new jSourceDataModel();
        $where4 = array( 'aid' => array('in', $aids));
        $sourcedata = $jSourceDataModel->getData($where4, 10000);

        //合并数据
        foreach ($data as $k => $val) {
            foreach ($sourcedata as $row) {
                if ($val['aid'] == $row['aid']) {
                    $temparr = json_decode($row['data'], true);
                    $temparr = empty($temparr)?array():$temparr;
                    $data[$k] = array_merge($temparr, $val);
                }
            }
            if (in_array($val['gid'], $channelgameidlist)) {
                if($val['source'] == 1){
                    $channelgamelist[$val['gid']]['articlelist'][] = $data[$k];
                }else if($val['source'] == 2){
                    $channelgamelist[$val['gid']]['giftlist'][] = $data[$k];
                }
            }

        }
        return $channelgamelist;
    }


}