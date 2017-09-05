<?php 
require(ROOT_PATH."/include/class_user.php");
$User=new User();

//登录登出控制器
$username=$_REQUEST['username'];
$password=$_REQUEST['password'];
$auto=$_REQUEST['auto'];

if($username&&$password){
	$User->User_login($username,$password,$auto);
}else{
	$User->User_logout();
}
?>