<?php 
//系统
class System{
	//获得语言数组
	function Get_word(){
		$language=Get_db_one("select Language from Diy");
		$word_arrs=mysql_query("select `Index`,`".$language."` from Language");
		$word=array();
		while($word_arr=mysql_fetch_assoc($word_arrs)){
			$word[$word_arr['Index']]=$word_arr[$language];		
		}
		return $word;
	}
	
	//系统启动停止
	function System_state(){
		mysql_query("update Monitors set Function='Nodect',Views=0");
		
		require(ROOT_PATH."/include/class_disk.php");
		$Disk=new Disk();
		
		require(ROOT_PATH."/include/class_monitor.php");
		$Monitor=new Monitor();
		
		$word=$this->Get_word();
		
		if($this->Get_state()==0){
			//系统启动
			$op="start";
			//$Disk->Disk_monitor_log(1);//运行检测磁盘
			$Disk->Disk_auto_mount();//自动挂载
			
			sleep(2);
			$op_back=Shell_cmd("/usr/local/bin/zm.sh ".$op);
			if($op_back['back']==10){
				//成功
				$this->System_log("normal","system","op",$word['word_log_system_start_success']);//记录日志
				return true;
			}else{
				//失败
				Shell_cmd("killall -9 perabytesNVR");
				$this->System_log("error","system","op",$word['word_log_system_start_failed']);//记录日志
				return false;
			}
		}else{
			//系统停止
			$op="stop";
			//$Disk->Disk_monitor_log(0);//停止检测磁盘
			$Monitor->Monitor_record_stop();//停止所有前端设备录像
			
			sleep(2);
			$op_back=Shell_cmd("/usr/local/bin/zm.sh ".$op);
			if($op_back['back']==0){
				//成功
				$this->System_log("normal","system","op",$word['word_log_system_stop_success']);//记录日志
				return true;
			}else{
				//失败
				Shell_cmd("killall -9 perabytesNVR");
				$this->System_log("error","system","op",$word['word_log_system_stop_failed']);//记录日志
				return false;
			}
		}
	}
	
	//系统自动启动
	function System_auto_nvr($item){
		$word=$this->Get_word();
		if($item['set']=="get"){
			echo Get_db_one("select Is_auto from Diy");
		}else{
			mysql_query("update Diy set Is_auto=".$item['is_auto']);
			$this->System_log("normal","system","op",$word['word_log_system_auto_nvr']);//记录日志
		}
	}
	
	//获取系统时间
	function Get_system_time(){
		return date("Y-m-d H:i:s",time());
	}
	//获得目录磁盘占用百分比
	function Get_disk_percent($path){
		$df = Shell_cmd( 'df '.$path );
		$space = -1;
		if ( preg_match( '/\s(\d+)%/ms', $df['back'], $matches ) )
			$space = $matches[1];
		return( $space );
	}
	//获得系统运行状态
	function Get_state(){
		$result=Shell_cmd("/usr/local/bin/zm.sh status");
		return ( preg_match( '/working/', $result['back'] ) );
	}
	//获得版本信息
	function Get_version(){
		$version_path="/usr/local/etc/RELEASE";
		if(file_exists($version_path)){
			$ver=file($version_path);
			return $ver[0];
		}else{
			return false;
		}
	}
	//获得产品型号
	function Get_module(){
		if(file_exists("/MODULE")){
			$module=file("/MODULE");
			return $module[0];
		}else{
			return false;
		}
	}
	//获得产品序列号
	function Get_sn(){
		if(file_exists("/SN")){
			$sn=file("/SN");
			return $sn[0];
		}else{
			return false;
		}
	}
	//获得banner状态
	function Get_banner_state(){
		$banner_state=array();
		
		$banner_state['datetime']=$this->Get_system_time();//获取系统时间
		//$banner_state['username']=$_SESSION['user']['Username'];//获取当前用户名
		
		if(Environment_check()){
			//$banner_state['sysdisk']=$this->Get_disk_percent("/root");//获得目录磁盘占用百分比
			$banner_state['version']=$this->Get_version();//获得版本信息
			$banner_state['state']=$this->Get_state();//获得系统运行状态
			$banner_state['module']=$this->Get_module();//获得产品型号
			$banner_state['sn']=$this->Get_sn();//获得产品序列号
		}else{
			//$banner_state['sysdisk']=1;//获得目录磁盘占用百分比
			$banner_state['version']="1.1.3";//获得版本信息
			$banner_state['state']=0;//获得系统运行状态
			$banner_state['module']="HAWK";//获得产品型号
			$banner_state['sn']="13520624434";//获得产品序列号
		}
		
		$diy_arr=Get_db_arr("select Footer_word,Welcome_word from Diy");
		$banner_state['footer_word']=$diy_arr['Footer_word'];
		$banner_state['welcome_word']=$diy_arr['Welcome_word'];
	  
		return json_encode($banner_state);
	}
	
