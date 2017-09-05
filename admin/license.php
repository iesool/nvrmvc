<?php 
require("../include/config.php");
require("../include/connect.php");
require("../include/functions.php");

//license类
class license{
	public $rc_path;
	
	public function __construct(){
		$this->rc_path="/etc/rc.local";
	}
	
	//获得license
	public function get_license(){
		//设备注册号
		File_top($this->rc_path);
		$rc=file($this->rc_path);
		foreach($rc as $v){
			if(preg_match("/check_lic(\s*)(\w*)/",$v,$rc_u)){
				$lic=$rc_u[2];
				break;
			}
		}
		if(!$lic){
			$lic=Get_db_one("select License from Diy");
		}
		return $lic;
	}
	
	//设置license
	public function set_license($license){
		Shell_cmd("check_lic ".$license);
		$rv=Shell_cmd("dmesg");
		$rv=$rv['back'];
		preg_match("/lic(\s*)(\w*)/",$rv,$rv);
		
		if(trim($rv[2])=="ok"){
			$this->op_license($license);
			return true;
		}else{
			return false;
		}
	}
	
	//执行操作
	public function op_license($license){
		File_top($this->rc_path);//修改文件权限至777
		$rc=file($this->rc_path);
		$rc2=array();
		$k=0;
		foreach($rc as $v){
			if(preg_match("/check_lic(\s*)(\w*)/",$v,$rc_u)){
				$lic=$rc_u[2];
				$v=str_replace($lic,$license,$v);
				$k++;
			}
			array_push($rc2,$v);
		}
		if($k>0){
			file_put_contents($this->rc_path,$rc2);//将新数组写入文件<br>
		}else{
			mysql_query("update Diy set License='".$license."'");
		}
	}
	
}

//实例化类
$license=new license();

//控制器
if(isset($_GET['do'])){
	$set=$license->set_license($_REQUEST['license']);
	if($set){
		/*echo "<script>alert('创建成功！');window.location='admin.php?view=license'</script>";*/
		echo "<script>alert('创建成功！');window.location='../admin/license.php'</script>";
	}else{
		echo "<script>alert('创建失败！');history.back()</script>";
	}
}else{
	//产品序列号
	if(file_exists("/SN")){
		$sn=file("/SN");
	}
	//产品型号
	if(file_exists("/MODULE")){
		$module=file("/MODULE");
	}
	//软件版本号
	if(file_exists("/usr/local/etc/RELEASE")){
		$ver=file("/usr/local/etc/RELEASE");
	}
	//设备唯一标识号
	$kernal_arr=explode(".",php_uname("r"));
	$kernal=$kernal_arr[0].".".$kernal_arr[1];
	if(floatval($kernal)>=3.6){
		$deviceid=Shell_cmd("/sbin/check_lic");
	}else{
		$deviceid=Shell_cmd("/usr/local/bin/deviceid");
	}
	$deviceid=$deviceid['back'];
	
	//licesne
	$lic=$license->get_license();
?>
	<h3>license管理</h3>
    <div>
      <!--<form method="post" action="admin.php?view=license&do=1">-->
      <form method="post" action="../admin/license.php?do=1">
        <table border="0">
          <tr>
            <td>产品序列号：</td>
            <td><?php echo $sn[0]; ?></td>
          </tr>
          <tr>
            <td>产品型号：</td>
            <td><?php echo $module[0]; ?></td>
          </tr>
          <tr>
            <td>软件版本号：</td>
            <td><?php echo $ver[0]; ?></td>
          </tr>
          <tr>
            <td>设备唯一标识号：</td>
            <td><?php echo $deviceid; ?></td>
          </tr>
          <tr>
            <td>设备注册号：</td>
            <td><input type="text" value="<?php echo $lic; ?>" name="license" id="" size="50" /></td>
          </tr>
        </table>
        <div class="main_op"><input type="submit" value="创建" /><input type="reset" value="重置" /></div>
      </form>
    </div>
<?php 
}
?>