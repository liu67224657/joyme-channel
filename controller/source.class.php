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

class sourceController extends coreController
{

    //channel list
    public function channellist()
    {
        $jchannelmodel = new jChannelModel();
        $lists = $jchannelmodel->select("cid,ckey,channel_name,template", array(), 'cid DESC');

        echo json_encode($lists);
        exit;


    }

    public function test()
    {

    }

    //上报cms和礼包数据
    public function savedata()
    {
        $aid = Request::getParam('aid', 0);
        $cid = Request::getParam('cid', '');
        $gid = Request::getParam('gid', 0);
        $atype = Request::getParam('atype', 0);
        $source = Request::getParam('source', 1);
        $extra = empty($_POST['extra']) ? '' : $_POST['extra'];
        $pubdate = Request::getParam('pubdate', '');
        $aurl = Request::getParam('url', '');
        $extraArr = json_decode($extra, true);

        if ($source == 2) {
            $cid = 1;
            $pubdate = $pubdate / 1000;
        }

        if (empty($aid) || empty($cid) || empty($source) || empty($extraArr) || empty($pubdate)) {
            self::sourceReturn(-10001, '参数不全');
        }
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
        if ($source == 2 && $extraArr['actStatus']['code'] == 'n') {
            $isblock = 1;
        } else {
            $isblock = 0;
        }

        $cidArr = explode(',', $cid);

        $jchannelmodel = new jChannelModel();
        $tplArr = $jchannelmodel->select("cid,template,size", array());

        $tplCidArr = array_column($tplArr, 'cid');
        $tplArr = array_combine($tplCidArr, $tplArr);

        $typeid = empty($extraArr['typeid']) ? '' : $extraArr['typeid'];
        $typeid2 = empty($extraArr['typeid2']) ? '' : $extraArr['typeid2'];
        $typeid .= ',' . $typeid2;
        $typeidArr = explode(',', $typeid);

        $channelDataModel = new jChannelDataModel();

        //查询所属的栏目列表
        $channelGameModel = new jChannelGameModel();
        $channelgameRs = $channelGameModel->select("cid,gid", array('gid' => array('in', $typeidArr), 'datatype' => 2));


        $channelgameArr = array();
        foreach($channelgameRs as $v){
            $channelgameArr[$v['cid']][] = $v['gid'];
        }

        if ($rs !== false && empty($sourceDataRs)) {
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

                //判断渠道是否开启游戏或栏目
                if ($gid) {
                    $channeldata['datatype'] = 1;
                    $channeldata['gid'] = $gid;
                    $channelDataModel->addData($channeldata);
                }
                $typeid = empty($extraArr['typeid']) ? 0 : $extraArr['typeid'];

                if ($channelgameArr[$v] && in_array($typeid, $channelgameArr[$v])) {
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
            self::sourceReturn(1, 'ok');
        } else if ($rs !== false) {
            $channelDataModel = new jChannelDataModel();
            $where1 = $where2 = $where = array('aid' => $aid, 'source' => $source);

            $where1['datatype'] = 1;
            $channelDatars1 = $channelDataModel->getData($where1, 50);
            $cids1 = array_column($channelDatars1,'cid');
            $where2['datatype'] = 2;
            $channelDatars2 = $channelDataModel->getData($where2, 50);
            $cids2 = array_column($channelDatars2,'cid');

            $cidArr = explode(',', $cid);

            //不存在的则添加
            if (!empty($cidArr)) {
                $url = '';
                $channeldata = array(
                    'cid' => '',
                    'gid' => '',
                    'aid' => $aid,
                    'atype' => $atype,
                    'litpic' => $litpic,
                    'source' => $source,
                    'url' => $url,
                    'pubdate' => $pubdate,
                    'isblock' => $isblock
                );


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
                    if ($size && !empty($size[$atype]) && $source == 1) {
                        $channeldata['litpic'] = $litpic . '?imageView2/1/w/' . $size[$atype]['w'] . '/h/' . $size[$atype]['h'];
                    }

                    $channeldata['cid'] = $v;
                    //判断渠道是否开启游戏或栏目
                    if ($gid && !in_array($v,$cids1)) {
                        $channeldata['datatype'] = 1;
                        $channeldata['gid'] = $gid;
                        $channelDataModel->addData($channeldata);
                    }
                    $typeid = empty($extraArr['typeid']) ? 0 : $extraArr['typeid'];

                    if ($channelgameArr[$v] && in_array($typeid, $channelgameArr[$v]) && !in_array($v,$cids2)) {
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
            }

            $channeldata = array(
                'atype' => $atype,
                'pubdate' => $pubdate,
                'isblock' => $isblock,
            );
            $rs = $channelDataModel->update($channeldata, $where);
            if ($rs !== false) {
                self::sourceReturn(1, 'ok');
            } else {
                $message = '更新channeldata数据失败';
                Log::error($message);
                self::sourceReturn(-10002, $message);
            }
        } else {
            $message = '更新sourcedata数据失败';
            Log::error($message);
            self::sourceReturn(-10002, $message);
        }
    }

    //上报游戏库
    public function savegame()
    {
        $gid = Request::getParam('gid', 0);
        $extra = empty($_POST['extra']) ? '' : $_POST['extra'];
        $extraArr = json_decode($extra, true);
        if (empty($gid) || empty($extraArr)) {
            self::sourceReturn(-10001, '参数不全');
        }
        $litpic = empty($extraArr['litpic']) ? '' : $extraArr['litpic'];
        $gameurl = 'http://www.joyme.com/collection/' . $gid;

        $gamedata = array(
            'gid' => $gid,
            'listnewsurl' => $gameurl . '/news',
            'liststrategyurl' => $gameurl . '/guides',
            'listvideourl' => $gameurl . '/videos',
            'extra' => $extra
        );

        $gameModel = new jGameModel();
        $row = $gameModel->getRowData(array('gid' => $gid));
        if ($row) {
            $exsitextra = json_decode($row['extra'], true);
            if ($exsitextra['sougoupcgametype']) {
                $extra = json_decode($extra, true);
                $extra['sougoupcgametype'] = $exsitextra['sougoupcgametype'];
                $extra = json_encode($extra);
            }
            $rs = $gameModel->update(array('extra' => $extra), array('gid' => $gid));
            if ($rs !== false) {
                self::sourceReturn(1, 'ok');
            } else {
                $message = '更新数据库失败';
                Log::error($message);
                self::sourceReturn(-10002, $message);
            }
        } else {
            $rs = $gameModel->addData($gamedata);
            if ($rs !== false) {
                self::sourceReturn(1, 'ok');
            } else {
                $message = '插入数据库失败';
                Log::error($message);
                self::sourceReturn(-10002, $message);
            }
        }

    }

    public function query()
    {
        $GLOBALS['config']['checklogin'] = false;
        $ckey = Request::getParam('ckey', '');
        $gameid = Request::getParam('gameid', 0);
        $type = Request::getParam('type', 0); // 1游戏 2数据
        $page = Request::getParam('page', 1);
        $size = Request::getParam('page_size', 20);

        if (empty($ckey)) {
            echo 'no ckey';
            exit;
        }

        $cache_file = AROOT . 'cache/' . $ckey . '_' . $type . '_' . $gameid . '_' . $page. '_' . $size . '.data';
        $expiration = $GLOBALS['config']['source']['expiration']; // 过期时间

        $now = time();

        if (file_exists($cache_file) && (filemtime($cache_file) > $now - $expiration)) {
            $str = file_get_contents($cache_file);
            $data = json_decode($str, true);
        } else {
            $jchannel = NC('jchannel');
            $cid = $jchannel->getCidByKey($ckey);

            if (empty($cid)) {
                echo 'no this channel';
                exit;
            }

            $source = sourceController::createSource($ckey);
            $data = $source->query($cid);
            @file_put_contents($cache_file, json_encode($data));
        }

        $tpl = $ckey;
        render($data, 'source', $tpl);
    }

    /**
     * @param $name
     * @return source object
     * @throws Exception
     */
    public static function createSource($name)
    {
        switch ($name) {
            case 'baiduglb':
                return NC('sourceBaiduglb'); //百度哥伦布
                break;
            case 'sougoupc':
                return NC('sourceSougoupc'); //搜狗pc端
                break;
            case 'sougouwap':
                return NC('sourceSougouwap'); //搜狗手机端
                break;
            case 'baidugl':
                return NC('sourceBaidugl'); //百度攻略
                break;
            case 'aliyunos':
                return NC('sourceAliyunos'); //阿里云OS
                break;
            case 'sougouclient':
                return NC('sourceSougouclient'); //搜狗浏览器
                break;
            default :
                throw new Exception('没有任何渠道');
                break;
        }
    }

    public function sourceReturn($code, $msg)
    {
        $data = array('rs' => $code, 'msg' => $msg);
        echo json_encode($data);
        exit;
    }
}