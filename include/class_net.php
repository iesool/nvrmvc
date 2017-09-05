<?php 
//网络
class Net{
	
	public $interfaces;
	public $hostpath;
	public $dnspath;
	
	//构造函数
	function __construct(){
		$this->interfaces="/etc/network/interfaces";
		$this->hostpath="/etc/hostname";
		$this->dnspath="/etc/resolv.conf";
		File_top($this->interfaces);
		File_top($this->hostpath);
		File_top($this->dnspath);
	}
	
	//网络重启
	function Net_restart(){
		global $System;
		$word=$System->Get_word();
		
		$net_arrs=$this->boot_interface();
		$rc_local=file('/etc/rc.local');
		$rc_local[(count($rc_local)-1)]='';
		
		
		//删除绑定
		$bond_exists=0;
		foreach($net_arrs as $net){
			if (preg_match("/bond/",$net['fullname'])) {
				$bond_exists=1;
				break;
			}
		}
		if($bond_exists==0){
			Shell_cmd("rmmod bonding");
			
			//删除rc.local里的ifenslave
			foreach($rc_local as $key=>$rc){
				if(preg_match("/ifenslave/",$rc)){
					$rc_local[$key]='';
				}
			}
			file_put_contents('/etc/rc.local',$rc_local);
		}
		
		//重启网络
		Shell_cmd("service networking stop");
		$rv=Shell_cmd("service networking start");
			
		if($rv['rv']==0){
			//判断rc.local里是否有ifenslave
			$ifenslave=0;
			foreach($rc_local as $key=>$rc){
				if(preg_match("/ifenslave/",$rc)){
					$ifenslave=1;
					break;
				}
			}
			//绑定
			foreach($net_arrs as $net){
				if (preg_match("/bond/",$net['fullname'])) {
					Shell_cmd("/sbin/ifenslave -d ".$net['fullname']." ".join(" ",$net['bondlist']));
					Shell_cmd("/sbin/ifenslave ".$net['fullname']." ".join(" ",$net['bondlist']));
					
					array_push($rc_local,"/sbin/ifenslave -d ".$net['fullname']." ".join(" ",$net['bondlist'])."\n");
					array_push($rc_local,"/sbin/ifenslave ".$net['fullname']." ".join(" ",$net['bondlist'])."\n");
				}
				Shell_cmd("route add -net 224.0.0.0 netmask 240.0.0.0 dev ".$net['fullname']);//ONVIF
			}
			array_push($rc_local,'exit 0');
			
			//往rc.local里的写ifenslave
			if($ifenslave==0){
				file_put_contents('/etc/rc.local',$rc_local);
			}

			$System->System_log("normal","net","op",$word['word_log_edited_net_setting']);
			return true;
		}else{
			$System->System_log("error","net","op",$word['word_log_edited_net_setting_failed']);
			return false;
		}
	}



	//获取网卡列表
	function Net_arrs(){
		$net_arrs=$this->boot_interface();
		//print_r($net_arrs);exit;
		$result_arrs=array();
		foreach($net_arrs as $net){
			$fullname="<a href='javascript:netcard(".$net['index'].")'>".$net['fullname']."</a>";
			
			if (preg_match("/bond/",$net['fullname'])) {
				$class="bond";
				sort($net['bondlist']);
				foreach ($net['bondlist'] as $val){
					$fullname.= "&nbsp;<span class=\"blist\">$val</span>";	
				}
				$fullname.="&nbsp;<a href=javascript:bond_del('".$net['fullname']."')>删除</a>";
			}else{
				if($net['virtual']){
					$fullname="<a href=javascript:virtual_edit('".$net['fullname']."',".$net['index'].")>".$net['fullname']."</a>";
					$class='Ethernet(虚拟)';
					$fullname.="&nbsp;<a href=javascript:virtual_del('".$net['fullname']."')>删除</a>";
				}else{
					$class='Ethernet';
				}
			}
			if($net['dhcp']){
				$net['address']="自动分配";
				$net['netmask']="自动分配";
			}
			
			$net_arr=array("fullname"=>$fullname,"class"=>$class,"address"=>$net['address'],"netmask"=>$net['netmask'],"up"=>$net['up']);
			$result_arrs[]=$net_arr;
		}
		return json_encode($result_arrs);
	}



