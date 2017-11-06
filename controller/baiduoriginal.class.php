<?php
/**
 * Description:百度原创管理
 * Author: gradydong
 * Date: 2017/6/30
 * Time: 15:43
 * Copyright: Joyme.com
 */
if (!defined('IN')) die('bad request');
include_once(AROOT . 'controller' . DS . 'app.class.php');
use Joyme\core\Request;

class baiduoriginalController extends appController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $conditions = $where = array();
        //项目
        $source = Request::getParam('source', 0);
        if ($source) {
            $where['source'] = $source;
            $conditions['source'] = $source;
        }
        //文章标题
        $title = Request::getParam('title');
        if ($title) {
            $where['title'] = array('like', '%' . $title . '%');
            $conditions['title'] = $title;
        }
        //提交时间开始
        $begintime = Request::getParam('begintime');
        //提交时间结束
        $endtime = Request::getParam('endtime');
        if ($begintime && empty($endtime)) {
            $gbegintime = strtotime($begintime);
            $where['addtime'] = array('gt', $gbegintime);
            $conditions['begintime'] = $begintime;
        }
        elseif (empty($begintime) && $endtime) {
            $gendtime = strtotime("$endtime +1 days");
            $where['addtime'] = array('lt', $gendtime);
            $conditions['endtime'] = $endtime;
        }
        elseif ($begintime && $endtime) {
            $gbegintime = strtotime($begintime);
            $gendtime = strtotime("$endtime +1 days");
            $where['addtime'] = array(array('gt',$gbegintime),array('lt',$gendtime),'and');
            $conditions['begintime'] = $begintime;
            $conditions['endtime'] = $endtime;
        }

        $baiduoriginalmodel = new baiduOriginalModel();
        $total = $baiduoriginalmodel->count($where);
        $psize = 20; //每页显示条数
        $pno = Request::get('pb_page', 1);
        $skip = 0;
        if ($pno) {
            $skip = (intval($pno) - 1) * $psize;
        }
        $lists = $baiduoriginalmodel->select("id,source,title,url,result,addtime", $where, 'id DESC', $psize, $skip);
        if($lists){
            foreach ($lists as $k => $list){
                if($list['source']==1){
                    $lists[$k]['source'] = 'wiki';
                }
                elseif ($list['source']==2){
                    $lists[$k]['source'] = 'cms';
                }
                $lists[$k]['addtime'] = date('Y-m-d',$list['addtime']);
            }
        }
        $page = new pageModel();
        $page->mainPage(array('total' => $total, 'perpage' => $psize, 'nowindex' => $pno, 'pagebarnum' => 10));
        $phtml = $page->show(2, $conditions);
        $data = array(
            'source' => $source,
            'title' => $title,
            'begintime' => $begintime,
            'endtime' => $endtime,
            'total' => $total,
            'list' => $lists,
            'phtml' => $phtml,
            'pno' => $pno
        );
        render($data, 'web', 'baiduoriginal/list');
    }


    public function add()
    {
        render(array(), 'web', 'baiduoriginal/add');
    }

    public function addPro()
    {
        $source = Request::getParam('source');
        $title = Request::getParam('title');
        $url = Request::getParam('url');
        if($source&&$title&&$url){
            $opt_url = 'http://data.zz.baidu.com/urls?site=';
            if($source==1){
                $opt_url .= 'http://wiki.joyme.com/';
            }
            elseif ($source==2){
                $opt_url .= 'http://www.joyme.com/';
            }
            $opt_url .= '&token=WzGLirMD1oFFXN4n&type=original';
            $ch = curl_init();
            curl_setopt_array($ch,array(
                CURLOPT_URL=>$opt_url,
                CURLOPT_POST=>true,
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_POSTFIELDS=>$url,
                CURLOPT_HTTPHEADER=>array('Content-Type: text/plain'),
                CURLOPT_TIMEOUT=>60
            ));
            $result = curl_exec($ch);
            curl_close($ch);
            $data = array(
                'source' => $source,
                'title' => $title,
                'url' => $url,
                'result' => $result,
                'addtime' => time()
            );
            $baiduoriginalmodel = new baiduOriginalModel();
            $ret = $baiduoriginalmodel->insert($data);
            if ($ret) {
                echo '操作成功 <a href="?c=baiduoriginal&a=index">返回列表</a>';
            } else {
                echo '操作失败 <a href="?c=baiduoriginal&a=index">返回列表</a>';
            }
        }else{
            echo '参数不能为空 <a href="?c=baiduoriginal&a=index">返回列表</a>';
        }
    }
}