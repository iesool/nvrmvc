<?php 
//磁盘
class Disk{	
	//发送命令
	function sendmsg($oper,$event,$raid,$disk,$act){
		$msg_arr=array("op"=>$oper,"event"=>$event,"param"=>array("raid"=>$raid,"disk"=>$disk),"action"=>$act);
		$msg=json_encode($msg_arr);
		
		$path="/tmp/pipe.d";
		File_top($path);//改变zmr.sock权限
		$socket=socket_create(AF_UNIX,SOCK_STREAM,0);//建立socket连接
		socket_connect($socket,$path);
		socket_send($socket,$msg,strlen($msg),0);
		socket_close($socket);//关闭socket连接
	}
	
	//运行python脚本,记录掉盘日志
	function Disk_monitor_log($state){
		//if($state==1){
			//file_put_contents("/usr/local/digitools/conf/monitor.conf","[mailmonitor]\n monitorlink = False\n monitordisk = False\n monitorraid = False\n mailinterval = 600\n [logmonitor]\n monitorlink = True\n monitordisk = True\n monitorraid = True\n loginterval = 5\n");//改写检查磁盘的程序的配置文件
			//Shell_cmd("python /usr/local/digitools/sysmonitor.py start");//运行检测磁盘
			//Shell_cmd("python /usr/local/digitools/libcommon/baseboard/startautocabinetnum.py");
		//}else{
			//Shell_cmd("python /usr/local/digitools/sysmonitor.py stop");//停止检测磁盘
		//}
	}
	
	//插入掉盘log
	/*function Disk_drop($log){
		if(file_exists("/usr/local/digitools/log/monitor.log")){
			$disk_drop=file("/usr/local/digitools/log/monitor.log");
			foreach($disk_drop as $tr){
				$td=explode(" ",$tr);
				array_push($log,$td[0]." ".$td[1]."   system   "."DISK".$td[5]." ".$td[7]." ".$td[8]."\n");
			}
			return $log;
		}else{
			return false;
		}
	}*/
	
	//自动挂载
	function Disk_auto_mount(){
		$mount_arrs=Get_db_arrs("select * from Mounts");
		foreach($mount_arrs as $mount){
			Shell_cmd("mount -t xfs -o noatime,logbufs=8,quota,usrquota,grpquota ".$mount['Lv']." ".$mount['Mount']);
		}
	}
	
	//获取RAID列表
	function Get_raid_list(){
		global $System;
		$word=$System->Get_word();
		
		$raid_list=array();
		
		$raiddevmatch='/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/';
		$raidsizematch='/\s+(\d+)\s+blocks/';
		$raidprocessmatch='(recovery|resync)';
		
		$mdstat = file("/proc/mdstat");
		
		if(count($mdstat)>=3){
			for($i=count($mdstat);$i>=0;$i--){
				if(preg_match($raiddevmatch,$mdstat[$i],$result)){
					$raid_disk_arr=explode(" ",$result[5]);
					foreach($raid_disk_arr as $v){
						preg_match("/sd(\w*)/",$v,$r);
						//$raid_word[$i][]=$this->Word_to_num($r[1]);
						$raid_word[$i][]=$r[1];
					}
					$rw[$i]=join(",",$raid_word[$i]);
					
					$raid_arr=array();
					//RAID名称
					$raid_arr['raid_name']="<a ".(preg_match($raidprocessmatch,$mdstat[$i+2])?"style=color:red":"style=color:green")." href=javascript:raid_show('$result[1]') onmousemove=raid_disk('$rw[$i]',1) onmouseout=raid_disk('$rw[$i]',0)>".$result[1]."</a>";
					//RAID大小
					if(preg_match($raidsizematch,$mdstat[$i+1],$raidsize_arr)){
						$raid_arr['raid_size']=round($raidsize_arr[1]/1024/1024,2)."G";
					}
					//RAID级别
					$raid_arr['raid_level']="RAID".$result[4];
					//RAID状态
					if(preg_match($raidprocessmatch,$mdstat[$i+2])){
						$resync=explode("%",$mdstat[$i+2]);
						$raid_arr['raid_state']=$resync[0]."%";
					}else{
						$raid_arr['raid_state']=$word['word_system_normal'];
					}
					$raid_list[]=$raid_arr;
				}
			}
		}
		return json_encode($raid_list);
	}
	
	//获得jobd(盘位个数)
	function Get_jobd(){
		/*if(file_exists("/usr/local/admin/softraid/jobd")){
			$jobd=file("/usr/local/admin/softraid/jobd");
			$jobd=$jobd[0];
		}else{
			if(file_exists("/usr/local/admin/")){
				$jobd=108;
			}else{*/
				$backinfo=Shell_cmd("python /usr/local/digitools/libcommon/baseboard/baseboard.py");
				$py_arr=json_decode($backinfo['back'],true);
				//$jobd=count($py_arr[1]).$py_arr[1][0]['diskcounttype'];
				$jobd=count($py_arr[1]).$py_arr[1][0];
				//$jobd=$py_arr[1];
			/*}
		}*/
		return $jobd;
	}
	
