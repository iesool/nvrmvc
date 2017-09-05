<?php 
require(ROOT_PATH."/include/class_monitor.php");
$Monitor=new Monitor();
$word=$System->Get_word();

switch($_GET['op']){
	//导出前端设备
	case "monitor_dump":
	  $filename="front_equipment_".date("Y-m-d-H-i-s",time()).".xls";
	  $filepath=ROOT_PATH."tmp/fe_backup/";
	  Make_file($filepath);
	  
	  $m_dump=array();
	  
	  $m_arrs=Get_db_arrs("select * from Monitors");
	  foreach($m_arrs as $m_arr){
		  $m=array();
		  
		  $m['ID']=$m_arr['Id'];
		  $m[$word['word_name']]=$m_arr['Name'];
		  
		  $factory=Get_db_one("select Name from Factorys where Id='".$m_arr['Factory']."'");
		  if($factory){
			  $m['厂家']=$factory;
		  }else{
			  $m['厂家']=$m_arr['Factory'];
		  }
		  
		  $model=Get_db_one("select Name from Models where Id='".$m_arr['Model']."'");
		  if($model){
			  $m['型号']=$model;
		  }else{
			  $m['型号']=$m_arr['Model'];
		  }
		  
		  $m['分辨率']=$m_arr['Resolution'];
		  $m['IP地址']=$m_arr['Ip'];
		  $m['设备帐号']=$m_arr['Passport'];
		  $m['设备密码']=$m_arr['Password'];
		  $m['所在组']=Get_db_one("select Name from Groups where Id=".$m_arr['Mgroup']);
		  $m['时']=intval($m_arr['SectionLength']/3600);
		  $m['分']=intval(date("i",$m_arr['SectionLength']));
		  $m['秒']=intval(date("s",$m_arr['SectionLength']));
		  
		  
		  $m['RTSP地址']=$m_arr['Path'];
		  $m['云台地址']=$m_arr['Ptz_path'];
		  $m['云台功能']=$m_arr['Ptz_function'];
		  
		  $m_dump[]=$m;
	  }
	  
	  require(ROOT_PATH."include/PHPExcel.php");
	  
	  $objPHPExcel = new PHPExcel(); 
	  
	  $objPHPExcel->getActiveSheet()->setCellValue("A1", "ID");   
	  $objPHPExcel->getActiveSheet()->setCellValue("B1", "名称");   
	  $objPHPExcel->getActiveSheet()->setCellValue("C1", "厂家");   
	  $objPHPExcel->getActiveSheet()->setCellValue("D1", "型号");   
	  $objPHPExcel->getActiveSheet()->setCellValue("E1", "分辨率");   
	  $objPHPExcel->getActiveSheet()->setCellValue("F1", "IP地址");   
	  $objPHPExcel->getActiveSheet()->setCellValue("G1", "设备帐号");   
	  $objPHPExcel->getActiveSheet()->setCellValue("H1", "设备密码");   
	  $objPHPExcel->getActiveSheet()->setCellValue("I1", "所在组");   
	  $objPHPExcel->getActiveSheet()->setCellValue("J1", "时");   
	  $objPHPExcel->getActiveSheet()->setCellValue("K1", "分");   
	  $objPHPExcel->getActiveSheet()->setCellValue("L1", "秒");   
	  
	  $objPHPExcel->getActiveSheet()->setCellValue("M1", "RTSP地址"); 
	  $objPHPExcel->getActiveSheet()->setCellValue("N1", "云台地址"); 
	  $objPHPExcel->getActiveSheet()->setCellValue("O1", "云台功能"); 
	  
	  foreach($m_dump as $key=>$v){
		  $key+=2;
		  $objPHPExcel->getActiveSheet()->setCellValue("A".$key, $v["ID"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("B".$key, $v["名称"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("C".$key, $v["厂家"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("D".$key, $v["型号"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("E".$key, $v["分辨率"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("F".$key, $v["IP地址"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("G".$key, $v["设备帐号"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("H".$key, $v["设备密码"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("I".$key, $v["所在组"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("J".$key, $v["时"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("K".$key, $v["分"]);   
		  $objPHPExcel->getActiveSheet()->setCellValue("L".$key, $v["秒"]);  
		  
		  $objPHPExcel->getActiveSheet()->setCellValue("M".$key, $v["RTSP地址"]); 
		  $objPHPExcel->getActiveSheet()->setCellValue("N".$key, $v["云台地址"]); 
		  $objPHPExcel->getActiveSheet()->setCellValue("O".$key, $v["云台功能"]); 
	  }
	  
	  $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
	  $objWriter->save($filepath.$filename);
	  
	  echo str_replace(ROOT_PATH,"",$filepath).$filename;
	  
	  $System->System_log("normal","system","op",$word['word_log_dump_fee']."“".$filename."”");
	break;


	//导入前端设备
	case "monitor_insert":
	  $filename=$_FILES['backup_file']["name"];
	  $filepath=ROOT_PATH."tmp/fe_tmp/";
	  Make_file($filepath);
	  
	  //移动上传文件
	  move_uploaded_file($_FILES['backup_file']["tmp_name"],$filepath.$filename);
	  
	  //解析xls文件
	  require(ROOT_PATH."include/PHPExcel/IOFactory.php");
	  $objPHPExcel = PHPExcel_IOFactory::load($filepath.$filename);
	  $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
	  
	  //删除上传文件
	  unlink($filepath.$filename);
	  
	  //print_r($sheetData);
	  $errors=array();
	  $sqls=array();
	  $ids=array();
	  
	  foreach($sheetData as $key=>$v){
		  if($key==1){
			  if(trim($v['A'])!="ID"||trim($v['B'])!="名称"||trim($v['C'])!="厂家"||trim($v['D'])!="型号"||trim($v['E'])!="分辨率"||trim($v['F'])!="IP地址"||trim($v['G'])!="设备帐号"||trim($v['H'])!="设备密码"||trim($v['I'])!="所在组"||trim($v['J'])!="时"||trim($v['K'])!="分"||trim($v['L'])!="秒"){
				  $errors[]="表头不正确！";
				  break;  
			  }
		  }else{
			  $id=trim($v['A']);
			  $name=trim($v['B']);
			  $format=Get_db_one("select Format from Models where Name='".trim($v['D'])."'");
			  $path=$Monitor->Get_path($format,trim($v['F']),trim($v['G']),trim($v['H']));
			  $width=$Monitor->Get_width_height(trim($v['E']),"width");
			  $height=$Monitor->Get_width_height(trim($v['E']),"height");
			  $sequence=1;
			  $sectionlength=trim($v['J'])*3600+trim($v['K'])*60+trim($v['L']);
			  $factory=Get_db_one("select Id from Factorys where Name='".trim($v['C'])."'");
			  $model=Get_db_one("select Id from Models where Name='".trim($v['D'])."'");
			  $resolution=trim($v['E']);
			  $ip=trim($v['F']);
			  $passport=trim($v['G']);
			  $password=trim($v['H']);
			  $mgroup=Get_db_one("select Id from Groups where Name='".trim($v['I'])."'");
			  
			  //记录错误
			  if(!$id){
				  $errors[]="A".$key;
			  }else{
				  $ids[]=$id;
			  }
			  if(!$name){
				  $errors[]="B".$key;
			  }
			  if(!$factory){
				  $errors[]="C".$key;
			  }
			  if(!$format||!$path||!$model){
				  $errors[]="D".$key;
			  }
			  if(!$resolution||!$width||!$height){
				  $errors[]="E".$key;
			  }
			  if(!$ip||!preg_match("/(?:\d+\.){3}(?:\d+)/",$ip)){
				  $errors[]="F".$key;
			  }
			  if(!$mgroup){
				  $errors[]="I".$key;
			  }
			  if($sectionlength<30){
				  $errors[]="J".$key."/K".$key."/L".$key;
			  }
			  
			  $sqls[]="insert into Monitors (Id,Name,Function,Format,Path,Width,Height,Sectionlength,Factory,Model,Resolution,Ip,Passport,Password,Mgroup) values ('$id','$name','Nodect','$format','$path','$width','$height','$sectionlength','$factory','$model','$resolution','$ip','$passport','$password','$mgroup')";
			  
		  }
	  }
	  
	  //如果有误则不导入
	  if(count($errors)!=0){
		  echo "EXCEL文件中".join(",",$errors)."填写有误！&nbsp;<a href='index.php'>点击这里返回</a>";
	  }elseif(count($ids)!=count(array_unique($ids))){
	  	  echo "EXCEL文件中ID中有重复值！&nbsp;<a href='index.php'>点击这里返回</a>";
	  }else{
		  mysql_query("truncate Monitors");
		  foreach($sqls as $sql){
			 mysql_query($sql);
			 //echo $sql."<br>";
		  }
		  $System->System_log("normal","system","op",$word['word_log_import_fee']."“".$filename."”");
		  echo "<script>window.location='index.php'</script>";
	  }
	break;
	
	
	//导出数据库
	case "db_dump":
	  $filename="nvrdb_backup_".date("Y-m-d-H-i-s",time()).".sql";
	  $filepath=ROOT_PATH."tmp/db_backup/";
	  Make_file($filepath);
	  
	  //导出数据库
	  Shell_cmd("mysqldump -uroot -pasdf zm>".$filepath.$filename,$out,$rv);
	  $file=fopen($filepath.$filename,"r+");
	  fwrite($file,"use zm;\n--");
	  fclose($file);
	  
	  //记录日志
	  $System->System_log("normal","system","op",$word['word_log_dump_db']."“".$filename."”");
	  
	  //下载文件
	  header('Content-type: application/octet-stream'); 
	  header('Content-Disposition: attachment; filename="'.$filename.'"'); 
	  $tmp_arr=file($filepath.$filename);
	  foreach($tmp_arr as $v){
		  echo $v;
	  }
	break;
	
	
	//导入数据库
	case "db_insert":
	  $filename=$_FILES['backup_file']["name"];
	  $filepath=ROOT_PATH."tmp/db_tmp/";
	  Make_file($filepath);
	  
	  //移动上传文件
	  move_uploaded_file($_FILES['backup_file']["tmp_name"],$filepath.$filename);
	  
	  //导入数据库
	  Shell_cmd("mysql -uroot -pasdf<".$filepath.$filename,$out,$rv);
	  
	  //删除上传文件
	  unlink($filepath.$filename);
	  
	  //记录日志
	  $System->System_log("normal","system","op",$word['word_log_import_db']."“".$filename."”");
	  
	  echo "<script>window.location='index.php'</script>";
	break;
}
?>