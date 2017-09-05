<?php
//数据库设置
if($_SERVER['REMOTE_ADDR']=="127.0.0.1"||file_exists("/var/www/zm")){
	define(DB_HOST,"localhost");
	define(DB_USER,"root");
	define(DB_PASSWORD,"asdf");
	define(DB_DATABASE,"zm");
}else{
	define(DB_HOST,"w.rdc.sae.sina.com.cn:3307");
	define(DB_USER,SAE_MYSQL_USER);
	define(DB_PASSWORD,SAE_MYSQL_PASS);
	define(DB_DATABASE,SAE_MYSQL_DB);
}

//数据库连接
mysql_connect(DB_HOST,DB_USER,DB_PASSWORD)or die(mysql_error());
mysql_select_db(DB_DATABASE)or die(mysql_error());
mysql_query("set names utf8");
header("Content-Type:text/html;charset=utf-8");
?>
