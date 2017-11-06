<?php

/**
 * Created by PhpStorm.
 * User: xinshi
 * Date: 2015/10/29
 * Time: 17:08
 */
if (!defined('IN'))
    die('bad request');

use Joyme\db\JoymeModel;

class joymeWikiModel extends JoymeModel{

    public $fields = array();

    public $tableName = 'joyme_wiki';

    public function __construct() {

        $this->db_config = array(
            'hostname' => $GLOBALS['config']['rds']['db_host'],
            'username' => $GLOBALS['config']['rds']['db_user'],
            'password' => $GLOBALS['config']['rds']['db_password'],
            'database' => $GLOBALS['config']['rds']['db_name']
        );
        parent::__construct();
    }

    //wiki����
    public function allWikiName(){

        $sql = 'select distinct joyme_wiki_key from  joyme_wiki;';
        return $this->excuteSql($sql);
    }

    //��������
    public function allWikiList($conditions,$total,$page=1,$pageage=10){

        $skip = intval(($page-1)*$pageage);

        //���wiki��Ϊ��
        if(!empty($conditions['wiki_name'])){
            $where['joyme_wiki_key'] = $conditions['wiki_name'];
        }
        if($total){
            $count = $this->count($where);
        }else{
            $arr = $this->select('*',$where,'',$pageage,$skip);
        }
        $result = $total?$count:$arr;
        return $result;
    }

    //����ID��ѯ
    function selectInfoByWikiId($id){

        $where = array(
            'joyme_wiki_id'=>$id
        );
        return $this->selectRow('*',$where);
    }

    //����ID�޸�
    function updateWikiById($data,$update_id){

        $where = array(
            'joyme_wiki_id'=>$update_id
        );
        return $this->update($data, $where);
    }
}