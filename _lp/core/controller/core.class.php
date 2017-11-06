<?php

if( !defined('IN') ) die('bad request');
use Joyme\qiniu\Qiniu_ImageView;

class coreController 
{
	function __construct()
	{

	}
    //判断token 是否正确， 默认返回 json数据； 如果 jsonp为true 则返回jsonp
    protected function checkToken($wikikey, $token, $callback = '') {

        if ($token !== $this->Protection($wikikey)) {
            $data = array('rs' => 1, 'msg' => "token 验证失败", 'result' => 'fail');
            if (strlen($callback) > 1) {
                echo $callback . "([" . json_encode($data) . "])";
            } else {
                echo json_encode($data);
            }
            exit;
        }
    }

    //判断结果 是否正确， 默认返回 json数据； 如果 jsonp为true 则返回jsonp
    protected function checkResult($data,$callback = '') {

        if($data){
            $data = is_array($data)?$data:'success';
            $data = array('rs' => 0, 'msg' => "操作成功", 'result' => $data);
        }else{
            $data = array('rs' => 2, 'msg' => "操作失败", 'result' => 'fail');
        }
        if (strlen($callback) > 1) {
            echo $callback . "([" . json_encode($data) . "])";
        } else {
            echo json_encode($data);
        }
        exit;
    }
}

//操作日志
function addlog($btype,$stype,$opafter){
    global $secrectkey;
    $userinfo = explode("|",$_COOKIE['t_jm_message']);
    $userid = $userinfo[2];
    $ip = $_SERVER["REMOTE_ADDR"];
    $encrypt = md5($secrectkey.$userid);
    $url = "tools.joyme.". $GLOBALS['domain']."/log/addlog";
    $data = "userid=".$userid."&btype=".$btype."&stype=".$stype."&ip=".$ip."&encrypt=".$encrypt."&opafter=".$opafter;
    sendRequest($url,$data);
}

//加密验证
function Encryption(){

    $info = func_get_args();
    //约定秘钥
    $arr = implode("",$info);
    $key = "zmsj&cz";
    $dense = md5($arr.$key);
    return $dense;
}

//计算图片地址
function jget_save_path($file_name,$lvl=2){
    $name =$file_name;
    $levels = $lvl;
    if ( $levels == 0 ) {
        return '';
    } else {
        $hash = md5( $name );
        $path = '';
        for ( $i = 1; $i <= $levels; $i++ ) {
            $path .= substr( $hash, 0, $i ) . '/';
        }
        return $path;
    }
}

//获取缩略图url
function getImageThumbUrl($baseUrl,$width,$height){
    //生成fopUrl
    $imgView = new Qiniu_ImageView;
    $imgView->Mode = 1;
    $imgView->Width = $width;
    $imgView->Height = $height;
    $imgViewUrl = $imgView->MakeRequest($baseUrl);
    return $imgViewUrl;
}

//curl请求
function sendRequest($url,$data = false){
    if(empty($url)){
        return false;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    if($data){
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = trim(curl_exec($ch));
    curl_close($ch);
    return  $result;
}

//实例化Model
function M($model_name){
    //先判断类是否存在
    if(class_exists($model_name)){
        $_model = new $model_name();
        return $_model;
    }
    $suffix_name = '.class.php';
    $model_file = AROOT . 'model'  . DS . $model_name.$suffix_name;
    if(file_exists($model_file)){
        include_once($model_file);
        $_model = new $model_name();
        return $_model;
    }
}


function jsonEncode($array){
    if(empty($array)){
        return '';
    }
    echo json_encode($array);
    exit();
}