	//获取磁盘阵列
	function Get_disk_matrix(){
		$disk_matrix=array();
		$sd_arr=$this->get_disknameset();
		
		$jobd=$this->Get_jobd()/100;
		$box_num=intval($jobd);
		$sd_num=trim(($jobd-$box_num)*100);
		
		if(count($sd_arr)){
			$disk_id_dev_arr=$this->get_disk_id_dev_arr();//获得磁盘数组
			
			for($i=0;$i<$box_num;$i++){
				for($j=1;$j<=$sd_num;$j++){
					if($box_num==1){
						$k=$j;
					}else{
						$k=$i."-".$j;
					}
					foreach($disk_id_dev_arr as $v){
						$result=explode(",",$v);
						$disk_id=$result[0];
						$disk_dev=$result[1];
						
						if($disk_id==$k){
							$disk_stat=$this->get_disk_stat($disk_dev);
							
							if($disk_stat=='<img src="../images/init.gif" />'){
								$disk_matrix[$disk_id]='<span style="width:20%;line-height:20px; float:left; margin-left:5px">&nbsp;</span><span style="text-align:center;"><img src="../images/init.gif" /></span>';
							}else{
								$disk_size=$this->get_disk_size($disk_dev);
								
								$disk_matrix[$disk_id]='<span style="width:20%;line-height:20px; float:left; margin-left:5px">'.$disk_size.'G</span><span style="width:55px; text-align:center;  line-height:20px; margin-left:5px; float:left;">DISK'.$disk_id.'</span><span style="width:15px; height:15px; float:right; margin-right:12px; margin-top:2px;">'.$disk_stat.'</span>';
							}
							
							$disk_matrix[$disk_id].="+".$disk_dev;
						}
					}
				}
			}
		}
		return json_encode($disk_matrix);
	}
	
	
	//获取单个RAID信息
	function Get_raid_show($raid_name){	
		global $System;
		$word=$System->Get_word();
		$raid_arrs=Shell_popen("mdadm -D /dev/".$raid_name);
		
		foreach($raid_arrs as $v){
			$result=explode(" ",$v);
			for($i=0;$i<count($result);$i++){
				if(trim($result[$i])=="Creation"&&trim($result[$i+1]=="Time")){
					$raid_arr['raid_creat_time']=$result[5]." ".$result[6]." ".$result[7]." ".$result[8]." ".$result[9];
				}elseif(trim($result[$i])=="Raid"&&trim($result[$i+1]=="Level")){
					$raid_arr['raid_level']=$result[8];
				}elseif(trim($result[$i])=="Array"&&trim($result[$i+1]=="Size")){
					$raid_arr['raid_size']=$result[8].$result[9].$result[10].$result[11].$result[12];
					$raid_arr['raid_disk_size']="&nbsp;";
				}elseif(trim($result[$i])=="Raid"&&trim($result[$i+1]=="Devices")){
					$raid_arr['raid_disk_num']=$result[6];
				}elseif(trim($result[$i])=="Total"&&trim($result[$i+1]=="Devices")){
					$raid_arr['raid_all_disk']=$result[5];
				}elseif(trim($result[$i])=="Update"&&trim($result[$i+1]=="Time")){
					$raid_arr['raid_edit_time']=$result[7]." ".$result[8]." ".$result[9]." ".$result[10]." ".$result[11];
				}elseif(trim($result[$i])=="State"&&isset($result[$i+1])){
					if(trim($result[12])=="clean"||trim($result[12])=="active"){
						$raid_arr['raid_state']=$word['word_system_normal'];
					}else{
						$raid_arr['raid_state']="进行中";
					}
				}elseif(trim($result[$i])=="Active"&&trim($result[$i+1]=="Devices")){
					$raid_arr['raid_disk_use']=$result[4];
				}elseif(trim($result[$i])=="Working"&&trim($result[$i+1]=="Devices")){
					$raid_arr['raid_disk_used']=$result[3];
				}elseif(trim($result[$i])=="Failed"&&trim($result[$i+1]=="Devices")){
					$raid_arr['raid_disk_fault']=$result[4];
				}elseif(trim($result[$i])=="Chunk"){
					$raid_arr['raid_c_size']=$result[8];
				}elseif(preg_match("/sd(\w*)/",$result[$i],$r)){
					$disk_word[]=$this->Word_to_num($r[1]);
				}
			}
		}
		
		sort($disk_word);
		$raid_arr['raid_disk']=join(",",$disk_word);
		
		$raiddevmatch='/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/';
		$mdstat = file("/proc/mdstat");
		for($i=0;$i<count($mdstat);$i++){
			if(preg_match($raiddevmatch,$mdstat[$i],$result)){
				if(trim($result[1])==$raid_name){
					$raid_disk_arr=explode(" ",$result[5]);
					foreach($raid_disk_arr as $v){
						preg_match("/\((\w)\)/",$v,$sf);//匹配是否为热备盘或损坏
						//echo $sf[1];
						if(isset($sf[1])&&$sf[1]=="S"){
							//echo $v;
							preg_match("/sd(\w*)/",$v,$r);
							$hotspare[]=$this->Word_to_num($r[1]);
						}
					}
				}
			}
		}
		if(isset($hotspare)){
			sort($hotspare);
			$raid_arr['raid_hotspare']=join(",",$hotspare);
		}else{
			$raid_arr['raid_hotspare']="&nbsp;";
		}
		
		return json_encode($raid_arr);
	}
	
	//获得未做RAID的磁盘
	function Get_unraid_disk(){
		//获取RAID信息
		$mdstat = file("/proc/mdstat");
		$mdstat_num=count($mdstat);
		$raiddevmatch='/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/';
		
		for($i=0;$i<$mdstat_num;$i++){
			if(preg_match($raiddevmatch,$mdstat[$i],$result)){
				$md_code[]=$result[2];//RAID编号
				
				$raid_disk_arr=explode(" ",$result[5]);
				foreach($raid_disk_arr as $v){
					preg_match("/sd(\w*)\[/",$v,$r);
					$raid_word[]=$r[1]	;				 
				}
				$disk_raid_arr=array_merge_recursive($raid_word);//递归地合并一个或多个数组。
			}
		}
		
		//获取磁盘信息
		$disk_id_dev_arr=$this->get_disk_id_dev_arr();
		//print_r($this->just_sys_disk());exit;
		if(isset($disk_id_dev_arr)){
			//去掉做了RAID的磁盘
			foreach($disk_id_dev_arr as $v){
				$result=explode(",",$v);
				$disk_id=$result[0];
				$disk_dev=$result[1];
				if(isset($disk_raid_arr)){
					if(!in_array($disk_dev,$disk_raid_arr)){
						$disk_id_arr[]=$disk_id;
					}
				}else{
					$disk_id_arr[]=$disk_id;
				}
			}
		}
		if(isset($disk_id_arr)){
			sort($disk_id_arr);
			return json_encode($disk_id_arr);
		}
	}
	
