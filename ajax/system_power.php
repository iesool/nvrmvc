<?php 
//系统操作控制器
switch($_GET['op']){
	case "get_system_state":
	  echo $System->Get_state();
	break;
	
	case "system_state":
	  if($System->System_state()){
		  echo 1;
	  }else{
		  echo 0;
	  }
	break;
	
	case "system_auto_nvr":
	  echo $System->System_auto_nvr($_GET);
	break;
	
	case "system_power":
	  $System->System_power($_GET['cmd']);
	break;	
	
	default:
	break;
}
?>