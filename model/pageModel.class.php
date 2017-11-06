<?php

/**

 * description:超强分页类，四种分页模式，默认采用类似baidu,google的分页风格。
 * 支持自定义风格，自定义样式，同时支持PHP4和PHP5,
 * example:
 * 模式四种分页模式：
require_once('../libs/classes/page.class.php');
$page=new page(array('total'=>1000,'perpage'=>20));
echo 'mode:1<br>'.$page->show();
echo '<hr>mode:2<br>'.$page->show(2);
echo '<hr>mode:3<br>'.$page->show(3);
echo '<hr>mode:4<br>'.$page->show(4);
开启AJAX：
$ajaxpage=new page(array('total'=>1000,'perpage'=>20,'ajax'=>'ajax_page','page_name'=>'test'));
echo 'mode:1<br>'.$ajaxpage->show();
采用继承自定义分页显示模式：
 */
class pageModel
{

    /**
     * config ,public
     */
    public $page_name = "pb_page"; //page标签，用来控制url页。比如说xxx.php?pb_page=2中的pb_page
    public $next_page = '>'; //下一页
    public $pre_page = '<'; //上一页
    public $first_page = 'First'; //首页
    public $last_page = 'Last'; //尾页
    public $pre_bar = '<<'; //上一分页条
    public $next_bar = '>>'; //下一分页条
    public $format_left = '';
    public $format_right = '';
    public $is_ajax = false; //是否支持AJAX分页模式
    public $total_num = " ";  //总条数
    /**
     * private
     *
     */
    public $pagebarnum = 6; //控制记录条的个数。
    public $totalpage = 0; //总页数
    public $ajax_action_name = ''; //AJAX动作名
    public $nowindex = 1; //当前页
    public $url = ""; //url地址头
    public $offset = 0;

    /**
     * constructor构造函数
     *
     * @param array $array['total'],$array['perpage'],$array['nowindex'],$array['url'],$array['ajax']...
     */
    function mainPage($array)
    {
    	$this->total_num = $array['total'];
        if (is_array($array)) {
            if (!array_key_exists('total', $array)
            ) $this->error(__FUNCTION__, 'need a param of total');
            $total = intval($array['total']);
            $perpage = (array_key_exists('perpage', $array)) ? intval($array['perpage']) : 10;
            $nowindex = (array_key_exists('nowindex', $array)) ? intval($array['nowindex']) : '';
            $url = (array_key_exists('url', $array)) ? $array['url'] : '';
            $flag = (array_key_exists('flag', $array)) ? $array['flag'] : '';
            $pagebarnum = (array_key_exists('pagebarnum', $array)) ? $array['pagebarnum'] : '';
        } else {
            $total = $array;
            $perpage = 10;
            $nowindex = '';
            $url = '';
        }
        if ((!is_int($total)) || ($total < 0)
        ) $this->error(__FUNCTION__, $total . ' is not a positive integer!');
        if ((!is_int($perpage)) || ($perpage <= 0)
        ) $this->error(__FUNCTION__, $perpage . ' is not a positive integer!');
        if (!empty($array['page_name'])
        ) $this->set('page_name', $array['page_name']); //设置pagename

        if($pagebarnum){
            $this->pagebarnum = $pagebarnum;
        }
        $this->_set_nowindex($nowindex); //设置当前页
        $this->_set_url($url,$flag); //设置链接地址
        $this->totalpage = ceil($total / $perpage);
        $this->offset = ($this->nowindex - 1) * $perpage;
        if (!empty($array['ajax'])
        ) $this->open_ajax($array['ajax']); //打开AJAX模式

    }

    /**
     *  设定类中指定变量名的值，如果改变量不属于这个类，将throw一个exception
     * @param $var
     * @param $value
     */
    function set($var, $value)
    {
        if (in_array($var, get_object_vars($this)))
            $this->$var = $value;
        else {
            $this->error(__FUNCTION__, $var . " does not belong to pb_page!");
        }
    }
	
    
    /**
     *  * 打开倒AJAX模式
     * @param string $action 默认ajax触发的动作。
     */
    function open_ajax($action)
    {
        $this->is_ajax = true;
        $this->ajax_action_name = $action;
    }

    /**
     *  获取显示"下一页"的代码
     * @param string $style
     * @return string
     */
    function next_page($style = '')
    {
        if ($this->nowindex < $this->totalpage) {
            return $this->_get_link($this->_get_url($this->nowindex + 1), $this->next_page, $style);
        }
        return '<span class="'.$style.'" ><a href="javascript:void(0);">' . $this->next_page . "</a></span>";
    }

