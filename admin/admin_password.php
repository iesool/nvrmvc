<?php 
if($_GET['do']){
	//Shell_cmd("mysql -uroot -pasdf -e 'update Users set Password=1 where Id=1;'");
	mysql_query("update Users set Password=Password('admin') where Id=1 and Username='admin'");
	echo "<script>alert('修改成功！');window.location='admin.php?view=admin_password'</script>";
}else{
?>
    <h3>重置admin密码</h3>
    <div>
      <form method="post" action="admin.php?view=admin_password&do=1">
        <input type="submit" onClick="return(confirm('您确定要将admin密码重置为admin？'))" value="重置" />
      </form>
    </div>
<?php 
}
?>