<?php
/**
 * Created by PhpStorm.
 * User: xinshi
 * Date: 2015/10/29
 * Time: 12:12
 */
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );
use Joyme\core\Request;

class lableController extends appController{

    //首页显示
    public function index(){

        global $GLOBALS;
        $wikimodel = M('joymeWikiModel');
        $channemode = M('joymeChannelModel');
        $itemmodel = M('joymeItemModel');
        $pb_show_num = 50; //每页显示条数
        $pb_page = Request::get('pb_page',1); //获取当前页码
        $conditions['wiki_name'] = Request::getParam('wiki_name');
        $conditions['wiki_channe_name'] = Request::getParam('wiki_channe_name');
        $conditions['is_home'] = Request::getParam('is_home');
        $conditions['wiki_key'] = Request::getParam('wiki_key');
        $conditions['is_pc'] = Request::getParam('is_pc');
        $total = $itemmodel->allItemList($conditions,true);
        $data['item'] = $itemmodel->allItemList($conditions,false,$pb_page,$pb_show_num);
        $page = M('pageModel');
        $page->mainPage(array('total' => $total,'perpage'=>$pb_show_num,'nowindex'=>$pb_page,'pagebarnum'=>10));
        $data['page_str'] = $page->show(2,$conditions);
        $data['static_url'] = $GLOBALS['static_url'];
        $data['wiki_name'] = $wikimodel->allWikiName();
        $data['channe_name'] = $channemode->allChannelName();
        $data['param'] = $conditions;
        render($data,'web','digital/lable_list');
    }

    //添加标签页面显示
    function showAddLablePage(){

        global $GLOBALS;
        $wikimodel = M('joymeWikiModel');
        $channemode = M('joymeChannelModel');
        $data['wiki_name'] = $wikimodel->allWikiName();
        $data['channe_name'] = $channemode->allChannelName();
        $data['static_url'] = $GLOBALS['static_url'];
        render($data,'web','digital/add_lable');
    }

