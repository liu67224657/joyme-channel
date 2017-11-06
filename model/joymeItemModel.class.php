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

class joymeItemModel extends JoymeModel{

    public $fields = array();

    public $tableName = 'joyme_item';

    public function __construct() {

        $this->db_config = array(
            'hostname' => $GLOBALS['config']['rds']['db_host'],
            'username' => $GLOBALS['config']['rds']['db_user'],
            'password' => $GLOBALS['config']['rds']['db_password'],
            'database' => $GLOBALS['config']['rds']['db_name']
        );
        parent::__construct();
    }

    public function allItemList($conditions,$total,$page=1,$pageage=10){

        $skip = intval(($page-1)*$pageage);

        if(!empty($conditions['wiki_name'])){
            $where['wiki'] = array('like','%'.$conditions['wiki_name'].'%');
        }

        if(!empty($conditions['wiki_channe_name'])){
            $where['channel'] = $conditions['wiki_channe_name'];
        }

        if(!empty($conditions['is_home'])){
            $where['is_index'] = $conditions['is_home'];
        }

        if(!empty($conditions['wiki_key'])){
            $where['item_key'] = $conditions['wiki_key'];
        }

        if(!empty($conditions['is_pc'])){
            $where['context_path'] = $conditions['is_pc'];
        }

        $files = 'joyme_item_id,wiki,channel,item_key,is_index,item_type';

        if($total){
            $count = $this->count($where);
        }else{
            $arr = $this->select($files,$where,'',$pageage,$skip);
        }
        $result = $total?$count:$arr;
        return $result;
    }

    function insertItem($data){

        return $this->insert($data);
    }

    function selectInfoByItemId($id){

        $where = array(
            'joyme_item_id'=>$id
        );
        return $this->selectRow('*',$where);
    }

    function updateItemById($data,$update_id){

        $where = array(
            'joyme_item_id'=>$update_id
        );
        return $this->update($data, $where);
    }
}