	//记录日志
	function System_log($level,$class,$type,$event){
		$time=$this->Get_system_time();
		mysql_query("insert into Logs (Level,Class,Type,Time,User,Ip,Event) values ('".$level."','".$class."','".$type."','".$time."','".$_SESSION['user']['Username']."','".$_SERVER['REMOTE_ADDR']."','".$event."')");
	}
	
	//日志过滤
	function Log_filter($item,$is_str=false){
		$word=$this->Get_word();
		
		$where=" where Id!=0";
		$str=array();
		
		if(isset($item['time'])&&$item['time']!="all"){
			
			$time_arr=explode("all",$item['time']);
						
			if($time_arr[0]&&!$time_arr[1]){
				$where.=" and Time>'".$time_arr[0]."'";
				array_push($str,$word['word_system_timepart'].$time_arr[0]."——");
			}elseif(!$time_arr[0]&&$time_arr[1]){
				$where.=" and Time<'".$time_arr[1]."'";
				array_push($str,$word['word_system_timepart']."——".$time_arr[1]);
			}else{
				$where.=" and Time>'".$time_arr[0]."' and Time<'".$time_arr[1]."'";
				array_push($str,$word['word_system_timepart'].$time_arr[0]."——".$time_arr[1]);
			}
			
		}
		
		if(isset($item['level'])&&$item['level']!="all"){
			$where.=" and Level='".$item['level']."'";
			array_push($str,$word['word_system_level']."‘".$this->Log_level($item['level'],1)."’");
		}
		
		if(isset($item['class'])&&$item['class']!="all"){
			$where.=" and Class='".$item['class']."'";
			array_push($str,$word['word_system_class']."‘".$this->Log_class($item['class'])."’");
		}
		
		if(isset($item['type'])&&$item['type']!="all"){
			$where.=" and Type='".$item['type']."'";
			array_push($str,$word['word_system_type']."‘".$this->Log_type($item['type'])."’");
		}
		
		if(isset($item['user'])&&$item['user']!="all"){
			$where.=" and User='".$item['user']."'";
			array_push($str,$word['word_user']."‘".$item['user']."’");
		}
		
		if(isset($item['ip'])&&$item['ip']!="all"){
			$where.=" and Ip='".$item['ip']."'";
			array_push($str,"IP‘".$item['ip']."’");
		}
		
		if(isset($item['keyword'])){
			$where.=" and Event like '%".$item['keyword']."%'";
			array_push($str,$word['word_system_event_keyword']."‘".$item['keyword']."’");
		}
		
		if($is_str){
			return join("，",$str);
		}else{
			return $where;
		}
	}
	
	//显示log
	function Get_log_list($item){
		//获取日志过滤
		$where=$this->Log_filter($item);
		
		//日志分页排序
		$where.=" order by ".$item['order']." ".$item['by']." limit ".($item['page']-1)*$item['pagesize'].",".$item['pagesize'];
		
		if(isset($item['get'])){
			return Get_db_one("select count(*) from Logs".$where);
		}else{
			$log_list=array();
			
			$log_arrs=Get_db_arrs("select * from Logs".$where);
			if($log_arrs){
				foreach($log_arrs as $log_arr){
					$log=array();
					
					$log['time']=$log_arr['Time'];
					$log['level']=$this->Log_level($log_arr['Level']);
					$log['class']=$this->Log_class($log_arr['Class']);
					$log['type']=$this->Log_type($log_arr['Type']);
					$log['user']=$log_arr['User'];
					$log['ip']=$log_arr['Ip'];
					$log['event']=$item['keyword']?str_replace($item['keyword'],"<font color=red>".$item['keyword']."</font>",$log_arr['Event']):$log_arr['Event'];
					
					$log_list[]=$log;
				}
			}
			return json_encode($log_list);
		}
	}
	
