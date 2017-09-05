<?php 
//用户
class User{
	//用户登录
	function User_login($username,$password,$auto=false){
		global $System;
		$word=$System->Get_word();
		
		$user_name=Get_db_one("select Username from Users where Username='".$username."'");
		if($user_name){
			$user_arr=Get_db_arr("select * from Users where Username='".$username."' and Password=password('".$password."')");
			if($user_arr){
				$_SESSION['user']=$user_arr;
				$System->System_log("normal","user","op",$word['word_log_login']);
				
				if($auto){
					echo "<script>window.location='index.php'</script>";
				}
			}else{
				if($auto){
					echo "<script>alert('".$word['word_user_password_error']."');window.location='index.php'</script>";
				}else{
					echo 2;
				}
			}
		}else{
			if($auto){
				echo "<script>alert('".$word['word_user_user_not_exists']."');window.location='index.php'</script>";
			}else{
				echo 1;
			}
		}
	}
	//用户登出
	function User_logout(){
		global $System;
		$word=$System->Get_word();
		
		$System->System_log("normal","user","op",$word['word_exit']);
		unset($_SESSION['user']);
		session_destroy();
	}
	
	
	//获取用户列表
	function Get_user_list(){
		if($_SESSION['user']['Id']==1){
			$user_arrs=Get_db_arrs("select * from Users order by Id asc");
		}else{
			$user_arrs=Get_db_arrs("select * from Users where Id=".$_SESSION['user']['Id']);
		}
		
		$user_list=array();
		
		foreach($user_arrs as $user_arr){
		   $user=array();
		   
		   $user['Name']=$user_arr['Username'];
		   
		   //实时观看
		   if($user_arr['Watch_right']==1){
			   $user['Watch_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Watch_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   //录像观看
		   if($user_arr['Event_right']==1){
			   $user['Event_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Event_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   //云台控制
		   if($user_arr['Ptz_right']==1){
			   $user['Ptz_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Ptz_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   //组管理
		   if($user_arr['Group_right']==1){
			   $user['Group_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Group_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   //前端设备编辑
		   if($user_arr['Monitor_modify_right']==1){
			   $user['Monitor_modify_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Monitor_modify_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   //前端设备控制
		   if($user_arr['Monitor_control_right']==1){
			   $user['Monitor_control_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Monitor_control_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   //录像下载
		   if($user_arr['Event_download_right']==1){
			   $user['Event_download_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Event_download_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   //录像删除
		   if($user_arr['Event_del_right']==1){
			   $user['Event_del_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Event_del_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   //日志管理
		   if($user_arr['Log_right']==1){
			   $user['Log_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Log_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   //计划设置
		   if($user_arr['Plan_right']==1){
			   $user['Plan_right']='<img src="../images/diskinuse.png" width=15 height=15 />';
		   }else{
			   $user['Plan_right']='<img src="../images/diskdead.png" width=15 height=15 />';
		   }
		   
		   $user['op']='<a href="javascript:;" onclick="user_edit('.$user_arr["Id"].')"><img src="../images/ps07.png" /></a>';
		   
		   if($user_arr['Id']==1||$_SESSION['user']['Id']!=1){
			   $user['op'].='';
		   }else{
			   $user['op'].='&nbsp;&nbsp;<a href="javascript:;" onclick="user_del('.$user_arr["Id"].')"><img src="../images/ps03.png" /></a>';
		   }
		   
		   array_push($user_list,$user);
		}
		
		return json_encode($user_list);
	}
	
	//获取单个用户数组	
	function Get_user_arr($uid){
		$group_arrs=Get_db_arrs("select Id,Name from Groups");
		if($uid){
			$user_arr=Get_db_arr("select * from Users where Id=".$uid);
			$user_arr['group_arrs']=$group_arrs;
			return json_encode($user_arr);
		}else{
			return json_encode($group_arrs);
		}
	}
	
	//判断用户名是否存在
	function User_name_validate($user_name){
		if(Get_db_one("select Id from Users where Username='".$user_name."'")){
			return 1;
		}
	}
	
	//判断旧密码是否正确
	function User_password_validate($uid,$old_password){
		if(Get_db_one("select Id from Users where Id=".$uid." and Password=password('".$old_password."')")){
			return 1;
		}
	}
	
	
	//创建用户
	function User_creat($item){
		mysql_query("insert into Users (Username,Password,Watch_right,Event_right,Ptz_right,Group_right,Monitor_modify_right,Monitor_control_right,Event_download_right,Event_del_right,Log_right,Plan_right,Groups) values ('".$item['user_name']."',Password('".$item['password']."'),'".$item['watch_right']."','".$item['event_right']."','".$item['ptz_right']."','".$item['group_right']."','".$item['monitor_modify_right']."','".$item['monitor_control_right']."','".$item['event_download_right']."','".$item['event_del_right']."','".$item['log_right']."','".$item['plan_right']."','".join(",",$item['groups_right'])."')");
		
		global $System;
		$word=$System->Get_word();
		$System->System_log("normal","user","op",$word['word_log_add_new_user']."“".$item['user_name']."”");
	}
	//修改用户
	function User_edit($item){
		$edit_sql="update Users set ";
		
		$set_arr=array();
		if($item['password']){
			$set_arr[]="Password=Password('".$item['password']."')";
		}
		
		if($item['uid']!=1&&$_SESSION['user']['Id']==1){
			$set_arr[]="Watch_right='".$item['watch_right']."',Event_right='".$item['event_right']."',Ptz_right='".$item['ptz_right']."',Group_right='".$item['group_right']."',Monitor_modify_right='".$item['monitor_modify_right']."',Monitor_control_right='".$item['monitor_control_right']."',Event_download_right='".$item['event_download_right']."',Event_del_right='".$item['event_del_right']."',Log_right='".$item['log_right']."',Plan_right='".$item['plan_right']."',Groups='".join(",",$item['groups_right'])."'";
		}
		
		$edit_sql.=join(",",$set_arr);
		$edit_sql.=" where Id=".$item['uid'];
		
		mysql_query($edit_sql);
		
		$user_name=Get_db_one("select Username from Users where Id=".$item['uid']);
		
		global $System;
		$word=$System->Get_word();
		
		$System->System_log("normal","user","op",$word['word_log_edit_user']."“".$user_name."”");
	}
	//删除用户
	function User_del($uid){
		$user_name=Get_db_one("select Username from Users where Id=".$uid);
		global $System;
		$word=$System->Get_word();
		$System->System_log("normal","user","op",$word['word_log_delete_user']."“".$user_name."”");
		
		mysql_query("delete from Users where Id=".$uid);
	}
}

?>