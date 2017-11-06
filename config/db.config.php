<?php

if( defined('SAE_APPNAME') )
{
	$GLOBALS['config']['db']['db_host'] = SAE_MYSQL_HOST_M;
	$GLOBALS['config']['db']['db_host_read'] = SAE_MYSQL_HOST_S;
	$GLOBALS['config']['db']['db_port'] = SAE_MYSQL_PORT;
	$GLOBALS['config']['db']['db_user'] = SAE_MYSQL_USER;
	$GLOBALS['config']['db']['db_password'] = SAE_MYSQL_PASS;
	$GLOBALS['config']['db']['db_name'] = SAE_MYSQL_DB;
}
else
{
    if($GLOBALS['domain'] == 'com'){

        $GLOBALS['config']['db']['db_host'] = 'alyweb005.prod';
        $GLOBALS['config']['db']['db_port'] = 3306;
        $GLOBALS['config']['db']['db_user'] = 'wikipage';
        $GLOBALS['config']['db']['db_password'] = 'wikipage2015';

        $GLOBALS['config']['rds']['db_host'] = 'rm-2zed40rbv0xc9iam0.mysql.rds.aliyuncs.com';
        $GLOBALS['config']['rds']['db_port'] = 3306;
        $GLOBALS['config']['rds']['db_user'] = 'td_userrw';
        $GLOBALS['config']['rds']['db_password'] = '2QWdf#Z9fc0o*$zE';

    }elseif($GLOBALS['domain'] == 'beta'){

        $GLOBALS['config']['db']['db_host'] = 'alyweb002.prod';
        $GLOBALS['config']['db']['db_port'] = 3306;
        $GLOBALS['config']['db']['db_user'] = 'wikipage';
        $GLOBALS['config']['db']['db_password'] = 'wikipage';

        $GLOBALS['config']['rds'] = $GLOBALS['config']['db'];

    }else{

        $GLOBALS['config']['db']['db_host'] = '172.16.75.75';
        $GLOBALS['config']['db']['db_port'] = 3306;
        $GLOBALS['config']['db']['db_user'] = 'root';
        $GLOBALS['config']['db']['db_password'] = '654321';

        $GLOBALS['config']['rds'] = $GLOBALS['config']['db'];

    }

    $GLOBALS['config']['rds']['db_name'] = 'wikiurl';
    $GLOBALS['config']['db']['channel_db_name'] = 'channel';
}