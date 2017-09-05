<?php 
require(ROOT_PATH."/include/class_monitor.php");
$Monitor=new Monitor();

//前端设备列表
switch($_GET['op']){
	//monitor
	case "get_monitor_list":
	  echo $Monitor->Get_monitor_list($_GET);
	break;
	
	case "get_monitor_tree":
	  echo $Monitor->Get_monitor_tree();
	break;
	
	case "get_monitor":
	  echo $Monitor->Get_monitor($_GET['mid']);
	break;
	
	case "get_model":
	  echo $Monitor->Get_model($_GET['fid']);
	break;
	
	case "get_resolution":
	  echo $Monitor->Get_resolution($_GET['mid']);
	break;
	
	case "get_plan":
	  echo $Monitor->Get_plan();
	break;
	
	case "monitor_name_validate":
	  $Monitor->Monitor_name_validate($_GET['name']);
	break;
	
	case "monitor_creat":
	  $Monitor->Monitor_creat($_POST);
	break;
	
	case "monitor_edit":
	  $Monitor->Monitor_edit($_POST);
	break;
	
	case "monitor_control":
	  $Monitor->Monitor_control($_GET['control'],$_POST);
	break;
	
	//event
	case "get_event_list":
	  echo $Monitor->Get_event_list($_GET);
	break;
	
	case "get_operator_event_list":
	  echo $Monitor->Get_operator_event_list($_GET);
	break;
	
	case "event_del":
	  $Monitor->Event_del($_POST);
	break;
	
	case "event_download":
	  $Monitor->Event_download($_GET['path'],$_GET['mid']);
	break;
	
	//视频播放
	case "monitor_iframe":
	  $Monitor->Monitor_iframe($_GET);
	break;
	
	//云台控制
	case "ptz_control":
	  $Monitor->Ptz_control($_GET);
	break;
	
	//动态连接
	case "watch_connect_start":
	  $Monitor->Watch_connect_start($_GET['mid_str']);
	break;
	
	case "watch_connect_stop":
	  $Monitor->Watch_connect_stop($_GET['mid_str']);
	break;
	
	//获取预设点
	/*case "get_preset":
	  echo $Monitor->Get_preset($_GET['mid']);
	break;*/
	
	//预置点窗口
	case "set_preset":
	  $Monitor->Set_preset($_GET['mid']);
	break;
	
	//设备发现
	case "monitor_discover":
	   $Monitor->Monitor_discover($_GET,$_POST);
	break;
	
	//用于服务器判断是否实时中
	case "connectting":
	   $Monitor->Connectting($_GET['mid_str']);
	break;
	
	case "get_command":
       $Monitor->Get_command();
	break;
	
	default:
	break;
}
?>