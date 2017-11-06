<?php
/**
 * Created by PhpStorm.
 * User: xinshi
 * Date: 2015/10/29
 * Time: 12:19
 */
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );
use Joyme\core\Request;

class templateController extends appController{

    function index(){

        global $GLOBALS;
        $wikimodel = M('joymeWikiModel');
        $channemode = M('joymeChannelModel');
        $tempmodel = M('joymeTemplateModel');
        $pb_show_num = 50; //每页显示条数
        $pb_page = Request::get('pb_page',1); //获取当前页码
        $conditions['wiki_name'] = Request::getParam('wiki_name');
        $conditions['wiki_channe_name'] = Request::getParam('wiki_channe_name');
        $conditions['is_home'] = Request::getParam('is_home');
        $conditions['is_pc'] = Request::getParam('is_pc');
        $total = $tempmodel->allTemplateList($conditions,true);
        $data['item'] = $tempmodel->allTemplateList($conditions,false,$pb_page,$pb_show_num);
        $page = M('pageModel');
        $page->mainPage(array('total' => $total,'perpage'=>$pb_show_num,'nowindex'=>$pb_page,'pagebarnum'=>10));
        $data['page_str'] = $page->show(2,$conditions);
        $data['static_url'] = $GLOBALS['static_url'];
        $data['wiki_name'] = $wikimodel->allWikiName();
        $data['channe_name'] = $channemode->allChannelName();
        $data['param'] = $conditions;
        render($data,'web','template/template_list');
    }

    //查看模板内容
    function showTemplatePage(){

        $temp_id = Request::get('temp_id'); //获取模板ID
        $templateContext = Request::get('templateContext');//预览内容
        $data['callback'] =  Request::get('callback');//回跳地址
        if(empty($temp_id) && $templateContext){
            $data['item']['template_context'] = $templateContext;
        }else{
            $tempmodel = M('joymeTemplateModel');
            $data['item'] = $tempmodel->selectInfoByTempId($temp_id);
        }
        render($data,'web','template/preview');
    }

    //创建模板
    function createTemplatePage(){

        global $GLOBALS;
        $channemode = M('joymeChannelModel');
        $wikimodel = M('joymeWikiModel');
        $data['wiki_name'] = $wikimodel->allWikiName();
        $data['channe_name'] = $channemode->allChannelName();
        $data['static_url'] = $GLOBALS['static_url'];
        render($data,'web','template/create_template');
    }

    //处理创建模板数据
    function addtemplateData(){
        $update_id = Request::post('update_id');
        $data['template_name'] = Request::post('template_name'); //模板名称
        $data['wiki'] = Request::post('wiki_name'); //所属wiki
        $data['context_path'] = Request::post('is_pc'); //是否PC
        $data['channel'] = Request::post('wiki_channel_name'); //渠道
        $data['is_index'] = Request::post('is_home'); //子页/首页
        $data['template_context'] = Request::post('templateContext'); //模板内容
        $data['is_enable'] = Request::post('is_enable',1); //模板内容
        $data['create_time'] = date('Y-m-d H:i:s ',time());
        $tempmodel = M('joymeTemplateModel');
        if($update_id){
            $result = $tempmodel->updateTempById($data,$update_id);
        }else{
            $result = $tempmodel->insertTemplate($data);
        }
        if($result){
            echo '操作成功 <a href="?c=template&a=index">返回列表</a>';
        }else{
            echo '操作失败 <a href="?c=template&a=createTemplatePage">返回添加</a>';
        }
    }

    //编辑
    function editTemplate(){

        global $GLOBALS;
        $template_id = Request::get('template_id');
        if(empty($template_id)){
            return '';
        }
        $tempmodel = M('joymeTemplateModel');
        $channemode = M('joymeChannelModel');
        $wikimodel = M('joymeWikiModel');
        $data['wiki_name'] = $wikimodel->allWikiName();
        $data['channe_name'] = $channemode->allChannelName();
        $data['item'] = $tempmodel->selectInfoByTempId($template_id);
        $data['static_url'] = $GLOBALS['static_url'];
        render($data,'web','template/edit_template');
    }
}