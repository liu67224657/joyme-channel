<?php
/**
 * Created by PhpStorm.
 * User: xinshi
 * Date: 2015/10/29
 * Time: 12:21
 */
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );
use Joyme\core\Request;

class joymewikiController extends appController{

    protected $accessKeyId = 'm2LJu94lrAKPMGBm';
    protected $accessKeySecret = 'jO3aBvvxQKfBoBEHXadiLhG0YFi8OJ';
    protected $serverUrl = 'http://rds.aliyuncs.com/';
    protected $dBInstanceId = 'rdsnu7brenu7bre';
    private $accountName = 'wikiuser';

    private $dbName = '';
    private $charSet = 'utf8';
    private $dbDescription = '';

    public $table_num = 92;
    public $file_path = './public/data/wiki.sql';
    public $sh_path = './public/sh/';
    public $wiki_key = null;

    function index(){

        global $GLOBALS;
        $wikimodel = M('joymeWikiModel');
        $pb_show_num = 50; //每页显示条数
        $pb_page = Request::get('pb_page',1); //获取当前页码
        $conditions['wiki_name'] = Request::getParam('wiki_name');
        $total = $wikimodel->allWikiList($conditions,true);
        $data['item'] = $wikimodel->allWikiList($conditions,false,$pb_page,$pb_show_num);
        $page = M('pageModel');
        $page->mainPage(array('total' => $total,'perpage'=>$pb_show_num,'nowindex'=>$pb_page,'pagebarnum'=>10));
        $data['page_str'] = $page->show(2,$conditions);
        $data['static_url'] = $GLOBALS['static_url'];
        $data['wiki_name'] = $wikimodel->allWikiName();
        $data['param'] = $conditions['wiki_name'];
        render($data,'web','wiki/wiki_list');
    }

    //编辑
    function showEditPage(){

        global $GLOBALS;
        $wiki_id = Request::get('wiki_id');
        if(empty($wiki_id)){
            return '';
        }
        $wikimodel = M('joymeWikiModel');
        $data['item'] = $wikimodel->selectInfoByWikiId($wiki_id);
        $data['static_url'] = $GLOBALS['static_url'];
        render($data,'web','wiki/edit_wiki');
    }

    function updateWikiData(){

        $update_id = Request::post('wiki_id');
        $data['joyme_wiki_key'] = Request::post('wiki_key');
        $data['context_path'] = Request::post('wiki_type');
        $data['joyme_wiki_domain'] = Request::post('wiki_domain');
        $data['joyme_wiki_name'] = Request::post('wiki_name');
        $data['support_subdomain'] = Request::post('supportSubDomain');
        $data['pc_keep_jscss'] = Request::post('pcKeepJscss');
        $data['m_keep_jscss'] = Request::post('mKeepJscss');
        $wikimodel = M('joymeWikiModel');
        if($wikimodel->updateWikiById($data,$update_id)){
            echo '操作成功 <a href="?c=joymewiki&a=index">返回列表</a>';
        }else{
            echo '操作失败 <a href="?c=joymewiki&a=index">返回列表</a>';
        }
    }

    //新开wiki
    function createWiki(){

        global $GLOBALS;
        if($_POST){
            $result = false;
            $data['site_name'] = Request::post('wiki_name');
            $wiki_key = strtolower(Request::post('wiki_key'));
            $data['site_type'] = Request::post('wiki_type');
            $data['second_domain'] = Request::post('is_secondary_domain');
            $data['create_reason'] = Request::post('create_reason');
            $data['create_remark'] = Request::post('create_note');
            $data['create_time'] = time();
            $data['user_name'] = $_COOKIE['joume_username'];
            $wiki_type = $data['site_type'];
            $site_name = $data['site_name'];
            $wiki_title = Request::post('wiki_title');
            $is_mobile = Request::post('is_mobile');
            $wiki_keywords = Request::post('wiki_keywords');
            $wiki_description = Request::post('wiki_description');
            if(empty($wiki_key)|| empty($data['site_name']) || empty($data['user_name']) || empty($data['site_type'])||empty($data['create_reason'])){
                jsonEncode(array('rs'=>1,'msg'=>'Important parameters to be empty','data'=>''));
            }
            $data['site_key'] = $wiki_key.'wiki';
            $user_editstatus = $wiki_type==1?1:0;
            $this->dbName = $data['site_key'];
            //创建数据库
            $wiikimodel = M('createDatabaseModel');
            if($GLOBALS['domain'] == 'com'){
                $this->dbDescription = $wiki_description;
                if(count($this->alyfindDataBase())>=1){
                    if($wiikimodel->getTableNumByDbName($this->dbName)==0){
                        $result = true;
                    }
                }else{
                    $result = $this->alyCreateDatabase();
                    sleep(5);
                }
            }else{
                $result = $wiikimodel->createDataBase($this->dbName);
            }
            if($result){
                //创建表
                $createmodel = M('wikiCreateModel');
                $wiikimodel->createTable($this->createSql(),$this->dbName);
                if($wiikimodel->getTableNumByDbName($this->dbName)==$this->table_num){
                    $wiikimodel->insertSeoTable($wiki_type,$site_name,$wiki_title,$wiki_keywords,$wiki_description,$user_editstatus,$is_mobile);
                    $data['site_key'] = $wiki_key;
                    if($createmodel->addData($data)){
                        jsonEncode(array('rs'=>0,'msg'=>'The table creation success','data'=>''));
                    }else{
                        jsonEncode(array('rs'=>2,'msg'=>'The wiki log write failed','data'=>''));
                    }
                }else{
                    jsonEncode(array('rs'=>3,'msg'=>'The table creation failed','data'=>''));
                }
            }else{
                jsonEncode(array('rs'=>4,'msg'=>'The database creation failed','data'=>''));
            }
        }else{
            $data['static_url'] = $GLOBALS['static_url'];
            render($data,'web','wiki/create_wiki');
        }
    }
	