	//获取做过的RAID的名称列表
	function Get_raid_name(){
		$raid_name_arr=array();
		$mdstat = file("/proc/mdstat");
		$raiddevmatch='/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/';
		
		foreach($mdstat as $v){
			if(preg_match($raiddevmatch,$v,$result)){
				$raid_name_arr[]=$result[1];
			}
		}
		
		if(isset($raid_name_arr)){
			sort($raid_name_arr);
			return json_encode($raid_name_arr);
		}
		
	}
	//获取做过的RAID包含的磁盘
	function Get_raid_disk($raid_name){
		$mdstat = file("/proc/mdstat");
		$mdstat_num=count($mdstat);
		$raiddevmatch='/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/';
		
		for($i=0;$i<$mdstat_num;$i++){
			if(preg_match($raiddevmatch,$mdstat[$i],$result)){
				if($result[1]==$raid_name){
					$raid_disk_arr=explode(" ",$result[5]);
					foreach($raid_disk_arr as $v){
						preg_match("/sd(\w*)\[/",$v,$r);
						$raid_disk[]=$r[1];	
					}
				}
			}
		}
		
		return $raid_disk;
	}
	//磁盘编号转字母
	function Num_to_word($disk_num){
		$disk_id_dev_arr=$this->get_disk_id_dev_arr();//获得磁盘数组
		foreach($disk_id_dev_arr as $v){
			$result=explode(",",$v);
			if($result[0]==$disk_num){
				$disk_word=$result[1];
			}
		}
		return $disk_word;
	}
	//磁盘字母转编号
	function Word_to_num($disk_word){
		$disk_id_dev_arr=$this->get_disk_id_dev_arr();//获得磁盘数组
		foreach($disk_id_dev_arr as $v){
			$result=explode(",",$v);
			if($result[1]==$disk_word){
				$disk_num=$result[0];
			}
		}
		return $disk_num;
	}
	
	//获得热备盘
	function Get_hotspare_disk(){
		$mdstat = file("/proc/mdstat");
		for($i=0;$i<count($mdstat);$i++){
			if(preg_match('/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/',$mdstat[$i],$result)){
				$raid_disk_arr=explode(" ",$result[5]);
				foreach($raid_disk_arr as $v){
					preg_match("/sd(\w*)\[/",$v,$r);
					preg_match("/\((\w)\)/",$v,$sf);//匹配是否为热备盘或损坏
					if($sf){
						$sp_word[$result[1]][]=$r[1];
					}
				}
			}
		}
		
		$disk_id_dev_arr=$this->get_disk_id_dev_arr();
		foreach(array_keys($sp_word) as $v){
			foreach($sp_word[$v] as $w){
				foreach($disk_id_dev_arr as $x){
					$result=explode(",",$x);
					$disk_id=$result[0];
					$disk_dev=$result[1];
					if($w==$disk_dev){
						$dd=$disk_id;
					}
				}
				$hotspare_disk[]=$v.":".$dd;
			}
		}
		
		if(isset($hotspare_disk)){
			sort($hotspare_disk);
			return json_encode($hotspare_disk);
		}
	}
	
	//获取未激活磁盘
	function Get_unactive_disk(){
		$mdstat = file("/proc/mdstat");
		$mdstat_num=count($mdstat);
		$raiddevmatch='/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/';
		
		for($i=0;$i<$mdstat_num;$i++){
			if(preg_match($raiddevmatch,$mdstat[$i],$result)){
				$md_code[]=$result[2];//RAID编号
				$raid_disk_arr=explode(" ",$result[5]);
				foreach($raid_disk_arr as $v){
					preg_match("/sd(\w*)\[/",$v,$r);
					$raid_word[]=$r[1]	;				 
				}
				$disk_word=array_merge_recursive($raid_word);//递归地合并一个或多个数组。
			}
		}
	
		//获取磁盘信息
		$disk=$this->get_disknameset();
		for($i=0;$i<count($disk);$i++){
			$sd=explode("sd",$disk[$i]);
			if(isset($sd[1])){
				$disk_arr[]=$sd[1];
			}
		}
		
		if(isset($disk_word)){
			$disk_arr=array_diff($disk_arr,$disk_word);//去掉已经被做RAID的磁盘
			sort($disk_arr);
		}
	
		foreach($disk_arr as $v){			
			$sd_arr=Shell_popen("mdadm -E /dev/sd".$v);
			
			//判断磁盘是否有RAID信息，有RAID信息的为未激活的
			if(trim($sd_arr[0])){
				
				foreach($sd_arr as $w){
					$result=explode(" ",$w);
					for($i=0;$i<count($result);$i++){
						//判断磁盘所在的RAID组
						if(trim($result[$i])=="Preferred"&&trim($result[$i+1]=="Minor")){
							$sd_raid=$result[$i+3];
						}
					}
				}
				
				$disk_arr2[]=$this->Word_to_num($v).":md".$sd_raid;
			}
		}
		
		if(isset($disk_arr2)){
			//sort($disk_arr2);
			return json_encode($disk_arr2);
		}
		
	}
	
	
	
	
	//创建RAID
	function Raid_creat($item){
		global $System;
		$word=$System->Get_word();
		
		foreach($item["selected_unraid_disk"] as $disk_num){
			$disk_word=$this->Num_to_word($disk_num);
			$disk_size_arr[]=$this->get_disk_size($disk_word);
			$sd_str_arr[]="/dev/sd".$disk_word;
			Shell_cmd("dd if=/dev/zero of=/dev/sd".$disk_word." bs=1M count=1");
		}
		
		$sd=join(" ",$sd_str_arr);//磁盘字符串
		$sd_num=count($sd_str_arr);//磁盘数
		
		//获得新RAID编号
		$mdstat = file("/proc/mdstat");
		$mdstat_num=count($mdstat);
		$raiddevmatch='/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/';
		
		for($i=0;$i<$mdstat_num;$i++){
			if(preg_match($raiddevmatch,$mdstat[$i],$result)){
				$md_code[]=$result[2];//RAID编号
			}
		}
		if(isset($md_code)){
			rsort($md_code);
			$md_code_max=$md_code[0];
			$md=$md_code_max+1;
		}else{
			$md=10;
		}
				
		//判断磁盘大小是否一致
		if(count(array_unique($disk_size_arr))!=1||$item['raid_level2']==1){
			$cmd="mdadm -C -c".$item['raid_chunk']." -l".$item['raid_level2']." -n".$sd_num." /dev/md".$md." ".$sd." -f<<
EOF
yes
EOF>>";
		}else{
			$cmd="mdadm -C -c".$item['raid_chunk']." -l".$item['raid_level2']." -n".$sd_num." /dev/md".$md." ".$sd." -f";
		}
		
		$rv=Shell_cmd($cmd);
		if($rv['rv']==1){
			echo 1;
		}else{
			Shell_cmd("mdadm -w /dev/md".$md);
			
			$System->System_log("normal","disk","op",$word['word_log_created_raid']."“md".$md."”");
		}
		
		//指示灯
		if(!file_exists("/usr/local/admin/")){
			$this->sendmsg("http","mdadm -C","/dev/md".$md,$item["selected_unraid_disk"],"createraid");
		}
	}
	
