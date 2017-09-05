<?php 
class Monitor{
//前端设备

	//获得前端设备状态
	function Get_monitor_function($monitor_function,$color=false){
		global $System;
		$word=$System->Get_word();
		
		$running=$System->Get_state();
		if($running){
			switch($monitor_function){
				case "Nodect":
				  //未实时未录像，给用户显示未连接
				  $function=$color?$word['word_monitor_offline']:"<font color=black>".$word['word_monitor_offline']."</font>";
				break;
				
				case "Watch":
				  //实时未录像，给用户显示未连接（该状态只用于程序判断）	
				  $function=$color?$word['word_monitor_watching']:"<font color=black>".$word['word_monitor_watching']."</font>";
				break;
				
				case "Record":
				  //未实时已录像，给用户显示录像中
				  $function=$color?$word['word_monitor_recording']:"<font color=green>".$word['word_monitor_recording']."</font>";
				break;
				
				case "SameTime":
				  //同时实时和录像，给用户显示录像中（该状态只用于程序判断）
				  $function=$color?$word['word_monitor_recording']:"<font color=green>".$word['word_monitor_recording']."</font>";	
				break;
				
				default:
				  $function=$color?$word['word_monitor_offline']:"<font color=black>".$word['word_monitor_offline']."</font>";
				break;
			}
		}else{
			$function=$color?$word['word_closed']:"<font color=red>".$word['word_closed']."</font>";
		}
		return $function;
	}

	//获取前端设备列表
	function Get_monitor_list($item){
		global $System;
		$word=$System->Get_word();
		$running=$System->Get_state();
		
		$where=" order by ".$item['order']." ".$item['by']." limit ".($item['page']-1)*$item['pagesize'].",".$item['pagesize'];
		
		if(isset($item['get'])){
			return Get_db_one("select count(*) from Monitors".$where);
		}else{
			$monitor_arrs=array();
			
			$m_arrs=mysql_query("select Id,Name,Function,Views,Ip,Mgroup,Plan_id from Monitors".$where);
			if($m_arrs){
				while($m_arr=mysql_fetch_assoc($m_arrs)){
					$m=array();
					
					//ID
					$m['id']=$m_arr['Id'];
					
					//名称
					if($running){
						$m['name']="<a href=javascript:monitor_pop('watch',1,".$m_arr['Id'].",'".$m_arr['Name']."')>".$m_arr['Name']."</a>";
					}else{
						$m['name']=$m_arr['Name'];
					}
					
					//所属组
					$m['gname']=Get_db_one("select Name from Groups where Id=".$m_arr['Mgroup']);
					
					//状态
					$m['function']=$this->Get_monitor_function($m_arr['Function']);
					if($m_arr['Plan_id']!=0){
						$plan_name=Get_db_one("select Name from Plans where Id=".$m_arr['Plan_id']);
						if($plan_name){
							$m['function'].="[".$plan_name."]";
						}else{
							mysql_query("update Monitors set Plan_id=0 where Id=".$m_arr['Id']);
						}
					}
					//if($m_arr['Function']!="Nodect"){
						//$m['function'].="(".$m_arr['Views'].")";
					//}
					
					//空间
					$m['space']=$this->Get_group_state($m_arr['Mgroup']);
					
					//IP
					$m['ip']=$m_arr['Ip'];
					
					//录像个数
					$event_count=Get_db_one("select count(*) from Events where MonitorId=".$m_arr['Id']);
					$m['event_count']="<a href=javascript:event_list(".$m_arr['Id'].",'".$m_arr['Name']."')>".$event_count."</a>";
					
					//操作
					$m['op']="";
					if($m_arr['Function']=="Record"||$m_arr['Function']=="SameTime"){
						$m['op'].='<a href=javascript:monitor_op(1,'.$m_arr['Id'].') title="'.$word['word_monitor_record_stop'].'"><img src="../images/ps09.gif" width=20 height=20 /></a>&nbsp;&nbsp;';
					}else{
						$m['op'].='<a href=javascript:monitor_op(0,'.$m_arr['Id'].') title="'.$word['word_monitor_record_start'].'"><img src="../images/ps08.gif" width=20 height=20 /></a>&nbsp;&nbsp;';
					}
					$m['op'].='<a href=javascript:monitor_op(3,'.$m_arr['Id'].') title="'.$word['word_monitor_edit'].'"><img src="../images/ps07.png" /></a>&nbsp;&nbsp;';
					$m['op'].='<a href=javascript:monitor_op(2,'.$m_arr['Id'].') title="'.$word['word_delete'].'"><img src="../images/ps03.png" /></a>';
					
					//选择
					$mid_arr=explode(",",$_COOKIE['mid_str']);
					if(in_array($m_arr['Id'],$mid_arr)){
						$m['check']="<input type='checkbox' name='markMids[]' id='edit_arr' value='".$m_arr['Id']."' onclick='check_cookie()' checked />";
					}else{
						$m['check']="<input type='checkbox' name='markMids[]' id='edit_arr' value='".$m_arr['Id']."' onclick='check_cookie()' />";
					}
					
					$monitor_arrs[]=$m;
				}
			}
			
			return json_encode($monitor_arrs);
		}
	}
	
	//获得树
	function Get_monitor_tree(){
		global $System;
		$word=$System->Get_word();
		$running=$System->Get_state();
		
		$mid_arr=explode(",",$_COOKIE['mid_str']);
		$gid_arr=explode(",",$_SESSION['user']['Groups']);
		
		$group_arrs=Get_db_arrs("select Id,Name,Is_mount from Groups");
		$groups=array();
		
		foreach($group_arrs as $group_arr){
			if(in_array($group_arr['Id'],$gid_arr)){
				$group=array();
				$group['gid']=$group_arr['Id'];
				$group['name']=$group_arr['Name'];
				$group['is_mount']=$group_arr['Is_mount'];
				$group['isParent']="true";
				$group['open']="true";
				$group['children']=array();
				
				//是否录像
				$is_record=Get_db_one("select Function from Monitors where Function='Record' and Mgroup=".$group_arr['Id']." or Function='SameTime' and Mgroup=".$group_arr['Id']);
				if($is_record){
					$group['is_record']=1;
				}
				
				$monitor_arrs=Get_db_arrs("select Id,Name,Function,Ip,Plan_id from Monitors where Mgroup=".$group_arr['Id']." order by Id asc");
				
				//获得计划
				$plan_id_arr=array();
				if(!empty($monitor_arrs)){
					foreach($monitor_arrs as $m){
						if($m['Plan_id']!=0){
							$plan_id_arr[]=$m['Plan_id'];
						}
					}
				}
				$plan_id=0;
				if(count(array_unique($plan_id_arr))==1&&count($plan_id_arr)==count($monitor_arrs)){
					$plan_id=$plan_id_arr[0];
				}
				$group['plan_id']=$plan_id;
				
				//获得前端设备
				if(!empty($monitor_arrs)){
					foreach($monitor_arrs as $monitor_arr){
						$monitor=array();
						$monitor['mid']=$monitor_arr['Id'];
						
						if($mid_arr&&in_array($monitor_arr['Id'],$mid_arr)){
							$monitor['checked']="true";
						}else{
							$monitor['checked']="false";
						}
						
						$monitor['name']="";
						if($running){
							$monitor['chkDisabled']="";
						}else{
							$monitor['chkDisabled']="true";
						}
						$monitor['name'].=$monitor_arr['Name'];
						if($monitor_arr['Plan_id']!=0){
							$plan_name=Get_db_one("select Name from Plans where Id=".$monitor_arr['Plan_id']);
							if($plan_name){
								$monitor['name'].="[".$plan_name."]";
							}else{
								mysql_query("update Monitors set Plan_id=0 where Id=".$monitor_arr['Id']);
							}
						}
						
						if($monitor_arr['Function']=="Record"||$monitor_arr['Function']=="SameTime"){
							$monitor['icon']="../images/ps11.gif";
						}else{
							$monitor['icon']="../images/ps10.gif";
						}
						array_push($group['children'],$monitor);
					}
				}
				array_push($groups,$group);
			}
		}
		$tree=array(
					 array("name"=>$word['word_all'],"isParent"=>"true","open"=>"true","children"=>$groups),
					 );
		return json_encode($tree);
	}
	
	
	
	//前端设备操作
	function Monitor_control($control,$item){
		switch($control){
			case 0:
			  $this->Monitor_record_start($item);
			break;
			
			case 1:
			  $this->Monitor_record_stop($item);
			break;
			
			case 2:
			  $this->Monitor_del($item);
			break;
			
			case 5:
			  $this->Monitor_plan($item);
			break;
			
			default:
			break;
		}
	}
	
	
	//前端设备开始录像
	function Monitor_record_start($item){
		global $System;
		$word=$System->Get_word();
		
		$mname_arr=array();//记录录像成功的前端设备名称用于记录日志
		
		foreach($item['markMids'] as $mid){
			$m_arr=Get_db_arr("select Name,Function,SectionLength,Width,Height,WarmupCount,Path,Path2,Stream,Mgroup from Monitors where Id=".$mid);
			
			//判断主辅码流
			if($m_arr['Stream']==1){
				$monitor_path=$m_arr['Path'];
			}else{
				$monitor_path=$m_arr['Path2'];
			}
			
			$is_mount=Get_db_one("select Is_mount from Groups where Id=".$m_arr['Mgroup']);//判断前端设备所在组是否挂载
			$is_full=Get_db_one("select StateFlag from Diskstratery where GroupId=".$m_arr['Mgroup']);//判断前端设备所在组所挂载的逻辑卷是否满（1为警告，2为满）
			
			if($is_mount==0){
				//如果未挂载
				echo "“".$m_arr['Name']."”".$word['word_monitor_no_space'];
			}elseif($is_full==2){
				//如果磁盘已满
				echo "“".$m_arr['Name']."”".$word['word_monitor_full_space'];
			}else{
				//判断前端设备当前状态，根据状态判断是否需要连接和录像
				switch($m_arr['Function']){
					case "Nodect":
						$is_connect=1;//是否需要连接
						$is_record=1;//是否需要录像
						$connected=0;//是否连接成功或已经连接
						$recorded=0;//是否录像成功或已经录像
						$next_function="Record";
					break;
					
					case "Watch":
						$is_connect=0;
						$is_record=1;
						$connected=1;
						$recorded=0;
						$next_function="SameTime";
					break;
					
					case "Record":
						$is_connect=0;
						$is_record=0;
						$connected=1;
						$recorded=1;
						$next_function="Record";
						
						echo "“".$m_arr['Name']."”".$word['word_monitor_has_start'];
					break;
					
					case "SameTime":
						$is_connect=0;
						$is_record=0;
						$connected=1;
						$recorded=1;
						$next_function="SameTime";
						
						echo "“".$m_arr['Name']."”".$word['word_monitor_has_start'];
					break;
					
					default:
					break;
				}
				
				if($is_connect==1){
					$connect_back=Shell_cmd("NVRCommandLine -C start -i 1000 -m ".$mid." -u '".$monitor_path."'");//开始连接
					switch($connect_back['back']){
						case 0:
							$connected=1;
						break;
						
						case 1:
							$connected=1;
						break;
						
						case 15:
							$connected=0;
							echo "“".$m_arr['Name']."”".$word['word_monitor_connect_start_failed'];
						break;
						
						case 16:
							$connected=0;
							echo "“".$m_arr['Name']."”".$word['word_monitor_connect_start_failed'];
						break;
						
						default:
							$connected=0;
							echo "“".$m_arr['Name']."”".$word['word_monitor_connect_start_failed'];
						break;
					}
				}
				
				if($is_record==1&&$connected==1){
					$record_back=Shell_cmd("NVRCommandLine -R start -i 1000 -m ".$mid." -d ".$m_arr['SectionLength']." -h ".$m_arr['Height']." -l ".$m_arr['Width']." -v ".$m_arr['WarmupCount']);//开始录像
					switch($record_back['back']){
						case 0:
							$recorded=1;
						break;
						
						case 1:
							$recorded=1;
						break;
						
						case 15:	
							$recorded=0;
							echo "“".$m_arr['Name']."”".$word['word_monitor_record_start_failed'];
						break;
						
						case 16:	
							$recorded=0;
							echo "“".$m_arr['Name']."”".$word['word_monitor_record_start_failed'];
						break;
						
						default:
							$recorded=0;
							echo "“".$m_arr['Name']."”".$word['word_monitor_record_start_failed'];
						break;
					}
				}
				
				if(($is_connect==1&&$connected==1)||($is_record==1&&$recorded==1)){
					mysql_query("update Monitors set Function='".$next_function."',LostFlag=0 where Id = ".$mid);
					$mname_arr[]=$m_arr['Name'];
				}
				
			}
		}
		
		//如果有成功开始录像的记录日志
		if(!empty($mname_arr)){
			$mname_str=join(",",$mname_arr);
			$System->System_log("normal","monitor","op","“".$mname_str."”".$word['word_log_start_record']);
		}
	}
	
	//前端设备停止录像
	function Monitor_record_stop($item,$del=false){
		global $System;
		$word=$System->Get_word();
		
		if($item){
			$mid_arr=$item['markMids'];//获得指定ID
		}else{
			$mid_arr=Get_db_col("select Id from Monitors","Id");//获得全部ID
		}
		
		$mname_arr=array();
		$stoped_mid=array();
		
		foreach($mid_arr as $mid){
			$m_arr=Get_db_arr("select Name,Function from Monitors where Id=".$mid);
			
			//判断前端设备当前状态，根据状态判断是否需要连接和录像
			switch($m_arr['Function']){
				case "Nodect":
					$is_deconnect=0;//是否需要断开连接
					$is_derecord=0;//是否需要停止录像
					$deconnected=1;//是否断开连接成功或已经断开连接
					$derecorded=1;//是否停止录像成功或已经停止录像
					$next_function="Nodect";
				
					if($del){
						$mname_arr[]=$m_arr['Name'];
						$stoped_mid[]=$mid;
					}else{
						echo "“".$m_arr['Name']."”".$word['word_monitor_has_stop'];
					}
				break;
				
				case "Watch":
					$is_deconnect=0;//是否需要断开连接
					$is_derecord=0;//是否需要停止录像
					$deconnected=0;//是否断开连接成功或已经断开连接
					$derecorded=1;//是否停止录像成功或已经停止录像
					$next_function="Watch";
					
					if($del){
						echo "“".$m_arr['Name']."”".$word['word_monitor_watch'];
					}else{
						echo "“".$m_arr['Name']."”".$word['word_monitor_has_stop'];
					}
				break;
				
				case "Record":
					$is_deconnect=1;//是否需要断开连接
					$is_derecord=1;//是否需要停止录像
					$deconnected=0;//是否断开连接成功或已经断开连接
					$derecorded=0;//是否停止录像成功或已经停止录像
					$next_function="Nodect";
				break;
				
				case "SameTime":
					$is_deconnect=0;//是否需要断开连接
					$is_derecord=1;//是否需要停止录像
					$deconnected=0;//是否断开连接成功或已经断开连接
					$derecorded=0;//是否停止录像成功或已经停止录像
					$next_function="Watch";
				
					if($del){
						$is_derecord=0;
						echo "“".$m_arr['Name']."”".$word['word_monitor_watch'];
					}
				break;
				
				default:
				break;
			}
			
			if($is_derecord==1){
				$record_back=Shell_cmd("NVRCommandLine -R stop -i 1000 -m ".$mid);//停止录像
				switch($record_back['back']){
					case 0:
						$derecorded=1;
					break;
					
					case 1:
						$derecorded=1;
					break;
					
					case 15:	
						$derecorded=0;
						echo "“".$m_arr['Name']."”".$word['word_monitor_record_stop_failed'];
					break;
					
					case 16:	
						$derecorded=0;
						echo "“".$m_arr['Name']."”".$word['word_monitor_record_stop_failed'];
					break;
					
					default:
						$derecorded=0;
						echo "“".$m_arr['Name']."”".$word['word_monitor_record_stop_failed'];
					break;
				}
			}
				
			if($is_deconnect==1&&$derecorded==1){
				$connect_back=Shell_cmd("NVRCommandLine -C stop -i 1000 -m ".$mid);//断开连接
				switch($connect_back['back']){
					case 0:
						$deconnected=1;
					break;
					
					case 1:
						$deconnected=1;
					break;
					
					case 15:	
						$deconnected=0;
						echo "“".$m_arr['Name']."”".$word['word_monitor_connect_stop_failed'];
					break;
					
					case 16:	
						$deconnected=0;
						echo "“".$m_arr['Name']."”".$word['word_monitor_connect_stop_failed'];
					break;
					
					default:
						$deconnected=0;
						echo "“".$m_arr['Name']."”".$word['word_monitor_connect_stop_failed'];
					break;
				}
			}
			
			if(($is_deconnect==1&&$deconnected==1)||($is_derecord==1&&$derecorded==1)){
				mysql_query("update Monitors set Function='".$next_function."',LostFlag=0 where Id = ".$mid);
				$mname_arr[]=$m_arr['Name'];
				$stoped_mid[]=$mid;
			}
					
		}
		
		if($del){
			if(!empty($stoped_mid)){
				return $stoped_mid;
			}
		}else{
			if(!empty($mname_arr)){
				$mname_str=join(",",$mname_arr);
				$System->System_log("normal","monitor","op","“".$mname_str."”".$word['word_log_stop_record']);
			}
		}
		
	}
	