    function total_num($style = ''){
        return '<span class="'.$style.'" ><a href="javascript:void(0);">共' . $this->total_num . "条</a></span>";
    }
    /**
     *  获取显示“上一页”的代码
     * @param string $style
     * @return string
     */
    function pre_page($style = '')
    {
        if ($this->nowindex > 1) {
            return $this->_get_link($this->_get_url($this->nowindex - 1), $this->pre_page, $style);
        }
        return '<span class="'.$style.'" ><a href="javascript:void(0);">' . $this->pre_page . "</a></span>";
    }

    
    /**
     *  获取显示“首页”的代码
     * @param string $style
     * @return string
     */
    function first_page($style = '')
    {
        if ($this->nowindex == 1) {
            return '<span class="'.$style.'" > <a href="javascript:void(0);">' . $this->first_page . "</a></span>";
        }
        return $this->_get_link($this->_get_url(1), $this->first_page, $style);
    }

    
    
    /**
     *  获取显示“尾页”的代码
     * @param string $style
     * @return string
     */
    function last_page($style = '')
    {
        if ($this->nowindex == $this->totalpage) {
            return '<span class="'.$style.' "> <a href="javascript:void(0);">' . $this->last_page . "</a></span>";

        }
        return $this->_get_link($this->_get_url($this->totalpage), $this->last_page, $style);
    }
		
    /**
     *  中间页码
     * @param string $style
     * @param string $nowindex_style
     * @return string
     */
    function nowbar($style = '', $nowindex_style = '')
    {
        $plus = ceil($this->pagebarnum / 2);
        if ($this->pagebarnum - $plus + $this->nowindex > $this->totalpage
        ) $plus = ($this->pagebarnum - $this->totalpage + $this->nowindex);
        $begin = $this->nowindex - $plus + 1;
        $begin = ($begin >= 1) ? $begin : 1;
        $return = '';

        for ($i = $begin; $i < $begin + $this->pagebarnum; $i++) {

            if ($i <= $this->totalpage) {
                if ($i != $this->nowindex){
                    $return .= $this->_get_text($this->_get_link($this->_get_url($i), $i, $style));
                }else{
                    $return .= $this->_get_text('<span class="'.$nowindex_style.'" ><a href="javascript:void(0);">' . $i . '</a></span>');
                }


            } else {
                break;
            }
            $return .= "\n";
        }

        unset($begin);
        return $return;
    }

    //拼接搜索条件
    function map($conditions){

        $j = 0;
        if(count($conditions)>0){
            $map = '&';
        }
        foreach($conditions as $k=>$v){
            $j ++;
            if($v != ''){
                if(count($conditions) == $j){
                    $map.= $k."=".$v;
                }else{
                    $map.= $k."=".$v."&";
                }
            }
        }
        return $map;
    }
    /**
     *  获取显示跳转按钮的代码
     * @return string
     */
    function select()
    {
        $return = '<select name="pb_page_Select" onblur="selectfun();" >';
        for ($i = 1; $i <= $this->totalpage; $i++) {
            if ($i == $this->nowindex) {
                $return .= '<option value="' . $i . '" selected>' . $i . '</option>';
            } else {
                $return .= '<option value="' . $i . '">' . $i . '</option>';
            }
        }
        unset($i);
        $return .= '</select>';
        return $return;
    }

    /**
     *  获取mysql 语句中limit需要的值
     * @return int
     * @author liupeng@qinqinbaby.com
     * @copyright 东方乐购信息技术有限公司.
     */
    function offset()
    {
        return $this->offset;
    }

    /**
     *  控制分页显示风格（你可以增加相应的风格）
     * @param int $mode  分页样式  1默认样式（无样式） 2admin管理后台分页样式 3my会员中心所用样式
     * @return string
     * @author liupeng@qinqinbaby.com
     * @copyright 东方乐购信息技术有限公司.
     */
    function show($mode = 1,$conditions=false)
    {
        global $TJ;
        $TJ = $conditions;
        switch ($mode) {
            case '0':
                $this->next_page = '下一页';
                $this->pre_page = '上一页';
                $this->first_page = '第一页';
                $this->last_page = '最后一页';
                return $this->first_page() . '&nbsp;' . $this->pre_page() . '&nbsp;' . $this->nowbar() . $this->next_page() . '&nbsp;' . $this->last_page();
                break;
            case '1':
                $this->next_page = '下一页';
                $this->pre_page = '上一页';
                $this->first_page = '首页';
                $this->last_page = '尾页';
                return $this->first_page() . '&nbsp;' . $this->pre_page() . '&nbsp;' . $this->nowbar() . $this->next_page() . '&nbsp;' . $this->last_page();
                break;
            case '2':
                $this->next_page = '下一页';
                $this->pre_page = '上一页';
                $this->first_page = '首页';
                $this->last_page = '尾页';
                $str  = '<div class=admin_page>';
                $str .=$this->first_page('first_page');
                $str .=$this->pre_page('pre_page');
                $str .=$this->nowbar("now_page","active");
                $str .=$this->next_page("next_page");
                $str .=$this->last_page("last_page");
                $str .=$this->total_num("total_num");
                $str .='</div>';
                return $str;
                break;
            case '3':
                $this->next_page = '下一页';
                $this->pre_page = '上一页';
                $this->first_page = '首页';
                $this->last_page = '尾页';
                $str  = '<div class="my_page">';
                $str .=$this->first_page('first_page');
                $str .=$this->pre_page('pre_page');
                $str .=$this->nowbar("now_page","vvgrzxactive");
                $str .=$this->next_page("next_page");
                $str .=$this->last_page("last_page");
                $str .='</div>';
                return $str;
                break;
            default:
                $this->next_page = '下一页';
                $this->pre_page = '上一页';
                return $this->pre_page() . $this->nowbar() . $this->next_page();

        }
    }

