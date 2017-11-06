<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );

class indexController extends appController{

    function index(){

        $data['username'] = $_COOKIE['joume_username'];
        render($data,'web','home');
    }
}