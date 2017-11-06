<?php

// db functions
function db( $host = null , $port = null , $user = null , $password = null , $db_name = null )
{
	$db_key = MD5( $host .'-'. $port .'-'. $user .'-'. $password .'-'. $db_name  );

	if( !isset( $GLOBALS['LP_'.$db_key] ) )
	{
		include_once( AROOT .  'config/db.config.php' );
		//include_once( CROOT .  'lib/db.function.php' );
		$db_config = $GLOBALS['config']['db'];
		if( $host == null ) $host = $db_config['db_host'];
		if( $port == null ) $port = $db_config['db_port'];
		if( $user == null ) $user = $db_config['db_user'];
		if( $password == null ) $password = $db_config['db_password'];
		if( $db_name == null ) $db_name = $db_config['db_name'];

		if( !$GLOBALS['LP_'.$db_key] = @mysql_connect( $host.':'.$port , $user , $password , true ) )
		{
			//
			echo 'can\'t connect to database';
			return false;
		}
		else
		{
			if( $db_name != '' )
			{
				if( !mysql_select_db( $db_name , $GLOBALS['LP_'.$db_key] ) )
				{
					echo 'can\'t select database ' . $db_name ;
					return false;
				}
			}
		}
		
		mysql_query( "SET NAMES 'UTF8'" , $GLOBALS['LP_'.$db_key] );
	}
	
	return $GLOBALS['LP_'.$db_key];
}

function s( $str , $db = NULL )
{
	if( $db == NULL ) $db = db();
	return   mysql_real_escape_string( $str , $db )  ;
}

// $sql = "SELECT * FROM `user` WHERE `name` = ?s AND `id` = ?i LIMIT 1 "
function prepare( $sql , $array )
{
	
	foreach( $array as $k=>$v )
		$array[$k] = s($v );
	$reg = '/\?([is])/i';
	$sql = preg_replace_callback( $reg , 'prepair_string' , $sql  );
	$count = count( $array );
	for( $i = 0 ; $i < $count; $i++ )
	{
		$str[] = '$array[' .$i . ']';	
	}
	$statement = '$sql = sprintf( $sql , ' . join( ',' , $str ) . ' );';
	eval( $statement );
	return $sql;
}

function prepair_string( $matches )
{
	if( $matches[1] == 's' ) return "'%s'";
	if( $matches[1] == 'i' ) return "'%d'";	
}


function get_data( $sql , $db = NULL )
{
	if( $db == NULL ) $db = db();
	$GLOBALS['LP_LAST_SQL'] = $sql;
	$data = Array();
	$i = 0;
	$result = mysql_query( $sql ,$db );
	if( mysql_errno() != 0 )
		echo mysql_error() .' ' . $sql;
	while( $Array = mysql_fetch_array($result, MYSQL_ASSOC ) )
	{
		$data[$i++] = $Array;
	}
	
	if( mysql_errno() != 0 )
		echo mysql_error() .' ' . $sql;
	mysql_free_result($result);
	if( count( $data ) >= 0 )
		return $data;
	else
		return false;
}

function get_line( $sql , $db = NULL )
{
	$data = get_data( $sql , $db  );
	return @reset($data);
}

function get_var( $sql , $db = NULL )
{
	$data = get_line( $sql , $db );
	return $data[ @reset(@array_keys( $data )) ];
}

function last_id( $db = NULL )
{
	if( $db == NULL ) $db = db();
	return get_var( "SELECT LAST_INSERT_ID() " , $db );
}

function run_sql( $sql , $db = NULL )
{
	if( $db == NULL ) $db = db();
	$GLOBALS['LP_LAST_SQL'] = $sql;
	return mysql_query( $sql , $db );
}

function db_errno( $db = NULL )
{
	if( $db == NULL ) $db = db();
	return mysql_errno( $db );
}


function db_error( $db = NULL )
{
	if( $db == NULL ) $db = db();
	return mysql_error( $db );
}

function last_error()
{
	if( isset( $GLOBALS['LP_DB_LAST_ERROR'] ) )
	return $GLOBALS['LP_DB_LAST_ERROR'];
}

function close_db( $db = NULL )
{
	if( $db == NULL )
		$db = $GLOBALS['LP_DB'];
	unset( $GLOBALS['LP_DB'] );
	mysql_close( $db );
}

/*
 * MYSQL_ASSOC - 关联数组   mysql_fetch_assoc();
 * MYSQL_NUM   - 数字数组   mysql_fetch_row(); 索引数组
 * MYSQL_BOTH  - 默认 - 同时产生关联和数字数组   效率不高
 * */
