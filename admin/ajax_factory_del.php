<?php 
mysql_query("delete from Factorys where Id=".$_GET['fid']);
mysql_query("delete from Models where Factory_Id=".$_GET['fid']);
?>