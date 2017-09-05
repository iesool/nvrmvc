<style>
.factory{
	text-align:center
}
.factory table{
	border-top:1px solid #CCC;
	
}
</style>

<?php 
$mid=$_GET['mid'];



$m_arr=mysql_query("select Name,Resolution from Models where Id=".$mid);
$m_arr=mysql_fetch_array($m_arr,MYSQL_ASSOC);

$r_old_arr=explode(",",$m_arr['Resolution']);

if(isset($_GET['op'])){
	
	$resolution=array_diff($r_old_arr,$_POST['resolution']);
	$resolution=join(",",$resolution);
	
	mysql_query("update Models set Resolution='".$resolution."' where Id=".$mid);
	echo "<script>window.close();window.opener.location.reload()</script>";
	
}else{
?>
   <title><?php echo $m_arr['Name']; ?> - 删除分辨率</title>
    <div class="factory">
      <h5><?php echo $m_arr['Name']; ?> - 删除分辨率</h5>
      <div>
      <form action="../admin/factory.php?view=factory_resolution_del&op=do&mid=<?php echo $mid; ?>" method="post" id="form_factory">
        <table border="0" width="100%" cellpadding="5" cellspacing="0" style="font-size:12px">
        
          <tr>
            <td width="15%">分辨率：</td>
            <td>
              <select id="resolution" name="resolution[]" style="width:100%; height:400px" multiple>
              <?php 
			  foreach($r_old_arr as $r){
				echo "<option value=".$r.">".$r."</option>";  
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
	function resolution_check(){
		var resolution=document.getElementById('resolution');
		
		var j=0;
		for(var i=0;i<resolution.length;i++){
			if(resolution.options[i].selected){
				j++;
			}
		}
		
		if(resolution.value){
			if(resolution.length==j){
				alert("请至少保留一个分辨率");
				return false;
			}else{
				return true;
			}
		}else{
			alert("请至少选择一个分辨率");
			return false;
		}
	}
	
	function submit_form(){
		if(resolution_check()){
			document.getElementById('form_factory').submit();
		}
	}
	</script>
<?php 
}
?>