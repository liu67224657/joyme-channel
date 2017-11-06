<?php

/**
 * Description:
 * Author: gradydong
 * Date: 2017/4/19
 * Time: 14:20
 * Copyright: Joyme.com
 */
if (!defined('IN')) {
    die('bad request');
}
use Joyme\db\JoymeModel;

class jSourceDataModel extends JoymeModel
{
    public $tableName = 'sourcedata';

    public function __construct()
    {
        $this->db_config = array(
            'hostname' => $GLOBALS['config']['db']['db_host'],
            'username' => $GLOBALS['config']['db']['db_user'],
            'password' => $GLOBALS['config']['db']['db_password'],
            'database' => $GLOBALS['config']['db']['channel_db_name']
        );
        parent::__construct();
    }

    //根据id批量查询数据
    public function getData($where,$limit=10,$skip=0){
        return $this->select('*', $where, '', $limit, $skip);
    }

    public function getRowData($where){
        return $this->selectRow('*', $where);
    }

    public function addData($data){
        return $this->insert($data);
    }
}