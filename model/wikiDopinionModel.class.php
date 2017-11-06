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

class wikiDopinionModel extends JoymeModel{

    public $fields = array();

    public $tableName = 'wiki_card_opinion';

    public function __construct() {

        $this->db_config = array(
            'hostname' => $GLOBALS['config']['rds']['db_host'],
            'username' => $GLOBALS['config']['rds']['db_user'],
            'password' => $GLOBALS['config']['rds']['db_password'],
            'database' => $GLOBALS['config']['rds']['db_name']
        );
        parent::__construct();
    }

    //�����б�
    public function allOpinionList($conditions,$total,$page=1,$pageage=10){

        $skip = intval(($page-1)*$pageage);

        //���wiki��Ϊ��
        if(!empty($conditions['wiki_name'])){
            $where['wiki'] = $conditions['wiki_name'];
        }
        //�Ƿ�ɾ��
        if(!empty($conditions['remove_state'])){
            $where['remove_state'] = $conditions['remove_state'];
        }
        if($total){
            $count = $this->count($where);
        }else{
            $arr = $this->select('*',$where,'',$pageage,$skip);
        }
        $result = $total?$count:$arr;
        return $result;
    }

    //����
    function updateState($remove_state,$opinion_id){

       $data = array(
           'remove_state'=>$remove_state
       );
       $where = array(
           'opinion_id'=>$opinion_id
       );
       return $this->update($data, $where);
    }
}