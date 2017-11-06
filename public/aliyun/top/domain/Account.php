<?php

/**
 * Rds云产品账户
 * @author auto create
 */
class Account
{
	
	/** 
	 * 帐号备注信息
	 **/
	public $account_description;
	
	/** 
	 * DB操作帐号名称
	 **/
	public $account_name;
	
	/** 
	 * 帐号状态
	 **/
	public $account_status;
	
	/** 
	 * Database Privilege对象
	 **/
	public $database_privilege;
	
	/** 
	 * 帐号所属实例名称
	 **/
	public $db_instance_id;	
}
?>