	//获取单个网卡数组
	function Get_netcard_arr($index){
		$net_arrs=$this->boot_interface();
		$result_arr=array();
		foreach($net_arrs as $net){
			if($net['index']==$index){
				$net['vcount']=$this->Get_virtual_count($index);
				$result_arr=$net;
			}
		}
		return json_encode($result_arr);
	}
	//保存单个网卡配置
	function saveNetcard($item){
		
		$pattern    = "/#?auto\s+".$item['netcard_fullname']."[^:].*?(?=#auto|auto|$)/is";		
		$file       = file_get_contents($this->interfaces);		
		$newsetting = '';
		
		if (preg_match($pattern,$file,$match)){
			
			$newsetting = $match[0];
			
			if ($item['ip_mode'] == 0)
			{
				//如果是自动分配
				$newsetting = preg_replace("/static/i","dhcp",$newsetting);
				$newsetting = preg_replace("/address\s+(?:[0-9]{1,3}\.){3}[0-9]{1,3}\n?/i","",$newsetting);
				$newsetting = preg_replace("/netmask\s+(?:[0-9]{1,3}\.){3}[0-9]{1,3}\n?/i","",$newsetting);
				$newsetting = preg_replace("/gateway\s+(?:[0-9]{1,3}\.){3}[0-9]{1,3}\n?/i","",$newsetting);				
			}
			else if ($item['ip_mode'] == 1)
			{	
				//如果是指定IP
				$newsetting = ($item['up'] == '1' ? 'auto ' : '#auto ').$item['netcard_fullname']."\n".
								"iface ".$item['netcard_fullname']." inet static\n".
								"address ".$item['address']."\n".
								"netmask ".$item['netmask']."\n".
								(trim($item['gateway']) == '' ? "#gateway \n\n" : ("gateway ".$item['gateway']."\n\n"));
								
				if (preg_match("/bond/",$item['netcard_fullname']))
				{
					// 如果是网卡绑定
					$newsetting = ($item['up'] == '1' ? 'auto ' : '#auto ').$item['netcard_fullname']."\n".
								"iface ".$item['netcard_fullname']." inet static\n".
								"address ".$item['address']."\n".
								"netmask ".$item['netmask']."\n";
								
					if (trim($item['gateway']) != ''){
						$newsetting .="gateway ".$item['gateway']."\n";
					}
						
					preg_match("/slaves.*?bond_updelay\s+200/is",$match[0],$bond);
					$newsetting .= $bond[0]."\n";
				}
			}
			
			if ($item['up'] == '1'){
				$newsetting = preg_replace("/#?auto/i","auto",$newsetting);
			}else{
				$newsetting = preg_replace("/#?auto/i","#auto",$newsetting);
			}
			
			if ($file = preg_replace($pattern,$newsetting,$file)){
				file_put_contents($this->interfaces,$file);					  
			}
		
		}
		
	}