	//下载log
	function Log_download($item){
		$where=$this->Log_filter($item);
		$log_arrs=Get_db_arrs("select * from Logs".$where." order by Time desc");
		$log_name="log_".date("Y-m-d-H-i-s",time()).".".$item['download_class'];
		
		if($item['download_class']=="log"){
			//文本文件
			header('Content-type: application/octet-stream');  
			header('Content-Disposition: attachment; filename="'.$log_name.'"');  
			
			if($log_arrs){
				foreach($log_arrs as $log_arr){
					$log=array();
					
					echo $log_arr['Time']." ";
					echo $this->Log_level($log_arr['Level'],1)." ";
					echo $this->Log_class($log_arr['Class'])." ";
					echo $this->Log_type($log_arr['Type'])." ";
					echo $log_arr['User']." ";
					echo $log_arr['Ip']." ";
					echo $log_arr['Event'];
					
					echo "\r\n";
				}
			}else{
				echo "日志为空，或该条件下没有日志";
			}
		}else{
			//excel文件
			$filepath=ROOT_PATH."tmp/log_backup/";
			$filename=$log_name;
			
			require(ROOT_PATH."include/PHPExcel.php");
			$objPHPExcel = new PHPExcel(); 
			
			if($log_arrs){
				$objPHPExcel->getActiveSheet()->setCellValue("A1", "时间");   
				$objPHPExcel->getActiveSheet()->setCellValue("B1", "级别");   
				$objPHPExcel->getActiveSheet()->setCellValue("C1", "类型");   
				$objPHPExcel->getActiveSheet()->setCellValue("D1", "性质");   
				$objPHPExcel->getActiveSheet()->setCellValue("E1", "用户");   
				$objPHPExcel->getActiveSheet()->setCellValue("F1", "IP");   
				$objPHPExcel->getActiveSheet()->setCellValue("G1", "事件");   
				
				foreach($log_arrs as $key=>$log_arr){
					$key+=2;
					$objPHPExcel->getActiveSheet()->setCellValue("A".$key, $log_arr['Time']);   
					$objPHPExcel->getActiveSheet()->setCellValue("B".$key, $this->Log_level($log_arr['Level'],1));   
					$objPHPExcel->getActiveSheet()->setCellValue("C".$key, $this->Log_class($log_arr['Class']));   
					$objPHPExcel->getActiveSheet()->setCellValue("D".$key, $this->Log_type($log_arr['Type']));   
					$objPHPExcel->getActiveSheet()->setCellValue("E".$key, $log_arr['User']);   
					$objPHPExcel->getActiveSheet()->setCellValue("F".$key, $log_arr['Ip']);   
					$objPHPExcel->getActiveSheet()->setCellValue("G".$key, $log_arr['Event']);   
				}
			}else{
				$objPHPExcel->getActiveSheet()->setCellValue("A1", "日志为空，或该条件下没有日志");   
			}
			
			$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
			File_top($filepath);
			$objWriter->save($filepath.$filename);
			echo str_replace(ROOT_PATH,"",$filepath).$filename;
		}
		
		//记录
		$word=$this->Get_word();
		$str=$this->Log_filter($item,1);
		if($str){
			$this->System_log("normal","system","op",$word['word_log_download_condition_log']."“".$str."”:“".$log_name."”");
		}else{
			$this->System_log("normal","system","op",$word['word_log_download_all_log']."“".$log_name."”");
		}
	}
	
	//删除log
	function Log_del($item){
		$where=$this->Log_filter($item);
		mysql_query("delete from Logs".$where);
		$word=$this->Get_word();
		$str=$this->Log_filter($item,1);
		if($str){
			$this->System_log("normal","system","op",$word['word_log_delete_condition_log']."“".$str."”");
		}else{
			$this->System_log("normal","system","op",$word['word_log_delete_all_log']);
		}
	}
	
	//获得日志备份时间
	function Get_time_date(){
		$time_date=Get_db_arr("select Time,Date from Logconfig");
		$time_date['Time']=$time_date['Time']!=0?date("Y-m-d H:i:s",$time_date['Time']):"";
		$time_date['Date']=$time_date['Date']!=0?date("Y-m-d H:i:s",$time_date['Date']):"";
		return json_encode($time_date);
	}
	
	//获得日志备份列表
	function Get_log_file(){
		$word=$this->Get_word();
		$file_list=array();
		$file_arr=scandir(ROOT_PATH."logs",1);
		foreach($file_arr as $key=>$filename){
			if($key<(count($file_arr)-2)){
				$file=array();
				
				$file['time']=date("Y-m-d H:i:s",filectime(ROOT_PATH."logs/".$filename));
				$file['name']=$filename."&nbsp;"."<a href=javascript:; onClick=log_file_download('".ROOT_PATH."logs/".$filename."')>".$word['word_download']."</a>";
				
				$file_list[]=$file;
			}
		}
		return json_encode($file_list);
	}
	