	//删除RAID
	function Raid_del($item){
		global $System;
		$word=$System->Get_word();
		
		$fail=array();
		$vactory=array();
		
		foreach($item['raid_del_list'] as $raid_name){
			$raid_disk=$this->Get_raid_disk($raid_name);
			$rv=Shell_cmd("mdadm -S /dev/".$raid_name);
			if($rv['rv']==1){
				$fail[]=$raid_name;
			}else{
				$vactory[]=$raid_name;
			}
			
			$raiddisks=array();
			foreach($raid_disk as $disk){
				Shell_cmd("mdadm --zero-super /dev/sd".$disk);
				
				$raiddisks[]=$this->Word_to_num($disk);
			}
			
			if(!file_exists("/usr/local/admin/")){
				$this->sendmsg("http","rm bitmap","/dev/".$raid_name,$raiddisks,"delraid");
				$this->sendmsg("http","mdadm zero-super","",$raiddisks,"cleardisk");
			}
		}
		if(!empty($fail)){
			echo join(",",$fail);
		}
		if(!empty($vactory)){
			$System->System_log("normal","disk","op",$word['word_log_delete_raid']."“".join(",",$vactory)."”");
		}
		
	}
	
	//创建热备盘
	function Hotspare_creat($item){
		global $System;
		$word=$System->Get_word();
		
		//raid0不能创建热备盘
		$raid_arr=json_decode($this->Get_raid_show($item['hotspare_raid']),true);
		if(trim($raid_arr['raid_level'])=="raid0"){
			echo 1;
			exit;
		}
		
		//开始创建
		foreach($item["selected_unhotspare_disk"] as $disk_num){
			$disk_word=$this->Num_to_word($disk_num);
			Shell_cmd("mdadm /dev/".$item['hotspare_raid']." -a /dev/sd".$disk_word);
		}
		
		//指示灯
		if(!file_exists("/usr/local/admin/")){
			$this->sendmsg("http","mdadm -add","/dev/".$item['hotspare_raid'],$item["selected_unhotspare_disk"],"sethotspare");
		}
		
		$System->System_log("normal","disk","op","RAID“".$item['hotspare_raid']."”".$word['word_log_created_hotspare'].join(",",$item["selected_unhotspare_disk"]));
	}
	
	//删除热备盘
	function Hotspare_del($item){
		global $System;
		$word=$System->Get_word();
		
		
		foreach($item['hotspare_disk'] as $v){
			$result=explode(":",$v);
			$disk_word=$this->Num_to_word($result[1]);
			Shell_cmd("mdadm /dev/".$result[0]." -r /dev/sd".$disk_word);
			Shell_cmd("mdadm --zero-super /dev/sd".$disk_word);
			
			if(!file_exists("/usr/local/admin/")){
				$this->sendmsg("http","mdadm --fail --remove","/dev/".$result[0],$result[1],"del_hotspare");
			}
		}
		
		$System->System_log("normal","disk","op",$word['word_log_deleted_hotspare']."“".join(",",$item["hotspare_disk"])."”");
	}
	
	//激活磁盘
	function Disk_active($item){
		foreach($item['selected_unactive_disk'] as $v){
			$result=explode(":",$v);
			$disk_word=$this->Num_to_word($result[0]);
			Shell_cmd("mdadm --zero-super /dev/sd".$disk_word);
			
			if(!file_exists("/usr/local/admin/")){
				$this->sendmsg("http","mdadm zero-super","",$result[0],"cleardisk");
			}
		}
	}
	
	//激活RAID
	function Raid_active(){
		Shell_cmd("mdadm -A --scan");//激活
		
		//如果是debian 6发灯
		if(!file_exists("/usr/local/admin/")){
			//获取RAID信息
			$mdstat = file("/proc/mdstat");
			$mdstat_num=count($mdstat);
			$raiddevmatch='/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/';
			
			for($i=0;$i<$mdstat_num;$i++){
				if(preg_match($raiddevmatch,$mdstat[$i],$result)){
					$md_code[]=$result[2];//RAID编号
					/*for($j=0;$j<count($result);$j++){
						echo $result[$j]."<br>";
					}*/
					//echo $result[5];
					$raid_disk_arr=explode(" ",$result[5]);
					foreach($raid_disk_arr as $v){
						preg_match("/sd(\w*)\[/",$v,$r);
						$raid_word[]=$r[1]	;				 
					}
					//sort($raid_word);
					//print_r($raid_word);
					$disk_word=array_merge_recursive($raid_word);//递归地合并一个或多个数组。
				}
			}
			
			//获取磁盘信息
			$disk=$this->get_disknameset();
			for($i=0;$i<count($disk);$i++){
				$sd=explode("sd",$disk[$i]);
				if(isset($sd[1])){
					$disk_arr[]=$sd[1];
				}
			}
			
			if(isset($disk_word)){
				$disk_arr=array_diff($disk_arr,$disk_word);//去掉已经被做RAID的磁盘
				sort($disk_arr);
			}
			
			//print_r($disk_arr);
			
			foreach($disk_arr as $v){
				$sd_arr=Shell_popen("mdadm -E /dev/sd".$v);
				
				//判断磁盘是否有RAID信息，有RAID信息的为未激活的
				if(trim($sd_arr[0])){
					
					foreach($sd_arr as $w){
						$result=explode(" ",$w);
						for($i=0;$i<count($result);$i++){
							//if(file_exists("/usr/local/admin/")){
								//判断磁盘所在的RAID组(debian5)
								//if(trim($result[$i])=="Preferred"&&trim($result[$i+1]=="Minor")){
									//$sd_raid=$result[$i+3];
								//}
							//}else{
								//判断磁盘所在的RAID组(debian6)
								if(trim($result[$i])=="Name"&&trim($result[$i+1]==":")){
									$sd_raid_arr=explode(":",$result[$i+2]);
									$sd_raid=$sd_raid_arr[1];
								}
							//}
						}
					}
					
					$disk_arr2[]=$v.":".$sd_raid;
				}
			}
			
			//print_r($disk_arr2);
			
			foreach($disk_arr2 as $v){
				$p=explode(":",$v);
				$selected_raid[]=$p[1];
			}
			
			$selected_raid=array_unique($selected_raid);
			
			foreach($selected_raid as $v){
				$s=array();
				$raiddisks=array();
				foreach($disk_arr2 as $w){
					$q=explode(":",$w);
					if($q[1]==$v){
						$s[]="/dev/sd".$q[0];
						$raiddisks[]=$this->Word_to_num($q[0]);
					}
				}
				$raid_name="/dev/md".trim($v);
				$selected_disk=join(" ",$s);
				
				//echo "mdadm -A ".$raid_name." ".$selected_disk."";
				//Shell_cmd("mdadm -A ".$raid_name." ".$selected_disk."");
				
				$this->sendmsg("http","mdadm -A",$raid_name,$raiddisks,"activeraid");//发灯
			}
		}
		
	}
	
	
	
