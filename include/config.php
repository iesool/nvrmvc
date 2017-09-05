<?php 
//路径设置
define(ROOT_PATH,$_SERVER['DOCUMENT_ROOT']);
define(ROOT_IP,"http://".$_SERVER['HTTP_HOST']);

define(EVENT_PATH,ROOT_PATH."events/");
define(EMPTY_PATH,ROOT_PATH."empty/");
define(SOCKET_PATH,ROOT_PATH."zmr.sock");

//设置默认时区
date_default_timezone_set("Asia/Shanghai");

session_start();
?>
