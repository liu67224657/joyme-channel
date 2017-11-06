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

class jChannelGameModel extends JoymeModel
{
    public $tableName = 'channelgame';

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

    public function getData($where, $limit = 10, $skip = 0)
    {
        return $this->select('*', $where, 'id desc', $limit, $skip);
    }

    public function getRowData($where)
    {
        return $this->selectRow('*', $where);
    }

    public function getCount($where){
        return $this->count($where);
    }

    public function addData($data=array())
    {
        if($data){
            $count = $this->count(array(
                'cid' => $data['cid'],
                'gid' => $data['gid'],
                'datatype' => $data['datatype']
            ));
            if($count){
                return $this->update(array(
                    'gamename' => $data['gamename']
                ),array(
                    'cid' => $data['cid'],
                    'gid' => $data['gid'],
                    'datatype' => $data['datatype']
                ));
            }else{
                return $this->insert($data);
            }
        }else{
            return false;
        }
    }

}