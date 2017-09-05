<?php 
if($_GET['do']){
	$language=$_POST['language'];
	$diy_type=$_POST['diy_type'];
	if($diy_type=="preset"){
		$preset=$_POST['preset'];
		//复制文件
		Shell_cmd("cp -r ".ROOT_PATH."/diy/".$preset."/css/* ".ROOT_PATH."/css");
		Shell_cmd("cp -r ".ROOT_PATH."/diy/".$preset."/images/* ".ROOT_PATH."/images");
		Shell_cmd("cp -r ".ROOT_PATH."/diy/".$preset."/skins/* ".ROOT_PATH."/skins");
		//获取预设目录下设置
		require(ROOT_PATH."/diy/".$preset."/config.php");
	}else{
		$preset="";
		//上传图片
		if($_FILES['header_image']){
			File_top(ROOT_PATH."/images/header.jpg");
			move_uploaded_file($_FILES['header_image']['tmp_name'],ROOT_PATH."/images/header.jpg");
		}
		$footer_word=$_POST['footer_word'];
		$welcome_word=$_POST['welcome_word'];
	}
	Shell_cmd("cp -r ".ROOT_PATH."/js/My97DatePicker/".$language."/* ".ROOT_PATH."/js/My97DatePicker");
	$default_group=Get_db_one("select `".$language."` from Language where `Index`='word_default_group'");
	mysql_query("update Groups set name='".$default_group."' where Id=1");
	mysql_query("update Diy set Language='".$language."',Preset='".$preset."',Footer_word='".$footer_word."',Welcome_word='".$welcome_word."' ");
	echo "<script>alert('修改成功！');window.location='admin.php?view=diy'</script>";
}else{
	//获取diy目录下的预设界面目录
	$diy_dir=scandir(ROOT_PATH."/diy");
?>
    <h3>界面管理</h3>
    <div>
      <form method="post" action="admin.php?view=diy&do=1" enctype="multipart/form-data">
        <div>
          <table border="0">
            <tr>
              <td>语言：</td>
              <td>
                <select name="language">
                  <option value="Chinese" <?php if($diy['Language']=="Chinese"){echo 'selected';} ?>>中文</option>
                  <option value="English" <?php if($diy['Language']=="English"){echo 'selected';} ?>>英文</option>
                </select>
              </td>
            </tr>
            <tr>
              <td>类型：</td>
              <td>
                预设：<input type="radio" name="diy_type" onClick="switch_diy_type('preset')" value="preset" <?php if($diy['Preset']){echo "checked";} ?>>
                自定义：<input type="radio" name="diy_type" onClick="switch_diy_type('selfset')" value="selfset" <?php if(!$diy['Preset']){echo "checked";} ?>>
              </td>
            </tr>
          </table>
        </div>
        
        <div>
          <div class="diyset" id="preset" <?php if(!$diy['Preset']){echo 'style="display:none"';} ?>>界面：
            <select name="preset">
              <?php 
			  foreach($diy_dir as $key=>$preset){
				  if($key>1){
					  ?>
					  <option value="<?php echo $preset; ?>" <?php if($diy['Preset']==$preset){echo 'selected';} ?>><?php echo $preset; ?></option>
					  <?php
				  }
			  }
			  ?>
            </select>
          </div>
          
          <div class="diyset" id="selfset" <?php if($diy['Preset']){echo 'style="display:none"';} ?>>
            <table border="0">
              <tr>
                <td>头部图片：</td>
                <td><img src="images/header.jpg" width="600px" height="60px" /></td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td><input type="file" name="header_image" />&nbsp;<font color="red">请上传分辨率大小为1000*100像素的图片</font></td>
              </tr>
              <tr>
                <td>底栏文字：</td>
                <td><input type="text" name="footer_word" size="100" value="<?php echo $diy['Footer_word']; ?>" /></td>
              </tr>
              <tr>
                <td>欢迎文字：</td>
                <td><input type="text" name="welcome_word" size="100" value="<?php echo $diy['Welcome_word']; ?>" /></td>
              </tr>
            </table>
          </div>
        </div>
        
        <div class="main_op"><input type="submit" value="保存" /></div>
      </form>
    </div>
    
    <script type="text/javascript">
    function switch_diy_type(type){
        $(".diyset").hide();
        $("#"+type).show();
    }
    </script>

<?php 
}
?>