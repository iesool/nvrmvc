<?php 
require(ROOT_PATH."/include/class_monitor.php");
$Monitor=new Monitor();

//组管理
switch($_GET['op']){
	case "get_group_list":
	  echo $Monitor->Get_group_list();
	break;
	
	case "get_ungroup_monitor":
	  echo $Monitor->Get_ungroup_monitor();
	break;
	
	case "get_group_monitor":
	  echo $Monitor->Get_group_monitor($_GET['gid'],$_GET['is_record']);
	break;
	
	case "get_group_name":
	  echo $Monitor->Get_group_name($_GET['gid']);
	break;
	
	case "group_name_validate":
	  echo $Monitor->Group_name_validate($_GET['gname']);
	break;
	
	case "group_creat":
	  $Monitor->Group_creat($_POST);
	break;
	
	case "group_add":
	  $Monitor->Group_add($_POST);
	break;
	
	case "group_remove":
	  $Monitor->Group_remove($_POST);
	break;

	case "group_edit":
	  $Monitor->Group_edit($_POST);
	break;
	
	case "group_del":
	  $Monitor->Group_del($_GET['gid']);
	break;

	default:
	break;
}
?>