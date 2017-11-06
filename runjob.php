#!/usr/bin/env php
<?php
/* lp app root */
// ↑____ for aoi . Do Not Delete it.
/****  load lp framework  ***/
define( 'DS' , DIRECTORY_SEPARATOR );

define( 'AROOT' , dirname( __FILE__ ) . DS  );

if(empty($argv[1])){
    echo 'no argv[1] controller';exit;
}
if(empty($argv[2])){
    echo 'no argv[2] function';exit;
}
if(empty($argv[3])){
    echo 'no argv[3] env';exit;
}
$_SERVER['SERVER_NAME'] = "joymewiki.joyme.".$argv[3];
$_REQUEST['c'] = $argv[1];
$_REQUEST['a'] = $argv[2];

//ini_set('include_path', dirname( __FILE__ ) . DS .'_lp' );
include_once( '_lp'.DS .'lp.init.php' );
/**** lp framework init finished ***/
