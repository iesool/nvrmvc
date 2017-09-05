<?php 
if($_GET['do']){
	foreach($_POST['Id'] as $key=>$id){
		mysql_query("update Language set `Index`='".$_POST['Index'][$key]."',English='".$_POST['English'][$key]."',Chinese='".$_POST['Chinese'][$key]."' where Id=".$id);
	}
	echo "<script>alert('修改成功！');window.location='admin.php?view=language&type=".$_GET['type']."'</script>";
}else{
	if(!$_GET['type']){
		$type='全部';
	}else{
		$type=$_GET['type'];
	}
?>
    <h3>语言管理<font color="red">（注：“索引”只有开发人员可以改动！和程序相关，方便开发人员之用）</font></h3>
    
    <div>
    <?php 
	if($type=="全部"){
		echo "<a href='admin.php?view=language&type=全部' style='color:red'>全部</a>&nbsp;";
	}else{
		echo "<a href='admin.php?view=language&type=全部'>全部</a>&nbsp;";
	}
	
	$type_arrs=Get_db_arrs("select distinct(Type) from Language");
	foreach($type_arrs as $type_arr){
		if($type==$type_arr['Type']){
			echo "<a href='admin.php?view=language&type=".$type_arr['Type']."' style='color:red'>".$type_arr['Type']."</a>&nbsp;";
		}else{
			echo "<a href='admin.php?view=language&type=".$type_arr['Type']."'>".$type_arr['Type']."</a>&nbsp;";
		}
	}
	?>
    </div>
    
    <div style=" text-align:center">
      <form method="post" action="admin.php?view=language&type=<?php echo $type ?>">
        <input type="text" name="word_keyword" value="<?php echo $_POST['word_keyword']; ?>" />
        <select name="word_col">
          <option value="Index" <?php if($_POST['word_col']=='Index'){echo "selected";} ?>>索引</option>
          <option value="English" <?php if($_POST['word_col']=='English'){echo "selected";} ?>>英文</option>
          <option value="Chinese" <?php if($_POST['word_col']=='Chinese'){echo "selected";} ?> selected>中文</option>
        </select>
        <input type="submit" value="搜索" />
      </form>
    </div>
    
    <div>
      <form method="post" action="admin.php?view=language&do=1&type=<?php echo $type ?>"> 
        <table border="1" cellpadding="5" cellspacing="0" width="100%">
          <th>Id</th>
          <th><font color="red">索引</font></th>
          <th>英文</th>
          <th>中文</th>
          <?php 
		  $sql="select * from Language where Id!=0";
		  if($type!="全部"){
			  $sql.=" and Type='".$type."'";
		  }
		  
		  if($_POST['word_col']){
			  $sql.=" and `".$_POST['word_col']."` like '%".$_POST['word_keyword']."%'";
		  }
		  $sql.=" order by Id";
		 
          $word_arrs=Get_db_arrs($sql);
		  if($word_arrs){
			  foreach($word_arrs as $word_arr){
				  ?>
                  <tr>
                    <td><?php echo $word_arr['Id'] ?><input type="hidden" name="Id[]" value="<?php echo $word_arr['Id'] ?>" /></td>
                    <td><input type="text" name="Index[]" size="40" value="<?php echo $word_arr['Index'] ?>" /></td>
                    <?php /*?><td><input type="text" name="English[]" size="100" value="<?php echo $word_arr['English'] ?>" /></td>
                    <td><input type="text" name="Chinese[]" size="100" value="<?php echo $word_arr['Chinese'] ?>" /></td><?php */?>
                    <td width="40%"><textarea name="English[]" style="width:100%; height:70px"><?php echo $word_arr['English'] ?></textarea></td>
                    <td width="40%"><textarea name="Chinese[]" style="width:100%; height:70px"><?php echo $word_arr['Chinese'] ?></textarea></td>
                  </tr>
				  <?php
			  }
		  }else{
			  echo "<tr><td colspan=4>暂无</td></tr>";
		  }
          ?>
        </table>
        <div class="main_op"><input type="submit" value="保存" /></div>
      </form>
    </div>
<?php 
}
?>