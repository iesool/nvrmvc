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
	$fname=$_POST['fname'];
	$mname=$_POST['mname'];
	$resolution=join(",",$_POST['resolution']);
	$format=trim($_POST['format']);
	$media_format=trim($_POST['media_format']);
	$ptz_format=trim($_POST['ptz_format']);
	
	mysql_query("insert into Factorys (Name) values ('$fname')");
	
	$fid=mysql_query("select Id from Factorys where Name='".$fname."'");
	$fid=mysql_fetch_row($fid);
	$fid=$fid[0];
	
	mysql_query("insert into Models (Factory_Id,Name,Resolution,Format,Media_format,Ptz_format) values ('$fid','$mname','$resolution','$format','$media_format','$ptz_format')");
	
	echo "<script>window.close();window.opener.location.reload()</script>";
}else{
?>
   <title>添加厂家</title>
    <div class="factory">
      <h5>添加厂家（一个厂家至少有一个型号！）</h5>
      <div>
      <form action="../admin/factory.php?view=factory_add&op=do" method="post" id="form_factory">
        <table border="0" width="100%" cellpadding="5" cellspacing="0" style="font-size:12px">
        
          <tr>
            <td>厂家名称：</td>
            <td>
              <input type="text" id="fname" name="fname" value="" onBlur="ajax_factory_name()" />
              <input type="hidden" name="check_fname" id="check_fname" value="1" />
            </td>
            
          </tr>
          <tr>
            <td width="20%">型号：</td>
            <td><input type="text" id="mname" name="mname" value="" /></td>
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
            <td>RTSP格式：</td>
            <td><input type="text" id="format" name="format" value="" size="60" /></td>
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
	function fname_check(){
		var fname=document.getElementById('fname').value;
		var check_fname=document.getElementById('check_fname').value;
		
		if(fname){
			if(check_fname==1){
				return true;
			}else{
				alert("该厂家名称已存在！");
				return false;
			}
		}else{
			alert('请填写厂家名称！');
			return false;
		}
	}
	
	
	
	function mname_check(){
		var mname=document.getElementById('mname').value;
		if(mname){
			return true;
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
		if(fname_check()&&mname_check()&&resolution_check()&&format_check()){
			document.getElementById('form_factory').submit();
		}
	}
	</script>
<?php 
}
?>