	//删除前端设备
	function Monitor_del($item){
		global $System;
		$word=$System->Get_word();
		
		//停止录像并获得已停止录像的mid
		$stoped_mid=$this->Monitor_record_stop($item,"del");
		
		$mname_arr=array();
		foreach($stoped_mid as $mid){
			$m_arr=Get_db_arr("select Name,Mgroup,Ptz_path,Ptz_function from Monitors where Id=".$mid);
			
			//删除录像
			$delete_path=EVENT_PATH.$m_arr['Mgroup']."/".$mid."/";
			
			if(file_exists($delete_path)){
				File_top($delete_path);
				Shell_cmd("rsync --delete-before -a -H -v --progress --stats ".EMPTY_PATH." ".$delete_path);
				Shell_cmd("rm -rf ".$delete_path);
			}
			
			//删除预置点
			if($m_arr['Ptz_path']&&$m_arr['Ptz_function']){
				$this->Ptz_control(array("mid"=>$mid,"ptz_path"=>$m_arr['Ptz_path'],"control"=>"preset","val"=>"clear","val2"=>"all"));
			}
			
			//删除数据库记录
			mysql_query( "delete from Monitors where Id = ".$mid );
			mysql_query( "delete from Events where MonitorId = ".$mid );
			
			$mname_arr[]=$m_arr['Name'];
		}
		if(!empty($mname_arr)){
			$mname_str=join(",",$mname_arr);
			$System->System_log("normal","monitor","op",$word['word_log_deleted_monitor']."“".$mname_str."”");
		}
	}
	
	//前端设备设置录像计划
	function Monitor_plan($item){
		$mid_arr=explode(",",$item['mid_str']);
		foreach($mid_arr as $mid){
			mysql_query("update Monitors set Plan_id=".$item['plan']." where Id=".$mid);
		}
	}

	//获取单个前端设备信息
	function Get_monitor($mid=false){
		$monitor=array();
		
		if($mid){
			$m_arr=Get_db_arr("select * from Monitors where Id=".$mid);
			
			$monitor['name']=$m_arr['Name'];
			$monitor['function']=$m_arr['Function'];
			
			$monitor['factory']=$m_arr['Factory'];
			$monitor['model']=$m_arr['Model'];
			$monitor['resolution']=$m_arr['Resolution'];
			
			$monitor['ip']=$m_arr['Ip'];
			$monitor['passport']=$m_arr['Passport'];
			$monitor['password']=$m_arr['Password'];
			$monitor['mgroup']=Get_db_one("select Name from Groups where Id=".$m_arr['Mgroup']);
			$monitor['hour']=intval($m_arr['SectionLength']/3600);
			$monitor['minute']=date("i",$m_arr['SectionLength']);
			$monitor['second']=date("s",$m_arr['SectionLength']);
			
			$monitor['format']=$m_arr['Format'];
			$monitor['format2']=$m_arr['Format2'];
			$monitor['stream']=$m_arr['Stream'];
			
			if($m_arr['Format']){
				$monitor['model_arr']=Get_db_arrs("select Id,Name from Models where Factory_Id=".$m_arr['Factory']);
				$monitor['resolution_arr']=Get_db_one("select Resolution from Models where Id=".$m_arr['Model']);
				$monitor['resolution_arr']=explode(",",$monitor['resolution_arr']);
			}else{
				$monitor['resolution_arr']=$this->Get_resolution_arr();
			}
			
			$monitor['plan_id']=$m_arr['Plan_id'];
		}else{
			$table_status=$this->Get_table_status();
			$monitor['auto_increment']=$table_status['Auto_increment'];
			
			$monitor['model_arr']=Get_db_arrs("select Id,Name from Models");
		}
		
		$monitor['factory_arr']=Get_db_arrs("select Id,Name from Factorys");
		
		$groups_sql=$this->Group_right("Id");
		$monitor['group']=Get_db_arrs("select Id,Name from Groups where ".$groups_sql);
		
		return json_encode($monitor);
	}
	//获得型号列表
	function Get_model($fid){
		$model=Get_db_arrs("select Id,Name from Models where Factory_Id=".$fid);
		return json_encode($model);
	}
	//获得分辨率列表
	function Get_resolution($mid){
		$resolution=Get_db_arr("select Resolution,Format,Format2 from Models where Id=".$mid);
		return json_encode($resolution);
	}
	//获取前端设备表状态
	function Get_table_status(){
		$table_status=Get_db_arr("show table status where Name='Monitors'");
		return $table_status;
	}
	
	//验证前端设备名称是否存在
	function Monitor_name_validate($name){
		$mname=Get_db_one("select Name from Monitors where Name='$name'");
		echo $mname;
	}
	
	//添加前端设备
	function Monitor_creat($item){
		global $System;
		$word=$System->Get_word();
		
		$sectionLength=$item['hour']*3600+$item['minute']*60+$item['second'];
		
		//获取格式
		$model_arr=Get_db_arr("select * from Models where Id=".$item['model']);
		if($model_arr){
			$format=$model_arr['Format'];
			$format2=$model_arr['Format2'];
		}else{
			$format=$item['format'];
			$format2=$item['format2'];
		}
		
		$path=$this->Get_path($format,$item['ip'],$item['passport'],$item['password']);
		$path2=$this->Get_path($format2,$item['ip'],$item['passport'],$item['password']);
		
		$width=$this->Get_width_height($item['resolution'],"width");
		$height=$this->Get_width_height($item['resolution'],"height");
		
		//获取媒体地址和云台地址
		if($model_arr){
			if($model_arr['Media_format']&&$model_arr['Ptz_format']){
				$media_path=str_replace("{ip}",$item['ip'],$model_arr['Media_format']);
				$ptz_path=str_replace("{ip}",$item['ip'],$model_arr['Ptz_format']);
			}else{
				$media_path="";
				$ptz_path="";
			}
		}else{
			$media_path="";
			$ptz_path="";
		}
		
		mysql_query("insert into Monitors set Name = '".$item['name']."', Function = 'Nodect',  Format = '".$item['format']."', Path = '".$path."',Format2 = '".$item['format2']."', Path2 = '".$path2."',Stream='".$item['stream']."', Width = '".$width."', Height = '".$height."', WarmupCount = '25',  SectionLength = '".$sectionLength."', Factory='".$item['factory']."', Model='".$item['model']."', Resolution='".$item['resolution']."',Ip='".$item['ip']."',Passport='".$item['passport']."',Password='".$item['password']."',Mgroup ='".$item['group']."',Plan_id=".$item['plan'].",Media='".$media_path."',Ptz_path='".$ptz_path."',Ptz_function='1110011'");
		
		$System->System_log("normal","monitor","op",$word['word_log_created_monitor']."“".$item['name']."”");
	}
	
	//修改前端设备
	function Monitor_edit($item){
		global $System;
		$word=$System->Get_word();
		
		if($item['function']=="Record"||$item['function']=="SameTime"){
			//录像中的设备（只能修改名称）
			//mysql_query("update Monitors set Name='".$item['name']."',SectionLength='".$sectionLength."' where Id=".$item['mid']);
			mysql_query("update Monitors set Name='".$item['name']."' where Id=".$item['mid']);
			
		}elseif($item['format']==""){
			
			//设备发现的设备（能修改名称，分辨率，组，计划和录像段长度）
			$sectionLength=$item['hour']*3600+$item['minute']*60+$item['second'];
			
			$width=$this->Get_width_height($item['resolution'],"width");
			$height=$this->Get_width_height($item['resolution'],"height");
			
			mysql_query("update Monitors set Name='".$item['name']."',Width='".$width."',Height='".$height."',SectionLength='".$sectionLength."',Resolution='".$item['resolution']."',Passport='".$item['passport']."',Password='".$item['password']."',Mgroup='".$item['group']."',Plan_id=".$item['plan']." where Id=".$item['mid']);
		}else{
			//手动添加的设备（都能修改）
			$sectionLength=$item['hour']*3600+$item['minute']*60+$item['second'];
			
			//获取格式
			$model_arr=Get_db_arr("select * from Models where Id=".$item['model']);
			if($model_arr){
				$format=$model_arr['Format'];
				$format2=$model_arr['Format2'];
			}else{
				$format=$item['format'];
				$format2=$item['format2'];
			}
			
			$path=$this->Get_path($format,$item['ip'],$item['passport'],$item['password']);
			$path2=$this->Get_path($format2,$item['ip'],$item['passport'],$item['password']);
			
			$width=$this->Get_width_height($item['resolution'],"width");
			$height=$this->Get_width_height($item['resolution'],"height");
			
			//获取媒体地址和云台地址
			if($model_arr){
				if($model_arr['Media_format']&&$model_arr['Ptz_format']){
					$media_path=str_replace("{ip}",$item['ip'],$model_arr['Media_format']);
					$ptz_path=str_replace("{ip}",$item['ip'],$model_arr['Ptz_format']);
				}else{
					$media_path="";
					$ptz_path="";
				}
			}else{
				$media_path="";
				$ptz_path="";
			}
			
			mysql_query("update Monitors set Name='".$item['name']."',Format='".$item['format']."',Path='".$path."',Format2='".$item['format2']."',Path2='".$path2."',Stream='".$item['stream']."',Width='".$width."',Height='".$height."',SectionLength='".$sectionLength."',Factory='".$item['factory']."',Model='".$item['model']."',Resolution='".$item['resolution']."',Ip='".$item['ip']."',Passport='".$item['passport']."',Password='".$item['password']."',Mgroup='".$item['group']."',Plan_id=".$item['plan'].",Media='".$media_path."',Ptz_path='".$ptz_path."',Ptz_function='1110011' where Id=".$item['mid']);
		}
		
		$System->System_log("normal","monitor","op",$word['word_log_edited_monitor']."“".$item['name']."”");
	}
	
	//根据RPSP格式，IP和用户名密码获取RTSP地址
	function Get_path($format,$ip,$passport=false,$password=false){
		$path=str_replace("{ip}",$ip,$format);
		if($passport){
			$path=str_replace("{passport}",$passport,$path);
		}
		if($password){
			$path=str_replace("{password}",$password,$path);
		}
		return $path;
	}
	
	//获取分辨率数组
	function Get_resolution_arr(){
		return 	array("CIF","QCIF","DCIF","2CIF","4CIF","D1","D2","D3","720P","1080P","640*480","480*360","240*180");
	}
	
	//根据分辨率代码获取宽高
	function Get_width_height($resolution,$type){
		switch($resolution){
			case "CIF":
			$width=352;
			$height=288;
			break;
			
			case "QCIF":
			$width=176;
			$height=144;
			break;
			
			case "DCIF":
			$width=528;
			$height=384;
			break;
			
			case "2CIF":
			$width=704;
			$height=288;
			break;
			
			case "4CIF":
			$width=704;
			$height=576;
			break;
			
			case "D1":
			$width=720;
			$height=480;
			break;
			
			case "D2":
			$width=720;
			$height=480;
			break;
			
			case "D3":
			$width=1920;
			$height=1080;
			break;
			
			case "720P":
			$width=1280;
			$height=720;
			break;
			
			case "1080P":
			$width=1920;
			$height=1080;
			break;
			
			case "640*480":
			$width=640;
			$height=480;
			break;
			
			case "480*360":
			$width=480;
			$height=360;
			break;
			
			case "240*180":
			$width=240;
			$height=180;
			break;
			
			default:
			$width=false;
			$height=false;
			break;
		}
		
		if($type=="width"){
			return $width;
		}else{
			return $height;
		}
	}


//组	
	
	//获取组列表
	function Get_group_list(){
		global $System;
		$word=$System->Get_word();
		$running=$System->Get_state();
		
		$group_arrs=array();
		$g_arrs=Get_db_arrs("select Id,Name,Is_mount from Groups");
		foreach($g_arrs as $g_arr){
			$g=array();
			
			//是否录像
			$is_record=Get_db_one("select Function from Monitors where Function='Record' and Mgroup=".$g_arr['Id']." or Function='SameTime' and Mgroup=".$g_arr['Id']);
			
			//获得组所有的前端设备
			$m_arrs=Get_db_arrs("select Id,Name,Function,Plan_id from Monitors where Mgroup=".$g_arr['Id']);
			
			//获得计划
			$plan_id_arr=array();
			if(!empty($m_arrs)){
				foreach($m_arrs as $m){
					if($m['Plan_id']!=0){
						$plan_id_arr[]=$m['Plan_id'];
					}
				}
			}
			$plan_name="";
			$plan_id=0;
			if(count(array_unique($plan_id_arr))==1&&count($plan_id_arr)==count($m_arrs)){
				//$plan_name="[".$word['word_plan'].$plan_id_arr[0]."]";
				$plan_name=Get_db_one("select Name from Plans where Id=".$plan_id_arr[0]);
				$plan_id=$plan_id_arr[0];
			}
			
			$g['name']=$g_arr['Name'];
			if($plan_name){
				$g['name'].="[".$plan_name."]";
			}/*else{
				mysql_query("update Monitors set Plan_id=0 where Mgroup=".$g_arr['Id']);
			}*/
			
			//组操作
			$g['op']='';
			if($g_arr['Id']==1){
				$g['op']='&nbsp;';
			}else{
				$g['op'].='<a href=javascript:group_add('.$g_arr['Id'].')><img src="../images/ps01.png" title="'.$word['word_monitor_group_monitor_add'].'" /></a>&nbsp;&nbsp;';
				//if($is_record){
					//$g['op'].='<a href=javascript:alert("请先停止该组前端设备的录像！")><img src="../images/ps02.png" title="删除前端设备" /></a>&nbsp;&nbsp;';
				//}else{
					$g['op'].='<a href=javascript:group_remove('.$g_arr['Id'].')><img src="../images/ps02.png" title="'.$word['word_monitor_group_monitor_remove'].'" /></a>&nbsp;&nbsp;';
				//}
				if($is_record){
					$g['op'].='<a href=javascript:alert("'.$word['word_monitor_stop_record_please'].'")><img src="../images/ps03.png" title="'.$word['word_delete'].'" /></a>&nbsp;&nbsp;';
				}elseif($g_arr['Is_mount']==1){
					$g['op'].='<a href=javascript:alert("'.$word['word_monitor_delete_mount_please'].'")><img src="../images/ps03.png" title="'.$word['word_delete'].'" /></a>&nbsp;&nbsp;';
				}else{
					$g['op'].='<a href=javascript:group_del('.$g_arr['Id'].')><img src="../images/ps03.png" title="'.$word['word_delete'].'" /></a>&nbsp;&nbsp;';
				}
			}
			$g['op'].='<a href=javascript:group_edit('.$g_arr['Id'].',"'.$g_arr['Name'].'",'.$plan_id.')><img src="../images/ps07.png" title="'.$word['word_monitor_group_edit'].'" /></a>';
			
			//存储空间
			$g['is_mount']=$this->Get_group_state($g_arr['Id']);
			//前端设备
			$monitors=array();
			if(!empty($m_arrs)){
				foreach($m_arrs as $m){
					if($m['Plan_id']!=0){
						//$m['Name'].="[".$word['word_plan'].$m['Plan_id']."]";
						$plan_name=Get_db_one("select Name from Plans where Id=".$m['Plan_id']);
						if($plan_name){
							$m['Name'].="[".$plan_name."]";
						}else{
							mysql_query("update Monitors set Plan_id=0 where Id=".$m['Id']);
						}
					}
					
					$monitors[]=$m['Name'];
				}
			}
			if(!empty($monitors)){
				$g['monitors']=join("<br>",$monitors);
			}else{
				$g['monitors']="&nbsp;";
			}
			
			$group_arrs[]=$g;
		}
		return json_encode($group_arrs);
	}
	//获取默认组中的前端设备
	function Get_ungroup_monitor(){
		$ungroup_monitor=Get_db_arrs("select Id,Name from Monitors where Function!='Record' and Function!='SameTime' and Mgroup=1");
		return json_encode($ungroup_monitor);
	}
	//获取指定组中的前端设备
	function Get_group_monitor($gid,$is_record=false){
		if($is_record){
			$group_monitor=Get_db_arrs("select Id,Name from Monitors where Function!='Record' and Function!='SameTime' and Mgroup=".$gid);
		}else{
			$group_monitor=Get_db_arrs("select Id,Name from Monitors where Mgroup=".$gid);
		}
		
		return json_encode($group_monitor);
	}
	//获取组名称
	function Get_group_name($gid){
		$group_name=Get_db_one("select Name from Groups where Id=".$gid);
		return $group_name;
	}
	//组名称验证
	function Group_name_validate($gname){
		$group_name=Get_db_one("select Name from Groups where Name='".$gname."'");
		return $group_name;
	}
	
	//获得组状态
	function Get_group_state($gid){
		global $System;
		$word=$System->Get_word();
		
		$is_mount=Get_db_one("select Is_mount from Groups where Id=".$gid);
		
		if($is_mount==1){
			$mount_path=Get_db_one("select Mount_Path from Groups where Id=".$gid);
			$group_state=$System->Get_disk_percent($mount_path)."%";
		}else{
			$group_state=$word['word_monitor_no_space'];
		}
		return $group_state;
	}
	
	//获得组权限
	function Group_right($g){
		$user_groups=explode(",",$_SESSION['user']['Groups']);
		$groups_sql=array();
		foreach($user_groups as $gid){
			$groups_sql[]=$g."=".$gid;
		}
		return join(" or ",$groups_sql);
	}
	
