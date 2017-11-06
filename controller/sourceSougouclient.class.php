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

class sourceSougouclientController extends sourceAbstractController
{
    //渠道字段
    public $fields = array('indexData', 'title', 'image', 'url', 'pubtime', 'category');

    /**
     * 接口查询
     */
    public function query($cid)
    {
        global $GLOBALS;
        $type = Request::getParam('type', 1); // 1列表 2数据
        $page = Request::getParam('page', 1);
        $size = 1000;
        $skip = ($page - 1) * $size;

        $offset = time() - 30 * 24 * 3600;

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

        if ($type == 1) {
            $count = $jChannelDataModel->count($where3);
            $total = ceil($count/$size);
            $newdata = array('type'=>1,'total'=>$total);
        } else {
            $data = $jChannelDataModel->getData($where3, $size, $skip);

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
            $sourcedata = $jSourceDataModel->getData($where4, 10000);

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

                            $read_num = ceil((time()-$tmp['pubdate'])/3600/24)*87+intval(intval(substr($val['aid'],-4))*0.8);

                            if ($val['gid'] != $GLOBALS['config']['meituID']) {
                                $piclistr = self::getCatImgList($bodys1[$val['aid']]);
                                $piclist = array();
                                $media_type = '新闻';
                                $body = $bodys1[$val['aid']];
                                $pic_defaultsrc = empty($val['litpic'])?$piclistr[0]['uri']:$val['litpic'];
                                $pic_default = array('url' => $pic_defaultsrc, 'width' => 230, 'height' => 130);
                            } else {
                                $piclist = self::getTagImgList($bodys2[$val['aid']],$tmp['title']);
                                $media_type = '图集';
                                $body = $tmp['description'];
                                foreach ($piclist as $v) {
                                    $body .= '<br/><img src="' . $v['uri'] . '" width="' . $v['width'] . 'px" height="' . $v['height'] . 'px" />';
                                }
                                $pic_defaultsrc = empty($val['litpic'])?$piclist[0]['uri']:$val['litpic'];
                                $pic_default = array('url' => $pic_defaultsrc, 'width' => 210, 'height' => 318);
                            }
                            $description = self::getStrTrimTag($tmp['description']);
                            $keywords = self::getStrTrimTag($tmp['keywords']);
                            $tmparr = array(
                                'id' => $val['aid'],
                                'update_time' => $tmp['pubdate'],
                                'title' => $tmp['title'],
                                'shorttitle' => $tmp['shorttitle'],
                                'description' => $description,
                                'body' => $body,
                                'keywords' => $keywords,
                                'author' => $tmp['writer'],
                                'cat_id' => $cat_id_pre . $val['gid'],//区分栏目和游戏哟
                                'category' => $channelgamelist[$val['gid']]['gamename'],
                                'media_type' => $media_type,
                                'labels' => $tmp['keywords'],
                                'pic_default' => $pic_default,
                                'pic_list' => $piclist,
                                'm_url' => self::getStrTrimTag($val['url'] . '?source=sougouclient'),
                                'source' => '着迷网',
                                'read_num' => $read_num
                            );

                            $newdata[] = $tmparr;
                        }
                    }
                }


            }
        }
        return $newdata;
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
                    $src = trim($src);
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
                    $src = trim($src);
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

    public static function getTagImgList($content,$title)
    {
        $res = array();
        preg_match_all("/{dede:img.*?ddimg='(.*?)'.*?text='(.*?)'.*?width='(.*?)'.*?height='(.*?)'.*?{\/dede:img}/is", $content, $match);
        if (empty($match[1])) {
            return array();
        } else {
            foreach ($match[1] as $k => $src) {
                $src = trim($src);
                $imginfo = array('uri' => $src);
                if (!empty($match[2][$k])) {
//                    $imginfo['text'] = $match[2][$k];
                    $imginfo['text'] = self::getStrTrimTag($match[2][$k]);
                }else{
                    $imginfo['text'] = $title;
                }
                if (!empty($match[3][$k])) {
                    $imginfo['width'] = $match[3][$k];
                }
                if (!empty($match[4][$k])) {
                    $imginfo['height'] = $match[4][$k];
                }
                $res[] = $imginfo;
            }
            return $res;
        }
    }

    //去掉空格，回车，换行
    public static function getStrTrimTag($content)
    {
        $content = str_replace(" ",'',$content);
        $content = str_replace("　",'',$content);
//        $content = str_replace("\r",'',$content);
//        $content = str_replace("\n",'',$content);
//        $content = str_replace("\r\n",'',$content);
//        $content = str_replace("\t",'',$content);
        return $content;
    }

}