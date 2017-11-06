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

use Joyme\core\Log;

class joymeChannelModel extends JoymeModel{

    public $fields = array();

    public $tableName = 'joyme_channel';

    public function __construct() {

        $this->db_config = array(
            'hostname' => $GLOBALS['config']['rds']['db_host'],
            'username' => $GLOBALS['config']['rds']['db_user'],
            'password' => $GLOBALS['config']['rds']['db_password'],
            'database' => $GLOBALS['config']['rds']['db_name']
        );
        parent::__construct();
    }

    public function allChannelName(){

        $files = 'channel_name';
        return $this->select($files);
    }

}