<?php if (!defined('IN')) die('bad request');

/**
 * Description of douyuHezuoModel
 * @author   ISLANDER
 * @datetime 2016-9-19 18:58:03
 */

include_once(AROOT . 'controller' . DS . 'app.class.php');
use Joyme\core\Request;
use Joyme\net\Curl;
use Joyme\qiniu\Qiniu_Utils;

class douyuhezuoController extends appController
{

    public function __construct()
    {
        parent::__construct();
    }
    
    public function index(){
        $conditions = $where = array();
        //游戏名称
        $nickname = Request::getParam('nickname', '');
        if($nickname){
            $where['nickname'] = array('like', '%' . $nickname . '%');
            $conditions['nickname'] = $nickname;
        }
        //wikikey
        $wikikey = Request::getParam('wikikey', '');
        if($wikikey){
            $where['wikikey'] = array('like', '%' . $wikikey . '%');
            $conditions['wikikey'] = $wikikey;
        }
        $douyuHezuoModel = new douyuHezuoModel();
        $total = $douyuHezuoModel->getTotal($where);
        $psize = 20; //每页显示条数
        $pno = Request::get('pb_page', 1);
        $skip = 0;
        if ($pno) {
            $skip = (intval($pno) - 1) * $psize;
        }
        $lists = $douyuHezuoModel->select("*", $where, 'ctime DESC', $psize, $skip);//getListData($where);
        $listdata = array();
        foreach($lists as $key=>$val){
            $info = $this->getRoomInfo($val['roomid']);
            $listdata[$key] = $val;
            if($info && $info['error'] == 0){
                $listdata[$key]['roomstatus'] = $info['data']['room_status'];
            }elseif($info && $info['error'] == 101){
                $listdata[$key]['roomstatus'] = '房间不存在';
            }elseif($info && $info['error'] == 102){
                $listdata[$key]['roomstatus'] = '房间未激活';
            }elseif($info && $info['error'] == 103){
                $listdata[$key]['roomstatus'] = '房间获取错误';
            }
        }
        
        $page = new pageModel();//M('pageModel');
        
        $page->mainPage(array('total' => $total,'perpage'=>$psize,'nowindex'=>$pno,'pagebarnum'=>10));
        $phtml = $page->show(2, $conditions);
        $data = array(
            'nickname' => $nickname,
            'wikikey' => $wikikey,
            'total' => $total,
            'list' => $listdata,
            'phtml' => $phtml,
            'pno' => $pno
        );
        render($data, 'web', 'zhibo/douyuhezuolist');
    }

    public function add()
    {
        $douyuHezuoModel = new douyuHezuoModel();
        $data = array();
        render($data, 'web', 'zhibo/douyuhezuoadd');
    }

    public function addPro()
    {
        $douyuHezuoModel = new douyuHezuoModel();
        $data = $this->getPostData();
        $data['userstatus'] = 1;
        $ret = $douyuHezuoModel->insertData($data);
        if ($ret) {
            addlog('joymewiki', 'add', '添加了id为'.$ret.'的斗鱼合作数据');
            echo '操作成功 <a href="?c=douyuhezuo&a=index">返回列表</a>';
        } else {
            echo '操作失败 <a href="?c=douyuhezuo&a=index">返回列表</a>';
        }
    }

    public function edit()
    {
        $id = Request::get('id', 0);
        $douyuHezuoModel = new douyuHezuoModel();
        $item = $douyuHezuoModel->getDataById($id);
        $pno = Request::get('pno', 1);
        $data = array(
            'item' => $item,
            'pno' => $pno
        );
        render($data, 'web', 'zhibo/douyuhezuoedit');
    }

    public function editPro()
    {
        $id = Request::post('id');
        if(is_numeric($id)){
            $douyuHezuoModel = new douyuHezuoModel();
            $data = $this->getPostData();
            $data['id'] = $id;
            $errno = $douyuHezuoModel->updateData($data);
            if ($errno) {
                addlog('joymewiki', 'update', '修改了id为'.$id.'的斗鱼合作数据');
                echo '操作成功 <a href="?c=douyuhezuo&a=index">返回列表</a>';
            } else {
                echo '操作失败 <a href="?c=douyuhezuo&a=index">返回列表</a>';
            }
        }else{
            echo '参数错误 <a href="?c=douyuhezuo&a=index">返回列表</a>';
        }
    }

    public function del()
    {
        $id = (int)Request::post('id');
        if ($id) {
            $douyuHezuoModel = new douyuHezuoModel();
            $errno = $douyuHezuoModel->delete(array(
                'id' => $id
            ));
            if ($errno) {
                addlog('joymewiki', 'delete', '添加了id为'.$id.'的斗鱼合作数据');
                $res = array('rs' => 1, 'msg' => '删除成功');
            } else {
                $res = array('rs' => 2, 'msg' => '删除失败');
            }
        } else {
            $res = array('rs' => 2, 'msg' => '参数错误');
        }

        jsonEncode($res);
    }
    
    public function updateMsg(){
        $id = (int)Request::post('id');
        $rank = Request::post('rank');
        $userstatus = Request::post('userstatus');
        $islock = Request::post('islock');
        $data = array();
        if($rank !== null){
            $data = array('rank'=>intval($rank));
        }else if($userstatus !== null){
            $data = array('userstatus'=>intval($userstatus));
        }else if($islock !== null){
            $data = array('islock'=>intval($islock));
        }
        
        if ($id) {
            $douyuHezuoModel = new douyuHezuoModel();
            $errno = $douyuHezuoModel->update($data, array(
                'id' => $id
            ));
            if ($errno) {
                addlog('joymewiki', 'update', '添加了id为'.$id.'的斗鱼合作数据');
                $res = array('rs' => 1, 'msg' => '修改成功');
            } else {
                $res = array('rs' => 2, 'msg' => '修改失败');
            }
        } else {
            $res = array('rs' => 2, 'msg' => '参数错误');
        }

        jsonEncode($res);
    }
    
    public function getRoomInfo($roomid){
        $roomid = intval($roomid);
        if($roomid == 0) return false;
        $curl = new Curl();
        $json = $curl->Get('http://open.douyucdn.cn/api/RoomApi/room/'.$roomid);
        $data = json_decode($json, true);
        return $data;
    }

    private function getPostData()
    {
        $data = array(
            'wikikey' => Request::post('wikikey', ''),
            'userid' => Request::post('userid', 0),
            'roomid' => Request::post('roomid', 0),
            'nickname' => Request::post('nickname', ''),
            'qq' => Request::post('qq', 0),
            'cellphone' => Request::post('cellphone', ''),
            'userdesc' => Request::post('userdesc', ''),
            'ctime' => time()
        );
        return $data;
    }
}
