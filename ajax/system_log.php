<?php 
//系统日志控制器
switch($_GET['op']){
	case "get_user_list":
	  echo json_encode(Get_db_arrs("select distinct(User) from Logs"));
	break;
	
	case "get_ip_list":
	  echo json_encode(Get_db_arrs("select distinct(Ip) from Logs"));
	break;
	
	case "get_log_list":
	  echo $System->Get_log_list($_GET);
	break;
	
	case "log_download":
	  echo $System->Log_download($_GET);
	break;
	
	case "log_del":
	  echo $System->Log_del($_GET);
	break;
	
	case "get_time_date":
	  echo $System->Get_time_date();
	break;
	
	case "get_log_file":
	  echo 	$System->Get_log_file();
	break;
	
	case "download_log_file":
	  $System->Download_log_file($_GET['path']);
	break;

//disk
	case "get_disk_log":
	  echo 	$System->Get_disk_log();
	break;
	
	case "download_disk_log":
	  echo 	$System->Download_disk_log();	
	break;
	
	case "delete_disk_log":
	  echo 	$System->Delete_disk_log();
	break;
	
	default:
	break;
}
?>