	//创建成功后执行的脚本
	//$wiki_type 1 原生wiki 2 数字站wiki
	function addsh(){

		global $GLOBALS;
        $wikikey = strtolower(Request::getParam('wikikey'));
        $wiki_type = Request::getParam('wiki_type');
		if($wiki_type == '1'){
			$rs = shell_exec("/usr/bin/sudo python ".$this->sh_path."add_nginx_rule.py ".$wikikey." ".$GLOBALS['domain']);
			if(intval($rs) == 0){
				shell_exec('/usr/bin/sudo /usr/local/nginx/sbin/nginx -s reload -c /usr/local/nginx/conf/nginx.conf');
			}else{
                jsonEncode(array('rs'=>5,'msg'=>'nginx conf check fail','data'=>''));
            }
		}
        echo '<meta charset="UTF-8">';
        echo 'DB创建完毕，nginx重启完毕，现在去创建内网解析...';
		$sign = md5($wikikey.$wiki_type.$GLOBALS['domain'].'*&^jfFPGN^5fsf#;');
		$url = 'http://t.enjoyf.com/wiki/createwiki.php?wikikey='.$wikikey.'&wikitype='.$wiki_type.'&domain='.$GLOBALS['domain'].'&sign='.$sign;
		echo "<meta http-equiv='refresh' content='1;url=\"{$url}\"'>";exit;
	}


	//重启nginx
	function reloadnginx(){

		shell_exec('/usr/bin/sudo /usr/local/nginx/sbin/nginx -s reload -c /usr/local/nginx/conf/nginx.conf');
        global $GLOBALS;
        $data['static_url'] = $GLOBALS['static_url'];
        $time = time()+600;
        $data['y'] = date('Y',$time);
        $data['m'] = date('m',$time);
        $data['d'] = date('d',$time);
        $data['h'] = date('H',$time);
        $data['i'] = date('i',$time);
        $data['s'] = date('s',$time);
        render($data,'web','wiki/tip_success');
	}


    function againReloadNginx(){

        shell_exec('/usr/bin/sudo /usr/local/nginx/sbin/nginx -s reload -c /usr/local/nginx/conf/nginx.conf');
        $this->wikiList();
    }


    //Ali cloud query the database exists
    function alyfindDataBase(){

        include_once AROOT.'public'. DS .'aliyun'. DS .'TopSdk.php';
        $c = new AliyunClient;
        $c->accessKeyId = $this->accessKeyId;
        $c->accessKeySecret = $this->accessKeySecret;
        $c->serverUrl=$this->serverUrl;

        $req = new Rds20130528DescribeDatabasesRequest();
        $req->setdBInstanceId($this->dBInstanceId);
        $req->setdBName($this->dbName);

        $resp = $c->execute($req);
        return $resp->Databases->Database;
    }

    //Ali cloud create the database
    function alyCreateDatabase(){

        include_once AROOT.'public'. DS .'aliyun'. DS .'TopSdk.php';
        $c = new AliyunClient;
        $c->accessKeyId = $this->accessKeyId;
        $c->accessKeySecret = $this->accessKeySecret;
        $c->serverUrl=$this->serverUrl;
        $req = new Rds20130528CreateDatabaseRequest();
        $req->setAccountName($this->accountName);
        $req->setdBName($this->dbName);
        $req->setCharacterSetName($this->charSet);
        $req->setdBDescription($this->dbDescription.' ');
        $req->setdBInstanceId($this->dBInstanceId);
        $resp = $c->execute($req);
        if(!isset($resp->Code)){
            return true;
        }
        return false;
    }

    //新开wiki列表
    function wikiList(){

        $model = M('wikiCreateModel');
        $pb_show_num = 50; //每页显示条数
        $pb_page = Request::get('pb_page',1); //获取当前页码
        $conditions['wiki_type'] = Request::getParam('wiki_type');
        $conditions['create_reason'] = Request::getParam('create_reason');
        $conditions['start_time'] = strtotime(Request::getParam('start_time'));
        $conditions['end_time'] = strtotime(Request::getParam('end_time'));
        $total = $model->allWikiList($conditions,true);
        $data['item'] = $model->allWikiList($conditions,false,$pb_page,$pb_show_num);

        $page = M('pageModel');
        $conditions['start_time'] = Request::getParam('start_time');
        $conditions['end_time'] = Request::getParam('end_time');
        $page->mainPage(array('total' => $total,'perpage'=>$pb_show_num,'nowindex'=>$pb_page,'pagebarnum'=>10));
        $data['page_str'] = $page->show(2,$conditions);
        $data['static_url'] = $GLOBALS['static_url'];
        $data['param'] = $conditions;
        render($data,'web','wiki/wiki_log_list');
    }

    //get createSql
    function createSql(){

        if(file_exists($this->file_path)){
            $lines=file($this->file_path);
            $sqlstr="";
            foreach($lines as $line){
                $line=trim($line);
                if($line!=""){
                    if(!($line{0}=="#" || $line{0}.$line{1}=="--")){
                        $sqlstr.=$line;
                    }
                }
            }
            $sqls=explode(";",rtrim($sqlstr,";"));
            return $sqls;
        }else{
            jsonEncode(array('rs'=>6,'msg'=>'Could not find wiki.sql','data'=>''));
        }
    }

    //check wiki key
    function checkWikiKeyIsExist(){

        $wiki_key = Request::post('wiki_key');
        $model = M('wikiCreateModel');
        $result = $model->getWikiInfoByWikiKey($wiki_key);
        $this->checkResult($result);
    }


    function test(){

        print_r($this->createSql());
    }
}