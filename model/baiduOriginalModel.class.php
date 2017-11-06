<?php

/**
 * Description:百度原创文章记录
 * Author: gradydong
 * Date: 2017/6/30
 * Time: 17:10
 * Copyright: Joyme.com
 */
if (!defined('IN')) {
    die('bad request');
}
use Joyme\db\JoymeModel;

class baiduOriginalModel extends JoymeModel
{
    public $tableName = 'baidu_original';

    public function __construct()
    {
        $this->db_config = array(
            'hostname' => $GLOBALS['config']['rds']['db_host'],
            'username' => $GLOBALS['config']['rds']['db_user'],
            'password' => $GLOBALS['config']['rds']['db_password'],
            'database' => $GLOBALS['config']['rds']['db_name']
        );
        parent::__construct();
    }
}