	//新建虚拟接口
	function save_virtual_interface($item)
	{
			
		$handle = fopen($this->interfaces,"ab");
		
		fwrite($handle,"\n\n");
		if ($item['virtual_up'] == 1){
			fwrite($handle,"auto ".$item['virtual_first_name'].":".$item["virtual_last_name"]."\n");
		}else{
			fwrite($handle,"#auto ".$item['virtual_first_name'].":".$item["virtual_last_name"]."\n");
		}
		fwrite($handle,"iface ".$item['virtual_first_name'].":".$item['virtual_last_name']." inet static\n");
		fwrite($handle,"address ".$item['virtual_address']."\n");
		fwrite($handle,"netmask ".$item['virtual_netmask']."\n");
		fclose($handle);
	}
	//修改虚拟接口
	function Virtual_edit($item){
		$item["virtual_last_name"]=$item['virtual_last_name_2'];
		$line     = file($this->interfaces);
		$handle   = fopen($this->interfaces,"wb");
		$fullname = $item['virtual_first_name'].":".$item['virtual_last_name'];
		$pattern  = "/".$fullname."$/i";
		
		for ($index = 0; $index < count($line); ++$index)
		{
			// 逐行处理文件
			if (preg_match($pattern,$line[$index]) != null)
			{
				// 已经找到了，直接删除
				$index += 4;
			}
			else
			{
				// 如果不是待处理，则将内容原样写回文件
				fwrite($handle,$line[$index]);
			}
		}
		fclose($handle);
		
		$this->save_virtual_interface($item);
	}
	//验证虚拟接口名称是否存在
	function Virtual_name_check($firt_name,$last_name){
		$boot = $this->boot_interface();
		foreach ($boot as $item)
		{
			if ($item['name'] == $firt_name && $item['virtual'] == $last_name){
				echo 1;
			}
		}
	}
	//获取虚拟接口数组
	function Get_virtual_arr($name,$index){
		$result_arr=array();
		if (preg_match("/^(?P<eth>\w+):(?P<num>\d+)$/i",$name))
		{
			$boot=$this->boot_interface();
			$result_arr['up'] = $boot[$index]['up'];
			$result_arr['address'] = $boot[$index]['address'];
			$result_arr['netmask'] = $boot[$index]['netmask'];			
		}
		return json_encode($result_arr);
	}
	//求虚拟接口数
	function Get_virtual_count($index){
		$boot=$this->boot_interface();
		$tmp_boot = $boot[$index];
		$count = 0;

		if ( $tmp_boot && $tmp_boot['virtual'] == '')
		{
			foreach($boot as $item)
			{
				if ($item['virtual'] != '' && $item['name'] == $tmp_boot['name'])
					$count++;
			}
		}
		return $count;
	}
	//删除虚拟接口
	function Virtual_del($ethname){
		$pattern = "/#?auto\s+$ethname.*?(?=#auto|auto|$)/is";	
		$file    = file_get_contents($this->interfaces);	
		$file    = preg_replace($pattern,"",$file);
		file_put_contents($this->interfaces,$file);
		Shell_cmd("ifconfig ".$ethname." down");
	}
	