    /**
     *  设置url头地址
     * @param string $url
     */
    function _set_url($url = "",$flag=false)
    {
        if (!empty($url)) {
            //手动设置
            if($flag){
                $this->url = $url . '&'. $this->page_name . "=";
            }else{
                $this->url = $url . ((stristr($url, '?')) ? '&' : '?') . $this->page_name . "=";
            }
        } else {
            //自动获取
            if (empty($_SERVER['QUERY_STRING'])) {
                //不存在QUERY_STRING时
                $this->url = $_SERVER['REQUEST_URI'] . "?" . $this->page_name . "=";
            } else {
                //
                if (stristr($_SERVER['QUERY_STRING'], $this->page_name . '=')) {
                    //地址存在页面参数
                    $this->url = str_replace($this->page_name . '=' . $this->nowindex, '', $_SERVER['REQUEST_URI']);
                    $last = $this->url[strlen($this->url) - 1];
                    if ($last == '?' || $last == '&') {
                        $this->url .= $this->page_name . "=";
                    } else {
                        $this->url .= '&' . $this->page_name . "=";
                    }
                } else {
                    $REQUEST_URI = explode('?',$_SERVER['REQUEST_URI']);
                    if(isset($REQUEST_URI[1])){
                      //  echo  $url = $_SERVER['REQUEST_URI'] . '&page_name=';
                        $this->url = $_SERVER['REQUEST_URI'] . '&' . $this->page_name . '=';
                    }else{
                      //  echo  $url = $_SERVER['REQUEST_URI'] . '?&page_name=';
                        $this->url = $_SERVER['REQUEST_URI'] . '?' . $this->page_name . '=';
                    }
                 //   $this->url = $_SERVER['REQUEST_URI'] . '&' . $this->page_name . '=';
                }
                //end if
            }
            //end if
        }
        //end if
    }

    /**
     *  设置当前页面
     * @param $nowindex
     */
    function _set_nowindex($nowindex)
    {
        if (empty($nowindex)) {
            //系统获取

            if (isset($_GET[$this->page_name])) {
                $this->nowindex = intval($_GET[$this->page_name]);
            }
        } else {
            //手动设置
            $this->nowindex = intval($nowindex);
        }
    }

    /**
     *  为指定的页面返回地址值
     * @param int $pageno
     * @return string
     */
    function _get_url($pageno = 1)
    {
       global $TJ;
       if($TJ){
           $map = $this->map($TJ);
       }
       return $this->url . $pageno.$map;
    }

    /**
     *  获取分页显示文字，比如说默认情况下_get_text('<a href="">1</a>')将返回[<a href="">1</a>]
     * @param $str
     * @return string
     */
    function _get_text($str)
    {
        return $this->format_left . $str . $this->format_right;
    }

    /**
     *  获取链接地址
     * @param $url
     * @param $text
     * @param string $style
     * @return string
     */
    function _get_link($url, $text, $style = '')
    {
        $style = (empty($style)) ? '' : 'class="' . $style . '"';
        if ($this->is_ajax) {
            //如果是使用AJAX模式
            return '<span '.$style.'><a  href="javascript:' . $this->ajax_action_name . '(\'' . $url . '\')">' . $text . '</a></span>';
        } else {

            return '<span '.$style.' ><a  href="' . $url . '">' . $text . '</a></span>';
        }
    }

    /**
     * 出错处理方式
     * @param $function
     * @param $errormsg
     */
    function error($function, $errormsg)
    {
        die('Error in file <b>' . __FILE__ . '</b> ,Function <b>' . $function . '()</b> :' . $errormsg);
    }

}

?>