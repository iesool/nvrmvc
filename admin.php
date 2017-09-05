<?php 
//后台管理
require("include/config.php");
require("include/connect.php");
require("include/functions.php");

if($_GET['view']){
	$view=$_GET['view'];
}else{
	$view="diy";
}

$diy=Get_db_arr("select * from Diy");
?>

<style>
body{
	/*width:1000px;*/
	margin:auto;
	/*font-size:12px;*/
}
a{
	color:#7F7FB2;
	text-decoration:none;
}
a:hover{
	color:#00B6EF;
	text-decoration:underline;
}
h3{
	font-size:16px;
	border-bottom:1px solid #ccc;
	margin:0px;
	margin-bottom:20px;
}
img{
	border:0
}

.header {
	
}
.left{
	float:left;
	margin-top:10px;
	border-right:1px solid #ccc;
	width:150px;
	height:600px;
}
.main{
	margin-top:10px;
	margin-left:150px;
	padding-left:10px;
	/*border-left:1px solid #ccc;*/
	*width:100%;
}
.main_op{
	border-top:1px solid #CCC; 
	margin-top:20px; 
	text-align:left; 
	padding-top:10px;
}
.login{
	width:400px;
	height:150px;
	border:1px solid #ccc;
	text-align:center;
	margin:auto;
	margin-top:20px;
}
.footer {
	margin-top:10px;
	padding-top:10px;
	border-top:1px solid #ccc;
	text-align:center
}
</style>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/functions.js"></script>
<title>NVR - 出厂管理</title>
<body>

<div class="header">
  <img src="images/header.jpg" width="100%" height="100px" />
</div>

<div class="left">
  <table border="0" width="100%" cellpadding="5" cellspacing="5">
    <tr>
      <td><a href="admin.php?view=diy" <?php if($view=="diy"){echo 'style="color:#00B6EF";';} ?>>界面管理</a></td>
    </tr>
    <tr>
      <td><a href="admin.php?view=language" <?php if($view=="language"){echo 'style="color:#00B6EF";';} ?>>语言管理</a></td>
    </tr>
    <tr>
      <td><a href="admin.php?view=admin_password" <?php if($view=="admin_password"){echo 'style="color:#00B6EF";';} ?>>重置admin密码</a></td>
    </tr>
    <tr>
      <td><a href="admin.php?view=is_stream" <?php if($view=="is_stream"){echo 'style="color:#00B6EF";';} ?>>是否流转发</a></td>
    </tr>
    <?php /*?><tr>
      <td><a href="admin.php?view=license" <?php if($view=="license"){echo 'style="color:#00B6EF";';} ?>>license管理</a></td>
    </tr><?php */?>
    <tr>
      <td><a href="javascript:;" onClick="pop_window(800,600,'admin/license.php','license')">license管理</a></td>
    </tr>
    <tr>
      <td><a href="javascript:;" onClick="pop_window(800,600,'admin/factory.php','factory')">厂家型号管理</a></td>
    </tr>
  </table>
</div>

<div class="main">
  <?php require(ROOT_PATH."/admin/".$view.".php"); ?>
</div>

<div align="center">
  <div class="login">
    <!--<form>-->
      <div style="margin-top:10px; font-weight:bold">NVR出厂管理</div>
      <div style="margin-top:30px">请输入密码：<input type="password" id="password" value="" />&nbsp;<button type="submit" onClick="back_login()">确定</button></div>
    <!--</form>-->
  </div>
</div>

<div style="clear:both"></div>
<div class="footer">
  <?php echo $diy['Footer_word']; ?>
</div>	

</body>

<script type="text/javascript">
window.onload=function(){
	if(getCookie("back_login")){
		$(".left").show();
		$(".main").show();
		$(".login").hide();
	}else{
		$(".left").hide();
		$(".main").hide();
		$(".login").show();
	}
}

function back_login(){
	if($("#password").val()=="perabytes"){
		$(".left").show();
		$(".main").show();
		$(".login").hide();
		setCookie("back_login",1);
	}else{
		alert("密码错误！");
		return false;
	}
}

//弹出窗口
function pop_window(width,height,url,name){
	var left = (screen.width - width) / 2;
	var top  = (screen.height - height) / 2;
	window.open(url,name,'width='+width+',height='+height+',top='+top+',left='+left+',resizable=yes,scrollbars=yes');
}
</script>

