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

class joymeTemplateModel extends JoymeModel{

    public $fields = array();

    public $tableName = 'joyme_template';

    public function __construct() {

        $this->db_config = array(
            'hostname' => $GLOBALS['config']['rds']['db_host'],
            'username' => $GLOBALS['config']['rds']['db_user'],
            'password' => $GLOBALS['config']['rds']['db_password'],
            'database' => $GLOBALS['config']['rds']['db_name']
        );
        parent::__construct();
    }

    //ģ���б�
    public function allTemplateList($conditions,$total,$page=1,$pageage=10){

        $skip = intval(($page-1)*$pageage);

        //���wiki��Ϊ��
        if(!empty($conditions['wiki_name'])){
            $where['wiki'] = array('like','%'.$conditions['wiki_name'].'%');
        }
        //���������Ϊ��
        if(!empty($conditions['wiki_channe_name'])){
            $where['channel'] = $conditions['wiki_channe_name'];
        }
        //�������ҳ
        if(!empty($conditions['is_home'])){
            $where['is_index'] = $conditions['is_home'];
        }
        //�����PC
        if(!empty($conditions['is_pc'])){
            $where['context_path'] = $conditions['is_pc'];
        }
        $fields = 'joyme_template_id,template_name,channel,wiki,is_index,context_path';
        if($total){
            $count = $this->count($where);
        }else{
            $arr = $this->select($fields,$where,'',$pageage,$skip);
        }
        $result = $total?$count:$arr;

//        print_r($this->getQuerySql());

        return $result;
    }

    //����¼��
    function insertTemplate($data){

        return $this->insert($data);
//        print_r($this->getQuerySql());
    }

    //����ID��ѯ
    function selectInfoByTempId($id){

        $where = array(
            'joyme_template_id'=>$id
        );
        return $this->selectRow('*',$where);
    }

    //����ID�޸�
    function updateTempById($data,$update_id){

        $where = array(
            'joyme_template_id'=>$update_id
        );
        return $this->update($data, $where);
    }
}