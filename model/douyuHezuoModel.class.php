<?php if (!defined('IN')) {
    die('bad request');
}

/**
 * Description of BaiduHezuoModel
 * @author   ISLANDER
 * @datetime 2016-9-19 18:58:03
 */

/**
 * Description of BaiduHezuoModel
 *
 * @author wenleihou
 */
use Joyme\db\JoymeModel;

class douyuHezuoModel extends JoymeModel
{

    public $tableName = 'zhibo_douyu';

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

    public function getTotal($where)
    {
        return $this->count($where);
    }

    public function insertData($data = array())
    {
        return $this->insert($data);
    }

    public function getDataById($id = 0)
    {
        return $this->selectRow("*", array(
            'id'=>$id
        ));
    }

    public function updateData($data = array())
    {
        if (empty($data['id'])) {
            return false;
        }
        return $this->update($data, array(
            'id' => $data['id']
        ));
    }

    public function getMainCatData()
    {
        return array(
            '1'=>'资料',
            '2'=>'资讯',
            '3'=>'攻略',
            '4'=>'视频',
            '5'=>'礼包',
        );
    }

    public function getSonCatData()
    {
        return array(
            '1' => '最新资讯',
            '2' => '最新攻略',
            '3' => '英雄攻略',
            '4' => '攻略技巧',
            '5' => '出装攻略',
            '6' => '最新视频',
            '7' => '英雄视频',
            '8' => '解说视频',
            '9' => '其他',
        );
    }

}