	//下载备份文件
	function Download_log_file($path){
		$filename=basename($path);
		$word=$this->Get_word();
		$this->System_log("normal","system","op",$word['word_log_download_condition_log']."“".$filename."”");
		
		header('Content-type: application/log;charset=utf-8');  
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		readfile($path);
	}
	
	
	//磁盘日志
	function Get_disk_log(){
		$word=$this->Get_word();
			
		if($word['word_online']=="登录为"){
			$disk_log_path="/var/log/chmonitor.log";
		}else{
			$disk_log_path="/var/log/monitor.log";
		}
		
		Shell_cmd("chmod 777 ".$disk_log_path);
		$disk_log=file($disk_log_path);
		
		$disk_log_arr=array();
		foreach($disk_log as $v){
			$explode=explode(" ",iconv("GB2312","UTF-8",$v));
			
			$arr=array();
			$arr['time']=$explode[0]." ".$explode[1];
			array_shift($explode);
			array_shift($explode);
			$arr['event']=join(" ",$explode);
			$disk_log_arr[]=$arr;
		}
		
		return json_encode($disk_log_arr);
	}
	
	function Download_disk_log(){
		$word=$this->Get_word();
			
		if($word['word_online']=="登录为"){
			$disk_log_path="/var/log/chmonitor.log";
		}else{
			$disk_log_path="/var/log/monitor.log";
		}
		header('Content-type: application/log;charset=utf-8');  
		header('Content-Disposition: attachment; filename="nvr_base.log"');
		//readfile($disk_log_path);
		$disk_log=file($disk_log_path);
		foreach($disk_log as $v){
			echo str_replace("\n","\r\n",$v);
		}
	}
	
	function Delete_disk_log(){
		Shell_cmd("rm /var/log/chmonitor.log");
		Shell_cmd("rm /var/log/monitor.log");
	}
	
	
	//日志类型转换
	function Log_class($log_class){
		$word=$this->Get_word();
		switch($log_class){
			case "monitor":
			$log_name=$word['word_fee'];
			break;
			
			case "net":
			$log_name=$word['word_net'];
			break;
			
			case "disk":
			$log_name=$word['word_disk'];
			break;
			
			case "system":
			$log_name=$word['word_system'];
			break;
			
			case "user":
			$log_name=$word['word_user'];
			break;
		}
		return $log_name;
	}
	
	//日志级别转换
	function Log_level($log_level,$is_word=false){
		$word=$this->Get_word();
		switch($log_level){
			case "normal":
			$log_name=$is_word?$word['word_system_normal']:'<img src="../images/diskinuse.png" width=15 height=15 />';
			break;
			
			case "warn":
			$log_name=$is_word?$word['word_system_warn']:'<img src="../images/diskinactive.png" width=15 height=15 />';
			break;
			
			case "error":
			$log_name=$is_word?$word['word_system_error']:'<img src="../images/diskdead.png" width=15 height=15 />';
			break;
		}
		return $log_name;
	}
	
    //日志性质转换
	function Log_type($log_type){
		$word=$this->Get_word();
		switch($log_type){
			case "op":
			$log_name=$word['word_operation'];
			break;
			
			case "run":
			$log_name=$word['word_running'];
			break;
		}
		return $log_name;
	}


	
	
	//设置系统时间
	function Set_system_time($item){
		$system_time=explode(" ",$item['system_time_text']);
		
		Shell_cmd("date -s ".$system_time[0]);
		Shell_cmd("date -s ".$system_time[1]);
		Shell_cmd("hwclock --systohc");
		
		$word=$this->Get_word();
		$this->System_log("normal","system","op",$word['word_log_set_system_time']."“".$item['system_time_text']."”");
	}
	
