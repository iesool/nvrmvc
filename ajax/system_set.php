<?php 
//系统设置
switch($_GET['op']){
	case "get_system_time":
	  echo $System->Get_system_time();
	break;
	
	case "set_system_time":
	  $System->Set_system_time($_POST);
	break;
	
	case "get_auto_del":
	  echo $System->Get_auto_del();
	break;
	
	case "set_auto_del":
	  $System->Set_auto_del($_POST);
	break;
	
	
	//
	case "get_plan_list":
	  echo $System->Get_plan_list();
	break;
	
	case "get_plan":
	  echo $System->Get_plan($_GET);
	break;
	
	case "set_plan":
	  $System->Set_plan($_GET);
	break;
		
	case "get_timepart":
	  $System->Get_timepart($_GET);
	break;
	
	case "set_timepart":
	  $System->Set_timepart($_GET);
	break;
	//
	
	
	case "get_log_config":
	  echo $System->Get_log_config();
	break;
	
	case "set_log_config":
	  $System->Set_log_config($_POST);
	break;
	
	case "get_machine":
	  echo $System->Get_machine($_GET['id']);
	break;
	
	case "machine_add":
	  $System->Machine_add($_POST);
	break;
	
	case "machine_del":
	  $System->Machine_del($_GET['id']);
	break;
	
	case "machine_edit":
	  $System->Machine_edit($_POST,$_GET['id']);
	break;
	
	default:
	break;
}
?>