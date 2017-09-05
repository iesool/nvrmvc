<?php 
require(ROOT_PATH."/include/class_disk.php");
$Disk=new Disk();

//存储池管理
switch($_GET['op']){
	case "get_vg_list":
	  echo $Disk->Get_vg_list();
	break;
	
	case "get_lv_list":
	  echo $Disk->Get_lv_list();
	break;
	
	case "get_pv_list":
	  echo $Disk->Get_pv_list();
	break;
	
	case "get_umount_groups";
	  echo $Disk->Get_umount_groups();
	break;
	
	case "vg_creat":
	  $Disk->Vg_creat($_POST);
	break;
	
	case "vg_extend":
	  $Disk->Vg_extend($_POST);
	break;
	
	case "vg_reduce":
	  $Disk->Vg_reduce($_POST);
	break;
	
	case "vg_del":
	  $Disk->Vg_del($_GET['vg_name']);
	break;
	
	case "lv_creat":
	  $Disk->Lv_creat($_POST);
	break;
	
	case "lv_extend":
	  $Disk->Lv_extend($_POST);
	break;
	
	case "lv_del":
	  $Disk->Lv_del($_GET['lv_name']);
	break;
	
	case "lv_mkfs":
	  $Disk->Lv_mkfs($_GET['lv_name']);
	break;
	
	case "lv_mount":
	  $Disk->Lv_mount($_GET['lv_name'],$_POST);
	break;
	
	case "lv_umount":
	  $Disk->Lv_umount($_GET['mount_path']);
	break;
	
	default:
	break;
}
?>