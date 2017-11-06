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

class sourceAliyunosController extends sourceAbstractController
{
    //渠道字段
    public $fields = array('indexData', 'title', 'image', 'url', 'pubtime', 'category');

    /**
     * 接口查询
     * $type: 1新闻 2美图
     */
    public function query($cid)
    {
        global $GLOBALS;

        $type = Request::getParam('type', 1); // 1新闻 2美图
        $traceId = Request::getParam('traceId', '');
        $offset = Request::getParam('offset', '');

        //查询所有的游戏-栏目
        $jChannelGameModel = new jChannelGameModel();
        $where = array('cid' => $cid, 'gamestatus' => 1);

        $channelgamelist = $jChannelGameModel->getData($where, 1000);

        $gids = $gidall = '0';
        foreach ($channelgamelist as $val) {
            if ($val['datatype'] == 1) {
                $gids .= ',' . $val['gid'];
            }
            $gidall .= ',' . $val['gid'];
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
                    if (empty($temparr['wikiUrl'])) {
                        $temparr['wikiUrl'] = "http://www.joyme." . $GLOBALS['domain'] . "/collection/" . $v['gid'];
                    }
                    $p['gamename'] = $temparr['gameName'];
                    $channelgamelist[$o] = array_merge($p, $v, $temparr);
                }
            }
        }
        //重建数组
        $channelgameidlist = array_column($channelgamelist, 'gid');
        $channelgamelist = array_combine($channelgameidlist, $channelgamelist);

        //查询渠道数据
        $jChannelDataModel = new jChannelDataModel();
        $where3 = array('cid' => $cid, 'gid' => array('in', $gidall), 'isblock' => 0);
        if ($offset > 0) {
            $where3['pubdate'] = array('gt', $offset);
        }
        $data = $jChannelDataModel->getData($where3, 1000);

        $aids = $aids1 = $aids2 = '0';
        foreach ($data as $val) {
            if ($val['gid'] == $GLOBALS['config']['meituID']) {
                $aids2 .= ',' . $val['aid'];
            } else {
                $aids1 .= ',' . $val['aid'];
            }
            $aids .= ',' . $val['aid'];
        }
        $jSourceDataModel = new jSourceDataModel();
        $where4 = array('aid' => array('in', $aids));
        $sourcedata = $jSourceDataModel->getData($where4, 1000);

        //查询body
        $cmsModel = new cmsModel();
        $bodys1 = $cmsModel->getBodyByIds($aids);
        $cmsimgModel = new cmsimageModel();
        $bodys2 = $cmsimgModel->getBodyByIds($aids);
        //重建数组
        $bodys1 = array_column($bodys1, 'body', 'aid');
        $bodys2 = array_column($bodys2, 'body', 'aid');

        $newdata = array();
        //合并数据
        foreach ($data as $k => $val) {
            foreach ($sourcedata as $row) {
                if ($val['aid'] == $row['aid'] && !empty($channelgamelist[$val['gid']])) {
                    $tmp = json_decode($row['data'], true);
                    if ($tmp) {
                        $cat_id_pre = $val['datatype'] == 1 ? 'Youxi' : 'Lanmu';

                        if ($val['gid'] != $GLOBALS['config']['meituID']) {
                            $piclist = self::getCatImgList($bodys1[$val['aid']]);
                            if ($val['atype'] == 1) {
                                $media_type = 'video';
                            } else if ($piclist) {
                                $media_type = 'image_text';
                            } else {
                                $media_type = 'text';
                            }
                        } else {
                            $piclist = self::getTagImgList($bodys2[$val['aid']]);

                            $media_type = 'image';
                            foreach ($piclist as $v) {
                                if (substr($v['uri'], -3) == 'gif') {
                                    $media_type = 'image_gif';
                                    break;
                                }
                            }
                        }
                        $tmparr = array(
                            'id' => $val['aid'],
                            'gmt_create' => $tmp['pubdate'] * 1000,
                            'gmt_modified' => $tmp['pubdate'] * 1000,
                            'title' => $tmp['title'],
                            'description' => $tmp['description'],
                            'author' => $tmp['writer'],
                            'cat_id' => $cat_id_pre . $val['gid'],//区分栏目和游戏哟
                            'category' => $channelgamelist[$val['gid']]['gamename'],
                            'media_type' => $media_type,
                            'labels' => $tmp['keywords'],
                            'pic_default' => $val['litpic'],
                            'pic_list' => $piclist,
                            'm_url' => $val['url'].'?source=aliyunos',
                            'source' => '着迷网',
                            'status' => 'online'
                        );

                        $newdata[] = $tmparr;
                    }
                }
            }


        }
        $code = empty($newdata) ? 404 : 200;
        $newdata = json_encode($newdata);
        $result = array(
            'data' => array(
                'code' => $code,
                'msg' => 'OK',
                'traceId' => $traceId,
                'jsonModel' => $newdata,
                'sign' => md5($newdata),
                'nextOffset' => time()
            )
        );
        return $result;
    }

    public static function getCatImgList($content)
    {
        $res = array();
        preg_match_all('/<img.*?src="(.*?)".*?width="(.*?)".*?height="(.*?)".*?>/is', $content, $match);
        preg_match_all('/<img.*?width="(.*?)".*?height="(.*?)".*?src="(.*?)".*?\/>/is', $content, $match2);
        if (empty($match[1]) && empty($match2[3])) {
            return array();
        } else {
            $imglist = array();
            if (!empty($match[1])) {
                foreach ($match[1] as $k => $src) {
                    $imginfo = array('uri' => $src);
                    if (!empty($match[2][$k])) {
                        $imginfo['width'] = $match[2][$k];
                    }
                    if (!empty($match[3][$k])) {
                        $imginfo['height'] = $match[3][$k];
                    }
                    if (!in_array($src, $imglist)) {
                        $res[] = $imginfo;
                        $imglist[] = $src;
                    }
                }
            }
            if (!empty($match2[3])) {
                foreach ($match2[3] as $k => $src) {
                    $imginfo = array('uri' => $src);
                    if (!empty($match2[1][$k])) {
                        $imginfo['width'] = $match2[1][$k];
                    }
                    if (!empty($match2[2][$k])) {
                        $imginfo['height'] = $match2[2][$k];
                    }
                    if (!in_array($src, $imglist)) {
                        $res[] = $imginfo;
                        $imglist[] = $src;
                    }
                }
            }
            return $res;
        }
    }

    public static function getTagImgList($content)
    {
        $res = array();
        preg_match_all("/{dede:img.*?ddimg='(.*?)'.*?width='(.*?)'.*?height='(.*?)'.*?{\/dede:img}/is", $content, $match);
        if (empty($match[1])) {
            return array();
        } else {
            foreach ($match[1] as $k => $src) {
                $imginfo = array('uri' => $src);
                if (!empty($match[2][$k])) {
                    $imginfo['width'] = $match[2][$k];
                }
                if (!empty($match[3][$k])) {
                    $imginfo['height'] = $match[3][$k];
                }
                $res[] = $imginfo;
            }
            return $res;
        }
    }


}