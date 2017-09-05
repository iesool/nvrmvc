<?php 
//获取数据库一个表多条记录
function Get_db_arrs($sql,$type=MYSQL_ASSOC){
	$result=mysql_query($sql);
	while($db_arr=mysql_fetch_array($result,$type)){
		$db_arrs[]=$db_arr;
	}
	return $db_arrs;
}
//获取数据库一个表一条（一行）记录
function Get_db_arr($sql,$type=MYSQL_ASSOC){
	$result=mysql_query($sql);
	$db_arr=mysql_fetch_array($result,$type);
	return $db_arr;
}

//获取一列数据
function Get_db_col($sql,$col,$type=MYSQL_ASSOC){
	$result=mysql_query($sql);
	while($db_arr=mysql_fetch_array($result,$type)){
		$db_col[]=$db_arr[$col];
	}
	return $db_col;
}
//获取数据库一个表一个值
function Get_db_one($sql){
	$result=mysql_query($sql);
	$result=mysql_fetch_row($result);
	return $result[0];
}
//判断运行环境
function Environment_check(){
	if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
		return false;
	}else{
		if(file_exists("/var/www/zm/")){
			return true;
		}else{
			return false;
		}
	}
}
//以root权限执行命令
function Shell_cmd($cmd){
	if(Environment_check()){
		$back_info=array();
		$back_info['back']=exec("export LANG=C; /usr/bin/sudo ".$cmd,$out,$rv);
		$back_info['rv']=$rv;
		return $back_info;
	}
}
//popen
function Shell_popen($cmd){
	if(Environment_check()){
		$popen=popen("export LANG=C; /usr/bin/sudo ".$cmd,'r');
		$i=0;
		while (!feof($popen)){
			$arr[$i]=fgets($popen);
			$i++;
		}
		pclose($popen);
		return $arr;
	}
}
//设置权限为777
function File_top($path){
	Shell_cmd("chmod 777 ".$path);
}
//建立文件夹
function Make_file($filepath){
	if(!file_exists($filepath)){
		Shell_cmd("mkdir ".$filepath);
	}
	File_top($filepath);
}
//socket发送
function Socket_post($msg,$path){
	$socket = socket_create( AF_UNIX, SOCK_DGRAM, 0 );//建立socket连接
	File_top($path);//改变zmr.sock权限
	socket_sendto( $socket, $msg, strlen($msg), 0, $path ); //发送数据包
	socket_close( $socket );//关闭socket连接
}
?>