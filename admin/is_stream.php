<?php 
if($_GET['do']){
	mysql_query("update Diy set Is_stream=".$_POST['is_stream'].",Load_time=".$_POST['load_time'].",Rtsp_caching=".$_POST['rtsp_caching']);
	echo "<script>alert('修改成功！');window.location='admin.php?view=is_stream'</script>";
}else{
	$diy_arr=Get_db_arr("select Is_stream,Load_time,Rtsp_caching from Diy");
?>
    <h3>是否流转发</h3>
    <div>
      <form method="post" action="admin.php?view=is_stream&do=1">
      	是否流转发：
      	<select name="is_stream">
          <option value="0" <?php if($diy_arr['Is_stream']==0){echo "selected";} ?>>否</option>
          <option value="1" <?php if($diy_arr['Is_stream']==1){echo "selected";} ?>>是</option>
        </select>
        <br>
        加载间隔：
        <input type="text" name="load_time" value="<?php echo $diy_arr['Load_time']; ?>" />&nbsp;ms（毫秒）
        <br>
        RTSP缓存：
        <input type="text" name="rtsp_caching" value="<?php echo $diy_arr['Rtsp_caching']; ?>" />&nbsp;ms（毫秒）
        <br>
        <input type="submit" value="保存" />
      </form>
    </div>
<?php 
}
?>