function fetch_array($query, $result_type = MYSQL_ASSOC) {
    return mysql_fetch_array ( $query, $result_type );
}

/*
 *  参数:无
 *  作用：取回上一次MYSQL操作影响的行数
 **/
function affected_rows() {
    return mysql_affected_rows ( $this->link );
}

/*
 * 作用：返回关联数组
 * */
function fetch_assoc($query) {
    return mysql_fetch_assoc ( $query );
}

/*
 * 作用：返回错误号
 */
function errno() {
    return intval ( ($this->link) ? mysql_errno ( $this->link ) : mysql_errno () );
}

/*
 * 作用：
 *      返回结果集中信息的行数 即有多少条数据
 */
function num_rows($query) {
    //取得结果集中行的数目
    $query = mysql_num_rows ( $query );
    return $query;
}

/*
 *      返回结果集中字段的数量
 */
function num_fields($query) {
    return mysql_num_fields ( $query );
}

/*
 * 参数：
 *      结果集
 * 作用：
 *      释放内存
 */
function free_result($query) {
    return mysql_free_result ( $query );
}

/*
 *      取得上一次inset into 表名 操作产生的ID号
 *      mysql_insert_id($this->link))
 */
function insert_id() {

    return ($id = mysql_insert_id ( $this->link )) >= 0 ? $id : $this->result ( $this->query ( "SELECT last_insert_id()" ), 0 );
}

/*
 *      返回索引数组
 */
function fetch_row($query) {

    $query = mysql_fetch_row ( $query );
    return $query;

}

/*
 *       以对象的形式返回数据信息
 */
function fetch_fields($query) {

    return mysql_fetch_field ( $query );
}

/*
 *  作用：
 *       关闭链接通道
 *
 *
 **/
function close() {

    return mysql_close ( $this->link );
}

/*
 *      返回一条数据信息  一维数组
 */
function getone($SQL) {
    $query = $this->query ( $SQL );
    $rs = mysql_fetch_array ( $query, MYSQL_ASSOC );
    return $rs;
}


function getall($sql) {
    $res = run_sql( $sql );
    if ($res !== false) {
        $arr = array ();
        while ( ($row = mysql_fetch_assoc ( $res )) == true ) {
            $arr [] = $row;
        }
    } else {
        return false;
    }
    return $arr;
}

/*
 * 参数：
 *	    1.$table          表名
 *	    2.$array          字段的关联数组  字段作为键 => 修改的值
 *	    3.$where          where条件
 * 作用：
 *      更新数据表
 */
function update($table, $array, $where = null) {

    $values = array ();

    foreach ( $array as $key => $val ) {

        if (! is_numeric ( $val )) {

            $val = "'" . $this->encode ( $val ) . "'";
        }
        $values [] = $key . '=' . $val;
    }
    //(empty($where) ?  ';' : ' WHERE '.$where.';') 三目运算符 相当于if else
    $sql = 'UPDATE ' . $table . ' SET ' . implode ( ',', $values ) . (empty ( $where ) ? ';' : ' WHERE ' . $where . ';');

    if ($this->query ( $sql )) {
        return true;
    } else {
        return false;
    }
}
/*
 *  参数：
 *       字符串
 *  作用：
 *       将字符串中的特殊字符加上转义字符 \
 *
 *       run.php 页面中关闭了系统自动加转义字符
 *
 *       addslashes( 字符串 )  将字符串特殊字符加上转移字符 \ '
 *
 **/
function encode($s) {
    return addslashes ( $s );
}

/*
 *  参数：
 *       SQL语句 select count(*) from 表名
 *  作用：
 *       返回结果集中行数
 *
 *
 **/
function fetch_count($sql) {
    $rs = $this->fetch_row ( $this->query ( $sql ) );
    return $rs [0];
}

/*
 *  作用：错误信息展示
 */
function halt($message = '', $sql = '') {
    $dberror = $this->error ();
    $dberrno = $this->errno ();
    echo "<meta http-equiv='Content-Type' content='text/html;charset=utf-8' /><div style=\"position:absolute;font-size:11px;font-family:verdana,arial;background:#EBEBEB;padding:0.5em;\"><b>MySQL Error</b><br><b>Message</b>: $message<br><b>SQL</b>: $sql<br><b>Error</b>: $dberror<br><b>Errno.</b>: $dberrno<br></div>";
    exit ();
}