	//获得主板信息
	function get_mb(){
		$mb=Shell_cmd("/usr/sbin/dmidecode | grep -m 1  Product |awk '{print $3}'");
		if($mb['back']=="X8SIE"||$mb['back']=="X8DTL"){
			$pci_path = "sas_address";
		}else{
			$pci_path = "phy_identifier";
		}
		return $pci_path;
	}
	#----------------------------------------
	# get diskname set
	#----------------------------------------
	function get_disknameset(){
		$diskmatch = "/sd\D+/";
		$disks=array();
		
		$sysdisk = $this->just_sys_disk();
		//$sysdisk="/dev/sda";
		$sysdiskname = str_replace('/dev/','',$sysdisk);
		
		$sysblocks=scandir("/sys/block");
		foreach($sysblocks as $sysblock){
			if(preg_match($diskmatch,$sysblock)&&$sysblock!=$sysdiskname){
				$disks[]=$sysblock;
			}
		}
		return $disks;
	}
	#-----------------------------------------
	# check the system disk
	#-----------------------------------------    
	function just_sys_disk(){
		if($this->Get_jobd()==108){
			$sys_arr=Shell_popen("pvscan");
			
			foreach($sys_arr as $v){
				if(preg_match("/sd(\S)/",$v,$result)){
					$sysdisk=$result[0];
				}
			}
			return "/dev/".$sysdisk;
		}else{
			return "/dev/sda";
		}
	}
	#-----------------------------------------
	# conver disk dev to disk slot
	# dev   : /dev/sdq
	# return: 6:0:0:0
	#-----------------------------------------
	function get_disk_slot_from_dev($diskdev){
		$diskslot = '-1:0:0:0';
		$diskname = str_replace('/dev/','',$diskdev);
		$diskslotmatch = "/.*\/(\d+:\d+:\d+:\d+)/";
		if(file_exists("/sys/block/".$diskname."/device")){
			if(is_link("/sys/block/".$diskname."/device")){
				$result = readlink("/sys/block/".$diskname."/device");
				preg_match($diskslotmatch,$result,$m);
				if(isset($m)){
					$diskslot = $m[1];
				}
			}
		}
		return $diskslot;
	}
	#-----------------------------------------
	# covert slot to portaddr
	# slot      : 6:0:2:0
	# portAddr  : end_device-7:6
	#-----------------------------------------
	function convert_disk_slot_to_port_addr($diskslot){
		$devicelist = scandir("/sys/class/sas_device/");
		foreach($devicelist as $eachdev){
			if(preg_match("/end_device-\d+\:\d+/",$eachdev)){
				$devsubdir = "/sys/class/sas_device/".$eachdev."/device";
				$targetlist = scandir($devsubdir);
				foreach($targetlist as $eachtarget){
					if(preg_match("/target\d+\:\d+\:\d+/",$eachtarget)){
						$targetsubdir = "/sys/class/sas_device/".$eachdev."/device/".$eachtarget;
						$slotlist = scandir($targetsubdir);
						foreach($slotlist as $eachslot){
							if($eachslot == $diskslot){
								//$portaddr=$eachdev;
								
								$pci_path=$this->get_mb();
								//echo $pci_path."<br>";
								
								$phy_identifier=file("/sys/class/sas_device/".$eachdev."/".$pci_path);
								$phy_identifier=$phy_identifier[0];
								
								if(trim($pci_path)=="sas_address"){
									$portaddr=$phy_identifier;
									//echo $portaddr;
								}else{
									$portaddr=$phy_identifier;
									
									$portaddr=explode(":",$eachdev);
									$count=count($portaddr)-1;
									$portaddr[$count]=$phy_identifier;
									$portaddr=trim(join(":",$portaddr));
								}
							}
						}
					}
				}
			}
		}
		return $portaddr;
	}
	
	#-----------------------------------------
	# split diskslot
	# slot : 6:0:0:0
	# return : 2:0:0:0
	#-----------------------------------------	
	function analysissysslot1($diskslot){
		if(preg_match("/\d+:\d+:\d+:\d+/",$diskslot)){
			$result=explode(":",$diskslot);
			$maxid=$result[0];
			array_shift($result);
			
			if($maxid>=6){
				$maxid=$maxid-6;
			}else{
				$maxid=$maxid+4;
			}
			
			array_unshift($result,$maxid);
			return join(":",$result);
		}
	}
	function analysissysslot2($diskslot){
		if(preg_match("/\d+:\d+:\d+:\d+/",$diskslot)){
			$result=explode(":",$diskslot);
			$maxid=$result[0];
			array_shift($result);
			
			if($maxid>=5){
				$maxid=$maxid-4;
			}else{
				$maxid=$maxid+5;
			}
			
			array_unshift($result,$maxid);
			return join(":",$result);
		}
	}
	function analysissysslot($diskslot){
		if(preg_match("/\d+:\d+:\d+:\d+/",$diskslot)){
			$result=explode(":",$diskslot);
			return $result[0];
		}
	}
	#-----------------------------------------
	# conver slot to disk id
	# slot   : 6:0:0:0
	# return: 13
	#-----------------------------------------
	function convert_disk_slot_to_disk_id($diskslot){
		if (file_exists('/sys/class/sas_device')){
			if(file_exists('/usr/local/admin/softraid/raidconfig/lib/sas_sn.php')){
				require('/usr/local/admin/softraid/raidconfig/lib/sas_sn.php');
				$portaddr=$this->convert_disk_slot_to_port_addr($diskslot);
				//echo $DISKNUM[trim($portaddr)];
				return $DISKNUM[trim($portaddr)];
			}
		}else{
			if(file_exists('/usr/local/admin/softraid/raidconfig/lib/sata_sn.php')){
				require('/usr/local/admin/softraid/raidconfig/lib/sata_sn.php');
				
				if(in_array($diskslot,$PORTNUM)){
					return $DISKNUM[$diskslot];
				}else{
					$sysdisk=$this->just_sys_disk();
					$sysdiskslot=$this->get_disk_slot_from_dev($sysdisk);
					return $DISKNUM[$sysdiskslot];
				}
			}
		}
	}
	#----------------------------------------
	# get disk_id_dev_arr
	#----------------------------------------
	function get_disk_id_dev_arr(){
		$disk_id_dev_arr=array();
		$sd_arr=$this->get_disknameset();
		if(count($sd_arr)){
			/*if(file_exists("/usr/local/admin/")){
				foreach($sd_arr as $dev){
					$result=explode("sd",$dev);
					$slot=$this->get_disk_slot_from_dev($dev);
					$disk_id_dev_arr[]=$this->convert_disk_slot_to_disk_id($slot).",".$result[1];
				}
			}else{*/
				$backinfo=Shell_cmd("python /usr/local/digitools/libcommon/baseboard/baseboard.py");
				$py_arr=json_decode($backinfo['back'],true);
				foreach($py_arr[0] as $key=>$py){
					$result=explode("sd",$key);
					$disk_id_dev_arr[]=$py.",".$result[1];
				}
			//}
			return $disk_id_dev_arr;
		}
	}
	
