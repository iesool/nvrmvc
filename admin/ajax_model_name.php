<?php 
$mname=mysql_query("select Id from Models where Name='".$_GET['mname']."' and Factory_Id=".$_GET['fid']);
$mname=mysql_fetch_row($mname);
$mname=$mname[0];

if($mname){
	echo 0;
}else{
	echo 1;
}
?>