	//获取未绑定网卡名称列表
	function Get_netcard_list(){
		$a=$this->getNetCardInfo();
		$b=$this->getBondEthList();
		$c=array_diff($a,$b);
		return $c;
	}
	// 取得所有网卡列表
	function getNetCardInfo()
	{
		$filesContents	= file_get_contents($this->interfaces);	
		preg_match_all("/(?:#auto|auto)\s+(eth\d+[^:])/i",$filesContents,$matchs);		
		
		if (count($matchs) > 1)
		{
			$temp = array();
			foreach($matchs[1] as $value)
				array_push($temp,trim($value));
			return $temp;
		}
		
		return null;
	}
	// 取得目前已经绑定了网卡信息	
	function getBondCardInfo()
	{		
		$pattern = "/#?auto\s+bond\d+.*?(?=auto|#auto|$)/is";
		$retarry = array();
		
		$file    = file_get_contents($this->interfaces);
		preg_match_all($pattern,$file,$matches);
			
		if (count($matches[0]) > 0)
		{
			foreach($matches[0] as $item)
			{				
				// #?auto\s+(?P<name>bond\d+)|slaves\s+(?P<ethlist>[a-zA-Z0-9 ]+)|bond_mode\s+(?P<mode>\d+)
				// #?auto\s+(bond\d+)|slaves\s+([a-zA-Z0-9 ]+)|bond_mode\s+(\d+)
				preg_match_all("/#?auto\s+(?P<name>bond\d+)|slaves\s+(?P<ethlist>[a-zA-Z0-9 ]+)|bond_mode\s+(?P<mode>\d+)/i",
								$item,
								$submatches);				
				$temp = array();
				// echo $submatches[0][1]."<br/>";
				if (count($submatches[0]) > 0)
				{
					$temp['name']    = preg_replace("/#?auto\s+/i","",$submatches[0][0]);
					$temp['ethlist'] = preg_replace("/slaves\s+/i","",$submatches[0][1]);
					$temp['mode']    = preg_replace("/bond_mode\s+/i","",$submatches[0][2]);
					array_push($retarry,$temp);						
				}
			}
		}
		
		return $retarry;
	}
	// 取得已经被绑定的网卡数据
	function getBondEthList()
	{
		$temp = $this->getBondCardInfo();
		$ret  = array();
		
		if (count($temp) > 0)
		{
			foreach($temp as $item)
			{
				$tempret = preg_split("/\s+/",$item['ethlist']);
				foreach($tempret as $value)
				{
					array_push($ret,trim($value));		
				}
			}
		}
		
		return array_unique($ret);
	}
	//删除绑定
	function deleteBond($name)
	{
		// #?auto\s+
		$pattern = "/#?auto\s+".$name.".*?(?=#auto|auto|$)/is";
		$file    = file_get_contents($this->interfaces);
		
		$file    = preg_replace($pattern,"",$file);
		
		file_put_contents($this->interfaces,$file);
		
	}
	  // 生成bond号
	function getBondID()
	{
		$temp = $this->getBondCardInfo();
		if (count($temp) > 0)
		{
			$tempid = "bond".count($temp);
			return $tempid;
		}
		return "bond0";
	}
	//保存绑定
	function saveBond($item)
	{
		$name=$this->getBondID();
		$contents =  file_get_contents($this->interfaces);
		$string   = "\nauto ".$name."\n".
				    "iface ".$name." inet static\n";		
		
		// regex:/#?auto\s+
		$pattern  = "/#?auto\s+".$item['whichip']."[^:].*?(?=#auto|auto|$)/is";
					
		preg_match($pattern,$contents,$match);
		
		// 如果是DHCP形式，则通过ifconfig命令获取动态地址、否则，从配置文件中读取静态地址
		if ( preg_match("/static/",$match[0]) != null )
		{
			preg_match_all("/(?<addr>address\s+(?:[0-9]{1,3}\.){3}[0-9]{1,3}\n?)|(?<net>netmask\s+(?:[0-9]{1,3}\.){3}[0-9]{1,3}\n?)|".
					  "(?<gw>gateway\s+(?:[0-9]{1,3}\.){3}[0-9]{1,3}\n?)/is",
							$match[0],$matches);			
			
			//$string = $string.$matches['addr'][0].$matches['net'][1].$matches['gw'][2]."slaves ";
			//$string = $string.$matches['addr'][0].$matches['net'][1]."slaves ";
			if (isset($matches['addr'][0]))
				$string .= $matches['addr'][0];
			if (isset($matches['net'][1]))
				$string .= $matches['net'][1];
			if (isset($matches['gw'][3]))
				$string .= $matches['gw'][3];
			$string .= "slaves ";
		}
		else
		{			
			$command = "/sbin/ifconfig ".$item['whichip']." | /bin/sed -n '2p'";
			exec($command,$output);	
			preg_match("/inet addr:(\S+)/i",$output[0],$ip);
			preg_match("/Mask:(\S+)/i",$output[0],$mask);
			if (!isset($ip[0]) || !isset($mask[0]))
			{				
				throw new Exception("无法获取所选网卡的IP地址");
			}
			$string .= "address ".$ip[0]."\n"."netmask ".$mask[0]."\nslaves ";
		}
		
		$item['whichip'] = '';
		
		foreach ($item['netcard_list'] as $value)
		{
			if (preg_match("/eth\d+/i",$value) != null)
				$string = $string.$value." ";
		}
		$string = $string."\nbond_mode ".$item['mode']."\n".
				  "bond_miimon 100\n".
				  "bond_downdelay 200\n".
				  "bond_updelay 200";
		
		file_put_contents($this->interfaces,$contents.$string);
	}



	//获取DNS和主机名
	function getDnsAndHostName()
	{
		$ret  = array();
		$host = file_get_contents($this->hostpath);
		
		$content = file_get_contents($this->dnspath);		
		
		preg_match_all("/nameserver\s*([0-9.]+)/is",$content,$matches);
		
		array_push($ret,$host);
		
		foreach ($matches[1] as $value)
		{
			array_push($ret,$value);	
		}
		
		$dns_arr=array();
		
		$dns_arr['hostname']    = $ret[0];
		$dns_arr['dnsmask1'] = $ret[1];
		$dns_arr['dnsmask2'] = $ret[2];
		$dns_arr['dnsmask3'] = $ret[3];	
			
		return $dns_arr;
	}
	//保存DNS文件
	function save_dns_interface($conf)
	{
		// write /root/dnsname file
		$handle = fopen($this->dnspath,"w");
		
		if ($handle)
		{
			try
			{			
				fwrite($handle,"nameserver"." ".$conf['dnsmask1']."\n");
				fwrite($handle,"nameserver"." ".$conf['dnsmask2']."\n");
				fwrite($handle,"nameserver"." ".$conf['dnsmask3']."\n");
				
			}
			catch(Exception $ex)
			{
				fclose($handle);
				throw new Exception("写dns文件错误");
			}
			fclose($handle);
			
		}
		$host_name = fopen($this->hostpath,"w");
		
		if ($host_name)
		{
			try
			{			
				fwrite($host_name,$conf['hostname']);		
			}
			catch(Exception $ex)
			{
				fclose($host_name);
				throw new Exception("写name文件错误");
			}
			fclose($host_name);
		}
	}