	//获得日志备份设置
	function Get_log_config(){
		$word=$this->Get_word();
		$logconfig=array();
		
		$cfg_arrs=Get_db_arrs("select * from Logconfig");
		foreach($cfg_arrs as $key=>$cfg_arr){
			$config=array();
			
			/*$config['Name']="<input type='hidden' name='cid[]' id='cid".$key."' value='".$cfg_arr['Id']."' />".$this->Log_class($cfg_arr['Name']);*/
			
			if($cfg_arr['Backup']==1){
				$disabled='';
				$checked='checked';
			}else{
				$disabled='disabled';
				$checked='';
			}
			$click="is_auto_delete(this,'backup_time".$key."')";
			$config['Backup']="<input type='checkbox' name='backup".$key."' id='backup".$key."' value='1' ".$checked." onClick=".$click." />";
			
			$config['Backup_Time']="<select name='backup_time".$key."' id='backup_time".$key."' ".$disabled.">";
			for($i=1;$i<=7;$i++){
				if($cfg_arr['Backup_Time']==$i){
					$config['Backup_Time'].="<option value='$i' selected>$i</option>";
				}else{
					$config['Backup_Time'].="<option value='$i'>$i</option>";
				}
			}
			$config['Backup_Time'].="</select>&nbsp;".$word['word_day'];
			
			
			if($cfg_arr['Del']==1){
				$disabled='';
				$checked='checked';
			}else{
				$disabled='disabled';
				$checked='';
			}
			$click="is_auto_delete(this,'del_time".$key."')";
			$config['Del']="<input type='checkbox' name='del".$key."' id='del".$key."' value='1' ".$checked." onClick=".$click." />";
			
			$config['Del_Time']="<select name='del_time".$key."' id='del_time".$key."' ".$disabled.">";
			$config['Del_Time'].=$cfg_arr['Del_Time']==1?"<option value='1' selected>1 ".$word['word_month']."</option>":"<option value='1'>1 ".$word['word_month']."</option>";
			$config['Del_Time'].=$cfg_arr['Del_Time']==3?"<option value='3' selected>3 ".$word['word_month']."</option>":"<option value='3'>3 ".$word['word_month']."</option>";
			$config['Del_Time'].=$cfg_arr['Del_Time']==6?"<option value='6' selected>6 ".$word['word_month']."</option>":"<option value='6'>6 ".$word['word_month']."</option>";
			$config['Del_Time'].=$cfg_arr['Del_Time']==12?"<option value='12' selected>1 ".$word['word_year']."</option>":"<option value='12'>1 ".$word['word_year']."</option>";
			/*for($i=7;$i<=30;$i++){
				if($cfg_arr['Del_Time']==$i){
					$config['Del_Time'].="<option value='$i' selected>$i</option>";
				}else{
					$config['Del_Time'].="<option value='$i'>$i</option>";
				}
			}*/
			$config['Del_Time'].="</select>";
			
			$logconfig[]=$config;
		}
		
		return json_encode($logconfig);
	}
	
	//进行日志备份设置
	function Set_log_config($item){
		//$cid_arr=$item['cid'];
		
		//foreach($cid_arr as $key=>$cid){
			$key=0;
			if(isset($item['backup'.$key])){
				$backup_time=$item['backup_time'.$key];
				mysql_query("update Logconfig set Backup=1,Backup_Time=".$backup_time);
			}else{
				mysql_query("update Logconfig set Backup=0");
			}
			if(isset($item['del'.$key])){
				$del_time=$item['del_time'.$key];
				mysql_query("update Logconfig set Del=1,Del_Time='".$del_time."'");
			}else{
				mysql_query("update Logconfig set Del=0");
			}
		//}
		
		//记录日志
		
		$word=$this->Get_word();
		
		$this->System_log("normal","system","op",$word['word_log_edited_log_backup_setting']);
		
		//File_top("/usr/local/bin/zm_events_mng");
		//Shell_cmd("/usr/local/bin/zm_events_mng");
	}
	