	//获得录像策略
	function Get_plan(){
		$plan_list=array();
		$plan_arrs=Get_db_arrs("select Id,Name from Plans");
		foreach($plan_arrs as $plan_arr){
			$plan=array();
			$plan['id']=$plan_arr['Id'];
			$plan['name']=$plan_arr['Name'];
			$plan_list[]=$plan;
		}
		return json_encode($plan_list);
		//return json_encode(Get_db_col("select Id from Plans","Id"));
	}
	
	
	//添加组
	function Group_creat($item){
		global $System;
		$word=$System->Get_word();
		
		//插入组记录
		mysql_query("insert into Groups (Name) values ('".$item['gname']."')");
		$gid=mysql_insert_id();
		
		//录像策略插入组
		mysql_query("insert into Diskstratery (GroupId) values ('".$gid."')");
		
		//设置组挂载点
		mysql_query("update Groups set Mount_path='".EVENT_PATH.$gid."' where Id=".$gid);
		Make_file(EVENT_PATH.$gid);
	
		//给admin用户和当前用户添加一个gid
		$admin_groups=Get_db_one("select Groups from Users where Username='admin'");
		$admin_groups.=",".$gid;
		$_SESSION['user']['Groups'].=",".$gid;
		mysql_query("update Users set Groups='".$admin_groups."' where Id=1 and Username='admin'");
		mysql_query("update Users set Groups='".$_SESSION['user']['Groups']."' where Id=".$_SESSION['user']['Id']);
		
		//将前端设备组改为组ID
		if(isset($item['m_list'])){
			foreach($item['m_list'] as $mid){
				mysql_query("update Monitors set Mgroup=".$gid.",Plan_id=".$item['add_plan']." where Id=".$mid);
			}
		}
		
		$System->System_log("normal","monitor","op",$word['word_log_created_group']."“".$item['gname']."”");
	}
	//组内添加前端设备
	function Group_add($item){
		foreach($item['ungroupm'] as $mid){
			mysql_query("update Monitors set Mgroup=".$item['add_gid']." where Id=".$mid);
		}
	}
	//组内删除前端设备
	function Group_remove($item){
		foreach($item['groupm'] as $mid){
			mysql_query("update Monitors set Mgroup=1 where Id=".$mid);
		}
	}
	//修改组
	function Group_edit($item){
		if($item['edit_gid']!=1){
			mysql_query("update Groups set Name='".$item['gname_fa']."' where Id=".$item['edit_gid']);
		}
		
		mysql_query("update Monitors set Plan_id=".$item['edit_plan']." where Mgroup=".$item['edit_gid']);
	}
	
	//删除组
	function Group_del($gid){
		$gname=Get_db_one("select Name from Groups where Id=".$gid);
		//删除组录像
		$mount_path=Get_db_one("select Mount_Path from Groups where Id=".$gid);
		if(file_exists($mount_path)){
			File_top($mount_path);
			Shell_cmd("rsync --delete-before -a -H -v --progress --stats ".EMPTY_PATH." ".$mount_path);
			Shell_cmd("rm -rf ".$mount_path);
		}
		
		//删除用户组
		$user_groups=mysql_query("select Id,Groups from Users");
		while($user_group=mysql_fetch_array($user_groups,MYSQL_ASSOC)){
			$groups=false;
			$groups=explode(",",$user_group['Groups']);
			$groups_fa=array();
			foreach($groups as $v){
				if($v!=$gid){
					$groups_fa[]=$v;
				}
			}
			$groups=false;
			$groups=join(",",$groups_fa);
			mysql_query("update Users set Groups='$groups' where Id=".$user_group['Id']);
		}
		
		//删除数据库
		mysql_query("update Monitors set Mgroup=1 where Mgroup=".$gid);
		mysql_query("delete from Events where GroupId=".$gid);
		mysql_query("delete from Diskstratery where GroupId=".$gid);
		mysql_query("delete from Groups where Id=".$gid);
		
		global $System;
		$word=$System->Get_word();
		$System->System_log("normal","monitor","op",$word['word_log_deleted_group']."“".$gname."”");
	}
	
	
//录像列表	
		
	
	//获得录像列表
	function Get_event_list($item){
		global $System;
		$word=$System->Get_word();
		
		if($item['mid']){
			$where=" where MonitorId=".$item['mid'];
		}else{
			$where=" where MonitorId!=0";
		}
		
		/*if($item['start_time']&&!$item['end_time']){
			$where.=" and StartTime>'".$item['start_time']."'";
		}elseif(!$item['start_time']&&$item['end_time']){
			$where.=" and EndTime<'".$item['end_time']."'";
		}elseif($item['start_time']&&$item['end_time']){
			$where.=" and StartTime>'".$item['start_time']."' and EndTime<'".$item['end_time']."'";
		}*/
		
		$where.=" order by ".$item['order']." ".$item['by']." limit ".($item['page']-1)*$item['pagesize'].",".$item['pagesize'];
		
		if(isset($item['get'])){
			return Get_db_one("select count(*) from Events".$where);		   
		}else{
			$event_list=array();
			
			$event_arrs=Get_db_arrs("select Id,Name,StartTime,EndTime,State from Events".$where);
			if($event_arrs){
				foreach($event_arrs as $event){
					$e=array();
					
					$e['Id']=$event['Id'];
					if($event['State']==0||!file_exists($event['Name'])){
						$e['Name']=basename($event['Name']);
					}else{
						if($_SESSION['user']['Event_download_right']==1){
							$e['Name']="<a href=javascript:event_play(".$item['mid'].",'".$item['mname']."','play_one',".$event['Id'].")>".basename($event['Name'])."</a>&nbsp;<a href=javascript:event_download('".$event['Name']."') style=color:green>".$word['word_download']."</a>";
						}else{
							$e['Name']="<a href=javascript:event_play(".$item['mid'].",'".$item['mname']."','play_one',".$event['Id'].")>".basename($event['Name'])."</a>";
						}
					}
					$e['StartTime']=$event['StartTime'];
					$e['EndTime']=$event['EndTime'];
					if($event['State']==0){
						$e['State']="<font color=orange>".$word['word_monitor_file_error']."</font>";
					}elseif(!file_exists($event['Name'])){
						$e['State']="<font color=red>".$word['word_monitor_file_lost']."</font>";
					}else{
						$e['State']="<font color=green>".$word['word_monitor_file_normal']."</font>";
					}
					if($event['State']==0||!file_exists($event['Name'])){
						$e['check']="<input type=checkbox disabled />";
					}else{
						$e['check']="<input type=checkbox name='markEid[]' id='edit_arr' value=".$event['Id']." />";
					}
					$event_list[]=$e;
				}
			}
			
			return json_encode($event_list);
		}
	}
	
	//获得操作员界面录像列表
	function Get_operator_event_list($item){
		global $System;
		$word=$System->Get_word();
		//$sql="select Name from Events where MonitorId in (".$item['mid_str'].")";
		
		$mid_arr=explode(",",$item['mid_str']);
		$file_arr=array();
		$top=0;
		if(preg_match('/MSIE ([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT'])){
			$h=20.48;
		}else{
			$h=18;
		}
		
		foreach($mid_arr as $k=>$mid){
			$sql="select Name from Events where MonitorId=".$mid;
		
			if($item['start_time']&&!$item['end_time']){
				$mrl_arrs_1=Get_db_arrs($sql." and StartTime<'".$item['start_time']."' and EndTime>'".$item['start_time']."' order by Id asc");
				$mrl_arrs_2=Get_db_arrs($sql." and StartTime>'".$item['start_time']."' order by Id asc");
			}elseif(!$item['start_time']&&$item['end_time']){
				$mrl_arrs_2=Get_db_arrs($sql." and EndTime<'".$item['end_time']."' order by Id asc");
				$mrl_arrs_3=Get_db_arrs($sql." and StartTime<'".$item['end_time']."' and EndTime>'".$item['end_time']."' order by Id asc");
			}elseif($item['start_time']&&$item['end_time']){
				$mrl_arrs_1=Get_db_arrs($sql." and StartTime<'".$item['start_time']."' and EndTime>'".$item['start_time']."' order by Id asc");
				$mrl_arrs_2=Get_db_arrs($sql." and StartTime>'".$item['start_time']."' and EndTime<'".$item['end_time']."' order by Id asc");
				$mrl_arrs_3=Get_db_arrs($sql." and StartTime<'".$item['end_time']."' and EndTime>'".$item['end_time']."' order by Id asc");
			}else{
				$mrl_arrs_2=Get_db_arrs($sql." order by Id asc");
			}
			$mrl_arr=array();
			if($mrl_arrs_1){
				foreach($mrl_arrs_1 as $mrl){
					array_push($mrl_arr,$mrl['Name']);
				}
			}
			if($mrl_arrs_2){
				foreach($mrl_arrs_2 as $mrl){
					array_push($mrl_arr,$mrl['Name']);
				}
			}
			if($mrl_arrs_3){
				foreach($mrl_arrs_3 as $mrl){
					array_push($mrl_arr,$mrl['Name']);
				}
			}
			$mevent=array();
			
			$mname=Get_db_one("select Name from Monitors where Id=".$mid);
			if($mname){
				$mevent['mname']=$mname."<input type='hidden' id='scrolltop_".$k."' value='".$top."' />";
				$top+=$h;
			}
			if($mrl_arr){
				$file=array();
				foreach($mrl_arr as $play_key=>$mrl){
					$file_str='<a href="#" onclick="main_iframe.event_play('.$k.','.$play_key.')" id="main_event_name_'.$k.'_'.$play_key.'">'.basename($mrl).'</a>';
					if($_SESSION['user']['Event_download_right']==1){
						$file_str.='<a href=javascript:main_iframe.event_download("'.str_replace(ROOT_PATH,ROOT_IP."/",$mrl).'") style="color:green">'.$word['word_download'].'</a>';
					}
					$top+=$h;
					array_push($file,$file_str);
				}
				$mevent['file']=$file;
			}
			if($mevent['mname']){
				array_push($file_arr,$mevent);
			}
		}
		
