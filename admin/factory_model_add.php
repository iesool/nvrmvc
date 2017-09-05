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
	$mname=$_POST['mname'];
	$resolution=join(",",$_POST['resolution']);
	$format=trim($_POST['format']);
	$media_format=trim($_POST['media_format']);
	$ptz_format=trim($_POST['ptz_format']);
	
	mysql_query("insert into Models (Factory_Id,Name,Resolution,Format,Media_format,Ptz_format) values ('$fid','$mname','$resolution','$format','$media_format','$ptz_format')");
	
	echo "<script>window.close();window.opener.location.reload()</script>";
	
}else{
	
	$fname=mysql_query("select Name from Factorys where Id=".$_GET['fid']);
	$fname=mysql_fetch_row($fname);
?>
   <title>厂家 - <?php echo $fname[0]; ?> - 添加型号</title>
    <div class="factory">
      <h5>厂家 - <?php echo $fname[0]; ?> - 添加型号</h5>
      <div>
      <form action="../admin/factory.php?view=factory_model_add&op=do" method="post" id="form_factory">
      <input type="hidden" id="fid" name="fid" value="<?php echo $_GET['fid']; ?>" />
        <table border="0" width="100%" cellpadding="5" cellspacing="0" style="font-size:12px">
        
          <tr>
            <td width="20%">型号：</td>
            <td>
              <input type="text" id="mname" name="mname" value="" onBlur="ajax_model_name()" />
              <input type="hidden" name="check_mname" id="check_mname" value="1" />
            </td>
          </tr>
          
          <tr>
            <td>分辨率：</td>
            <td>
              <select id="resolution" name="resolution[]" style="width:100%; height:400px" multiple>
                <option value="CIF">CIF</option>
                <option value="QCIF">QCIF</option>
                <option value="DCIF">DCIF</option>
                <option value="2CIF">2CIF</option>
                <option value="4CIF">4CIF</option>
                <option value="D1">D1</option>
                <option value="D2">D2</option>
                <option value="D3">D3</option>
                <option value="720P">720P</option>
                <option value="1080P">1080P</option>
                <option value="640*480">640*480</option>
                <option value="480*360">480*360</option>
                <option value="240*180">240*180</option>
              </select>
            </td>
          </tr>
          
          <tr>
            <td>格式：</td><td><input type="text" id="format" name="format" value="" size="60" /></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
              例如：rtsp://{passport}:{password}@{ip}/axis-media/media.amp<br />
              {passport}为设备帐号，{password}为设备密码，{ip}为IP地址
            </td>
          </tr>
          
          <tr>
            <td>媒体地址格式：</td>
            <td><input type="text" id="media_format" name="media_format" value="" size="60" /></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
              例如：http://{ip}/onvif/media_service，{ip}为IP地址
            </td>
          </tr>
          
          <tr>
            <td>云台地址格式：</td>
            <td><input type="text" id="ptz_format" name="ptz_format" value="" size="60" /></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
              例如：http://{ip}/onvif/ptz_service，{ip}为IP地址
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
	function ajax_model_name(){
		var fid=document.getElementById('fid').value;
		var mname=document.getElementById('mname').value;
		var check_mname=document.getElementById('check_mname');
		
		if(!+[1,]){
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			xmlhttp = new XMLHttpRequest();
		}
		var time=new Date().getTime();//获取随机时间
		var url=encodeURI('../admin/factory.php?view=ajax_model_name&time='+time+'&mname='+mname+'&fid='+fid);
		xmlhttp.open('get',url,true);
		xmlhttp.onreadystatechange = function(){
			if(xmlhttp.readyState == 4 && xmlhttp.status == 200){
				msg = xmlhttp.responseText;
				//alert(msg);
				if(msg==1){
					check_mname.value=1;
				}else{
					check_mname.value=0;
				}
			}
		}
		xmlhttp.send(null);
	}
	function name_check(){
		var mname=document.getElementById('mname').value;
		var check_mname=document.getElementById('check_mname').value;
		
		if(mname){
			if(check_mname==1){
				return true;
			}else{
				alert("该名称已存在！");
				return false;
			}
		}else{
			alert('请填写型号名称！');
			return false;
		}
	}
	
	
	
	function resolution_check(){
		var resolution=document.getElementById('resolution').value
		if(resolution){
			return true;
		}else{
			alert("请至少选择一个分辨率");
			return false;
		}
	}
	function format_check(){
		var format=document.getElementById('format').value;
		if(format){
			return true;
		}else{
			alert("请填写格式！");
			return false;
		}
	}
	
	function submit_form(){
		if(name_check()&&resolution_check()&&format_check()){
			document.getElementById('form_factory').submit();
		}
	}
	</script>
<?php 
}
?>