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

class cmsModel extends JoymeModel
{
    public $tableName = 'dede_addon17_lanmu';

    public function __construct()
    {
        $this->db_config = array(
            'hostname' => $GLOBALS['config']['rds']['db_host'],
            'username' => $GLOBALS['config']['rds']['db_user'],
            'password' => $GLOBALS['config']['rds']['db_password'],
            'database' => 'article_cms'
        );
        parent::__construct();
    }

    public function seltable($tablename){
        $this->tableName = $tablename;
    }

    //根据id获取body
    public function getBodyByIds($ids){
        $rs = $this->select(array('aid','body'), $where = array('aid'=>array('in',$ids)),'',10000);
        return $rs;
    }
}