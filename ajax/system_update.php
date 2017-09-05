<?php 
//升级控制器

//取得上传数组
$sys_update=$_FILES['sys_update'];

//去掉上传文件扩展名
$dirname=preg_split("/.pak/",$sys_update['name']);
$dirname=$dirname[0];

//建立tmp修改tmp权限
if(!file_exists("/tmp")){
	mkdir("/tmp");
}
File_top("/tmp");

//压缩包路径和解压后的目录
$start_path="/tmp/".$sys_update['name'];
$end_path="/tmp/".$dirname;

//移动压缩包到压缩包路径
move_uploaded_file($sys_update['tmp_name'],$start_path);

//建立解压目录修改权限
if(!file_exists($end_path)){
	mkdir($end_path);
}
File_top($end_path);

//解压升级包
Shell_cmd("tar -xf ".$start_path." -C ".$end_path);

//复制文件
//Shell_cmd("cp -r ".$end_path."/nvrmvc/ /var/www/");
//Shell_cmd("cp -r ".$end_path."/etc/ /usr/local/");
//Shell_cmd("cp -r ".$end_path."/bin/ /usr/local/");

//运行sql脚本
//Shell_cmd("mysql -uroot -pasdf<".$end_path."/update.sql");

$ver=file($end_path."/RELEASE");

//执行升级shell脚本
File_top($end_path."/update.sh");
Shell_cmd("sh ".$end_path."/update.sh");

sleep(3);

//删除压缩包和解压目录
Shell_cmd("rm -rf ".$start_path);
Shell_cmd("rm -rf ".$end_path);

//记录日志
//$ver=explode("-",$dirname);
$System->System_log("normal","system","op",$word['word_log_update_to']."“".$ver[0]."”");

//完成后操作
echo "<script>alert('".$word['word_system_update_complete']."');window.close()</script>";
?>