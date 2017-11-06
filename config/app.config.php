<?php
$GLOBALS['config']['site_name'] = 'LazyPHP3';
$GLOBALS['config']['site_domain'] = 'lazyphp3.sinaapp.com';


//设置时区
date_default_timezone_set('PRC');
error_reporting(E_ALL);
//七牛图片地址

//需要跳转的环境
$strpos = explode(".",$_SERVER['SERVER_NAME']);
$GLOBALS['domain'] = $strpos[2];

//设置source接口缓存有效期
$GLOBALS['config']['source']['expiration'] = 3600;
$GLOBALS['config']['redis']['password'] = '';

if($GLOBALS['domain']=='dev'){
    $wgQiNiuPath = 'joymetest.joyme.com';
    $pathkey ='dev';
    $secrectkey = '7ejw!9d#';
    $GLOBALS['config']['qiniu']['bucket'] = 'joymetest';
    $GLOBALS['config']['redis']['host'] = '172.16.75.32';
    $GLOBALS['config']['redis']['port'] = 6379;
    $GLOBALS['config']['source']['expiration'] = 1;
}
if($GLOBALS['domain']=='alpha'){
    $wgQiNiuPath = 'joymetest.qiniudn.com';
    $pathkey ='alpha';
    $secrectkey = '8F5&JL3';
    $GLOBALS['config']['qiniu']['bucket'] = 'joymetest';
    $GLOBALS['config']['redis']['host'] = '172.16.75.32';
    $GLOBALS['config']['redis']['port'] = 6379;
    $GLOBALS['config']['source']['expiration'] = 1;
}
if($GLOBALS['domain']=='beta'){
    $wgQiNiuPath = 'joymepic.joyme.com';
    $pathkey ='beta';
    $secrectkey = '#4g%klwe';
    $GLOBALS['config']['qiniu']['bucket'] = 'joymepic';
    $GLOBALS['config']['redis']['host'] = 'r-2ze25cf88632c7b4.redis.rds.aliyuncs.com';
    $GLOBALS['config']['redis']['port'] = 6379;
    $GLOBALS['config']['redis']['password'] = 'FHW2n2Gh';
    $GLOBALS['config']['source']['expiration'] = 60;
}
if($GLOBALS['domain']=='com'){
    $wgQiNiuPath = 'joymepic.joyme.com';
    $pathkey ='prod';
    $secrectkey = 'yh87&sw2';
    $GLOBALS['config']['qiniu']['bucket'] = 'joymepic';
    $GLOBALS['config']['redis']['host'] = 'r-2zef16817404a374.redis.rds.aliyuncs.com';
    $GLOBALS['config']['redis']['port'] = 6379;
    $GLOBALS['config']['redis']['password'] = 'zIGMyY12';
}
//配置加载PHP公共库的具体路径
$GLOBALS['libPath'] = '/opt/www/joymephplib/'.$pathkey.'/phplib.php';
//$GLOBALS['libPath'] = '/opt/www/joymephplib/phplib.php';
//$GLOBALS['libPath'] =  'D:/wamp/www/joymephplib/trunk/phplib.php';
$GLOBALS['static_url'] = 'http://static.joyme.'.$GLOBALS['domain'];
$GLOBALS['config']['staticurl'] = 'http://static.joyme.'.$GLOBALS['domain'];

//redis前缀
$GLOBALS['config']['redis']['prefix'] = 'channel';

//检测是否需要登录
$GLOBALS['config']['checklogin'] = true;

//定义美图栏目ID
$GLOBALS['config']['meituID'] = 1334;