    //处理添加页面数据
    function addLableData(){

        $update_id = Request::post('update_id');  //是否是修改
        $data['context_path'] = Request::post('is_pc');  //PC/手机
        $data['wiki'] = Request::post('wiki_name');  //所属wiki
        $data['channel'] = Request::post('wiki_channel_name');  //所属渠道
        $data['is_index'] = Request::post('is_home');  //是否首页
        $isDefaultKey = Request::post('isDefaultKey');  //默认item key
        if($isDefaultKey==1){
            $data['item_key'] = Request::post('defaultIndexKey');  //默认item key
        }else{
            $data['item_key'] = Request::post('set_item_key');  //自定义item key
        }
        $data['item_description'] = Request::post('describe_item_key');  //key的描述
        $item_type = Request::post('itemType');  //所属类型
        $data['item_type'] = $item_type;
        if($item_type=='image'){
            //图片
            $json['imageUrl'] = Request::post('imageUrl');  //图片地址
            $json['imageLinkUrl'] = Request::post('imageLinkUrl');  //图片外链
            $json['imageIsBlank'] = Request::post('imageIsBlank');  //是否新页面打开
            $json['imageAlt'] = Request::post('imageAlt');  //图片ALT信息
            $json['imageWidth'] = Request::post('imageWidth');  //图片宽度信息
            $json['imageHeight'] = Request::post('imageHeight');  //图片高度信息
            $json['imageId'] = Request::post('imageId');  //图片ID
            $json['imageClass'] = Request::post('imageClass');  //图片Class
            $str = '';
            if(!empty($json['imageLinkUrl'] && $json['imageUrl'])){
                $str.='<a href="'.$json['imageLinkUrl'].'"';
            }elseif(!empty($json['imageIsBlank'])){
                $str.=' target="_blank" >';
            }
            $str.='<img src="'.$json['imageUrl'].'" ';
            if(!empty($json['imageId'])){
                $str.= 'id="'.$json['imageId'].'" ';
            }
            if(!empty($json['imageClass'])){
                $str.=' class="'.$json['imageClass'].'" ';
            }
            $str.=' width="'.$json['imageWidth'].'" height="'.$json['imageHeight'].'" alt="'.$json['imageAlt'].'" \></a>';
            $data['item_properties'] = json_encode($json);
            $data['item_context'] = $str;
        }elseif($item_type=='textlink'){
            //文本链
            $json['texturl'] = Request::post('texturl');  //文本链接
            $json['textInfo'] = Request::post('textInfo');  //文本信息
            $json['textIsBlank'] = Request::post('textIsBlank');  //是否新页面打开
            $json['textId'] = Request::post('textId');  //文本ID
            $json['textClass'] = Request::post('textClass');  //文本class
            $str = '<a';
            if(!empty($json['textId'])){
                $str.= ' id="'.$json['textId'];
            }else if(!empty($json['textClass'])){
                $str.= ' class="'.$json['textClass'].'\"';
            }else if(!empty($json['textIsBlank'])){
                $str.= ' target=_blank';
            }
            $str.= ' href="'.$json['texturl'].'>'.$json['textInfo']."<a>";
            $data['item_properties'] = json_encode($json);
            $data['item_context'] = $str;
        }elseif($item_type=='flash'){
            //flash
            $json['flashUrl'] = Request::post('flashUrl');  //flashURL
            $json['flashWidth'] = Request::post('flashWidth');  //flash宽度
            $json['flashHeight'] = Request::post('flashHeight');  //flash高度
            $str = '<object width="'.$json['flashWidth'].'" height="'.$json['flashHeight'].'">';
            $str.='<param name="movie" value="'.$json['flashUrl'].'"></param>  ';
            $str.='<param name="flashvars">';
            $str.='<param name="allowFullScreen" value="true"></param>';
            $str.='<param name="allowscriptaccess" value="always"></param>';
            $str.='<embed src="'.$json['flashUrl'].'" type="application/x-shockwave-flash\"';
            $str.=' allowscriptaccess="always" allowfullscreen="true"><\/embed>';
            $str.='<\/object>';
            $data['item_context'] = htmlspecialchars($str);
            $data['item_properties'] = json_encode($json);
        }elseif($item_type=='iframe'){
            //iframe
            $json['iframeUrl'] = Request::post('iframeUrl');  //iframeURL
            $json['iframeHeight'] = Request::post('iframeHeight');  //iframe高度
            $json['iframeWidth'] = Request::post('iframeWidth');  //iframe宽度
            $json['iframeId'] = Request::post('iframeId');  //iframeID
            $json['iframeClass'] = Request::post('iframeClass');  //iframeClass
            $str = '<iframe ';
            if(!empty($json['iframeId'])){
                $str.=' id="'.$json['iframeId'].'"';
            }elseif(!empty($json['iframeClass'])){
                $str.=' class="'.$json['iframeClass'].'"';
            }
            $str.=' src="'.$json['iframeUrl'].'" width="'.$json['iframeWidth'].'" height="'.$json['iframeHeight'].'"';
            $data['item_context'] = htmlspecialchars($str);
            $data['item_properties'] = json_encode($json);
        }elseif($item_type=='HTML'){
            //HTML
            $json['htmlContext'] = Request::post('htmlContext');  //htmlContext
            $data['item_context'] = $json['htmlContext'];
            $data['item_properties'] = json_encode($json);
        }
        $data['create_date'] = date('Y-m-d H:i:s ',time());
        $model = M('joymeItemModel');
        if(!empty($update_id)){
            $result = $model->updateItemById($data,$update_id);
        }else{
            $result = $model->insertItem($data);
        }
        if($result){
            echo '操作成功! <a href="?c=lable&a=index">返回列表</a>';
        }else{
            echo '操作失败! <a href="?c=lable&a=showAddLablePage">返回添加</a>';
        }
        exit;
    }

    //显示编辑页
    function showEditPage(){

        global $GLOBALS;

        $itemId = Request::get('item_id');  //htmlContext
        if(empty($itemId)){
            return ;
        }
        $model = M('joymeItemModel');
        $wikimodel = M('joymeWikiModel');
        $channemode = M('joymeChannelModel');
        $data['wiki_name'] = $wikimodel->allWikiName();
        $data['channe_name'] = $channemode->allChannelName();
        $data['wiki_name'] = $wikimodel->allWikiName();
        $data['static_url'] = $GLOBALS['static_url'];
        $dataInfo = $model->selectInfoByItemId($itemId);
        $data['item'] = $dataInfo;
        render($data,'web','digital/edit_lable');
    }
}