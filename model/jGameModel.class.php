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
use Joyme\net\Curl;

class jGameModel extends JoymeModel
{
    public $tableName = 'game';

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

    public function getData($where,$limit=10,$skip=0){
        return $this->select('*', $where, '', $limit, $skip);
    }

    public function getRowData($where){
        return $this->selectRow('*', $where);
    }
    public function addData($data){
        return $this->insert($data);
    }


    public function getsearchgame($gamename)
    {
        global $GLOBALS;
        if ($gamename) {
            $url = "http://api.joyme.".$GLOBALS['domain']."/collection/api/gamearchive/searchgame";
            $curl = new Curl();
            $result = $curl->Get($url,array(
                'searchtext'=> $gamename
            ));
            $result = json_decode($result,true);
            if ($result['rs']=='1') {
                return $result['result'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}