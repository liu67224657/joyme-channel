<?php

/**
 * 资源
 * @author auto create
 */
class Resource
{
	
	/** 
	 * 备份占用空间，单位：Byte
	 **/
	public $backup_size;
	
	/** 
	 * 数据文件占用空间，单位：Byte
	 **/
	public $data_size;
	
	/** 
	 * 实例id
	 **/
	public $db_instance_id;
	
	/** 
	 * 已用空间（DataSize+ LogSize），单位：Byte
	 **/
	public $disk_used;
	
	/** 
	 * 数据库类型
	 **/
	public $engine;
	
	/** 
	 * 日志占用空间，单位：Byte
	 **/
	public $log_size;
	
	/** 
	 * 流入流量，单位：Byte
	 **/
	public $network_in;
	
	/** 
	 * 流出流量，单位：Byte
	 **/
	public $network_out;	
}
?>