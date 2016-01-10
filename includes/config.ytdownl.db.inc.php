<?php
function getYTDOWNLConf(){
	$_access_YTDOWNLDB = array();
	
	$_access_YTDOWNLDB['host'] = 'p:localhost';
	$_access_YTDOWNLDB['user'] = 'root';
	$_access_YTDOWNLDB['pass'] = '';
	$_access_YTDOWNLDB['db'] = 'ytdownl';
	$_access_YTDOWNLDB['folder'] = '/path/to/downloads';
	return $_access_YTDOWNLDB;
}