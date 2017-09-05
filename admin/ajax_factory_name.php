<?php 
//厂家

$fname=mysql_query("select Name from Factorys where Name='".$_GET['fname']."'");
$fname=mysql_fetch_row($fname);
$fname=$fname[0];

if($fname){
	echo 0;
}else{
	echo 1;
}

?>