	//获取自动删除列表
	function Get_auto_del(){
		$word=$this->Get_word();
		$auto_del_arr=array();
		$group_arrs=Get_db_arrs("select Diskstratery.*,Groups.Name from Diskstratery,Groups where Groups.Id=Diskstratery.GroupId");
		
		foreach($group_arrs as $key=>$group){
			$a=array();
			//组
			$a['Name']="<input type='hidden' name='gid[]' id='gid".$key."' value='".$group['GroupId']."' />".$group['Name'];
			
			//按空间自动删除
			if($group['Space']==1){
				$disabled='';
				$checked='checked';
			}else{
				$disabled='disabled';
				$checked='';
			}
			$click="is_auto_delete(this,'space_date".$key."')";
			$a['Space']="<input type='checkbox' name='space".$key."' id='space".$key."' value='1' ".$checked." onClick=".$click." />";
			
			//保留空间比例
			$a['SpaceDate']="<select name='space_date".$key."' id='space_date".$key."' ".$disabled.">";
			for($i=85;$i<=95;$i++){
				if($group['SpaceDate']==$i){
					$a['SpaceDate'].="<option value='$i' selected>$i</option>";
				}else{
					$a['SpaceDate'].="<option value='$i'>$i</option>";
				}
			}
			$a['SpaceDate'].="</select>&nbsp;%";
			
			//按时间自动删除
			if($group['Time']==2){
				$disabled='';
				$checked='checked';
			}else{
				$disabled='disabled';
				$checked='';
			}
			$click="is_auto_delete(this,'time_date".$key."')";
			$a['Time']="<input type='checkbox' name='time".$key."' id='time".$key."' value='2' ".$checked." onClick=".$click." />";
			
			//保留时间长度
			$a['TimeDate']="<select name='time_date".$key."' id='time_date".$key."' ".$disabled.">";
			for($i=1;$i<=30;$i++){
				if($group['TimeDate']==$i){
					$a['TimeDate'].="<option value='$i' selected>$i</option>";
				}else{
					$a['TimeDate'].="<option value='$i'>$i</option>";
				}
			}
			$a['TimeDate'].="</select>&nbsp;".$word['word_day'];
			
			$auto_del_arr[]=$a;
		}
		return json_encode($auto_del_arr);
	}
	
	//录像自动删除
	function Set_auto_del($item){
		$gid_arr=$item['gid'];
		
		foreach($gid_arr as $key=>$gid){
			if(isset($item['space'.$key])){
				$space_date=$item['space_date'.$key];
				mysql_query("update Diskstratery set Space=1,SpaceDate=".$space_date." where GroupId=".$gid);
			}else{
				mysql_query("update Diskstratery set Space=0 where GroupId=".$gid);
			}
			if(isset($item['time'.$key])){
				$time_date=$item['time_date'.$key];
				mysql_query("update Diskstratery set Time=2,TimeDate='".$time_date."' where GroupId=".$gid);
			}else{
				mysql_query("update Diskstratery set Time=0 where GroupId=".$gid);
			}
		}
		
		//记录日志
		
		$word=$this->Get_word();
		
		$this->System_log("normal","system","op",$word['word_log_edited_auto_delete_setting']);
		
		File_top("/usr/local/bin/zm_events_mng");
		Shell_cmd("/usr/local/bin/zm_events_mng");
	}
	


	//获得计划列表
	function Get_plan_list(){
		$word=$this->Get_word();
		$plan_list=array();
		$plan_arrs=Get_db_arrs("select * from Plans order by Id asc");
		foreach($plan_arrs as $key=>$plan_arr){
			$plan=array();
			
			/*$plan['name']=$word['word_plan'].$plan_arr['Id'];
			
			if($plan_arr['Remark']){
				$plan['name'].="(".$plan_arr['Remark'].")";
			}*/
			
			$plan['name']=$plan_arr['Name'];
			
			switch($plan_arr['Type']){
				case 1:
				$plan['type']=$word['word_system_week_turn'];
				break;
				
				case 2:
				$plan['type']=$word['word_system_timepart'];
				break;
				
				default:
				$plan['type']="error";
				break;
			}
			
			//$plan['set']=$plan_arr['Start_day'].$plan_arr['End_day'].$plan_arr['Start_hour'].$plan_arr['End_hour'];
			//$plan['set']=Get_db_one("select Timepart from Timeparts where Pid=".$plan_arr['Id']);
			//echo "select Timepart from Timeparts where Pid=".$plan_arr['Id'];
			$plan_set_arrs=Get_db_arrs("select * from Timeparts where Pid=".$plan_arr['Id']." order by Id asc");
			if($plan_set_arrs){
				//$plan['set']=join("<br>",$plan_set_col);
				//$plan_set=array();
				$plan['set']="<table border=1 align=center style='border:0' width=100%>";
				foreach($plan_set_arrs as $plan_set_arr){
					//$plan_set[]=$this->trans_set($plan_arr['Type'],$plan_set_arr['Timepart'],$plan_set_arr['Is_record']);
					$plan['set'].="<tr>".$this->trans_set($plan_arr['Id'],$plan_arr['Type'],$plan_set_arr['Id'],$plan_set_arr['Timepart'])."</tr>";
				}
				$plan['set'].="</table>";
				//$plan['set']=join("<br>",$plan_set);
			}else{
				$plan['set']=$word['word_none'];
			}
			
			if($plan_arr['Is_record']==1){
				$plan['state']=$word['word_system_record_enable'];
			}else{
				$plan['state']=$word['word_system_record_disable'];
			}
			
			if($_SESSION['user']['Plan_right']==1){
				$plan['op']="<a href='javascript:;' onclick=plan_op('del',".$plan_arr['Id'].")><img src='../images/ps03.png' /></a>&nbsp;&nbsp;";
				$plan['op'].="<a href='javascript:;' onclick=plan_op('edit',".$plan_arr['Id'].")><img src='../images/ps07.png' /></a>&nbsp;&nbsp;";
				$plan['op'].="<a href='javascript:;' onclick=timepart_op('add',".$plan_arr['Id'].")><img src='../images/ps01.png' /></a>";
			}
			
			$plan_list[]=$plan;
		}
		return json_encode($plan_list);
	}
	
