<?php 


if(isset($_GET['op'])){
	mysql_query("update Factorys set Name='".$_POST['fname']."' where Id=".$_GET['fid']);
	echo "<script>window.close();window.opener.location.reload()</script>";
}else{
?>
    <title>厂家名称修改</title>
    
    <div style="text-align:center">
      <h5>厂家名称修改</h5>
      <form action="../admin/factory.php?view=factory_edit&op=do&fid=<?php echo $_GET['fid']; ?>" method="post" id="form_fname">
      <table border="0" width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center">
		  <?php 
          $fname=mysql_query("select Name from Factorys where Id=".$_GET['fid']);
          $fname=mysql_fetch_row($fname);
          ?>
          <input type="text" id="fname" name="fname" value="<?php echo $fname[0]; ?>" onBlur="ajax_factory_name()" />
          <input type="hidden" name="check_fname" id="check_fname" value="1" />
        </td>
      </tr>
      <tr><td>&nbsp;</td></tr>
      <tr>
        <td align="center">
          <input type="button" onclick="submit_form()" value="确定" />
          <input type="button" onclick="window.close()" value="取消" />
        </td>
      </tr>
      </table>
      </form>
    </div>
    <script type="text/javascript">
	function ajax_factory_name(){
		var fname=document.getElementById('fname').value;
		var check_fname=document.getElementById('check_fname');
		
		if(!+[1,]){
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			xmlhttp = new XMLHttpRequest();
		}
		var time=new Date().getTime();//获取随机时间
		var url=encodeURI('../admin/factory.php?view=ajax_factory_name&time='+time+'&fname='+fname);
		xmlhttp.open('get',url,true);
		xmlhttp.onreadystatechange = function(){
			if(xmlhttp.readyState == 4 && xmlhttp.status == 200){
				msg = xmlhttp.responseText;
				//alert(msg);
				if(msg==1){
					check_fname.value=1;
				}else{
					check_fname.value=0;
				}
			}
		}
		xmlhttp.send(null);
	}
	function name_check(){
		var fname=document.getElementById('fname').value;
		var check_fname=document.getElementById('check_fname').value;
		
		if(fname){
			if(check_fname==1){
				return true;
			}else{
				if(fname!='<?php echo $fname[0]; ?>'){
					alert("该名称已存在！");
					return false;
				}else{
					return true
				}
			}
		}else{
			alert('请填写厂家名称！');
			return false;
		}
	}
	
	
	function submit_form(){
		if(name_check()){
			document.getElementById('form_fname').submit();
		}
	}
	</script>
<?php 
}
?>