	#----------------------------------------
	# get disk_size
	#----------------------------------------
	function get_disk_size($disk){
		$disk_arr=file("/proc/partitions");
		for($i=0;$i<count($disk_arr);$i++){
			$result=explode(" ",$disk_arr[$i]);
			for($j=0;$j<count($result);$j++){
				if(preg_match("/sd(\w*)/",$result[$j],$r)){
					if(trim($r[1])==trim($disk)){
						$disk_size=trim($result[$j-1]);
						$disk_size = intval($disk_size)*1.024/1000/1000;
						$disk_size = strval(intval($disk_size));
					}
				}
			}
		}
		return $disk_size;
	}
	#----------------------------------------
	# get disk_stat
	#----------------------------------------
	function get_disk_stat($disk){
		$raiddevmatch='/^(md(\d+))\s+:\s+(active\s+raid(\d+)|inactive)\s+(.*)\n/';
		$raidprocessmatch='(recovery|resync)';
		
		$use=0;
		$sp=0;
		$f=0;
		$process=0;
		
		$mdstat = file("/proc/mdstat");
		for($i=0;$i<count($mdstat);$i++){
			if(preg_match($raiddevmatch,$mdstat[$i],$result)){
				$raid_disk_arr=explode(" ",$result[5]);
				foreach($raid_disk_arr as $v){
					preg_match("/\((\w)\)/",$v,$sf);//匹配是否为热备盘或损坏
					preg_match("/sd(\w*)\[/",$v,$r);
					if(trim($disk)==trim($r[1])){
						if($sf[1]=="S"){
							$sp++;
						}elseif($sf[1]=="F"){
							$f++;
						}elseif(preg_match($raidprocessmatch,$mdstat[$i+2])){
							$process++;
						}
						$use++;
					}
				}
			}
		}
				
		if($use==0){
			$sd_arr=Shell_popen("mdadm -E /dev/sd".$disk);
			
			if(isset($sd_arr[1])){
				if(count($mdstat)>=3){
					return '<img src="../images/diskdead.png" width="15" height="15" />';//判断为损坏
				}else{
					return '<img src="../images/diskinactive.png" width="15" height="15" />';//未激活
				}
			}else{
				return '<img src="../images/diskunuse.png" width="15" height="15" />';//未应用
			}
		}else{
			if($sp!=0){
				return '<img src="../images/diskspare.png" width="15" height="15" />';//判断为热盘
			}elseif($f!=0){
				return '<img src="../images/diskdead.png" width="15" height="15" />';//判断为损坏
			}else{
				if($process!=0){
					return '<img src="../images/init.gif" />';//进行中 
				}else{
					return '<img src="../images/diskinuse.png" width="15" height="15" />';//应用中
				}
			}
		}
	}
	
	
	//获取存储池列表
	function Get_vg_list(){
		global $System;
		$word=$System->Get_word();
		
		$vg_arr=Shell_popen("vgdisplay");
		$pv_arr=Shell_popen("pvdisplay");
		
		$vg_list=array();
		for($i=0;$i<count($vg_arr);$i++){
			$vg_head=explode(" ",$vg_arr[$i]);
			if($vg_head[3]=="Volume"&&$vg_head[4]=="group"){
				preg_match("/\w+(?<=$)/",$vg_arr[$i+1],$vg_name);
				if($vg_name[0]!="localhost"){
					$vg=array();
					$pv=array();
					
					for($p=0;$p<count($pv_arr);$p++){
						$pv_head=explode(" ",$pv_arr[$p]);
						if($pv_head[3]=="Physical"&&$pv_head[4]=="volume"){
							$pv_vg=explode(" ",$pv_arr[$p+2]);
							if(trim($pv_vg[18])==$vg_name[0]){
								$pv_num++;
								$vg_pv=explode(" ",$pv_arr[$p+1]);
								$pv[]=str_replace("/dev/","",$vg_pv[18]);
							}
						}
					}
					$pv_str=join(",",$pv);
					preg_match("/\s+(?:\d+.?\d+\s+\w+)/",$vg_arr[$i+14],$vg_size); 
					$free=explode("/",$vg_arr[$i+18]);
						  
					$vg['name']=$vg_name[0];
					$vg['op']='';
					$vg['op'].='<a href=javascript:vg_extend("'.$vg_name[0].'")><img src="../images/ps01.png" title="'.$word['word_disk_vg_extend'].'" /></a>&nbsp;';
					if(count($pv)>1){
						$vg['op'].='<a href=javascript:vg_reduce("'.$vg_name[0].'",'.json_encode($pv).')><img src="../images/ps02.png" title="'.$word['word_disk_vg_reduce'].'" /></a>&nbsp;';
					}else{
						$vg['op'].='<a href=javascript:alert("'.str_replace(" ","&nbsp;",$word['word_disk_vg_reduce_less_1']).'")><img src="../images/ps02.png" title="'.$word['word_disk_vg_reduce'].'" /></a>&nbsp;';
					}
					$vg['op'].='<a href=javascript:vg_del("'.$vg_name[0].'")><img src="../images/ps03.png" title="'.$word['word_delete'].'" /></a>&nbsp;';
					$vg['pv']=$pv_str;
					$vg['size']=$vg_size[0];
					$vg['free']=$free[2];
					
					$vg_list[]=$vg;
				}
			}
		}
		
		if(isset($vg_list)){
			//print_r($vg_list);
			//sort($vg_list);
			return json_encode($vg_list);
		}
	}
	
