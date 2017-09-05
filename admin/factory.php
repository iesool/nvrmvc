<?php 
//厂家型号管理
require("../include/config.php");
require("../include/connect.php");

if($_GET['view']){
	$view=$_GET['view'];
}else{
	$view="factory_manage";
}

require(ROOT_PATH."/admin/".$view.".php");
?>