		return json_encode($file_arr);
	}
	
	//录像删除
	function Event_del($item){
		$mname=Get_db_one("select Name from Monitors where Id=".$item['mid']);
		global $System;
		$word=$System->Get_word();
		
		if(isset($item['op'])){
			switch($item['op']){
				//删除该前端设备所有的录像
				case "del_all":
				  $gid=Get_db_one("select Mgroup from Monitors where Id=".$item['mid']);
				  $delete_path=EVENT_PATH.$gid."/".$item['mid'];
				  File_top($delete_path);
				  Shell_cmd("rsync --delete-before -a -H -v --progress --stats ".EMPTY_PATH." ".$delete_path);
				  Shell_cmd("rm -rf ".$delete_path);
				  mysql_query("delete from Events where MonitorId=".$item['mid']);
				  
				  $System->System_log("normal","monitor","op",$word['word_log_deleted_monitor']."“".$mname."”".$word['word_log_all_record']);
				break;
				
				//删除该前端设备异常的录像
				case "del_error":
				  $delete_arrs=Get_db_arrs("select Name from Events where MonitorId=".$item['mid']." and State=0");
				  foreach($delete_arrs as $delete){
					  File_top($delete['Name']);
					  Shell_cmd("rm -rf ".$delete['Name']);
				  }
				  mysql_query("delete from Events where MonitorId=".$item['mid']." and State=0");
				  
				  $System->System_log("normal","monitor","op",$word['word_log_deleted_monitor']."“".$mname."”".$word['word_log_errored_record']);
				break;
				
				//删除该前端设备丢失的录像
				case "del_lost":
				  $delete_arrs=Get_db_arrs("select Id,Name from Events where MonitorId=".$item['mid']);
				  foreach($delete_arrs as $delete){
					  if(!file_exists($delete['Name'])){
						  mysql_query("delete from Events where Id=".$delete['Id']);
					  }
				  }
				  
				  $System->System_log("normal","monitor","op",$word['word_log_deleted_monitor']."“".$mname."”".$word['word_log_losted_record']);
				break;
			}
		}else{
			//删除标记的录像
			foreach($item['markEid'] as $eid){
				$delete_arr=Get_db_arr("select Id,Name from Events where Id=".$eid);
				File_top($delete_arr['Name']);
				Shell_cmd("rm -rf ".$delete_arr['Name']);
				mysql_query("delete from Events where Id=".$delete_arr['Id']);
			}
			
			$System->System_log("normal","monitor","op",$word['word_log_deleted_monitor']."“".$mname."”".$word['word_log_marked_record']);
		}
	}
	
	
	//录像下载
	function Event_download($path,$mid=false){
		global $System;
		$word=$System->Get_word();
		
		if($path=="all"){
			//文本文件
			$mname=Get_db_one("select Name from Monitors where Id=".$mid);
			$events_path=Get_db_arrs("select Name from Events where MonitorId=".$mid);
			
			$filename=iconv("UTF-8","GB2312","“".$mname."”录像文件路径".date("Y-m-d-H-i-s",time()).".txt");
			
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			
			echo "建议使用迅雷7下载。迅雷7下载方法：请复制本文件所有内容，粘贴到“新建”里\r\n\r\n";			
			foreach($events_path as $v){
				echo str_replace("/var/www/zm",ROOT_IP,$v['Name'])."\r\n";
			}
			
			$System->System_log("normal","monitor","op",$word['word_log_downloaded']."“".$mname."”".$word['word_log_all_record']);
		}else{
			$filesize=filesize($path);
			
			//echo $filesize;exit;
			
			if($filesize<1073741824){
				header( "Pragma: public" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Cache-Control: private", false ); 
				header( "Content-Description: File Transfer" );
				header( 'Content-disposition: attachment; filename="'.basename($path).'"' ); 
				header( "Content-Transfer-Encoding: binary" );
				header( "Content-Type: application/force-download" );
				header( "Content-Length: ".$filesize ); 
				readfile( $path );
			}else{
				$filename=iconv("UTF-8","GB2312","录像文件路径".basename($path).".txt");
				header('Content-type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				echo "文件太大，超过1GB，建议使用迅雷7下载。迅雷7下载方法：请复制本文件所有内容，粘贴到“新建”里\r\n\r\n";
				echo str_replace("/var/www/zm",ROOT_IP,$path)."\r\n";
			}
			
			$System->System_log("normal","monitor","op",$word['word_log_downloaded_event']."“".basename($path)."”");
		}
	}
	
	
	
//视频播放


	//视频iframe页面（包括实时和录像），因为在ie下代表vlc插件的的<object>标签无法通过js预置和无法通过弹出层显示这两个原因，所以用iframe嵌入了一个php预置标签的混合页面，以达到ie和其他浏览器的兼容性
	function Monitor_iframe($item){
		global $System;
		$word=$System->Get_word();
		
		//通用参数隐藏域
		?>
        <input type="hidden" value="" id="k_select" /><!--被选中的-->
        
        <input type="hidden" value="<?php echo $item['type']; ?>" id="type" /><!--是实时还是录像-->
        <input type="hidden" value="<?php echo $item['muti']; ?>" id="muti" /><!--路数-->
        <input type="hidden" value="<?php echo $item['mid_str']; ?>" id="mid_str" /><!--前端设备ID串-->
        <input type="hidden" value="<?php echo $item['pop_width']; ?>" id="pop_width" /><!--单路时弹出窗口大小-->
        <input type="hidden" value="<?php echo $item['vlc_width']; ?>" id="vlc_width" /><!--单路时VLC大小-->
        
        <input type="hidden" value="<?php if($_COOKIE['width_fix']){echo $_COOKIE['width_fix'];}else{echo $item['width_fix'];} ?>" id="width_fix" /><!--宽度修正-->
        <input type="hidden" value="<?php if($_COOKIE['height_fix']){echo $_COOKIE['height_fix'];}else{echo $item['height_fix'];} ?>" id="height_fix" /><!--高度修正-->
        <input type="hidden" value="<?php echo $item['hide_bar']; ?>" id="hide_bar" /><!--隐藏状态栏-->
        
        <input type="hidden" value="<?php echo $item['operator']; ?>" id="operator" /><!--操作员界面判定（值为操作员界面iframe高度）-->
        <?php 
		
		//通用参数处理
		$muti_muti=$item['muti']*$item['muti'];
		$mid_arr=explode(",",$item['mid_str']);
		
		//php部分实时录像判断
		if($item['type']=="watch"){
			//获取实时所需数据库数据
			$title=$word['word_monitor_watch'];
			
			$diy_arr=Get_db_arr("select Load_time,Rtsp_caching from Diy");
			//$diy_arr=array("Load_time"=>3000,"Rtsp_caching"=>2000);
			?>
            <!--已运行：<span id="keep_time"></span>-->
            <input type="hidden" value="0" id="k_load" /><!--下一个要加载的屏幕-->
            <input type="hidden" value="0" id="over_load" /><!--加载超时-->
            <input type="hidden" value="<?php echo $diy_arr['Load_time']; ?>" id="load_time" /><!--单个屏幕加载时间-->
            <input type="hidden" value="<?php echo $diy_arr['Rtsp_caching']; ?>" id="rtsp_caching" /><!--rtsp缓存时间-->
            <?php
			
			//判断轮巡，轮巡时间隐藏域
			if($muti_muti<count($mid_arr)){
				?>
				<input type="hidden" value="<?php echo $item['turn_time']; ?>" id="turn_time" />
				<?php
				$is_turn=true;
			}else{
				$is_turn=false;
			}
		}else{
			
			//获取录像所需数据库数据
			$title=$word['word_monitor_event'];
			foreach($mid_arr as $mid){
				$m_arr=Get_db_arr("select Name from Monitors where Id=".$mid);
				
				$mrl_arr=array();
				if($item['eid_str']){
					//如果是选择某一文件或多个文件播放
					$eid_arr=explode(",",$item['eid_str']);
					foreach($eid_arr as $eid){
						$mrl_arr[]=str_replace(ROOT_PATH,ROOT_IP."/",Get_db_one("select Name from Events where Id=".$eid." order by Id asc"));
					}
				}else{
					//如果是选择时间段或所有文件
					if($item['start_time']&&!$item['end_time']){
						$mrl_arrs_1=Get_db_arrs("select Name from Events where MonitorId=".$mid." and StartTime<'".$item['start_time']."' and EndTime>'".$item['start_time']."' order by Id asc");
						$mrl_arrs_2=Get_db_arrs("select Name from Events where MonitorId=".$mid." and StartTime>'".$item['start_time']."' order by Id asc");
					}elseif(!$item['start_time']&&$item['end_time']){
						$mrl_arrs_2=Get_db_arrs("select Name from Events where MonitorId=".$mid." and EndTime<'".$item['end_time']."' order by Id asc");
						$mrl_arrs_3=Get_db_arrs("select Name from Events where MonitorId=".$mid." and StartTime<'".$item['end_time']."' and EndTime>'".$item['end_time']."' order by Id asc");
					}elseif($item['start_time']&&$item['end_time']){
						$mrl_arrs_1=Get_db_arrs("select Name from Events where MonitorId=".$mid." and StartTime<'".$item['start_time']."' and EndTime>'".$item['start_time']."' order by Id asc");
						$mrl_arrs_2=Get_db_arrs("select Name from Events where MonitorId=".$mid." and StartTime>'".$item['start_time']."' and EndTime<'".$item['end_time']."' order by Id asc");
						$mrl_arrs_3=Get_db_arrs("select Name from Events where MonitorId=".$mid." and StartTime<'".$item['end_time']."' and EndTime>'".$item['end_time']."' order by Id asc");
					}else{
						$mrl_arrs_2=Get_db_arrs("select Name from Events where MonitorId=".$mid." order by Id asc");
					}
					$mrl_arr=array();
					if($mrl_arrs_1){
						foreach($mrl_arrs_1 as $mrl){
							array_push($mrl_arr,str_replace(ROOT_PATH,ROOT_IP."/",$mrl['Name']));
						}
					}
					if($mrl_arrs_2){
						foreach($mrl_arrs_2 as $mrl){
							array_push($mrl_arr,str_replace(ROOT_PATH,ROOT_IP."/",$mrl['Name']));
						}
					}
					if($mrl_arrs_3){
						foreach($mrl_arrs_3 as $mrl){
							array_push($mrl_arr,str_replace(ROOT_PATH,ROOT_IP."/",$mrl['Name']));
						}
					}
				}
				//将一个前端设备的所有mrl连接成一个字符串存入一个隐藏域
				$mrl_str=join(",",$mrl_arr);
				?>
				<input type="hidden" id="event_num_<?php echo $mid; ?>" value="<?php echo count($mrl_arr); ?>" />
				<input type="hidden" id="play_key_<?php echo $mid; ?>" value="0" />
				<input type="hidden" id="mrl_<?php echo $mid; ?>" value="<?php echo $mrl_str; ?>" size="600" />
				<input type="hidden" id="mname_<?php echo $mid; ?>" value="<?php echo $m_arr['Name']; ?>" />
				<?php
			}
		}
		?>
        
        <!--html部分-->
        <link type="text/css" rel="stylesheet" href="css/jquery-ui-1.8.17.custom.css" />
        <script type="text/javascript" src="js/jquery.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.8.17.custom.min.js"></script>
        <style>
		body{
			font-size:12px;
			margin:0;
		}
		a{
			color:#7F7FB2;
			text-decoration:none;
		}
		a:hover{
			color:#00B6EF;
			text-decoration:underline;
		}
		table{
			font-size:12px;
			/*text-align:center;*/
		}
		
		.state_bar{
			text-align:center; 
			background: url(../images/ui-bg_glass_40_111111_1x400.png) repeat-x scroll 50% 50% transparent; 
			color:#FFF; 
			margin-bottom:2px; 
			padding:3px
		}
		/*.screen{
			width:800px; 
			height:450px;
		}*/
		.right{
			width:200px; 
			height:550px; 
			border:1px solid #CCC; 
			overflow-y:scroll; 
			float:right;
		}
		.right_header{
			width:100%; 
			height:20px; 
			color:#FFF;
			padding-top:5px;
			text-align:center; 
			/*border:1px solid #000;*/
			background: url(../images/ui-bg_glass_40_111111_1x400.png) repeat-x scroll 50% 50% transparent;
		}
		.console{
			/*width:800px; */
			height:65px; 
			margin-top:30px;
			*margin-top:5px;
			border:1px solid #ccc;
		}
		#event_control{
			margin-top:5px;
			text-align:center;
			vertical-align:middle;
		}
		.info{
			font-size:16px;
			font-family:"黑体";
			font-weight:bold;
		}
		</style>
        
        <!--标题-->
        <title><?php echo $muti_muti.$word['word_monitor_way'].$title; ?></title>
        
        <!--播放列表或云台控制-->
        <?php 
		if($item['muti']==1){
		?>
			<div class="right">
			  <?php 
              if($item['type']=='watch'){
				  ?>
                  <!--云台控制-->
                  <div class="right_header"><?php echo $word['word_monitor_ptz']; ?></div>	
                  <div class="right_main">	
                  <?php
				  if($_SESSION['user']['Ptz_right']==1){
				  ?>
					  <div id="ptz">
                      <!--焦距视角-->
					  <table border="0" align="center" style="margin-top:10px; width:80%"> 
						<tr class="ptz_auto">
                          <td><?php echo $word['word_monitor_ptz_auto']; ?></td>
						  <td><button id="ptz_auto_on" style="font-size:10px" onClick="ptz('auto','on')"><?php echo $word['word_on']; ?></button></td>
                          <td><button id="ptz_auto_off" style="font-size:10px" onClick="ptz('auto','off')"><?php echo $word['word_off']; ?></button></td>
						</tr>
						<tr class="ptz_zoom">
						  <td><?php echo $word['word_monitor_ptz_zoom']; ?></td>
                          <td>
							<button id="ptz_zoom_out" style="font-size:10px" onMouseDown="ptz('zoom','out')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_zoom_out']; ?></button>
						  </td>
						  <td>
							<button id="ptz_zoom_in" style="font-size:10px" onMouseDown="ptz('zoom','in')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_zoom_in']; ?></button>
                          </td>
						</tr>
					  </table>
					  
					  <!--方向-->
                      <table border="0" align="center" style="margin-top:10px;">
                        <tr>
                          <td><button id="ptz_leftup" onMouseDown="ptz('direct','leftup')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_leftup']; ?></button></td>
                          <td><button id="ptz_up" onMouseDown="ptz('direct','up')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_up']; ?></button></td>
                          <td><button id="ptz_rightup" onMouseDown="ptz('direct','rightup')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_rightup']; ?></button></td>
                        </tr>
                        <tr>
                          <td><button id="ptz_left" onMouseDown="ptz('direct','left')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_left']; ?></button></td>
                          <td><button id="ptz_home" onClick="ptz('home','goto')"><?php echo $word['word_monitor_ptz_home']; ?></button></td>
                          <td><button id="ptz_right" onMouseDown="ptz('direct','right')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_right']; ?></button></td>
                        </tr>
                        <tr>
                          <td><button id="ptz_leftdown" onMouseDown="ptz('direct','leftdown')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_leftdown']; ?></button></td>
                          <td><button id="ptz_down" onMouseDown="ptz('direct','down')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_down']; ?></button></td>
                          <td><button id="ptz_rightdown" onMouseDown="ptz('direct','rightdown')" onMouseUp="ptz('direct','stop')"><?php echo $word['word_monitor_ptz_rightdown']; ?></button></td>
                        </tr>
                      </table>
                        
                      <!--速度预设点-->  
					  <table border="0" align="center" style="margin-top:10px; width:90%">	
						<tr>
						  <td><?php echo $word['word_speed']; ?></td>
						  <td>
							<select id="ptz_speed" onChange="ptz('speed',this.value)">
							  <option value="1" id="ptz_speed_val_1">1x</option>
							  <option value="2" id="ptz_speed_val_2">2x</option>
							  <option value="3" id="ptz_speed_val_3">3x</option>
							  <option value="4" id="ptz_speed_val_4">4x</option>
							  <option value="5" id="ptz_speed_val_5">5x</option>
							  <option value="6" id="ptz_speed_val_6">6x</option>
							  <option value="7" id="ptz_speed_val_7">7x</option>
							  <option value="8" id="ptz_speed_val_8">8x</option>
							  <option value="9" id="ptz_speed_val_9">9x</option>
							  <option value="10" id="ptz_speed_val_10">10x</option>
							</select>
						  </td>
                          <td><button id="ptz_home_set" style="font-size:10px" onClick="ptz('home','set')"><?php echo $word['word_monitor_ptz_home_set']; ?></button></td>
						</tr>
						<tr class="ptz_preset">
						  <td><?php echo $word['word_monitor_ptz_preset']; ?></td>
						  <td><select id="ptz_preset" onChange="ptz('preset','goto',this.value)"></select></td>
                          <td><button id="ptz_preset_set" style="font-size:10px" onClick="ptz('preset','pop')"><?php echo $word['word_monitor_ptz_preset_set']; ?></button></td>
						</tr>
					  </table>
                      
                      <!--云台操作日志-->
                      <table border="0" align="center" style="margin-top:10px; width:90%">
                        <tr>
                          <td><?php echo $word['word_monitor_ptz_log'] ?></td>
                          <td><a href="#" onclick="$('#ptz_log').empty()"><?php echo $word['word_clear'] ?></a></td>
                        </tr>
						<tr>
						  <td colspan="2"><div id="ptz_log" style="overflow-y:scroll; width:100%; height:120px; border:1px solid #ccc; text-align:left"></div></td>
						</tr>
                      </table>
                      </div>
                      <div id="ptz_none" align="center"><?php echo $word['word_none']; ?></div>
				  <?php 
				  }else{
					  echo $word['word_no_right'];
				  }
			  ?>
                  </div>	 
			  <?php	
              }else{
              ?>
                  <!--录像列表-->
              	  <div class="right_header"><?php echo $word['word_monitor_event_list']; ?></div>	
                  <div class="right_main">
                    <table border="0" width="100%">
                      <?php
                      if(count($mrl_arr)==0){
                          ?>
                          <tr><td><?php echo $word['word_none'] ?></td></tr>
                          <?php
                      }else{
                          foreach($mrl_arr as $play_key=>$mrl){
                              ?>
                              <tr onmousemove='move_color(this)' onmouseout='out_color(this)'>
                                <td>
                                  <a href="javascript:event_play(0,<?php echo $play_key; ?>)" id="event_name_<?php echo $play_key; ?>" class="event_name"><?php echo basename($mrl); ?></a>
                                  <?php 
                                  if($_SESSION['user']['Event_download_right']==1){
                                  ?>
                                      <a href="javascript:event_download('<?php echo $mrl; ?>')" style="color:green"><?php echo $word['word_download'] ?></a>
                                  <?php 
                                  }
                                  ?>
                                </td>
                              </tr>
                              <?php
                          }
                      }
                      ?>
                    </table>
                  </div>
              <?php 
              }
              ?>
			</div>
		<?php 
		}
		?>
        
        <!--屏幕-->
        <div id="screen" class="screen">
          <table border="0" cellpadding="0" cellspacing="1">
          <?php
		  //根据路数循环出多屏或单屏
          for($i=0;$i<$item['muti'];$i++){
              echo "<tr>";
              for($j=0;$j<$item['muti'];$j++){
				  $k=$i*$item['muti']+$j;
				  
				  //当有前端设备时显示状态栏和VLC，否则显示空单元格
				  if($k<count($mid_arr)&&$mid_arr[0]!=0){
					  ?>
					  <!--单元格-->
					  <td id="m_td_<?php echo $k; ?>" class="m_td" onClick="<?php if($item['muti']>1){?>td_select(<?php echo $k; ?>)<?php } ?>">
                      
						<!--状态栏-->
						<div class="state_bar" id="state_bar_<?php echo $k; ?>" <?php if($item['hide_bar']&&$item['muti']>1){echo 'style="display:none"';} ?>>
						  <?php
						  //if($item['muti']==1){
							  ?>
							  <span id="monitor_name_<?php echo $k; ?>"></span>&nbsp;<!--前端设备名称-->
							  <?php
						  /*}else{
						  ?>
							  <a href="javascript:;" onClick="single_window(<?php echo $k; ?>)"><span id="monitor_name_<?php echo $k; ?>"></span></a>&nbsp;<!--前端设备名称-->
						  <?php
						  }*/
						  if($item['type']=='watch'){
							  ?>
							  <span id="monitor_state_<?php echo $k; ?>"></span><!--实时时的状态-->
                              <input type="hidden" id="passport_<?php echo $k; ?>" value="" />
                              <input type="hidden" id="password_<?php echo $k; ?>" value="" />
                              <input type="hidden" id="media_<?php echo $k; ?>" value="" />
                              <input type="hidden" id="ptz_path_<?php echo $k; ?>" value="" />
                              <input type="hidden" id="ptz_function_<?php echo $k; ?>" value="" />
                              <input type="hidden" id="ptz_speed_<?php echo $k; ?>" value="" />
                              <input type="hidden" id="ptz_preset_<?php echo $k; ?>" value="" />
                              <?php /*?><textarea id="vlc_input_state_<?php echo $k; ?>"></textarea><?php */?>
							  <?php
						  }else{
							  ?>
                              <!--播放完毕-->
							  <span id="play_off_<?php echo $k; ?>" style="display:none"><?php echo $word['word_monitor_play_off']; ?></span>
							  
							  <!--倍率，事件时间，文件时间播放完隐藏掉-->
							  <span class="event_state_<?php echo $k; ?>">
								  <!--文件名称：--><span id="file_name_<?php echo $k; ?>" style="display:none"></span><!--目前不显示但需要取值，作为隐藏域-->
                                  
                                  <!--事件时间-->
								  <?php if($item['muti']==1){echo $word['word_monitor_event_time']; ?>&nbsp;<span id="event_time_<?php echo $k; ?>"></span><?php } ?>
                                  <!--文件时间-->
								  <?php
								  if($item['muti']==1){
								  ?>
									  <?php echo $word['word_monitor_file_time']; ?>&nbsp;<span id="file_time_<?php echo $k; ?>"></span>
								  <?php
								  }
								  ?>
							  </span>
							  
							  <!--文件序号/文件个数-->
							  <?php
							  if($item['muti']!=1){
							  ?>
								  <span id="file_num_<?php echo $k; ?>"></span>/<span id="file_all_<?php echo $k; ?>"></span>
							  <?php
							  }
							  ?>
                              <!--倍率-->
                              <span class="event_state_<?php echo $k; ?>"><?php if($item['muti']==1){echo $word['word_monitor_ratio'];} ?>&nbsp;<span id="play_rate_<?php echo $k; ?>"></span></span>
						  <?php
						  }
						  ?>
						</div>
						
						<!--vlc屏幕-->
						<div><?php echo $this->Out_vlc($k);//vlc标签 ?></div>
					  </td>
				  <?php
				  }else{
				  ?>
                  	  <!--未选择显示空的单元格-->
                      <td id="m_td_<?php echo $k; ?>" class="m_td" style="background:#ccc;">&nbsp;</td>			  
				  <?php  
				  }
              }
              echo "</tr>";
          }
          ?>
          </table>
        </div>
        
        <!--控制台-->
        <?php 
		if($item['muti']==1){
		?>
            <div class="console">
			  <?php 
			  //进度条
              if($item['type']=='event'){
              ?>
                  <div id="event_slider"></div>
			  <?php 
              }
			  //录像控制台
              ?>
              <div id="event_control">
                <table border="0" width="100%">
                  <tr>
					<?php 
                    if($item['type']=='watch'){
						?>
                        <td width="30%">&nbsp;</td>
                        <?php
						if($_SESSION['user']['Monitor_control_right']==1){
							?>
                            <td><button id="record_start" onClick="if(window.opener){var obj=window.opener.opener}else{var obj=parent};if(obj.monitor_op(0,<?php echo $item['mid_str']; ?>,'',1)){alert('<?php echo $word['word_monitor_has_start']; ?>');window.location.reload()}"><?php echo $word['word_monitor_record_start'] ?></button></td>
                            <td><button id="record_stop" onClick="if(window.opener){var obj=window.opener.opener}else{var obj=parent};if(obj.monitor_op(1,<?php echo $item['mid_str']; ?>,'',1)){alert('<?php echo $word['word_monitor_has_stop']; ?>');window.location.reload()}"><?php echo $word['word_monitor_record_stop'] ?></button></td>
							<?php
						}else{
							echo "&nbsp;";
						}
						?>
                        <td><button id="full"><?php echo $word['word_monitor_event_full'] ?></button></td>
                        <td><button id="refresh" onClick="window.location.reload()"><?php echo $word['word_refresh'] ?></button></td>
                        <td>
						  <?php
                          //判断轮巡
                          if(!$is_turn&&$_SESSION['user']['Watch_right']==1&&$_SESSION['user']['Event_right']==1){
							  ?>
                              <button id="translate" onClick="translate('<?php echo $word['word_monitor_event']; ?> - <?php echo $m_arr['Name']; ?>','index.php?do=ajax&view=monitor_list&op=monitor_iframe&type=event&muti=1&mid_str=<?php echo $item['mid_str']; ?>&vlc_width=<?php echo $item['vlc_width']; ?>')"><?php echo $word['word_monitor_translate_event']; ?></button>
							  <?php
                          }else{
							  echo "&nbsp;";
						  }
                          ?>
                        </td>
                        <td width="30%">&nbsp;</td>
					<?php 
                    }else{
                    ?>
                        <td width="10%">&nbsp;</td>
                        
                        <td width="40px"><button id="stop"><?php echo $word['word_monitor_event_stop'] ?></button></td>
                        <td width="40px"><button id="prev"><?php echo $word['word_monitor_event_prev'] ?></button></td>
                        <td width="40px"><button id="slow"><?php echo $word['word_monitor_event_slow'] ?></button></td>
                        <td width="45px"><button id="play" style="font-size:18px"><?php echo $word['word_monitor_event_play'] ?></button></td>
                        <td width="40px"><button id="fast"><?php echo $word['word_monitor_event_fast'] ?></button></td>
                        <td width="40px"><button id="next"><?php echo $word['word_monitor_event_next'] ?></button></td>
                        <td width="40px"><button id="full"><?php echo $word['word_monitor_event_full'] ?></button></td>
                        
                        <td width="2%">&nbsp;</td>
                        <td width="5%" style="text-align:right"><button id="vol" style="font-size:10px"><?php echo $word['word_monitor_event_mute'] ?></button></td>
                        <td width="3%" style="text-align:right">-</td>
                        <td width="14%"><div id="vol_slider" style="width:100px;"></div></td>
                        <td width="3%" style="text-align:left">+</td>
                        <td width="3%">&nbsp;</td>
                        
                        <td><button id="refresh" onClick="window.location.reload()"><?php echo $word['word_refresh'] ?></button></td>
                        <td>
                          <button id="translate" onClick="translate('<?php echo $word['word_monitor_watch']; ?> - <?php echo $m_arr['Name']; ?>','index.php?do=ajax&view=monitor_list&op=monitor_iframe&type=watch&muti=1&mid_str=<?php echo $item['mid_str']; ?>&vlc_width=<?php echo $item['vlc_width']; ?>')"><?php echo $word['word_monitor_translate_watch']; ?></button>
                        </td>
					<?php 
                    }
                    ?>
                  </tr>
                </table>
              </div>
            </div>
		<?php 
		}
		?>
        
        <script type="text/javascript">
		//获取隐藏域参数
		var type=$("#type").val();
		var muti=parseInt($("#muti").val());
		var mid_str=$("#mid_str").val();
		
		//处理隐藏域参数
		var muti_muti=muti*muti;
		var mid_arr=new Array();
		mid_arr=mid_str.split(",");
		
		//初始化屏幕大小
		size_init();
		
		
		//用于服务器判断是否实时中
		if(type=="watch"){
			var begin_time=new Date().getTime();
			connectting();
			window.setInterval(connectting,1000);
		}
		function connectting(){
			$.ajax({url:"index.php?do=ajax&view=monitor_list&op=connectting&mid_str="+mid_str,async:false});
			var keep_time=formatTime(new Date().getTime()-begin_time);
			//$("#keep_time").html(keep_time);
		}
		
        window.onbeforeunload=function(){
			//退出时停止vlc内任何播放，以保证稳定释放资源
            for(var k=0;k<muti_muti;k++){
				var vlc=document.getElementById('vlc_'+k);
				if(vlc){
					vlc.playlist.stop();
				}
            }
			//多路和操作员实时退出动态关闭连接
			if(mid_str!=0){
				var backinfo=$.ajax({url:"index.php?do=ajax&view=monitor_list&op=watch_connect_stop&mid_str="+mid_str,async:false});
				if(backinfo.responseText){-
					alert(backinfo.responseText);
				}
			}
        }
		
		//初始化按钮
		$(document).ready(function(){
			//定义按钮
			$("button").button();
			
			//播放
			$("#play").button({
				text: false,
				icons: {
					primary: "ui-icon-pause"
				}
			});
			
			//全屏
			$("#full").button({
				text: false,
				icons: {
					primary: "ui-icon-arrow-4-diag"
				}
			});
								   
			//刷新按钮
			$("#refresh").button({
				text: false,
				icons: {
					primary: "ui-icon ui-icon-arrowrefresh-1-s"
				}
			});
			
			//切换按钮
			$("#translate").button({
				text: false,
				icons: {
					primary: "ui-icon ui-icon-newwin"
				}
			});
			
			//实时和录像js判断
			switch(type){
				//实时
				case "watch":
					if(muti==1){
						var vlc=document.getElementById('vlc_0');
						
						$("#ptz_auto_on").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-check"
							}
						});
						$("#ptz_auto_off").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-close"
							}
						});
						
						//视角
						$("#ptz_zoom_in").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-minus"
							}
						});
						$("#ptz_zoom_out").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-plus"
							}
						});
						
						//云台控制左上
						$("#ptz_leftup").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-arrowthick-1-nw"
							}
						});
						
						//云台控制上
						$("#ptz_up").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-arrowthick-1-n"
							}
						});
						
						//云台控制右上
						$("#ptz_rightup").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-arrowthick-1-ne"
							}
						});
						
						//云台控制左
						$("#ptz_left").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-arrowthick-1-w"
							}
						});
						
						//云台控制复位
						$("#ptz_home").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-radio-off"
							}
						});
						
						//云台控制右
						$("#ptz_right").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-arrowthick-1-e"
							}
						});
						
						//云台控制左下
						$("#ptz_leftdown").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-arrowthick-1-sw"
							}
						});
						
						//云台控制下
						$("#ptz_down").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-arrowthick-1-s"
							}
						});
						
						//云台控制右下
						$("#ptz_rightdown").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-arrowthick-1-se"
							}
						});
						
						//设置home位
						$("#ptz_home_set").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-home"
							}
						});
						
						//设置预设点
						$("#ptz_preset_set").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-pin-s"
							}
						});
						
						//开始录像按钮
						$("#record_start").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-bullet"
							}
						});
						
						//停止录像按钮
						$("#record_stop").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-stop"
							}
						});
						
						//设置按钮
						$("#setting").button({
							text: false,
							icons: {
								primary: "ui-icon ui-icon-gear"
							}
						});
						
						//播放/暂停
						$("#play").click(function(){
							//if($("#event_num_"+mid_str).val()>0){	
								vlc.playlist.togglePause();
								if(vlc.input.state==4){					  
									$("#play").button("option","icons",{primary:'ui-icon-pause'});
									$("#play").button("option","label","<?php echo $word['word_monitor_event_pause'] ?>");
								}else if(vlc.input.state==3){
									$("#play").button("option","icons",{primary:'ui-icon-play'});
									$("#play").button("option","label","<?php echo $word['word_monitor_event_play'] ?>");
								}
							//}
						});
						
						//全屏
						$("#full").click(function(){
							//if($("#event_num_"+mid_str).val()>0){					  
								if(vlc.input.state==3||vlc.input.state==4){
									vlc.video.toggleFullscreen();	
								}
							//}
						});
					}
					
					//实时键盘事件
					$("body").keyup(function(event){ 
						switch(event.which){
							case 103:
							ptz('direct','stop');//小键盘7左上
							break;
							
							case 104:
							ptz('direct','stop');//小键盘8上
							break;
							
							case 105:
							ptz('direct','stop');//小键盘9右上
							break;
							
							case 100:
							ptz('direct','stop');//小键盘4左
							break;
							
							case 102:
							ptz('direct','stop');//小键盘6右
							break;
							
							case 97:
							ptz('direct','stop');//小键盘1左下
							break;
							
							case 98:
							ptz('direct','stop');//小键盘2下
							break;
							
							case 99:
							ptz('direct','stop');//小键盘3右下
							break;
							
							case 96:
							ptz('direct','stop');//小键盘0视角+
							break;
							
							case 110:
							ptz('direct','stop');//小键盘.视角-
							break;
						}
											   
					});
					$("body").keydown(function(event){ 
						if(muti>1){
							//全局的的键盘事件
							switch(event.which){
								case 72:
								//状态栏H
								toggle_bar();
								break;
								
								case 73:
								//高度调低I
								size_init("height",-1);
								break;
								
								case 74:
								//宽度调窄J
								size_init("width",-1);
								break;
								
								case 75:
								//高度调高K
								size_init("height",1);
								break;
								
								case 76:
								//宽度调宽L
								size_init("width",1);
								break;
								
								default:
								break;
							}
							
							//选择某一路的时候执行的键盘事件
							if(!$('#k_select').val()){
								var key_arr=new Array(87,83,65,68,103,104,105,100,101,102,97,98,99,96,110,107,109);
								for(i in key_arr){
									if(event.which==key_arr[i]){
										alert("<?php echo $word['word_monitor_one_way_please'] ?>");
										break;
									}
								}
							}else{
								var k=parseInt($("#k_select").val());
								var k_max=muti_muti-1;
								//alert(event.which);
								switch(event.which){
									//上下左右选择
									case 87:
									//W上
									if(k>(muti-1)){
										td_select(k-muti);
									}
									break;
									
									case 83:
									//S下
									if(k<(muti_muti-muti)){
										td_select(k+muti);
									}
									break;
									
									case 65:
									//A左
									if(k>0){
										td_select(k-1);
									}
									break;
									
									case 68:
									//D右
									if(k<k_max){
										td_select(k+1);
									}
									break;
									
									
									//云台控制（小键盘数字键开启有效）
									case 103:
									ptz('direct','leftup','kb');//小键盘7左上
									break;
									
									case 104:
									ptz('direct','up','kb');//小键盘8上
									break;
									
									case 105:
									ptz('direct','rightup','kb');//小键盘9右上
									break;
									
									case 100:
									ptz('direct','left','kb');//小键盘4左
									break;
									
									case 101:
									ptz('home','goto','kb');//小键盘5复位
									break;
									
									case 102:
									ptz('direct','right','kb');//小键盘6右
									break;
									
									case 97:
									ptz('direct','leftdown','kb');//小键盘1左下
									break;
									
									case 98:
									ptz('direct','down','kb');//小键盘2下
									break;
									
									case 99:
									ptz('direct','rightdown','kb');//小键盘3右下
									break;
									
									case 96:
									ptz('zoom','in','kb');//小键盘0视角+
									break;
									
									case 110:
									ptz('zoom','out','kb');//小键盘.视角-
									break;
									
									case 106:
									ptz('auto');
									break;
									
									case 27:
									//esc清空选择
									td_select(-1);
									break;
									
									default:
									break;
								}
							}
						}
					});	
					
					//开始实时播放
					start_watch();
				break;
				
				//录像
				case "event":
					if(muti==1){
						var vlc=document.getElementById('vlc_0');
						
						$("#stop").button({
							text: false,
							icons: {
								primary: "ui-icon-stop"
							}
						});
						$("#prev").button({
							text: false,
							icons: {
								primary: "ui-icon-seek-first"
							}
						});
						$("#slow").button({
							text: false,
							icons: {
								primary: "ui-icon-seek-prev"
							}
						});
						/*$("#play").button({
							text: false,
							icons: {
								primary: "ui-icon-pause"
							}
						});*/
						$("#fast").button({
							text: false,
							icons: {
								primary: "ui-icon-seek-next"
							}
						});
						$("#next").button({
							text: false,
							icons: {
								primary: "ui-icon-seek-end"
							}
						});
						/*$("#full").button({
							text: false,
							icons: {
								primary: "ui-icon-arrow-4-diag"
							}
						});*/
						$("#vol").button({
							text: false,
							icons: {
								primary: "ui-icon-volume-on"
							}
						});
						
						//时间滑块
						$("#event_slider").slider({
							range: "min",
							slide: function( event, ui ) {
								if($("#event_num_"+mid_str).val()>0){
									vlc.input.position=ui.value/(vlc.input.length-1000);
								}
							}
						});
						
						//音量滑块
						$("#vol_slider").slider({
							range: "min",
							slide: function( event, ui ) {
								if($("#event_num_"+mid_str).val()>0){
									vlc.audio.volume=ui.value;
								}
							}
						});
						
						//停止
						$("#stop").click(function(){
							if($("#event_num_"+mid_str).val()>0){												  
								if(vlc.input.state==3||vlc.input.state==4){					  
									vlc.playlist.stop();
								}
							}
						});
						
						//上一个
						$("#prev").click(function(){
							if($("#play_key_"+mid_str).val()>0){					  
								if(vlc.input.state==3||vlc.input.state==4){
									var play_key=$("#play_key_"+mid_str).val();
									play_key--;
									event_play(0,play_key);
									
									$("#play").button("option","icons",{primary:'ui-icon-pause'});
									$("#play").button("option","label","<?php echo $word['word_monitor_event_pause'] ?>");
								}
							}
						});
						
						//慢进
						$("#slow").click(function(){
							if($("#event_num_"+mid_str).val()>0){					  
								if(vlc.input.state==3||vlc.input.state==4){
									if(vlc.input.rate>0.25){
										vlc.input.rate = vlc.input.rate / 2;
									}
								}
							}
						});
						
						//播放/暂停
						$("#play").click(function(){
							if($("#event_num_"+mid_str).val()>0){					  
								if(vlc.input.state==5){					  
									vlc.playlist.play();
								}else if(vlc.input.state==3||vlc.input.state==4){
									vlc.playlist.togglePause();
								}
							}
						});
						
						//快进
						$("#fast").click(function(){
							if($("#event_num_"+mid_str).val()>0){					  
								if(vlc.input.state==3||vlc.input.state==4){
									if(vlc.input.rate<4){
										vlc.input.rate = vlc.input.rate * 2;
									}
								}
							}
						});
						
						//下一个
						$("#next").click(function(){
							if($("#play_key_"+mid_str).val()<($("#event_num_"+mid_str).val()-1)){					  
								if(vlc.input.state==3||vlc.input.state==4){
									var play_key=$("#play_key_"+mid_str).val();
									play_key++;
									event_play(0,play_key);
									
									$("#play").button("option","icons",{primary:'ui-icon-pause'});
									$("#play").button("option","label","<?php echo $word['word_monitor_event_pause'] ?>");
								}
							}
						});
						
						//全屏
						$("#full").click(function(){
							if($("#event_num_"+mid_str).val()>0){					  
								if(vlc.input.state==3||vlc.input.state==4){
									vlc.video.toggleFullscreen();	
								}
							}
						});
						
						//静音
						$("#vol").click(function(){
							if($("#event_num_"+mid_str).val()>0){												 
								vlc.audio.toggleMute();
								
								if(vlc.audio.mute){
									$("#vol").button("option","icons",{primary:'ui-icon-volume-off'});
									$("#vol").button("option","label","打开声音");
								}else{
									$("#vol").button("option","icons",{primary:'ui-icon-volume-on'});
									$("#vol").button("option","label","<?php echo $word['word_monitor_event_mute'] ?>");
								}
							}
						});
						
					}
					
					//键盘事件
					$("body").keydown(function(event){ 
						var k=parseInt($("#k_select").val());
						var k_max=muti_muti-1;
						
						switch(event.which){
							//上下左右选择
							case 87:
							//W上
							if(k>(muti-1)){
								td_select(k-muti);
							}
							break;
							
							case 83:
							//S下
							if(k<(muti_muti-muti)){
								td_select(k+muti);
							}
							break;
							
							case 65:
							//A左
							if(k>0){
								td_select(k-1);
							}
							break;
							
							case 68:
							//D右
							if(k<k_max){
								td_select(k+1);
							}
							break;
							
							
							//录像控制（小键盘数字开启有效）；先判断有没有前端设备if(vlc)，再判断前端设备有没有录像if(xxx>0)，最后判断vlc状态if(vlc.input)
							case 104:
							//小键盘8上一个
							event_kb("prev");
							break;
							
							case 100:
							//小键盘4慢进
							event_kb("slow");
							break;
							
							case 101:
							//小键盘5播放暂停
							event_kb("toggle");
							break;
							
							case 102:
							//小键盘6快进
							event_kb("fast");
							break;
							
							case 98:
							//小键盘2下一个
							event_kb("next");
							break;
							
							case 96:
							//小键盘0停止
							event_kb("stop");
							break;
							
							
							case 27:
							//esc清除选定
							td_select(-1);
							break;
							
							case 72:
							//状态栏H
							toggle_bar();
							break;
							
							case 73:
							//高度调低I
							size_init("height",-1);
							break;
							
							case 74:
							//宽度调窄J
							size_init("width",-1);
							break;
							
							case 75:
							//高度调高K
							size_init("height",1);
							break;
							
							case 76:
							//宽度调宽L
							size_init("width",1);
							break;
							
							default:
							break;
						}
					});	
					
					//开始录像播放
					start_play();
				break;
			}
		});
		
		//状态栏隐藏切换
		function toggle_bar(){
			if($("#hide_bar").val()==1){
				setCookie("hide_bar",1,-30);
				window.opener.opener_bar(0);
				window.location=window.location.href.replace("&hide_bar=1","&hide_bar=0");
			}else{
				setCookie("hide_bar",1,30);
				window.opener.opener_bar(1);
				window.location=window.location.href.replace("&hide_bar=0","&hide_bar=1");
			}
		}

		//获取单元格，VLC大小和VLC比例
		function get_size(size,fix,num){
			var td_width;
			var td_height;
			var vlc_width;
			var vlc_height;
			var vlc_ratio;
			
			if(muti==1){
				//单路（总屏，VLC屏和控制栏高度固定，宽度根据客户显示器调节大小）
				vlc_width=$("#vlc_width").val();
				vlc_height=450;
				
				$("#screen").css("width",vlc_width);
				$("#screen").css("height",vlc_height);
				$(".console").css("width",vlc_width);
				
				vlc_ratio=vlc_width+":"+vlc_height;
			}else{
				//获取多路总屏大小
				var screen_width;
				var screen_height;
				
				if($("#operator").val()){
					//操作员多路（固定的总屏大小）
					screen_width=640;
					screen_height=$("#operator").val()-15;
				}else{
					//全屏多路（根据修正值判定总屏的大小）
					var width_fix=parseInt($("#width_fix").val());
					var height_fix=parseInt($("#height_fix").val());
					
					if(fix=="width"){
						width_fix+=num;
						$("#width_fix").val(width_fix);
					}else if(fix=="height"){
						height_fix+=num;
						$("#height_fix").val(height_fix);
					}
					
					setCookie("width_fix",width_fix,30);
					setCookie("height_fix",height_fix,30);
					
					window.opener.opener_fix(width_fix,height_fix);
					
					screen_width=screen.width+width_fix;
					screen_height=screen.height+height_fix;
				}
				
				//获取单元格宽高
				td_width=screen_width/muti;
				td_height=screen_height/muti;
				
				//获取vlc宽高
				vlc_width=td_width;
				vlc_height=td_height;
				
				//如果有状态栏则VLC高度减掉状态栏高度
				if($("#hide_bar").val()!=1){
					vlc_height-=22;
				}
				
				//vlc比例
				vlc_ratio=vlc_width+":"+vlc_height;
			}
			
			//返回需要的值
			switch(size){
				case "td_width":
					return td_width;
				break;
				
				case "td_height":
					return td_height;
				break;
				
				case "vlc_width":
					return vlc_width;
				break;
				
				case "vlc_height":
					return vlc_height;
				break;
				
				case "vlc_ratio":
					return vlc_ratio;
				break;
				
				default:
				break;
			}
		}
		
		
		//屏幕大小初始化
		function size_init(fix,num){
			var td_width=get_size("td_width",fix,num);
			var td_height=get_size("td_height",fix,num);
			
			var vlc_width=get_size("vlc_width",fix,num);
			var vlc_height=get_size("vlc_height",fix,num);
			
			//将大小赋给每个单元格和VLC
			for(var k=0;k<muti_muti;k++){
				$("#m_td_"+k).css("width",td_width);
				$("#m_td_"+k).css("height",td_height);
				
				var vlc=document.getElementById('vlc_'+k);
				if(vlc){
					vlc.width=vlc_width;
					vlc.height=vlc_height;
				}
			}
		}
	
	
	
		//开始实时视频
		function start_watch(){
			var mid_str=$("#mid_str").val();
			
			//动态连接和提取前端设备数据
			if(mid_str!=0){
				var monitor=$.ajax({url:"index.php?do=ajax&view=monitor_list&op=watch_connect_start&mid_str="+mid_str,async:false});
				var monitor_str=monitor.responseText;
				start_load(monitor_str);
			}
		}
		
		//开始载入（分大循环和小循环，大循环就是轮巡，小循环是载入单个屏，根据k_load判断是该进行大循环还是小循环）
		function start_load(monitor_str){
			var mid_arr=$("#mid_str").val().split(",");
			var k=parseInt($("#k_load").val());
			var over_load=parseInt($("#over_load").val());
			
			//判断最多有几个vlc
			if(muti_muti<mid_arr.length){
				var k_max=muti_muti-1;
			}else{
				var k_max=mid_arr.length-1;
			}
			
			//获得上一个vlc
			if(k>0){
				var vlc_pre=document.getElementById('vlc_'+(k-1));
				/*$("#vlc_input_state_"+k).append(vlc_pre.input.state);
				if(vlc_pre.playlist.isPlaying){
					$("#vlc_input_state_"+k).append(0);
				}else{
					$("#vlc_input_state_"+k).append(1);
				}
				$("#vlc_input_state_"+k).append(",");*/
				var is_begin=false;
			}else{
				var vlc_pre=document.getElementById('vlc_'+k_max);
				
				if($("#turn_time").val()){
					var keep_time=parseInt((new Date().getTime()-begin_time)/1000);
					if(keep_time<$("#turn_time").val()){
						var is_begin=true;
					}else{
						var is_begin=false;
					}
				}else{
					var is_begin=true;
				}
			}
			
			//对上一个vlc状态扫描，如果已经播放了再开始执行下一个
			//k>0&&(vlc_pre.input.state==0||vlc_pre.input.state==1||vlc_pre.input.state==2||vlc_pre.input.state==7)
			if(is_begin==false&&vlc_pre.playlist.isPlaying==false&&over_load<3){
				over_load++;
				if(over_load==3){
					vlc_pre.playlist.stop();
				}
				$("#over_load").val(over_load);
				setTimeout("start_load('"+monitor_str+"')",1000);
			}else{
				$("#over_load").val(0);
			
				//从获取的前端设备数据里按ID提取
				var monitor_arr=eval("("+monitor_str+")");
				for(i in monitor_arr){
					if(monitor_arr[i]['id']==mid_arr[k]){
						var mname=monitor_arr[i]['name'];
						var mstate=monitor_arr[i]['state'];
						var mfunction=monitor_arr[i]['function'];
						var mrl=monitor_arr[i]['path'];
						var passport=monitor_arr[i]['passport'];
						var password=monitor_arr[i]['password'];
						var media=monitor_arr[i]['media'];
						var ptz_path=monitor_arr[i]['ptz_path'];
						var ptz_function=monitor_arr[i]['ptz_function'];
						var ptz_speed=monitor_arr[i]['ptz_speed'];
						var ptz_preset=monitor_arr[i]['ptz_preset'];
						break;
					}
				}
				$("#monitor_name_"+k).html(mname);
				$("#monitor_state_"+k).html(mstate);
				$("#passport_"+k).val(passport);
				$("#password_"+k).val(password);
				$("#media_"+k).val(media);
				$("#ptz_path_"+k).val(ptz_path);
				$("#ptz_function_"+k).val(ptz_function);
				$("#ptz_speed_"+k).val(ptz_speed);
				$("#ptz_preset_"+k).val(ptz_preset);
				
				//单路时初始化
				if(muti_muti==1){
					//开始停止录像按钮的判断
					if(mfunction=="Record"||mfunction=="SameTime"){
						$("#record_start").hide();
						$("#record_stop").show();
					}else{
						$("#record_start").show();
						$("#record_stop").hide();
					}
					
					//云台初始化
					if(ptz_path&&ptz_function){
						$("#ptz").show();
						$("#ptz_none").hide();
						
						//云台功能判断
						var ptz_function_arr=ptz_function.split("");
						//↑↓←→
						if(Number(ptz_function_arr[0])==0){
							$("#ptz_left").hide();
							$("#ptz_right").hide();
							$("#ptz_down").hide();
							$("#ptz_up").hide();
						}else{
							$("#ptz_left").show();
							$("#ptz_right").show();
							$("#ptz_down").show();
							$("#ptz_up").show();
						}
						//↖↙↗↘
						if(Number(ptz_function_arr[1])==0){
							$("#ptz_leftup").hide();
							$("#ptz_rightup").hide();
							$("#ptz_leftdown").hide();
							$("#ptz_rightdown").hide();
						}else{
							$("#ptz_leftup").show();
							$("#ptz_rightup").show();
							$("#ptz_leftdown").show();
							$("#ptz_rightdown").show();
						}
						//视角
						if(Number(ptz_function_arr[2])==0){
							$(".ptz_zoom").hide();
						}else{
							$(".ptz_zoom").show();
						}
						//预设点
						if(Number(ptz_function_arr[5])==0){
							$(".ptz_preset").hide();
						}else{
							$(".ptz_preset").show();
						}
						//Home位
						if(Number(ptz_function_arr[6])==0){
							$("#ptz_home").hide();
							$("#ptz_home_set").hide();
						}else{
							$("#ptz_home").show();
							$("#ptz_home_set").show();
						}
						
						$("#ptz_speed_val_"+ptz_speed).attr("selected",true);
						
						//预设点
						var ptz_preset_option="";
						if(ptz_preset){
							ptz_preset_option+="<option value=0 id='preset_none'><?php echo $word['word_select']; ?></option>";
							for(i in ptz_preset){
								ptz_preset_option+="<option value="+ptz_preset[i]['Preset']+">"+ptz_preset[i]['Name']+"</option>";
							}
						}else{
							ptz_preset_option+="<option value=0><?php echo $word['word_none']; ?></option>";
						}
						$("#ptz_preset").html(ptz_preset_option);
					}else{
						//没有云台地址
						$("#ptz").hide();
						$("#ptz_none").show();
					}
				}
				
				//开始调用vlc
				var vlc=document.getElementById('vlc_'+k);
				if(vlc){
					var is_update_vlc=update_vlc(vlc.VersionInfo);
					if(is_update_vlc){
						
						//获得屏幕比例
						var vlc_ratio=get_size("vlc_ratio");
						
						//根据版本不同设置不同参数
						var ver_arr=vlc.VersionInfo.split(".");
						if(ver_arr[0]<=1){
							//2.0以前的版本
							var vlc_option=new Array(":aspect-ratio="+vlc_ratio,":rtsp-caching="+$("#rtsp_caching").val(),"--rtsp-tcp");
						}else{
							//2.0以后的版本
							var vlc_option=new Array(":rtsp-caching="+$("#rtsp_caching").val(),"--rtsp-tcp");
						}
						
						vlc.playlist.items.clear()
						vlc.playlist.add(mrl,"vlc_"+k,vlc_option);
						vlc.playlist.playItem(0);
						
						//2.0后的要播放后才能设置比例
						if(ver_arr[0]>=2){
							vlc.video.aspectRatio=vlc_ratio;
						}
					}else{
						window.open("/skins/monitor_plugins.html");
						return false;
					}
				}else{
					window.open("/skins/monitor_plugins.html");
					return false;
				}
				
				
				//轮巡业务逻辑
				if(k<k_max){
					k++;
					$("#k_load").val(k);
					setTimeout("start_load('"+monitor_str+"')",$("#load_time").val());//执行小轮巡
				}else{
					var mid_arr_2=new Array();
					var mid_arr_3=new Array();
					var mid_arr_4=new Array();
					
					//如果路数小于选择ID的数量则轮巡，否则不轮巡（判断是否轮巡）
					if(muti_muti<mid_arr.length){
						//将ID数组按路数切分成两个数组
						for(n in mid_arr){
							if(n<muti_muti){
								mid_arr_2.push(mid_arr[n]);//需要显示的ID数组
							}else{
								mid_arr_3.push(mid_arr[n]);//剩余ID数组
							}
						}
						var is_turn=true;//轮巡
					}else{
						mid_arr_2=mid_arr;
						var is_turn=false;//不轮巡
					}
					
					if(is_turn){
						mid_arr_4=mid_arr_3.concat(mid_arr_2);//合成重新排序的ID数组
						$("#mid_str").val(mid_arr_4.join(","));//将重新排序的ID数组转换为字符串返回
						
						$("#k_load").val(0);
						setTimeout("start_load('"+monitor_str+"')",$("#turn_time").val());//执行大轮巡
					}
				}
			
			
			}
		}
		



		//云台控制
		var st=0;
		function ptz(control,val,val2){
			
			//如果是多路则取以k为键值的正在播数组的某一元素的值，该值是mid，k是用户选择的某一个屏幕
			if(muti>1){
				if($('#k_select').val()){
					var k=Number($('#k_select').val());
				}else{
					return false;
				}
				
				if($("#operator").val()){
					var ptz_speed=parent.ptz_speed();
				}else{
					var ptz_speed=$("#ptz_speed_"+k).val();
				}
			}else{
				var k=0;
				
				var ptz_speed=$("#ptz_speed").val();
			}
			
			var mid_arr=$("#mid_str").val().split(",");
			var mid=mid_arr[k];
			
			var passport=$("#passport_"+k).val();
			var password=$("#password_"+k).val();
			var media=$("#media_"+k).val();
			var ptz_path=$("#ptz_path_"+k).val();
			
			//alert(ptz_path);
			if(mid&&ptz_path){	
				var url="index.php?do=ajax&view=monitor_list&op=ptz_control&mid="+mid+"&passport="+passport+"&password="+password+"&media="+media+"&ptz_path="+ptz_path+"&ptz_speed="+ptz_speed+"&control="+control;
				
				switch(control){
					
					//自动巡航
					case "auto":
						url+="&val="+val;
					break;
					
					//焦距
					case "focus":
						url+="&val="+val;
					break;
					
					//视角
					case "zoom":
						url+="&val="+val;
					break;
					
					//方向
					case "direct":
						url+="&val="+val;
					break;
									
					//速度
					case "speed":
						url+="&val="+val;
					break;
					
					//Home
					case "home":
						if(val=="set"){
							if(confirm('<?php echo $word['word_monitor_ptz_home_set_confirm']; ?>')){
								url+="&val="+val;
							}else{
								return false;
							}
						}else{
							url+="&val="+val;
						}
					break;
					
					//预设点
					case "preset":
						if(val=="pop"){
							pop_window(450,300,"index.php?do=ajax&view=monitor_list&op=set_preset&mid="+mid,"monitor_ptz_preset")
							
							return false;
						}
						url+="&val="+val;
						
						if(val2==0){
							return false;
						}
						url+="&val2="+val2;
					break;
				}
				$.ajax({url:url,async:false,success:function(ptz_log){
						st+=30;
						if($("#operator").val()){
							parent.ptz_log(control,ptz_log,st);
						}else{
							if(control!="preset"){
								$("#preset_none").attr("selected",true);
							}
							
							if(ptz_log){
								$("#ptz_log").append(ptz_log+"<br>");
								$("#ptz_log").scrollTop(st);
							}
						}
						
						if(control=="speed"){
							$("#ptz_speed_"+k).val(val);
						}
						
					}
				});
			
			}
		}
		



		//开始录像播放
		function start_play(){
			//遍历每个屏幕
			for(var k=0;k<muti_muti;k++){
				//if($("#mname_"+mid_arr[k]).val()){
				if(k<mid_arr.length&&mid_arr[0]!=0){		
					$("#monitor_name_"+k).html($("#mname_"+mid_arr[k]).val());//在屏幕状态栏显示名称
					
					var vlc=document.getElementById('vlc_'+k);
					if(vlc.playlist){
						if($("#event_num_"+mid_arr[k]).val()>0){
							event_play(k,0);//播放第k个前端设备的第一个录像
						}
					}else{
						window.open("/skins/monitor_plugins.html");
						break;
					}
				}
			}
			setTimeout(scan_vlc_state, 500);
		}
		
		//单个录像播放
		function event_play(k,play_key){
			$("#play_key_"+mid_arr[k]).val(play_key);//将当前播放的文件的play_key更新
			
			var mrl_arr=$("#mrl_"+mid_arr[k]).val().split(",");//根据前端设备id找到mrl
			$("#file_name_"+k).html(basename(mrl_arr[play_key]));//在屏幕状态栏显示文件名
			
			//播放中的文件名称高亮
			if(muti==1){
				$(".event_name").css("color","#7F7FB2");
				$(".event_name").css("text-decoration","none");
				$("#event_name_"+play_key).css("color","#00B6EF");
				$("#event_name_"+play_key).css("text-decoration","underline");
			}else{
				if($("#operator").val()){
					parent.playing_event(k,play_key,mrl_arr.length);
				}
				$("#file_all_"+k).html($("#event_num_"+mid_arr[k]).val());//共几个
				$("#file_num_"+k).html(parseInt($("#play_key_"+mid_arr[k]).val())+1);//现在第几个
			}
			
			var vlc=document.getElementById('vlc_'+k);
			var vlc_ratio=get_size("vlc_ratio");
			
			var ver_arr=vlc.VersionInfo.split(".");
			var vlc_option=new Array(":aspect-ratio="+vlc_ratio);
			
			vlc.playlist.clear();
			vlc.playlist.add(mrl_arr[play_key],"vlc_"+k,vlc_option);
			vlc.playlist.playItem(0);
			
			if(ver_arr[0]>=2){
				vlc.video.aspectRatio=vlc_ratio;
			}
			
		}
		
		//扫描（每500毫秒扫描一次vlc的运行状态）
		function scan_vlc_state() {
			//遍历每个屏幕
			for(var k=0;k<muti_muti;k++){
				if($("#mname_"+mid_arr[k]).val()){
					if($("#event_num_"+mid_arr[k]).val()>0){
						var vlc=document.getElementById('vlc_'+k);
						if(vlc.input){
							switch(vlc.input.state){
								case 0:
								break;
								
								case 1:
								break;
								
								case 2:
								break;
								
								//播放中
								case 3:
									$("#event_time_"+k).html(event_time($("#file_name_"+k).html(),vlc.input.time));//事件时间
									$("#play_rate_"+k).html(vlc.input.rate+"x");//播放速度
									
									if(muti==1){
										$("#event_slider").slider("option","max",vlc.input.length-1000);//设定滑块最大值
										$("#event_slider").slider("option","value",vlc.input.time);//设定滑块的值
										$("#vol_slider").slider("option","value",vlc.audio.volume);//音量
									
										$("#play").button("option","icons",{primary:'ui-icon-pause'});
										$("#play").button("option","label","<?php echo $word['word_monitor_event_pause'] ?>");
										
										$("#file_time_"+k).html(formatTime(vlc.input.time)+"/"+formatTime(vlc.input.length-1000));//文件时间
									}
									
									
									if($("#operator").val()){
										if($("#k_select").val()&&document.getElementById('vlc_'+$("#k_select").val())){
											if(parseInt($("#k_select").val())==k){
												parent.playing_time(event_time($("#file_name_"+k).html(),vlc.input.time),formatTime(vlc.input.time)+"/"+formatTime(vlc.input.length-1000));
											}
										}else{
											parent.playing_time(0,0);
										}
									}
									
									$("#play_off_"+k).hide();
									$(".event_state_"+k).show();
								break;
								 
								//暂停 
								case 4:
									$("#play_rate_"+k).html("<?php echo $word['word_monitor_event_pause'] ?>");
									
									if(muti==1){
										$("#play").button("option","icons",{primary:'ui-icon-play'});
										$("#play").button("option","label","<?php echo $word['word_monitor_event_play'] ?>");
									}
								break;
								
								//停止
								case 5:
									$("#event_time_"+k).empty();
									$("#play_rate_"+k).empty();
									
									if(muti==1){
										$("#event_slider").slider("option","value",0);
										
										$("#play").button("option","icons",{primary:'ui-icon-play'});
										$("#play").button("option","label","<?php echo $word['word_monitor_event_play'] ?>");
										
										$("#file_time_"+k).empty();
									}
									
									if($("#operator").val()){
										if($("#k_select").val()&&document.getElementById('vlc_'+$("#k_select").val())){
											if(parseInt($("#k_select").val())==k){
												parent.playing_time(0,0);
											}
										}else{
											parent.playing_time(0,0);
										}
									}
								break;
								
								//一段录像播放完毕
								case 6:
									var play_key=$("#play_key_"+mid_arr[k]).val();
									
									if((parseInt(play_key)+1)<parseInt($("#event_num_"+mid_arr[k]).val())){
										//播放下一个
										play_key++;
										event_play(k,play_key);
									}else{
										$("#play_off_"+k).show();
										$(".event_state_"+k).hide();
										vlc.playlist.stop();
									}
								break;
								
								case 7:
								break;
								
								default:
								break;
							}
						}
					}else{
						$("#play_off_"+k).html("<?php echo $word['word_none'] ?>")
						$("#play_off_"+k).show();
						$(".event_state_"+k).hide();
					}
				}
			}
			setTimeout(scan_vlc_state, 500);
		}
		
		//录像键盘控制
		function event_kb(op){
			switch(op){
				case "stop":
					//小键盘0停止
					if($("#k_select").val()){
						var k=parseInt($("#k_select").val());
						var vlc=document.getElementById('vlc_'+k);
						if(vlc){
							if($("#event_num_"+mid_arr[k]).val()>0){
								if(vlc.input.state==3||vlc.input.state==4){
									vlc.playlist.stop();
								}
							}
						}
					}else{
						for(var k=0;k<muti_muti;k++){
							var vlc=document.getElementById('vlc_'+k);
							if(vlc){
								if($("#event_num_"+mid_arr[k]).val()>0){
									if(vlc.input.state==3||vlc.input.state==4){
										vlc.playlist.stop();
									}
								}
							}
						}
					}
				break;
				
				case "prev":
					//小键盘8上一个
					if($("#k_select").val()){
						var k=parseInt($("#k_select").val());
						var vlc=document.getElementById('vlc_'+k);
						if(vlc){
							if($("#play_key_"+mid_arr[k]).val()>0){
								if(vlc.input.state==3||vlc.input.state==4){
									var play_key=$("#play_key_"+mid_arr[k]).val();
									play_key--;
									event_play(k,play_key);
								}
							}
						}
					}else{
						for(var k=0;k<muti_muti;k++){
							var vlc=document.getElementById('vlc_'+k);
							if(vlc){
								if($("#play_key_"+mid_arr[k]).val()>0){
									if(vlc.input.state==3||vlc.input.state==4){
										var play_key=$("#play_key_"+mid_arr[k]).val();
										play_key--;
										event_play(k,play_key);
									}
								}
							}
						}
					}
				break;
				
				case "slow":
					//小键盘4慢进
					if($("#k_select").val()){
						var k=parseInt($("#k_select").val());
						var vlc=document.getElementById('vlc_'+k);
						if(vlc){
							if($("#event_num_"+mid_arr[k]).val()>0){
								if(vlc.input.rate>0.25){
									vlc.input.rate = vlc.input.rate / 2;
								}
							}
						}
					}else{
						for(var k=0;k<muti_muti;k++){
							var vlc=document.getElementById('vlc_'+k);
							if(vlc){
								if($("#event_num_"+mid_arr[k]).val()>0){
									if(vlc.input.rate>0.25){
										vlc.input.rate = vlc.input.rate / 2;
									}
								}
							}
						}
					}
				break;
				
				case "toggle":
					//小键盘5播放暂停
					if($("#k_select").val()){
						var k=parseInt($("#k_select").val());
						var vlc=document.getElementById('vlc_'+k);
						if(vlc){
							if($("#event_num_"+mid_arr[k]).val()>0){
								if(vlc.input.state==5){					  
									vlc.playlist.play();
								}else if(vlc.input.state==3||vlc.input.state==4){
									vlc.playlist.togglePause();
								}
							}
						}
					}else{
						for(var k=0;k<muti_muti;k++){
							var vlc=document.getElementById('vlc_'+k);
							if(vlc){
								if($("#event_num_"+mid_arr[k]).val()>0){
									if(vlc.input.state==5){					  
										vlc.playlist.play();
									}else if(vlc.input.state==3||vlc.input.state==4){
										vlc.playlist.togglePause();
									}
								}
							}
						}
					}
				break;
				
				case "fast":
					//小键盘6快进
					if($("#k_select").val()){
						var k=parseInt($("#k_select").val());
						var vlc=document.getElementById('vlc_'+k);
						if(vlc){
							if($("#event_num_"+mid_arr[k]).val()>0){
								if(vlc.input.rate<4){
									vlc.input.rate = vlc.input.rate * 2;
								}
							}
						}
					}else{
						for(var k=0;k<muti_muti;k++){
							var vlc=document.getElementById('vlc_'+k);
							if(vlc){
								if($("#event_num_"+mid_arr[k]).val()>0){
									if(vlc.input.rate<4){
										vlc.input.rate = vlc.input.rate * 2;
									}
								}
							}
						}
					}
				break;
				
				case "next":
					//小键盘2下一个
					if($("#k_select").val()){
						var k=parseInt($("#k_select").val());
						var vlc=document.getElementById('vlc_'+k);
						if(vlc){
							if($("#play_key_"+mid_arr[k]).val()<($("#event_num_"+mid_arr[k]).val()-1)){
								if(vlc.input.state==3||vlc.input.state==4){
									var play_key=$("#play_key_"+mid_arr[k]).val();
									play_key++;
									event_play(k,play_key);
								}
							}
						}
					}else{
						for(var k=0;k<muti_muti;k++){
							var vlc=document.getElementById('vlc_'+k);
							if(vlc){
								if($("#play_key_"+mid_arr[k]).val()<($("#event_num_"+mid_arr[k]).val()-1)){
									if(vlc.input.state==3||vlc.input.state==4){
										var play_key=$("#play_key_"+mid_arr[k]).val();
										play_key++;
										event_play(k,play_key);
									}
								}
							}
						}
					}
				break;
				
				default:
				break;
			}
		}
		
		//通过文件名和文件时间获取事件时间
		function event_time(file_name,file_time){
			var fn1=file_name.split(".mp4");
			var fn2=fn1[0].split("-");
			var start_time=Date.UTC(fn2[0],fn2[1],fn2[2],fn2[3],fn2[4],fn2[5]);
			var event_time=new Date(start_time+file_time);
			if(event_time.getUTCMonth()==0){
				var year=event_time.getUTCFullYear()-1;
				var month=12;
			}else{
				var year=event_time.getUTCFullYear();
				var month=event_time.getUTCMonth();
			}
			var day=event_time.getUTCDate();
			if(event_time.getUTCHours()<10){
				var hour="0"+event_time.getUTCHours();
			}else{
				var hour=event_time.getUTCHours();
			}
			if(event_time.getUTCMinutes()<10){
				var minute="0"+event_time.getUTCMinutes();
			}else{
				var minute=event_time.getUTCMinutes();
			}
			if(event_time.getUTCSeconds()<10){
				var second="0"+event_time.getUTCSeconds();
			}else{
				var second=event_time.getUTCSeconds();
			}
			return year+"-"+month+"-"+day+"&nbsp;"+hour+":"+minute+":"+second;
		}
		
		//从路径提取文件名
		function basename(path){
			var path_arr=path.split("/");
			return path_arr[path_arr.length-1];
		}
		
		//格式化时间
		function formatTime(timeVal){
			var timeHour = Math.round(timeVal / 1000);
			var timeSec = timeHour % 60;
			
			if( timeSec < 10 )
			timeSec = '0'+timeSec;
			timeHour = (timeHour - timeSec)/60;
			var timeMin = timeHour % 60;
			
			if( timeMin < 10 )
			timeMin = '0'+timeMin;
			timeHour = (timeHour - timeMin)/60;
			
			if( timeHour > 0 )
			return timeHour+":"+timeMin+":"+timeSec;
			else
			return timeMin+":"+timeSec;
		}
		
        //是否需要升级
        function update_vlc(vlc_ver){
            var cur_ver="1.1.11";
            cur_ver=cur_ver.split(".");
            
            vlc_ver=vlc_ver.split(" ");
            vlc_ver=vlc_ver[0].split(".");
            for(i in vlc_ver){
                if(parseInt(vlc_ver[i])>=parseInt(cur_ver[i])){
                    return true;
                }
            }
            return false;
        }
		
		//选择某一路
		function td_select(k){
			if(k==-1){
				$('.m_td').css('border','0');
				$('#k_select').val('');
				
				//操作员界面返回
				if($("#operator").val()){
					if(type=="watch"){
						parent.ptz_init(-1);
					}
				}
			}else{
				$('#k_select').val(k);
				$('.m_td').css('border','0');
				$('#m_td_'+k).css('border','3px solid #0F0');
				
				//操作员界面返回
				if($("#operator").val()){
					if(type=="watch"){
						var mid_arr=$("#mid_str").val().split(",");
						var mid=mid_arr[k];
						parent.ptz_init(mid,$("#ptz_path_"+k).val(),$("#ptz_function_"+k).val(),/*$("#ptz_auto_"+k).val(),$("#ptz_iris_"+k).val(),*/$("#ptz_speed_"+k).val(),$("#ptz_preset_"+k).val());
					}else{
						parent.playing_scroll(k);
					}
				}
			}
		}
		
		//某一路新窗口打开
		function single_window(k){
			var window_width=$("#pop_width").val()-30;
			var left = (screen.width - window_width) / 2;
			var top  = (screen.height - 560) / 2;
			var time=new Date().getTime();//获取随机时间
			window.open("index.php?do=ajax&view=monitor_list&op=monitor_iframe&type="+type+"&muti=1&mid_str="+mid_arr[k]+"&pop_width="+$("#pop_width").val()+"&vlc_width="+$("#vlc_width").val(),"single"+time,"resizable=no,scrollbars=no,fullscreen=no,width="+window_width+",height=560,top="+top+",left="+left);
		}
		
		//切换
		function translate(title,url){
			if(!window.opener){
				parent.pop_title(title,'monitor_pop')
			}
			window.location=url;
		}
		
		//录像下载
		function event_download(path){
			window.location.replace('index.php?do=ajax&view=monitor_list&op=event_download&path='+path);
		}
		
		//设置cookie
		function setCookie(c_name,value,expiredays,type){
			var exdate=new Date()
			if(type=="minute"){
				exdate.setMinutes(exdate.getMinutes()+expiredays)
			}else if(type=="second"){
				exdate.setSeconds(exdate.getSeconds()+expiredays)
			}else{
				exdate.setDate(exdate.getDate()+expiredays)
			}
			document.cookie=c_name+ "=" +escape(value)+
			((expiredays==null) ? "" : ";expires="+exdate.toGMTString())
		}
		function move_color(tr){
			tr.style.backgroundColor="#eee";
		}
		
		function out_color(tr){
			tr.style.backgroundColor="";
		}
		//弹出窗口
		function pop_window(width,height,url,name){
			var left = (screen.width - width) / 2;
			var top  = (screen.height - height) / 2;
			window.open(url,name,'width='+width+',height='+height+',top='+top+',left='+left);
		}
        </script> 
		<?php
	}
	
	
	
	//输出VLC标签
	function Out_vlc($mid,$width=false,$height=false){
		if(preg_match('/MSIE ([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT'])){
		?>
			<OBJECT classid="clsid:9BE31822-FDAD-461B-AD51-BE1D1C159921"
					width="<?php echo $width; ?>"
					height="<?php echo $height; ?>"
                    name="<?php echo "vlc_".$mid; ?>"
					id="<?php echo "vlc_".$mid; ?>"
					events="False">
			<param name="wmode" value="transparent"/>
			<param name="fullscreen" value="false" />
			<param name="MRL" value="" />
			<param name="ShowDisplay" value="Flase" />
			<param name="AutoLoop" value="False" />
			<param name="AutoPlay" value="False" />
			<param name="Volume" value="50" />
			<param name="StartTime" value="0" />
			</OBJECT>
		<?php
		}else{
		?>
			<embed type="application/x-vlc-plugin" pluginspage="" version="VideoLAN.VLCPlugin.2" width="<?php echo $width; ?>" height="<?php echo $height; ?>" src="" id="<?php echo "vlc_".$mid; ?>" name="<?php echo "vlc_".$mid; ?>"></embed>
		<?php
		}
	}
	
	//用于服务器判断是否实时中
	function Connectting($mid_str){
		$mid_arr=explode(",",$mid_str);
		foreach($mid_arr as $mid){
			mysql_query("update Monitors set Connectting=".time()." where Id=".$mid);
		}
	}
	
	
	//云台控制
	function Ptz_control($item){
		global $System;
		$word=$System->Get_word();
		
		$ptz_speed=$item['ptz_speed']/10;
		
		switch($item['control']){
			
			//巡航
			case "auto":
				if($item['val']=="on"){
					$back=Shell_cmd("NVRCommandLine -P auto  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
					echo $word['word_monitor_ptz_auto']."&nbsp;"."<font color=green>".$word['word_on']."</font>"."&nbsp;".$this->Ptz_back($back['back']);
				}else{
					$back=Shell_cmd("NVRCommandLine -P autoStop  -i 1000 -d ".$item['mid']." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
					echo $word['word_monitor_ptz_auto']."&nbsp;"."<font color=red>".$word['word_off']."</font>"."&nbsp;".$this->Ptz_back($back['back']);
				}
			break;
			
			
			//视角
			case "zoom":
				if($item['val']=="in"){
					$back=Shell_cmd("NVRCommandLine -P zoomIn  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
					echo $word['word_monitor_ptz_zoom_in']."&nbsp;".$this->Ptz_back($back['back']);
				}else{
					$back=Shell_cmd("NVRCommandLine -P zoomOut  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
					echo $word['word_monitor_ptz_zoom_out']."&nbsp;".$this->Ptz_back($back['back']);
				}
			break;
			
			//方向
			case "direct":
				switch($item['val']){
					case "left":
						$back=Shell_cmd("NVRCommandLine -P left  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
						echo $word['word_monitor_ptz_left']."&nbsp;".$this->Ptz_back($back['back']);
					break;
					
					case "right":
						$back=Shell_cmd("NVRCommandLine -P right  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
						echo $word['word_monitor_ptz_right']."&nbsp;".$this->Ptz_back($back['back']);
					break;
					
					case "down":
						$back=Shell_cmd("NVRCommandLine -P down  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
						echo $word['word_monitor_ptz_down']."&nbsp;".$this->Ptz_back($back['back']);
					break;
					
					case "up":
						$back=Shell_cmd("NVRCommandLine -P up  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
						echo $word['word_monitor_ptz_up']."&nbsp;".$this->Ptz_back($back['back']);
					break;
					
					case "leftdown":
						$back=Shell_cmd("NVRCommandLine -P leftDown  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
						echo $word['word_monitor_ptz_leftdown']."&nbsp;".$this->Ptz_back($back['back']);
					break;
					
					case "rightdown":
						$back=Shell_cmd("NVRCommandLine -P rightDown  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
						echo $word['word_monitor_ptz_rightdown']."&nbsp;".$this->Ptz_back($back['back']);
					break;
					
					case "leftup":
						$back=Shell_cmd("NVRCommandLine -P leftUp  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
						echo $word['word_monitor_ptz_leftup']."&nbsp;".$this->Ptz_back($back['back']);
					break;
					
					case "rightup":
						$back=Shell_cmd("NVRCommandLine -P rightUp  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
						echo $word['word_monitor_ptz_rightup']."&nbsp;".$this->Ptz_back($back['back']);
					break;
					
					case "stop":
						$back=Shell_cmd("NVRCommandLine -P autoStop  -i 1000 -d ".$item['mid']." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
					break;
					
					default:
					break;
				}
			break;
			
			//Home
			case "home":
				if($item['val']=="goto"){
					$back=Shell_cmd("NVRCommandLine -P gotohome  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
					echo $word['word_monitor_ptz_home']."&nbsp;".$this->Ptz_back($back['back']);
				}else{
					$back=Shell_cmd("NVRCommandLine -P sethome  -i 1000 -d ".$item['mid']." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."'");
					echo $word['word_monitor_ptz_home_set']."&nbsp;".$this->Ptz_back($back['back']);
				}
			break;
							
			//速度
			case "speed":
				mysql_query("update Monitors set Ptz_speed=".$item['ptz_speed']." where Id=".$item['mid']);
				echo $word['word_speed']."&nbsp;".$item['ptz_speed']."x";//."&nbsp;".$back['back'];
			break;
			
			//预设点
			case "preset":
				switch($item['val']){
					case "set":
						mysql_query("insert into Presets (Mid,Preset,Name) values (".$item['mid'].",'','".$item['val2']."')");
						$back=Shell_cmd("NVRCommandLine -P preset  -i 1000 -d ".$item['mid']." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."' -v ".mysql_insert_id());
					break;
					
					case "goto":
						$back=Shell_cmd("NVRCommandLine -P gotoPreset  -i 1000 -d ".$item['mid']." -s ".$ptz_speed." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."' -p ".$item['val2']);
						echo $word['word_monitor_ptz_preset']."&nbsp;".$item['val2']."&nbsp;".$this->Ptz_back($back['back']);
					break;
					
					case "clear":
						if($item['val2']=="all"){
							$preset_arrs=Get_db_arrs("select Preset from Presets where Mid=".$item['mid']);
							if($preset_arrs){
								foreach($preset_arrs as $preset_arr){
									$back=Shell_cmd("NVRCommandLine -P clearPreset -i 1000 -d ".$item['mid']." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."' -p ".$preset_arr['Preset']);
								}
							}
							mysql_query("delete from Presets where Mid=".$item['mid']);
						}else{
							$back=Shell_cmd("NVRCommandLine -P clearPreset -i 1000 -d ".$item['mid']." -u '".$item['ptz_path']."' -m '".$item['media']."' -a '".$item['passport']."' -b '".$item['password']."' -p ".$item['val2']);
							mysql_query("delete from Presets where Mid=".$item['mid']." and Preset='".$item['val2']."'");
						}
					break;
				}
			break;
		}
	}
	
	//PTZ返回值
	function Ptz_back($back){
		global $System;
		$word=$System->Get_word();
		if($back==0){
			return "<font color=green>".$word['word_success']."</font>";
		}else{
			return "<font color=red>".$word['word_failed']."</font>";
		}
	}
	
	//预置点窗口
	function Set_preset($mid){
		global $System;
		$word=$System->Get_word();
		$preset_arrs=Get_db_arrs("select Preset,Name from Presets where Mid=".$mid);
		?>
        
        <link type="text/css" rel="stylesheet" href="css/console.css" />
        <link type="text/css" rel="stylesheet" href="css/jquery-ui-1.8.17.custom.css" />
        <script type="text/javascript" src="js/jquery.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.8.17.custom.min.js"></script>
        
        <div>
          <?php echo $word['word_monitor_preset_add']; ?>&nbsp;
          <input type="text" id="preset_name" value="" />&nbsp;
          <button onClick="ptz_preset('set',$('#preset_name').val())" style="font-size:10px"><?php echo $word['word_accept']; ?></button>
          <button onClick="ptz_preset('clear','all')" style="font-size:10px"><?php echo $word['word_monitor_preset_clear']; ?></button>
        </div>
        
        <div style="margin-top:10px; width:450px">
          <table border="0" width="100%" class="pop_table">
            <thead>
              <tr>
                <th width="50%"><?php echo $word['word_name']; ?></th>
                <th width="50%"><?php echo $word['word_operation']; ?></th>
              </tr>
            </thead>
            <tbody>
            <?php
			if($preset_arrs){
				foreach($preset_arrs as $preset_arr){
					?>
                    <tr>
                      <td><?php echo $preset_arr['Name']; ?></td>
                      <td><a href="javascript:;" onclick="ptz_preset('clear','<?php echo $preset_arr['Preset']; ?>')"><img src=images/ps03.png /></a></td>
                    </tr>
					<?php
				}
			}else{
				?>
                <tr><td colspan="2"><?php echo $word['word_none']; ?></td></tr>
                <?php
			}
			?>
            </tbody>
          </table>
        </div>
        
        <script type="text/javascript">
        $(document).ready(function(){
            $("button").button();
        });
        
        function ptz_preset(type,val){
            switch(type){
                
                case "set":
                    if(val){
                        if(confirm('<?php echo $word['word_monitor_preset_add_confirm']; ?>')){
							$("#preset_name").val("");
							
                            window.opener.ptz('preset','set',encodeURI(val));
                            window.opener.location.reload();
							
							window.location.reload();
                        }
                    }else{
                        alert('<?php echo $word['word_name_please']; ?>');
                    }
                break;
                
                case "clear":
                    if(val=="all"){
                        var conf='<?php echo $word['word_monitor_preset_clear_confirm']; ?>';
                    }else{
                        var conf='<?php echo $word['word_monitor_preset_del']; ?>';
                    }
                    
                    if(confirm(conf)){
                        window.opener.ptz('preset','clear',val);
                        window.opener.location.reload();
						
						window.location.reload();
                    }
                break;
            }
        }
        </script>        
        <?
	}
	
	//实时动态打开连接
	function Watch_connect_start($mid_str){
		global $System;
		$word=$System->Get_word();
		
		$monitor_arr=array();
		
		$is_stream=Get_db_one("select Is_stream from Diy");//流转发开关
		
		$mid_arr=explode(",",$mid_str);
		foreach($mid_arr as $mid){
			$m_arr=Get_db_arr("select * from Monitors where Id=".$mid);
			
			//判断是否要增加连接数
			$overtime=time()-$m_arr['Connectting'];
			if($overtime>=10){
				$m_arr['Views']++;
			}
			
			//判断主辅码流
			if($m_arr['Stream']==1){
				$connect_path=$m_arr['Path'];
			}else{
				$connect_path=$m_arr['Path2'];
			}
			
			//判断前端设备当前状态
			switch($m_arr['Function']){
				case "Nodect":
					if($is_stream==1){
						$connect_back=Shell_cmd("NVRCommandLine -C start -i 1000 -m ".$mid." -u '".$connect_path."'");//发送流转发开始连接命令
						switch($connect_back['back']){
							case 0:
								$is_connect=1;
							break;
							
							case 1:
								$is_connect=1;//命令重复
							break;
							
							case 15:
								$is_connect=0;//设备离线
							break;
							
							case 16:
								$is_connect=0;//设备离线
							break;
							
							default:
								$is_connect=0;//命令格式错误
							break;
						}
					}else{
						$is_connect=1;
					}
					$next_function="Watch";
				break;
				
				case "Watch":
					$is_connect=1;
					$next_function="Watch";
				break;
				
				case "Record":
					$is_connect=1;
					$next_function="SameTime";
				break;
				
				case "SameTime":
					$is_connect=1;
					$next_function="SameTime";
				break;
				
				default:
					$is_connect=0;
					$next_function="Nodect";
				break;
			}
			
			//判断是否已连接或连接成功
			if($is_connect==1){
				$monitor_path="rtsp://".$_SERVER['HTTP_HOST']."/".$mid;
				$state=$this->Get_monitor_function($next_function,1);
				mysql_query("update Monitors set Function='".$next_function."',Views=".$m_arr['Views']." where Id=".$mid);
			}else{
				$monitor_path="null";
				$state=$word['word_monitor_connect_start_failed'];
			}
			
			$monitor=array();
			$monitor['id']=$mid;
			$monitor['name']=$m_arr['Name'];
			if($is_stream==1){
				$monitor['path']=$monitor_path;
			}else{
				$monitor['path']=$connect_path;
			}
			$monitor['state']=$state;
			$monitor['function']=$next_function;
			$monitor['passport']=$m_arr['Passport'];
			$monitor['password']=$m_arr['Password'];
			$monitor['media']=$m_arr['Media'];
			$monitor['ptz_path']=$m_arr['Ptz_path'];
			$monitor['ptz_function']=$m_arr['Ptz_function'];
			$monitor['ptz_speed']=$m_arr['Ptz_speed'];
			$preset_arrs=Get_db_arrs("select Preset,Name from Presets where Mid=".$mid);
			if(!empty($preset_arrs)){
				$monitor['ptz_preset']=$preset_arrs;
			}else{
				$monitor['ptz_preset']="";
			}
			$monitor_arr[]=$monitor;			   
		}
		
		//sleep(3);
		
		echo json_encode($monitor_arr);
	}
	
	//实时动态关闭连接
	function Watch_connect_stop($mid_str){
		unset($_SESSION['is_turn']);
		
		$mid_arr=explode(",",$mid_str);
		foreach($mid_arr as $mid){
			$m_arr=Get_db_arr("select Function,Views from Monitors where Id=".$mid);
			
			//判断前端设备当前状态
			switch($m_arr['Function']){
				case "Nodect":
				
				break;
				
				case "Watch":
					if($m_arr['Views']<=1){
						Shell_cmd("NVRCommandLine -C stop -i 1000 -m ".$mid);//发送流转发停止连接命令
						mysql_query("update Monitors set Function='Nodect',Views=0 where Id=".$mid);
					}else{
						$m_arr['Views']--;
						mysql_query("update Monitors set Views=".$m_arr['Views']." where Id=".$mid);
					}
				break;
				
				case "Record":
				
				break;
				
				case "SameTime":
					if($m_arr['Views']<=1){
						mysql_query("update Monitors set Function='Record',Views=0 where Id=".$mid);
					}else{
						$m_arr['Views']--;
						mysql_query("update Monitors set Views=".$m_arr['Views']." where Id=".$mid);
					}
				break;
				
				default:
				
				break;
			}
		}
	}
	
	//设备发现
	function Monitor_discover($item,$data=false){
		global $System;
		$word=$System->Get_word();
		switch($item['type']){
			case "get_1":
				//第一步，获取设备地址
				Shell_cmd("NVRCommandLine -D discovery -i 1000");
				
				$mcache_list=array();
				$mcache_arrs=Get_db_arrs("select * from Mcache");
				mysql_query("truncate table Mcache");//清空表以放入处理过的新地址
				if($mcache_arrs){
					$ip_arr=array();
					foreach($mcache_arrs as $mcache_arr){
						$mcache=array();
						
						//IP
						$new_ip=$this->Address_filter($mcache_arr['Ip']);//处理错误的ip
						preg_match("/(?:\d+\.){3}(?:\d+)/",$new_ip,$ip);
						$m_arr=Get_db_arr("select Passport,Password from Monitors where Ip='".$ip[0]."'");//从monitor表读出相同ip的用户名密码
						if($m_arr){
							$mcache['ip']=$ip[0];
							$is_exists=1;
						}else{
							$mcache['ip']="<font color=blue>".$ip[0]."</font>";
							$is_exists=0;
						}
						
						//用户名密码
						$mcache['passport']="<input type='text' size='15' name='passport_".$mcache_arr['Id']."' class='pass' value='".$m_arr['Passport']."' />";
						$mcache['password']="<input type='text' size='15' name='password_".$mcache_arr['Id']."' class='pass' value='".$m_arr['Password']."' />";
						
						//过滤掉重复的
						if(!in_array($new_ip,$ip_arr)){
							$mcache_list[]=$mcache;
							$ip_arr[]=$new_ip;
							mysql_query("insert into Mcache (Ip,Is_exists) values ('".$new_ip."',".$is_exists.")");
						}
					}
				}
				sort($mcache_list);
				echo json_encode($mcache_list);
			break;
			
			case "set_1":
				//将用户名密码返回数据库
				foreach($data as $k=>$v){
					$k_arr=explode("_",$k);
					if($k_arr[0]=="passport"){
						mysql_query("update Mcache set Passport='".$v."' where Id=".$k_arr[1]);
					}else{
						mysql_query("update Mcache set Password='".$v."' where Id=".$k_arr[1]);
					}
				}
			break;
			
			case "get_2":
				//第二步，通过设备地址，获取厂家型号，RTSP地址，媒体地址，云台地址
				$mcache_arrs=Get_db_arrs("select * from Mcache");
				if($mcache_arrs){
					foreach($mcache_arrs as $mcache_arr){
						Shell_cmd("NVRCommandLine -D information -i 1000 -u '".$mcache_arr['Ip']."' -c '".$mcache_arr['Passport']."' -s '".$mcache_arr['Password']."'");//厂家型号
						Shell_cmd("NVRCommandLine -D rtspurl -i 1000 -d ".$mcache_arr['Id']." -u '".$mcache_arr['Ip']."' -c '".$mcache_arr['Passport']."' -s '".$mcache_arr['Password']."'");//rtsp地址
						Shell_cmd("NVRCommandLine -D mediauri -i 1000 -d ".$mcache_arr['Id']." -u '".$mcache_arr['Ip']."' -c '".$mcache_arr['Passport']."' -s '".$mcache_arr['Password']."'");//媒体地址
						Shell_cmd("NVRCommandLine -P getUri -i 1000 -d ".$mcache_arr['Id']." -u '".$mcache_arr['Ip']."' -a '".$mcache_arr['Passport']."' -b '".$mcache_arr['Password']."'");//云台地址
					}
				}
				//exit;
				
				//第三步，通过厂家型号，获取云台能力
				$mcache_arrs=Get_db_arrs("select * from Mcache");
				if($mcache_arrs){
					foreach($mcache_arrs as $mcache_arr){
						Shell_cmd("NVRCommandLine -P capability -i 1000 -d ".$mcache_arr['Id']." -M '".$mcache_arr['Factory']."' -S '".$mcache_arr['Model']."'");
					}
				}
				//exit;
				
				//获取monitor表的自动编号，以便自动命名
				$table_status=$this->Get_table_status();
				$number=$table_status['Auto_increment'];
				
				//向用户返回完整的设备发现信息
				$mcache_list=array();
				$mcache_arrs=Get_db_arrs("select * from Mcache order by Is_exists desc");
				if($mcache_arrs){
					$ip_arr=array();
					foreach($mcache_arrs as $mcache_arr){
						$mcache=array();
						
						//名称
						$name=$word['word_fee']."-".$number;//自动命名
						$mcache['name']="<input type='text' size='15' value='".$name."' name='name_".$mcache_arr['Id']."' />";
						
						//IP
						preg_match("/(?:\d+\.){3}(?:\d+)/",$mcache_arr['Ip'],$ip);
						$m_resolution=Get_db_one("select Resolution from Monitors where Ip='".$ip[0]."'");
						if($m_resolution){
							$mcache['ip']=$ip[0];
						}else{
							$mcache['ip']="<font color=blue>".$ip[0]."</font>";
						}
						
						//分辨率
						$select="<select name='resolution_".$mcache_arr['Id']."'>";
						$resolution_arr=$this->Get_resolution_arr();
						foreach($resolution_arr as $resolution){
							if($m_resolution==$resolution){
								$select.="<option value='".$resolution."' selected>".$resolution."</option>";
							}else{
								$select.="<option value='".$resolution."'>".$resolution."</option>";
							}
						}
						$select.="</select>";
						$mcache['resolution']=$select;
						
						//云台
						if($mcache_arr['Ptz']&&$mcache_arr['Media']&&$mcache_arr['Function']){
							$mcache['ptz']='<img src="../images/diskinuse.png" width=15 height=15 />';
						}else{
							$mcache['ptz']='<img src="../images/diskdead.png" width=15 height=15 />';
						}
						
						//厂家型号
						$mcache['factory']=substr($mcache_arr['Factory'],0,20);
						$mcache['model']=substr($mcache_arr['Model'],0,20);
						
						//复选框
						$mcache['checkbox']="<input type='checkbox' name='markDid[]' id='edit_arr' value='".$mcache_arr['Id']."' checked />";
						
						//过滤带掉没有RTSP的和IP重复的
						if($mcache_arr['Rtsp']&&!in_array($ip[0],$ip_arr)){
							$mcache_list[]=$mcache;
							$ip_arr[]=$ip[0];
							$number++;//自动编号+1
						}
					}
				}
				echo json_encode($mcache_list);
			break;
			
			case "set_2":
				//print_r($data);
				foreach($data as $k=>$v){
					$k_arr=explode("_",$k);
					
					switch($k_arr[0]){
						case "name":
							mysql_query("update Mcache set Name='".$v."' where Id=".$k_arr[1]);
						break;
						
						case "resolution":
							mysql_query("update Mcache set Resolution='".$v."' where Id=".$k_arr[1]);
						break;
						
						case "markDid":
							$markdid=$v;
						break;
						
						default:
						break;
					}
					
				}
				
				//导入
				$mcache_arrs=Get_db_arrs("select * from Mcache");
				if($mcache_arrs){
					foreach($mcache_arrs as $mcache_arr){
						//所选的
						if(in_array($mcache_arr['Id'],$markdid)){
							//过滤掉没有RTSP的
							if($mcache_arr['Rtsp']){
								preg_match("/(?:\d+\.){3}(?:\d+)/",$mcache_arr['Ip'],$ip);
								
								$name=$mcache_arr['Name'];
								$path=$mcache_arr['Rtsp'];
								$width=$this->Get_width_height($mcache_arr['Resolution'],"width");
								$height=$this->Get_width_height($mcache_arr['Resolution'],"height");
								$factory=substr($mcache_arr['Factory'],0,20);
								$model=substr($mcache_arr['Model'],0,20);
								$resolution=$mcache_arr['Resolution'];
								$ip=$ip[0];
								$passport=$mcache_arr['Passport'];
								$password=$mcache_arr['Password'];
								$media=$mcache_arr['Media'];
								$ptz_path=$mcache_arr['Ptz'];
								$ptz_function=$mcache_arr['Function'];
								
								mysql_query("insert into Monitors set Name='".$name."',Function='Nodect',Format='',Path='".$path."',Format2='',Path2='',Stream='1',Width='".$width."',Height='".$height."',WarmupCount='25',SectionLength='600',Factory='".$factory."', Model='".$model."',Resolution='".$resolution."',Ip='".$ip."',Passport='".$passport."',Password='".$password."',Mgroup=1,Plan_id=0,Media='".$media."',Ptz_path='".$ptz_path."',Ptz_function='".$ptz_function."'");
							}
						}
					}
				}
			break;
			
			case "cancel":
				mysql_query("truncate table Mcache");
			break;
			
			default:
			break;
		}
	}
	
	//处理错误的地址（IP错误如010.010.108.130）
	function Address_filter($old_ip){
		preg_match("/(?:\d+\.){3}(?:\d+)/",$old_ip,$ip);
		$ip_arr=explode(".",$ip[0]);
		$new_ip_arr=array();
		foreach($ip_arr as $v){
			$new_ip_arr[]=intval($v);
		}
		$new_ip=join(".",$new_ip_arr);
		$new_ip=preg_replace("/(?:\d+\.){3}(?:\d+)/",$new_ip,$old_ip);
		return $new_ip;
	}
	
	
	function Get_command(){
		$muti=Get_db_one("select Muti from Command");
		echo $muti;
	}
}
?>