	//获得单个计划
	function Get_plan($item){
		echo json_encode(Get_db_arr("select * from Plans where Id=".$item['plan_id']));
	}
	
	//设置单个计划
	function Set_plan($item){
		
		$word=$this->Get_word();
		
		switch($item['control']){
			case "add":
				mysql_query("insert into Plans (Type,Name,Is_record) values (".$item['plan_type'].",'".$item['plan_name']."',".$item['is_record'].")");
				
				$this->System_log("normal","system","op",$word['word_log_plan_add']." ".$item['plan_name']);
			break;
			
			case "edit":
				mysql_query("update Plans set Type=".$item['plan_type'].",Name='".$item['plan_name']."',Is_record=".$item['is_record']." where Id=".$item['plan_id']);
				if($item['plan_type']!=$item['plan_type_yuan']){
					mysql_query("delete from Timeparts where Pid=".$item['plan_id']);
				}
				
				$this->System_log("normal","system","op",$word['word_log_plan_edit']." ".$item['plan_name']);
			break;
			
			case "del":
				$plan_name=Get_db_one("select Name from Plans where Id=".$item['plan_id']);
				
				mysql_query("delete from Plans where Id=".$item['plan_id']);
				mysql_query("delete from Timeparts where Pid=".$item['plan_id']);
							
				$this->System_log("normal","system","op",$word['word_log_plan_del']." ".$plan_name);
			break;
		}
	}
	
	//获得单个时间段
	function Get_timepart($item){
		if($item['timepart_id']){
			$sql="select Timeparts.*,Plans.Type from Timeparts,Plans where Plans.Id=Timeparts.Pid and Timeparts.Id=".$item['timepart_id'];
		}else{
			$sql="select Type from Plans where Id=".$item['plan_id'];
		}
		echo json_encode(Get_db_arr($sql));
	}
	
	//设置单个时间段
	function Set_timepart($item){
		
		$word=$this->Get_word();
		
		switch($item['control']){
			case "add":
				mysql_query("insert into Timeparts (Pid,Timepart) values (".$item['plan_id'].",'".$item['timepart']."')");
				
				$plan_name=Get_db_one("select Name from Plans where Id=".$item['plan_id']);
				$this->System_log("normal","system","op",$plan_name." ".$word['word_log_timepart_add']);
			break;
			
			case "edit":
				mysql_query("update Timeparts set Timepart='".$item['timepart']."' where Id=".$item['timepart_id']);
				
				$plan_name=Get_db_one("select Plans.Name from Plans,Timeparts where Timeparts.Id=".$item['timepart_id']." and Timeparts.Pid=Plans.Id");
				$this->System_log("normal","system","op",$plan_name." ".$word['word_log_timepart_edit']);
			break;
			
			case "del":
				$plan_name=Get_db_one("select Plans.Name from Plans,Timeparts where Timeparts.Id=".$item['timepart_id']." and Timeparts.Pid=Plans.Id");
				
				mysql_query("delete from Timeparts where Id=".$item['timepart_id']);
							
				$this->System_log("normal","system","op",$plan_name." ".$word['word_log_timepart_del']);
			break;
		}
	}
	
