<?php 
require(ROOT_PATH."/include/class_net.php");
$Net=new Net();

//主机名和DNS设置
switch($_GET['op']){
	case "set_dns":
	  $Net->save_dns_interface($_POST);
	break;
	
	default:
	  echo json_encode($Net->getDnsAndHostName());
	break;
}
?>