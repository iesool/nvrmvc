<?php 
//主控制器
require("include/config.php");

if(!$_GET['do']){
	//静态页
	$do="skins";
	
	if(isset($_SESSION['user'])){
		//如果用户ID为1，则为admin用户，进入管理员界面，否则进入操作员界面
		if($_SESSION['user']['Id']==1){
			$view="console";
		}else{
			$view="operator";
		}
	}else{
		//如果没有session则进入登录页面
		$view="user_login";
	}
	
	$last_name="html";
}else{
	//ajax请求
	
	require("include/connect.php");
	require("include/functions.php");
	
	require("include/class_system.php");
	$System=new System();
	
	$do=$_GET['do'];
	$view=$_GET['view'];
	$last_name="php";
}

require(ROOT_PATH."/".$do."/".$view.".".$last_name);
?>