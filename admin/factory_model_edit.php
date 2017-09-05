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

$m_arr=mysql_query("select * from Models where Id=".$mid);
$m_arr=mysql_fetch_array($m_arr,MYSQL_ASSOC);

if(isset($_GET['op'])){
	
	$mname=trim($_POST['mname']);
	$format=trim($_POST['format']);
	$format2=trim($_POST['format2']);
	$media_format=trim($_POST['media_format']);
	$ptz_format=trim($_POST['ptz_format']);
	
	mysql_query("update Models set Name='".$mname."',Format='".$format."',Format2='".$format2."',Media_format='".$media_format."',Ptz_format='".$ptz_format."' where Id=".$mid);
	echo "<script>window.close();window.opener.location.reload()</script>";
}else{
?>
   <title><?php echo $m_arr['Name']; ?> - 修改型号名称和格式</title>
    <div class="factory">
      <h5><?php echo $m_arr['Name']; ?> - 修改型号名称和格式</h5>
      <div>
      <form action="../admin/factory.php?view=factory_model_edit&op=do&mid=<?php echo $mid; ?>" method="post" id="form_factory">
      <input type="hidden" id="fid" name="fid" value="<?php echo $m_arr['Factory_Id']; ?>" />
        <table border="0" width="100%" cellpadding="5" cellspacing="0" style="font-size:12px">
        
          <tr>
            <td width="20%">型号名称：</td>
            <td>
              <input type="text" id="mname" name="mname" value="<?php echo $m_arr['Name']; ?>" onBlur="ajax_model_name()" />
              <input type="hidden" name="check_mname" id="check_mname" value="1" />
            </td>
          </tr>
          
          <tr>
            <td>格式：</td><td><input type="text" id="format" name="format" value="<?php echo $m_arr['Format']; ?>" size="60" /></td>
          </tr>
          <tr>
            <td>格式2：</td><td><input type="text" id="format2" name="format2" value="<?php echo $m_arr['Format2']; ?>" size="60" /></td>
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
            <td><input type="text" id="media_format" name="media_format" value="<?php echo $m_arr['Media_format']; ?>" size="60" /></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
              例如：http://{ip}/onvif/media_service，{ip}为IP地址
            </td>
          </tr>
          
          <tr>
            <td>云台地址格式：</td>
            <td><input type="text" id="ptz_format" name="ptz_format" value="<?php echo $m_arr['Ptz_format']; ?>" size="60" /></td>
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
				if(mname==<?php echo $m_arr['Name']; ?>){
					return true;
				}else{
					alert("该名称已存在！");
					return false;
				}
			}
		}else{
			alert('请填写型号名称！');
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
		if(name_check()&&format_check()){
			document.getElementById('form_factory').submit();
		}
	}
    </script>

<?php 
}
?>