	//转换设置
	function trans_set($plan_id,$plan_type,$timepart_id,$timepart){
		$word=$this->Get_word();
		
		$set="";
		$timepart_arr=explode(" ",$timepart);
		
		if($plan_type==1){
			//$set.=$this->trans_week($timepart_arr['0'])." ".$timepart_arr['1']." ".$word['word_system_to']." ".$this->trans_week($timepart_arr['2'])." ".$timepart_arr['3'];
			$set.="<td width=20%>".$this->trans_week($timepart_arr['0'])."</td>"."<td width=15%>".$timepart_arr['1']."</td>"."<td width=5%>".$word['word_system_to']."</td>"."<td width=20%>".$this->trans_week($timepart_arr['2'])."</td>"."<td width=15%>".$timepart_arr['3']."</td>";
		}else{
			//$set.=$timepart_arr['0']." ".$timepart_arr['1']." ".$word['word_system_to']." ".$timepart_arr['2']." ".$timepart_arr['3'];
			$set.="<td  width=20%>".$timepart_arr['0']."</td>"."<td width=15%>".$timepart_arr['1']."</td>"."<td width=5%>".$word['word_system_to']."</td>"."<td width=20%>".$timepart_arr['2']."</td>"."<td width=15%>".$timepart_arr['3']."</td>";
		}
		
		/*if($is_record==1){
			//$set.=" [录像]";
			$set.="<td width=12%>".$word['word_system_record_enable']."</td>";
		}else{
			//$set.=" [不录像]";
			$set.="<td width=12%>".$word['word_system_record_disable']."</td>";
		}*/
		if($_SESSION['user']['Plan_right']==1){
			$set.="<td width=16%>";
			$set.="<a href='javascript:;' onclick=timepart_op('del',".$plan_id.",".$timepart_id.")><img src='../images/ps03.png' /></a>&nbsp;&nbsp;";
			$set.="<a href='javascript:;' onclick=timepart_op('edit',".$plan_id.",".$timepart_id.")><img src='../images/ps07.png' /></a>";
			$set.="</td>";
		}
		return $set;				
	}
	
	//周转换
	function trans_week($num){
		$word=$this->Get_word();
		switch($num){
			case 0:
			$week=$word['word_system_mon'];
			break;
			
			case 1:
			$week=$word['word_system_tue'];
			break;
			
			case 2:
			$week=$word['word_system_wed'];
			break;
			
			case 3:
			$week=$word['word_system_thu'];
			break;
			
			case 4:
			$week=$word['word_system_fri'];
			break;
			
			case 5:
			$week=$word['word_system_sat'];
			break;
			
			case 6:
			$week=$word['word_system_sun'];
			break;
		}
		return $week;
	}
	
	
	
	
	//获得设备
	function Get_machines($get){
		$machines=array();
		$machine_arrs=Get_db_arrs("select * from Machines");
		foreach($machine_arrs as $machine_arr){
			$machine=array();
			
			$href="http://".$machine_arr['Ip']."/index.php?do=ajax&view=user_login&username=".$machine_arr['Username']."&password=".$machine_arr['Password']."&auto=1";
			
			$machine['name']="<a href='".$href."' target='_bank'>".$machine_arr['Name']."</a>";
			$machine['ip']="<a href='".$href."' target='_bank'>".$machine_arr['Ip']."</a>";
			
			if($get=="set"){
				$machine['op']="<a href='javascript:;' onclick='machine_del(".$machine_arr['Id'].")'><img src='../images/ps03.png' /></a>&nbsp;&nbsp;&nbsp;";
				$machine['op'].="<a href='javascript:;' onclick='machine_edit(".$machine_arr['Id'].")'><img src='../images/ps07.png' /></a>";
			}
			$machines[]=$machine;
		}
		return json_encode($machines);
	}
	//添加设备
	function Machine_add($item){
		mysql_query("insert into Machines (Name,Ip,Username,Password) values ('".$item['machine_name']."','".$item['machine_ip']."','".$item['machine_username']."','".$item['machine_password']."')");
	}
	//删除设备
	function Machine_del($id){
		mysql_query("delete from Machines where Id=".$id);
	}
	//获得要修改的设备
	function Get_machine($id){
		return json_encode(Get_db_arr("select * from Machines where Id=".$id));
	}
	//修改
	function Machine_edit($item,$id){
		mysql_query("update Machines set Name='".$item['machine_name']."',Ip='".$item['machine_ip']."',Username='".$item['machine_username']."',Password='".$item['machine_password']."' where Id=".$id);
	}
	
	
	
	//机器关机重启
	function System_power($cmd){
		$word=$this->Get_word();
		//停止系统
		if($this->Get_state()==1){
			$this->System_state();
		}
		//记录日志
		if($cmd==6){
			$this->System_log("normal","system","op",$word['word_log_machine_restart']);
		}else{
			$this->System_log("normal","system","op",$word['word_log_machine_shutdown']);
		}
		//执行命令
		Shell_cmd("init ".$cmd);
	}
	
	
}

?>