<?php 
//控制台控制器
switch($_GET['op']){
	case "get_language":
		echo json_encode($System->Get_word());
	break;
	
	case "get_user":
	  echo json_encode($_SESSION['user']);
	break;
	
	case "get_machines":
	  echo $System->Get_machines($_GET['get']);
	break;
	
	case "get_banner_state":
	  echo $System->Get_banner_state();//获取bunner信息
	break;
	
	default:
	break;
}
?>