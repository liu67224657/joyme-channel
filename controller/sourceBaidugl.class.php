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

class sourceBaiduglController extends sourceAbstractController
{
    //渠道字段
    public $fields = array('indexData', 'title', 'image', 'url', 'pubtime', 'category');


    /**
     * 接口查询
     */
    public function query($cid)
    {
        //获取参数
        $uptime = Request::getParam('update_time', time() - 24 * 3600);
        $type = Request::getParam('type', 1); // 1游戏 2数据
        $page = Request::getParam('page', 1);
        $size = Request::getParam('page_size', 20);

        $skip = ($page - 1) * $size;

        $count = 0;

        $data = array(
            'org_name' => '着迷网',
            'org_url' => 'http://www.joyme.com',
            'update_time' => date('Y-m-d H:i:s', $uptime),
            'update_count' => $count,
            'list' => array()
        );

        if ($type == 1) {
            //查询游戏信息
            $jChannelGameModel = new jChannelGameModel();
            $where = array('cid' => $cid, 'gamestatus' => 1);
            $channelgamelist = $jChannelGameModel->getData($where, $size, $skip);
            $datacount = $jChannelGameModel->getCount($where);

            $gids = '0';
            foreach ($channelgamelist as $val) {
                $gids .= ',' . $val['gid'];
            }

            //查询游戏附加数据
            $jGameModel = new jGameModel();
            $where2 = array('gid' => array('in', $gids));
            $gamelist = $jGameModel->getData($where2, $size);

            foreach ($channelgamelist as $o => $p) {
                foreach ($gamelist as $k => $v) {
                    if ($p['gid'] == $v['gid']) {
                        $temparr = json_decode($v['extra'], true);
                        $temparr = empty($temparr) ? array() : $temparr;
                        $channelgamelist[$o] = array_merge($p, $v, $temparr);
                    }
                }
            }


            foreach ($channelgamelist as $v) {
                $categoryTypeSet = '';//分类
                foreach ($v['categoryTypeSet'] as $v2) {
                    $categoryTypeSet .= ',' . $v2['value'];
                }
                $categoryTypeSet = substr($categoryTypeSet, 1);
                $platformMap = ''; //平台
                foreach ($v['platformMap'] as $v2) {
                    foreach ($v2 as $v3) {
                        $platformMap .= ',' . $v3['desc'];
                    }
                }
                $platformMap = substr($platformMap, 1);

                $levelGame = $v['levelGame'] == false ? '否' : '是';

                $gameurl = 'http://www.joyme.com/collection/' . $v['gid'];

                if (strtotime($v['createDate']) < $uptime) {
                    continue;
                }

                $data['list'][] = array(
                    'url' => $gameurl,
                    'name' => $v['gameName'],
                    'game_alias' => $v['anotherName'],
                    'category' => $categoryTypeSet,
                    'developers' => self::null2str($v['gameDeveloper']),
                    'game_platform' => $platformMap,
                    'ios_url' => self::null2str($v['iosDownload']),
                    'android_url' => self::null2str($v['androidDownload']),
                    'web_url' => self::null2str($v['webpageDownload']),
                    'pc_url' => self::null2str($v['pcDownload']),
                    'xboxone_url' => self::null2str($v['xboxoneDownload']),
                    'ps4_url' => self::null2str($v['ps4Download']),
                    'has_tollgate' => $levelGame,
                    'topic_names' => '',
                    'icon' => $v['gameIcon'],
                    'publish_time' => date('Y-m-d', strtotime($v['gamePublicTime'])),
                    'update_time' => date('Y-m-d', strtotime($v['createDate'])),
                );
            }
            $data['update_count'] = $datacount;

        } else {
            //查询游戏信息
            $jChannelGameModel = new jChannelGameModel();
            $where = array('cid' => $cid, 'gamestatus' => 1);
            $channelgamelist = $jChannelGameModel->getData($where, 1000);

            $gids = '0';
            foreach ($channelgamelist as $val) {
                $gids .= ',' . $val['gid'];
            }

            //查询游戏附加数据
            $jGameModel = new jGameModel();
            $where2 = array('gid' => array('in', $gids));
            $gamelist = $jGameModel->getData($where2, 1000);

            foreach ($channelgamelist as $o => $p) {
                foreach ($gamelist as $k => $v) {
                    if ($p['gid'] == $v['gid']) {
                        $temparr = json_decode($v['extra'], true);
                        $temparr = empty($temparr) ? array() : $temparr;
                        $channelgamelist[$o] = array_merge($p, $v, $temparr);
                    }
                }
            }

            //重建数组
            $channelgameidlist = array_column($channelgamelist, 'gid');
            $channelgamelist = array_combine($channelgameidlist, $channelgamelist);

            //查询渠道数据
            $jChannelDataModel = new jChannelDataModel();
            $where = array('cid' => $cid, 'gid' => array('in', $gids), 'source' => 1, 'isblock' => 0, 'pubdate' => array('gt', $uptime));
            $channelData = $jChannelDataModel->getData($where, $size, $skip);
            $datacount = $jChannelDataModel->getCount($where);
            $aids = '0';
            foreach ($channelData as $val) {
                $aids .= ',' . $val['aid'];
            }
            //查询附加数据
            $jSourceDataModel = new jSourceDataModel();
            $where2 = array('source' => 1, 'aid' => array('in', $aids));
            $sourcedata = $jSourceDataModel->getData($where2, $size);

            //查询body
            $cmsModel = new cmsModel();
            $bodys = $cmsModel->getBodyByIds($aids);
            //重建数组
            $bodys = array_column($bodys, 'body', 'aid');
//            var_dump($bodys);exit;
            //合并数据
            foreach ($channelData as $k => $val) {
                if (empty($bodys[$val['aid']])) {
                    continue;
                }
                foreach ($sourcedata as $row) {
                    if ($val['aid'] == $row['aid']) {
                        $tmp = json_decode($row['data'], true);
                        if ($tmp) {
                            $tmparr = array(
                                'url' => $val['url'],
                                'title' => $tmp['title'],
                                'author' => $tmp['writer'],
                                'game_name' => $channelgamelist[$val['gid']]['gameName'],
                                'topic_name' => '',
                                'tollgate_name' => '',
                                'description' => $tmp['description'],
                                'content' => $bodys[$val['aid']],
                                'img_item' => self::getCatImgList($bodys[$val['aid']]),
                                'cover_img' => $val['litpic'],
                                'publish_time' => date('Y-m-d H:i:s', $val['pubdate']),
                                'update_time' => date('Y-m-d H:i:s', $val['pubdate'])
                            );

                            $data['list'][] = $tmparr;
                        }
                    }
                }
            }
            $data['update_count'] = $datacount;

        }

        return $data;
    }

    public static function null2str($val)
    {
        $val = empty($val) ? '' : $val;
        return $val;
    }

    public static function getCatImgList($content)
    {
        $res = array();
        preg_match_all('/<img.*?src="(.*?)".*?width="(.*?)".*?height="(.*?)".*?>/is', $content, $match);
        preg_match_all('/<img.*?width="(.*?)".*?height="(.*?)".*?src="(.*?)".*?\/>/is', $content, $match2);
        if (empty($match[1]) && empty($match2[3])) {
            return array();
        } else {
            if (!empty($match[1])) {
                foreach ($match[1] as $k => $src) {
                    $res[] = $src;
                }
            }
            if (!empty($match2[3])) {
                foreach ($match2[3] as $k => $src) {
                    $res[] = $src;
                }
            }
            $res = array_values(array_unique($res));
            return $res;
        }
    }


}