	//获取逻辑卷列表
	function Get_lv_list(){
		global $System;
		$word=$System->Get_word();
		
		$lv_arr=Shell_popen("lvdisplay");
		$mount_arr=Shell_popen("df");
		
		$lv_list=array();
		for($i=0;$i<count($lv_arr);$i++){
			$lv_head=explode(" ",$lv_arr[$i]);
			if($lv_head[3]=="Logical"&&$lv_head[4]=="volume"){
				preg_match("/\w+(?<=$)/",$lv_arr[$i+2],$vg_name);
				if($vg_name[0]!="localhost"){
					$lv=array();
							  
					$lv_name_arr=explode("/",$lv_arr[$i+1]);
					$lv_fullname=trim("/".$lv_name_arr[1]."/".$lv_name_arr[2]."/".$lv_name_arr[3]);
					preg_match("/\s+(?:\d+.?\d+\s+\w+)/",$lv_arr[$i+7],$lv_size);
					
					//扩展LUN参数
					$extend_lv_size=str_replace(" ","&nbsp;",trim($lv_size[0]));
					$vg_arr=json_decode($this->Get_vg_list());
					foreach($vg_arr as $vg){
						$a=array();
						foreach($vg as $key=>$v){
							$a[$key]=$v;
						}
						if($a['name']==$vg_name[0]){
							$extend_vg_size=str_replace(" ","&nbsp;",trim($a['free']));
						}
					}
					
					//获取挂载点
					global $System;
					$mount_point=false;
					$mount_percent=false;
					if(in_array("/dev/mapper/".$vg_name[0]."-".$lv_name_arr[3],$mount_arr)){
						for($m=0;$m<count($mount_arr);$m++){
							if($mount_arr[$m]=="/dev/mapper/".$vg_name[0]."-".$lv_name_arr[3]){
								$mount_p_arr=explode(" ",$mount_arr[$m+1]);
								foreach($mount_p_arr as $mount_point){
									if(strrchr($mount_point,"/")){
										$mount_path=trim($mount_point);
										$mount_percent=$System->Get_disk_percent($mount_path)."%";
										$gid=str_replace(EVENT_PATH,"",$mount_path);
										$mount_point=Get_db_one("select Name from Groups where Id=".$gid);
									}
								}
							}
						}
					}else{
						$mount_point="&nbsp;";
						$mount_percent="&nbsp;";
					}
					
					
					$lv['name']=$lv_name_arr[3];
					$lv['op']='';
					$lv['op'].='<a href=javascript:lv_extend("'.$lv_fullname.'","'.$extend_lv_size.'","'.$extend_vg_size.'")><img src="../images/ps01.png" title="'.$word['word_disk_lv_extend'].'" /></a>&nbsp;';
					if($mount_point=="&nbsp;"){
						$lv['op'].='<a href=javascript:lv_del("'.$lv_fullname.'")><img src="../images/ps03.png" title="'.$word['word_delete'].'" /></a>&nbsp;';
						$lv['op'].='<a href=javascript:lv_mkfs("'.$lv_fullname.'")><img src="../images/ps04.png" title="'.$word['word_disk_mkfs'].'" /></a>&nbsp;';
						$lv['op'].='<a href=javascript:lv_mount("'.$lv_fullname.'")><img src="../images/ps05.png" title="'.$word['word_disk_mount'].'" /></a>';
					}else{
						$lv['op'].='<a href=javascript:alert("'.str_replace(" ","&nbsp;",$word['word_disk_umount_first']).'")><img src="../images/ps03.png" title="'.$word['word_delete'].'" /></a>&nbsp;';
						$lv['op'].='<a href=javascript:alert("'.str_replace(" ","&nbsp;",$word['word_disk_mkfs_cant']).'")><img src="../images/ps04.png" title="'.$word['word_disk_mkfs'].'" /></a>&nbsp;';
						$lv['op'].='<a href=javascript:lv_umount("'.$mount_path.'","'.$lv_fullname.'","'.$vg_name[0].'")><img src="../images/ps06.png" title="'.$word['word_disk_umount'].'" /></a>';
					}
					$lv['vg']=$vg_name[0];
					$lv['size']=$lv_size[0];
					$lv['mount']=$mount_point;
					$lv['df']=$mount_percent;
					
					$lv_list[]=$lv;
				}
			}
		}
		if(isset($lv_list)){
			//print_r($lv_list);
			//sort($lv_list);
			return json_encode($lv_list);
		}
	}
	
	//获取PV列表
	function Get_pv_list(){
		$raid_arr=json_decode($this->Get_raid_name());
		$pv_arr=Shell_popen("pvdisplay");
		
		for($p=0;$p<count($pv_arr);$p++){
			if(trim($pv_arr[$p])=="--- Physical volume ---"){
				$vg_pv=explode(" ",$pv_arr[$p+1]);
				$pv_vg=explode(" ",$pv_arr[$p+2]);
				if(trim($pv_vg[18])!="localhost"){
					$md_arr2[]=str_replace("/dev/","",trim($vg_pv[18]));
				}
			}
		}
		
		if(isset($raid_arr)){
			if(isset($md_arr2)){
				$vg_md=array_diff($raid_arr,$md_arr2);
			}else{
				$vg_md=$raid_arr;
			}
		}
		if(isset($vg_md)){
			return json_encode($vg_md);
		}
	}
	