	//获取基本网卡数组
	function boot_interface(){
		if(Environment_check()){
			$rv = array();
			$confList =array();
			$b = array();
			$re = array();
			$net_scripts_config = $this->interfaces;	
			
			$confList = $this->read_env_file1($net_scripts_config);
		
			$j = 0;
			foreach(array_keys($confList) as $conf)
			{	
				$b['fullname'] = $confList[$conf]['DEVICE'];
			
				if(preg_match("/(\S+):(\d+)/",$b['fullname'],$matches))
				{	
					$b['name'] = $matches[1];
					$b['virtual'] =$matches[2];
				}
				else
				{
					$b['name'] = $b['fullname'];
					$b['virtual'] = '';
				}	
				if ($confList[$conf]['ONBOOT'] == 'yes')
					$b['up'] = 'yes';
				else
					$b['up'] = 'no';
					
				if (!(array_key_exists("BOOTPROTO",$confList[$conf])))
					$confList[$conf]['BOOTPROTO'] = '';
					
				if (!(array_key_exists("GATEWAY",$confList[$conf])))
					$confList[$conf]['GATEWAY'] = '';
				
				if (array_key_exists("BONDLIST",$confList[$conf]))
					$b['bondlist'] = $confList[$conf]['BONDLIST'];
				else
					$b['bondlist'] = array(); // 加入空段只是为了不影响排序
					
				if ($confList[$conf]['DHCP'] == 'dhcp')
				{
					$b['dhcp'] = 'yes';
					$b['address'] = '';
					$b['netmask'] = '';
					$b['gateway'] = '';
				}
				else
				{
					$b['dhcp'] = '';
					@$b['address'] = $confList[$conf]['IPADDR'];
					$b['netmask'] = $confList[$conf]['NETMASK'];
					$b['gateway'] = $confList[$conf]['GATEWAY'];
				}
				if ($confList[$conf]['BOOTPROTO'] == 'bootp')
					$b['bootp'] = 'yes';
				else
					$b['bootp'] = '';
				
				$b['edit'] = 'yes';
				$b['index'] = count($rv);
			
				array_push($rv,$b);
				$b = array();
			}
			return $rv;
		}else{
			//虚拟数据
			return array(array("fullname"=>"eth0","name"=>"eth0","virtual"=>"","up"=>"yes","bondlist"=>array(),"dhcp"=>"","address"=>"10.10.16.29","netmask"=>"255.0.0.0","gateway"=>"","edit"=>"yes","index"=>0),
						 array("fullname"=>"eth1","name"=>"eth1","virtual"=>"","up"=>"yes","bondlist"=>array(),"dhcp"=>"","address"=>"100.0.0.29","netmask"=>"255.0.0.0","gateway"=>"","edit"=>"yes","index"=>0)																					 			);
		}
		
	}
	function read_env_file1($arg){
		// 执行这个功能之前，先对文件进行整理
		$filecontent = file_get_contents($arg);
		$filecontent = preg_replace("/\n{3,}/","\n\n",$filecontent);
		file_put_contents($arg,$filecontent);
		
		$ethList = array();	
		
		$handle = fopen($arg,"r");
		
		while (!feof($handle))
		{
			$line = fgets($handle);
			$flag = false;
			$conf = array();		
			
			if (preg_match("/^auto\s+/",$line))
			{
				$conf['ONBOOT'] = 'yes';
				$flag = true;
			}
			else if(preg_match("/^#auto/",$line))
			{
				$conf['ONBOOT'] = 'no';
				$flag = true;
			}
			
			if ($flag)
			{
				$line = fgets($handle);
				
				//while (trim($line) == '') $line = fgets($handle);
				
				if (preg_match("/iface\s+(eth\S+)\s+inet\s+(\S+)/",$line,$eth))
				{
					$conf['DEVICE'] = $eth[1];
					$conf['DHCP'] = $eth[2];
					
					if ($conf['DHCP'] == 'dhcp')
					{
						array_push($ethList,$conf);
						continue;
					}
					else
					{
						// 寻找address,netmask,gateway					
						while (!feof($handle)) 
						{
							$pos = ftell($handle);
							$line = fgets($handle);	
							
							if (preg_match("/#?auto|iface/",$line))
							{
								fseek($handle,$pos,SEEK_SET);
								break;
							}
							
							if (preg_match("/address\s+(\S+)/",$line,$ip)) $conf['IPADDR'] = $ip[1];						
							if (preg_match("/netmask\s+(\S+)/",$line,$netmask)) $conf['NETMASK'] = $netmask[1]; 						
							if (preg_match("/#?gateway\s+(\S+)/",$line,$gateway)) $conf['GATEWAY'] = $gateway[1];
							
						}
						array_push($ethList,$conf);
					}
				}
				else if(preg_match("/iface\s+(br\S+)\s+inet\s+(\S+)/",$line,$br))
				{				
					$conf['DEVICE'] = $br[1];
					$conf['DHCP'] = $br[2];
					
					if ($conf['DHCP'] == 'dhcp')
					{
						array_push($ethList,$conf);
						continue;
					}
					else
					{
						// 寻找address,netmask,gateway					
						while (!feof($handle)) 
						{
							$pos = ftell($handle);
							$line = fgets($handle);	
							
							if (preg_match("/#?auto|iface/",$line))
							{
								fseek($handle,$pos,SEEK_SET);
								break;
							}
							
							if (preg_match("/address\s+(\S+)/",$line,$ip)) $conf['IPADDR'] = $ip[1];						
							if (preg_match("/netmask\s+(\S+)/",$line,$netmask)) $conf['NETMASK'] = $netmask[1]; 						
							if (preg_match("/#?gateway\s+(\S+)/",$line,$gateway)) $conf['GATEWAY'] = $gateway[1];
							
						}
						array_push($ethList,$conf);
					}
				}
				else if(preg_match("/iface\s+(bond\S+)\s+inet\s+(\S+)/",$line,$bond))
				{
					$conf['DEVICE'] = $bond[1];
					$conf['DHCP'] = $bond[2];
					
					if ($conf['DHCP'] == 'dhcp')
					{
						// 寻找绑定列表
						while (!feof($handle)) 
						{
							$pos = ftell($handle);
							$line = fgets($handle);	
							
							if (preg_match("/#?auto|iface/",$line))
							{
								fseek($handle,$pos,SEEK_SET);
								break;
							}						
							
							if (preg_match("/slaves/",$line))
							{
								preg_match_all("/eth\d+/",$line,$ethlist);
								$conf['BONDLIST'] = $ethlist[0];
							}
							
						}
						array_push($ethList,$conf);
						continue;
					}
					else
					{
						// 寻找address,netmask,gateway					
						while (!feof($handle)) 
						{
							$pos = ftell($handle);
							$line = fgets($handle);	
							
							if (preg_match("/#?auto|iface/",$line))
							{
								fseek($handle,$pos,SEEK_SET);
								break;
							}
							
							if (preg_match("/address\s+(\S+)/",$line,$ip)) $conf['IPADDR'] = $ip[1];						
							if (preg_match("/netmask\s+(\S+)/",$line,$netmask)) $conf['NETMASK'] = $netmask[1]; 						
							if (preg_match("/#?gateway\s+(\S+)/",$line,$gateway)) $conf['GATEWAY'] = $gateway[1];
							
							if (preg_match("/slaves/",$line))
							{
								preg_match_all("/eth\d+/",$line,$ethlist);
								$conf['BONDLIST'] = $ethlist[0];
							}
							
						}
						array_push($ethList,$conf);
					}	
				}
			}
			//
		}
		
		fclose($handle);
		return $ethList;
	}

}
?>