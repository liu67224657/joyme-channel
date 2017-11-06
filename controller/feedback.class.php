<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );
use Joyme\core\Request;

class feedbackController extends appController{

    function index(){

        global $GLOBALS;
        $dopinionmodel = M('wikiDopinionModel');
        $wikimodel = M('joymeWikiModel');
        $pb_show_num = 50; //ÿҳ��ʾ����
        $pb_page = Request::get('pb_page',1); //��ȡ��ǰҳ��
        $conditions['wiki_name'] = Request::getParam('wiki_name');
        $conditions['remove_state'] = Request::getParam('remove_state');
        $total = $dopinionmodel->allOpinionList($conditions,true);
        $data['item'] = $dopinionmodel->allOpinionList($conditions,false,$pb_page,$pb_show_num);
        $page = M('pageModel');
        $page->mainPage(array('total' => $total,'perpage'=>$pb_show_num,'nowindex'=>$pb_page,'pagebarnum'=>10));
        $data['page_str'] = $page->show(2,$conditions);
        $data['static_url'] = $GLOBALS['static_url'];
        $data['wiki_name'] = $wikimodel->allWikiName();
        $data['param'] = $conditions;
        render($data,'web','feedback/feedbacklist');
    }

    //����
    function removeState(){

        $remove_state = Request::getParam('remove_state');
        $opinion_id = Request::getParam('opinion_id');
        if(!in_array($remove_state,array(0,1)) || empty($opinion_id)){
            return ;
        }

        if(intval($remove_state) == 0){
            $remove_state = 1;
        }else{
            $remove_state = 0;
        }

        $dopinionmodel = M('wikiDopinionModel');
        $dopinionmodel->updateState($remove_state,$opinion_id);
        $url = 'http://'.$_SERVER['HTTP_HOST'].'?c=feedback&a=index';
        header("Location:$url");
    }
}