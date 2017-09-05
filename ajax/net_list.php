<?php 
require(ROOT_PATH."/include/class_net.php");
$Net=new Net();

//网卡列表
switch($_GET['op']){
	
	case "get_netcard_arr":
	  echo $Net->Get_netcard_arr($_GET['index']);
	break;
	
	case "get_netcard_list":
	  echo json_encode($Net->Get_netcard_list());
	break;
	
	case "get_whichip":
	  echo json_encode($Net->getNetCardInfo());
	break;
	
	case "get_virtual_arr":
	  echo $Net->Get_virtual_arr($_GET['name'],$_GET['index']);
	break;
	
	case "netcard":
	  $Net->saveNetcard($_POST);
	break;
	
	case "bond":
	  $Net->saveBond($_POST);
	break;
	
	case "bond_del":
	  $Net->deleteBond($_GET['name']);
	break;
	
	case "virtual":
	  $Net->save_virtual_interface($_POST);
	break;
	
	case "virtual_name_check":
	  $Net->Virtual_name_check($_GET['first_name'],$_GET['last_name']);
	break;
	
	case "virtual_edit":
	  $Net->Virtual_edit($_POST);
	break;
	
	case "virtual_del":
	  $Net->Virtual_del($_GET['name']);
	break;
	
	default:
	  echo $Net->Net_arrs();
	break;
}
?>