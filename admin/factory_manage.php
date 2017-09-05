<style>
.factory{
	text-align:center
}
.factory a{
	text-decoration:none;
}
.factory a:link{
	color:blue;
}
.factory a:visited{
	color:blue;
}
.factory a:hover{
	color:#F00;
	text-decoration:underline;
}
</style>

<title>厂家型号管理</title>

<div class="factory">
  <h3>厂家型号管理</h3>
  <div style="float:right"><a href="javascript:pop_window(600,800,'../admin/factory.php?view=factory_add','factory_add')">添加厂家</a></div>
  
  <table border="1" cellspacing="0" cellpadding="10" style="width:1900px">
    <th width="10%">厂家名称</th>
    <th width="6%">操作</th>
    <th width="84%">型号</th>
    
    <?php 
	
	$factory_arrs=mysql_query("select * from Factorys order by Id asc");
	if(mysql_num_rows($factory_arrs)){
		while($factory_arr=mysql_fetch_array($factory_arrs,MYSQL_ASSOC)){
		?>
			<tr>
			  <td align="center">
                <span style="font-size:16px; font-weight:bold"><?php echo $factory_arr['Name']; ?></span>
              </td>
              <td align="center" style="font-size:12px">
                <a href="javascript:pop_window(600,800,'../admin/factory.php?view=factory_model_add&fid=<?php echo $factory_arr['Id']; ?>','factory_model_add')">添加型号</a><br><br>
                <?php /*?><a href="javascript:pop_window(500,600,'../admin/factory.php?view=factory_model_del&fid=<?php echo $factory_arr['Id']; ?>','factory_model_del')">删除型号</a><?php */?>
                <a href="javascript:pop_window(230,150,'../admin/factory.php?view=factory_edit&fid=<?php echo $factory_arr['Id']; ?>','factory_edit')">修改厂家名称</a><br><br>
                <a href="javascript:factory_del('<?php echo $factory_arr['Id']; ?>')">删除厂家</a>
              </td>
              
			  <td align="center" colspan="4">
                <table border="1" width="100%" cellspacing="0" cellpadding="5">
                <th width="15%">型号名称</th>
                <th width="15%">分辨率</th>
                <th width="15%">RTSP格式（主码流）</th>
                <th width="15%">RTSP格式2（辅码流）</th>
                <th width="15%">媒体地址格式</th>
                <th width="15%">云台地址格式</th>
                <th width="10%">操作</th>
                <?php 
                $model_arrs=mysql_query("select * from Models where Factory_Id=".$factory_arr['Id']);
                $m_num=mysql_num_rows($model_arrs);
                while($model_arr=mysql_fetch_array($model_arrs,MYSQL_ASSOC)){
                    ?>
                    <tr>
                      <td><span style="color:green"><?php echo $model_arr['Name']; ?></span></td>
                      <td><span style="color:blue"><?php echo $model_arr['Resolution']; ?></span></td>
                      <td><span style="color:red"><?php echo $model_arr['Format']; ?></span></td>
                      <td><span style="color:red"><?php echo $model_arr['Format2']?$model_arr['Format2']:"&nbsp;"; ?></span></td>
                      <td><span style="color:red"><?php echo $model_arr['Media_format']?$model_arr['Media_format']:"&nbsp;"; ?></span></td>
                      <td><span style="color:red"><?php echo $model_arr['Ptz_format']?$model_arr['Ptz_format']:"&nbsp;"; ?></span></td>
                      <td align="center" style="font-size:12px">
                        <a href="javascript:pop_window(500,600,'../admin/factory.php?view=factory_resolution_add&mid=<?php echo $model_arr['Id']; ?>','factory_resolution_add')">添加分辨率</a><br><br>
                        <a href="javascript:pop_window(500,600,'../admin/factory.php?view=factory_resolution_del&mid=<?php echo $model_arr['Id']; ?>','factory_resolution_del')">删除分辨率</a><br><br>
                        <a href="javascript:pop_window(550,450,'../admin/factory.php?view=factory_model_edit&mid=<?php echo $model_arr['Id']; ?>','factory_model_edit')">修改型号名称和格式</a><br><br>
                        <a href="javascript:model_del('<?php echo $model_arr['Id']; ?>','<?php echo $m_num ?>')">删除这个型号</a>
                      </td>
                    </tr>
                    <?php
                }
                ?>
                </table>
			  </td>
			</tr>
		<?php
		}
	}else{
		echo "<tr><td colspan=3 align=center>暂无厂家</td></tr>";
	}
	?>
  </table>
</div>

<script type="text/javascript">
function factory_del(fid){
	if(confirm("您确实要删除这个厂家吗？该厂家所有的型号也将被删除！")){
		var time=new Date().getTime();//获取随机时间
		if(!+[1,]){
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			xmlhttp = new XMLHttpRequest();
		}		
		var url=encodeURI('../admin/factory.php?view=ajax_factory_del&time='+time+'&fid='+fid);
		xmlhttp.open('get',url,true);
		xmlhttp.onreadystatechange = function(){
			if(xmlhttp.readyState == 4 && xmlhttp.status == 200){
				/*var msg = xmlhttp.responseText;
				alert(msg);*/
				window.location.reload();
			}
		}
		xmlhttp.send(null);
	}
}

function model_del(mid,m_num){
	if(m_num==1){
		alert("每厂家请至少保留一个型号！");
	}else{
		if(confirm("您确实要删除这个型号吗！")){
			var time=new Date().getTime();//获取随机时间
			if(!+[1,]){
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			}else{
				xmlhttp = new XMLHttpRequest();
			}		
			var url=encodeURI('../admin/factory.php?view=ajax_model_del&time='+time+'&mid='+mid);
			xmlhttp.open('get',url,true);
			xmlhttp.onreadystatechange = function(){
				if(xmlhttp.readyState == 4 && xmlhttp.status == 200){
					/*var msg = xmlhttp.responseText;
					alert(msg);*/
					window.location.reload();
				}
			}
			xmlhttp.send(null);
		}
	}
}



function pop_window(width,height,url,name){
	var left = (screen.width - width) / 2;
	var top  = (screen.height - height) / 3;
	window.open(url,name,'width='+width+',height='+height+',top='+top+',left='+left);
}
</script>
