<?php 
require(ROOT_PATH."/include/class_disk.php");
$Disk=new Disk();

//RAID管理
switch($_GET['op']){
	case "get_raid_list":
	  echo $Disk->Get_raid_list();
	break;
	
	case "get_jobd":
	  echo $Disk->Get_jobd();
	break;
	
	case "get_disk_matrix":
	  echo $Disk->Get_disk_matrix();
	break;
	
	case "get_raid_show":
	  echo $Disk->Get_raid_show($_GET['raid_name']);
	break;
	
	case "get_unraid_disk":
	  echo $Disk->Get_unraid_disk();
	break;
	
	case "get_raid_name":
	  echo $Disk->Get_raid_name();
	break;
	
	case "get_raid_disk":
	  echo $Disk->Get_raid_disk($_GET['raid_name']);
	break;
	
	case "get_hotspare_disk":
	  echo $Disk->Get_hotspare_disk();
	break;
	
	case "get_unactive_disk":
	  echo $Disk->Get_unactive_disk();
	break;
	
	case "raid_creat":
	  $Disk->Raid_creat($_POST);
	break;
	
	case "raid_del":
	  $Disk->Raid_del($_POST);
	break;
	
	case "hotspare_creat":
	  $Disk->Hotspare_creat($_POST);
	break;
	
	case "hotspare_del":
	  $Disk->Hotspare_del($_POST);
	break;
	
	case "disk_active":
	  $Disk->Disk_active($_POST);
	break;
	
	case "raid_active":
	  $Disk->Raid_active();
	break;
}
?>