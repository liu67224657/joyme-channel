<?php

/**
 * 备份
 * @author auto create
 */
class Backup
{
	
	/** 
	 * 是否提供备份文件下载链接URL
	 **/
	public $backup_download;
	
	/** 
	 * 备份结束时间
	 **/
	public $backup_end_time;
	
	/** 
	 * 备份ID
	 **/
	public $backup_set_id;
	
	/** 
	 * 备份文件大小
	 **/
	public $backup_set_size;
	
	/** 
	 * 备份状态
	 **/
	public $backup_set_status;
	
	/** 
	 * 本次备份开始时间
	 **/
	public $backup_start_time;
	
	/** 
	 * 备份类型
	 **/
	public $backup_type;
	
	/** 
	 * 实例名
	 **/
	public $db_instance_name;
	
	/** 
	 * 备份模式
	 **/
	public $job_mode;	
}
?>