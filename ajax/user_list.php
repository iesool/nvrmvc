<?php 
require(ROOT_PATH."/include/class_user.php");
$User=new User();

//用户列表控制器
switch($_GET['op']){
	//获取用户列表数组
	case "get_user_list":
	  echo $User->Get_user_list();
	break;
	
	//获取单个用户数组	
	case "get_user_arr":
	  echo $User->Get_user_arr($_GET['uid']);
	break;
	
	//判断用户名是否存在
	case "user_name_validate":
	  echo $User->User_name_validate($_GET['user_name']);
	break;
	
	//判断旧密码是否正确
	case "user_password_validate":
	  echo $User->User_password_validate($_GET['uid'],$_GET['old_password']);
	break;
	
	//添加新用户
	case "user_creat":
	  $User->User_creat($_POST);
	break;
	
	//修改用户
	case "user_edit":
	  $User->User_edit($_POST);
	break;
	
	//删除用户
	case "user_del":
	  $User->User_del($_GET['uid']);	  
	break;
	
	default:
	break;
}
?>