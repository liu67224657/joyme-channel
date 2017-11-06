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

class jChannelModel extends JoymeModel
{
    public $tableName = 'channel';

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

    //根据key获取cid
    public function getIDByKey($key){
        $rs = $this->selectRow('cid', $where = array('ckey'=>$key));
        if($rs){
            return $rs['cid'];
        }else{
            return 0;
        }
    }
}