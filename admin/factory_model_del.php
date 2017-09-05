<style>
.factory{
	text-align:center
}
.factory table{
	border-top:1px solid #CCC;
	
}
</style>

<?php 

 
if(isset($_GET['op'])){
	$fid=$_POST['fid'];
	$mids=$_POST['models'];
	foreach($mids as $mid){
		mysql_query("delete from Models where Id=".$mid." and Factory_Id=".$fid);
	}
	echo "<script>window.close();window.opener.location.reload()</script>";
}else{
	$fname=mysql_query("select Name from Factorys where Id=".$_GET['fid']);
	$fname=mysql_fetch_row($fname);
?>
   <title>厂家 - <?php echo $fname[0]; ?> - 删除型号</title>
    <div class="factory">
      <h5>厂家 - <?php echo $fname[0]; ?> - 删除型号</h5>
      <div>
      <form action="../admin/factory.php?view=factory_model_del&op=do" method="post" id="form_factory">
      <input type="hidden" id="fid" name="fid" value="<?php echo $_GET['fid']; ?>" />
        <table border="0" width="100%" cellpadding="5" cellspacing="0" style="font-size:12px">
          <tr>
            <td width="20%">型号：</td>
            <td>
              <select id="models" name="models[]" multiple="multiple" style="width:100%; height:400px">
              <?php 
			  $m_arrs=mysql_query("select Id,Name from Models where Factory_Id=".$_GET['fid']);
			  while($m_arr=mysql_fetch_array($m_arrs,MYSQL_ASSOC)){
				  echo "<option value=".$m_arr['Id'].">".$m_arr['Name']."</option>";
			  }
			  ?>
              </select> 
            </td>
          </tr>
          <tr><td colspan="2" style=";border-bottom:1px solid #CCC">&nbsp;</td></tr>
          <tr>
            <td align="right" colspan="2">
              <input type="button" onclick="submit_form()" value="确定" />
              <input type="button" onclick="window.close()" value="取消" />
            </td>
          </tr>
        </table>
      </form>
      </div>
    </div>
    
    <script type="text/javascript">
	
	function models_check(){
		var models=document.getElementById('models');
		
		var j=0;
		for(var i=0;i<models.length;i++){
			if(models.options[i].selected){
				j++;
			}
		}
		
		if(models.value){
			if(models.length==j){
				alert("请至少保留一个型号！");
				return false;
			}else{
				return true;
			}
		}else{
			alert("请至少选择一个型号");
			return false;
		}
	}
	
	
	function submit_form(){
		if(models_check()&&confirm("您确定要删除这个型号吗？")){
			document.getElementById('form_factory').submit();
		}
	}
	</script>
<?php 
}
?>