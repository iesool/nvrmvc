<?php
require(ROOT_PATH."/include/class_net.php");
$Net=new Net();

//重启网络服务控制器
if($Net->Net_restart()){
	echo 1;
}else{
	echo 0;
}
?>