	//获取未挂载组名称
	function Get_umount_groups(){
		$group_arrs=array();
		
		$mount_arr=Shell_popen("mount");
		$group_mounts=Get_db_arrs("select Mount_Path,Name,Is_mount from Groups");
		
		foreach($group_mounts as $group_mount){
			$key=0;
			//遍历所有挂载点数组，看组的挂载点是否存在与挂载点数组中
			foreach($mount_arr as $v){
				if(strchr($v,$group_mount['Mount_Path'])){
					$key++;
				}
			}
			//如果不存在于所有挂载点数组中，或组挂载点为未挂载状态
			if($key==0||$group_mount['Is_mount']==0){
				$g=array();
				$g['Mount_Path']=$group_mount['Mount_Path'];
				$g['Name']=$group_mount['Name'];
				$group_arrs[]=$g;
			}
		}
		
		return json_encode($group_arrs);
	}
	
	
	//创建存储池
	function Vg_creat($item){
		global $System;
		$word=$System->Get_word();
		
		foreach($item['pv_list'] as $v){
			Shell_cmd("dd if=/dev/zero of=".$item['pv_list']." bs=1M count=1");
			Shell_cmd("pvcreate ".$v."");
		}
		$pv_str=join(" ",$item['pv_list']);
		$backinfo=Shell_cmd("vgcreate -s ".$item['pe_size'].$item['pe_unit']." ".$item['vg_name']." ".$pv_str."");
		if($backinfo['rv']==0){
			$System->System_log("normal","disk","op",$word['word_log_created_vg']."“".$item['vg_name']."”");
		}
		echo $backinfo['rv'];
	}
	//删除存储池
	function Vg_del($vg_name){
		global $System;
		$word=$System->Get_word();
		
		$pv_arr=Shell_popen("pvdisplay");
		for($p=0;$p<count($pv_arr);$p++){
			$pv_head=explode(" ",$pv_arr[$p]);
			if($pv_head[3]=="Physical"&&$pv_head[4]=="volume"){
				$pv_vg=explode(" ",$pv_arr[$p+2]);
				if(trim($pv_vg[18])==$vg_name){
					$vg_pv=explode(" ",$pv_arr[$p+1]);
					$pv_remove[]=$vg_pv[18];
				}
			}
		}
		$backinfo=Shell_cmd("vgremove ".$vg_name."");
		foreach($pv_remove as $v){
			Shell_cmd("pvremove ".$v."");
		}
		if($backinfo['rv']==0){
			$System->System_log("normal","disk","op",$word['word_log_delete_vg']."“".$vg_name."”");
		}
		echo $backinfo['back'];
	}
	//扩展存储池
	function Vg_extend($item){
		global $System;
		$word=$System->Get_word();
		
		Shell_cmd("pvcreate ".$item['free_pv_list']);
		$backinfo=Shell_cmd("vgextend ".$item['extend_vg_name']." ".$item['free_pv_list']);
		if($backinfo['rv']==0){
			$System->System_log("normal","disk","op",$word['word_log_extended_vg']."“".$item['extend_vg_name']."”，".$word['word_log_extend_pv_is']."“".$item['free_pv_list']."”");
		}
	}
	//缩减存储池
	function Vg_reduce($item){
		global $System;
		$word=$System->Get_word();
		
		$backinfo=Shell_cmd("vgreduce ".$item['reduce_vg_name']." ".trim($item['used_pv_list']));
		if($backinfo['rv']==0){
			Shell_cmd("pvremove ".trim($item['used_pv_list']));
			$System->System_log("normal","disk","op",$word['word_log_reduced_vg']."“".$item['reduce_vg_name']."”，".$word['word_log_reduce_pv_is']."“".trim($item['used_pv_list'])."”");
		}
		echo $backinfo['rv'];
	}
		
	//创建LUN
	function Lv_creat($item){
		global $System;
		$word=$System->Get_word();
		
		$backinfo=Shell_cmd("lvcreate -L ".$item['lv_size'].$item['lv_unit']." -n ".$item['lv_name']." ".$item['vg_list']);
		if($backinfo['rv']==0){
			$System->System_log("normal","disk","op",$word['word_log_created_lv']."“".$item['lv_name']."”");
		}
		echo $backinfo['rv'];
	}
	function Lv_name($lv_name){
		$lv_name_arr=explode("/",$lv_name);
		return $lv_name_arr[3];
	}
	//删除LUN
	function Lv_del($lv_name){
		global $System;
		$word=$System->Get_word();
		
		$backinfo=Shell_cmd("lvremove ".$lv_name." -f");
		if($backinfo['rv']==0){
			$System->System_log("normal","disk","op",$word['word_log_deleted_lv']."“".$this->Lv_name($lv_name)."”");
		}
		echo $backinfo['rv'];
	}
	//扩展LUN
	function Lv_extend($item){
		global $System;
		$word=$System->Get_word();
		
		$backinfo=Shell_cmd("lvextend -L +".$item['extend_lv_size'].$item['extend_lv_unit']." ".$item['extend_lv_name']);
		
		//如果该LUN挂载，则扩展XFS文件系统
		$mount=Get_db_one("select Mount from Mounts where Lv='".$item['extend_lv_name']."'");
		if($mount){
			Shell_cmd("xfs_growfs ".$mount);
		}
		if($backinfo['rv']==0){
			$System->System_log("normal","disk","op",$word['word_log_extended_lv']."“".$this->Lv_name($item['extend_lv_name'])."”，".$word['word_log_extend_size_is']."".$item['extend_lv_size'].$item['extend_lv_unit']);
		}
		echo $backinfo['rv'];
	}
	//创建文件系统
	function Lv_mkfs($lv_name){
		global $System;
		$word=$System->Get_word();
		
		$backinfo=Shell_cmd("mkfs.xfs -f ".$lv_name);
		if($backinfo['rv']==0){
			$System->System_log("normal","disk","op","LUN“".$this->Lv_name($lv_name)."”".$word['word_log_created_xfs']);
		}
	}
	//创建映射（挂载）
	function Lv_mount($lv_name,$item){
		global $System;
		$word=$System->Get_word();
		
		$lv_name_arr=explode("/",$lv_name);
		$vg_name=$lv_name_arr[2];
		
		if(!file_exists($item['mount_path'])){
			Make_file($item['mount_path']);
		}
		
		$backinfo=Shell_cmd("mount -t xfs -o noatime,logbufs=8,quota,usrquota,grpquota ".$lv_name." ".$item['mount_path']);
		if($backinfo['rv']==0){
			mysql_query("insert into Mounts set Vg='".$vg_name."',Lv='".$lv_name."',Mount='".$item['mount_path']."'");
			mysql_query("update Groups set Is_mount=1 where Mount_path='".$item['mount_path']."'");
			
			$gname=Get_db_one("select Name from Groups where Mount_Path='".$item['mount_path']."'");
			$System->System_log("normal","disk","op","LUN“".$this->Lv_name($lv_name)."”".$word['word_log_mounted_to']."“".$gname."”");
		}
		echo $backinfo['rv'];
	}
	//删除映射（卸载）
	function Lv_umount($mount_path){
		global $System;
		$word=$System->Get_word();
		
		$backinfo=Shell_cmd("umount ".$mount_path);
		if($backinfo['rv']==0){
			mysql_query("delete from Mounts where Mount='".$mount_path."'");
			mysql_query("update Groups set Is_mount=0 where Mount_Path='".$mount_path."'");
			
			$gname=Get_db_one("select Name from Groups where Mount_Path='".$mount_path."'");
			$System->System_log("normal","disk","op","“".$gname."”".$word['word_log_mount_deleted']);
		}
		echo $backinfo['rv'];
	}

}
?>