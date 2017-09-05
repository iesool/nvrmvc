// JavaScript Document

//获取cookie
function getCookie(c_name){
	if (document.cookie.length>0)
	  {
	  c_start=document.cookie.indexOf(c_name + "=")
	  if (c_start!=-1)
		{ 
		c_start=c_start + c_name.length+1 
		c_end=document.cookie.indexOf(";",c_start)
		if (c_end==-1) c_end=document.cookie.length
		return unescape(document.cookie.substring(c_start,c_end))
		} 
	  }
	return ""
}

//设置cookie
function setCookie(c_name,value,expiredays,type){
	var exdate=new Date()
	//alert(exdate.getMinutes());
	//type="minute";
	if(type=="minute"){
		exdate.setMinutes(exdate.getMinutes()+expiredays)
	}else if(type=="second"){
		exdate.setSeconds(exdate.getSeconds()+expiredays)
	}else{
		exdate.setDate(exdate.getDate()+expiredays)
	}
	//alert(exdate);
	document.cookie=c_name+ "=" +escape(value)+
	((expiredays==null) ? "" : ";expires="+exdate.toGMTString())
}


//选项卡操作
function tab_load(tab){
	setCookie("tab",tab,30);
	var time=new Date().getTime();
	$("#tab_"+tab).empty();
	$("#tab_"+tab).load("skins/tab_div/"+tab+".html?time="+time);
}
function tab_get(def){
	//根据cookie选择选项卡
	if(getCookie("tab")&&$("#"+getCookie("tab")).html()){
		$("#"+getCookie("tab")).click();
	}else{
		$("#"+def).click();
	}
}


//全部选择/取消
function check_all(colMark,form_form){
	var form_edit=document.getElementById(form_form);
	if(form_edit.edit_arr!=undefined){
		var leng = form_edit.edit_arr.length;
		if(leng==undefined){
			leng=1;
			if(colMark.checked){
				form_edit.edit_arr.checked=true;
			}else{
				form_edit.edit_arr.checked=false;
			}
		}else{  
			for( var i = 0; i < leng; i++){
				if(colMark.checked){
					form_edit.edit_arr[i].checked = true;
				}else{
					form_edit.edit_arr[i].checked = false;
				}
			}
		} 
		return false;
	}
}

//弹出窗口
function pop_window(width,height,url,name){
	var left = (screen.width - width) / 2;
	var top  = (screen.height - height) / 2;
	window.open(url,name,'width='+width+',height='+height+',top='+top+',left='+left);
}

//弹出层
function pop_div(width,height,title,id,position){
	if(!position){
		var position="center center";
	}
	
	$("#main").append('<div id="pop_div_'+id+'" class="pop_div"><img src="images/ajax-loader.gif" />&nbsp;'+word['word_loading']+'</div>');
	$("#pop_div_"+id).dialog({
		modal: true,				 
		autoOpen:false,
		resizable:false,
		width:width,
		height:height,
		title:title,
		position: { my: "center center", at: position, of: document },
		open:function(){
			//操作员界面时关闭屏幕
			if(document.getElementById("main_iframe")){
				if($("#main_iframe").attr("src")==""){
					$(this).dialog("option","dialogClass","children");//是否子弹出层
				}else{
					iframe_toggle("stop");
				}
			}
		},
		close:function(){
			//操作员界面时打开屏幕
			if(document.getElementById("main_iframe")&&$("#main_iframe").attr("src")==""&&$(this).dialog("option","dialogClass")!="children"){
				iframe_toggle("start");
			}
			//单路实时退出动态关闭连接
			if(id=="monitor_pop"){
				//从iframe的src提取mid_str(mid)
				var mid_index=$("#play_frame").attr("src").indexOf("mid_str=")+8;
				var width_index=$("#play_frame").attr("src").indexOf("&width_fix");
				var mid_str=$("#play_frame").attr("src").substring(mid_index,width_index);
				//alert(mid_str);
				var backinfo=$.ajax({url:"index.php?do=ajax&view=monitor_list&op=watch_connect_stop&mid_str="+mid_str,async:true});
				if(backinfo.responseText){
					alert(backinfo.responseText);
				}
				if(document.getElementById("main_iframe")){
					get_monitor_tree();
				}else{
					main_load('monitor_list');
				}
			}
			//设备发现关闭时清空Mcache
			if(id=="monitor_discover"){
				$.ajax({url:"index.php?do=ajax&view=monitor_list&op=monitor_discover&type=cancel",async:false});
			}
			//关闭时移除弹出层
			$(this).remove();
		}
	});
	var time=new Date().getTime();
	$("#pop_div_"+id).dialog("open");
	$("#pop_div_"+id).load("skins/pop_div/"+id+".html?time="+time);
}

//弹出层标题
function pop_title(title,id){
	$("#pop_div_"+id).dialog("option","title",title);
}

//弹出层按钮
function pop_buttons(buttons,id){
	$("#pop_div_"+id).dialog("option","buttons",buttons);
}

//弹出层关闭
function pop_close(id){
	$("#pop_div_"+id).dialog("close");
}


//trim
function trim(str){
	 return str.replace(/(^\s*)|(\s*$)/g,"");
}

//in_array
function in_array(v,arr){
	var k=0;
	for(i in arr){
		if(v==arr[i]){
			k++;
		}
	}
	if(k==0){
		return false;
	}else{
		return true;
	}
}


//点击复选框
function is_auto_delete(auto_delete,sl){
	if(auto_delete.checked){
		$("#"+sl).attr("disabled",false);
	}else{
		$("#"+sl).attr("disabled",true);
	}
}


//左侧选项添加到右边
function add_disk(a,b){
	var select_disk=document.getElementById(a);
	var selected_disk=document.getElementById(b);
	
	for(var i=select_disk.length-1; i>=0; i--){
		if(select_disk.options[i].selected){
			var j=(select_disk.length-1)-i;
			var op=document.createElement('option');
			op.value=select_disk.options[i].value;
			op.text=select_disk.options[i].text;
			op.selected="selected";
			selected_disk.add(op);
			select_disk.remove(i);
		}
	}
}
function add_all_disk(a,b){
	var select_disk=document.getElementById(a);
	var selected_disk=document.getElementById(b);
	
	for(var i=select_disk.length-1; i>=0; i--){
		var j=(select_disk.length-1)-i;
		var op=document.createElement('option');
		op.value=select_disk.options[j].value;
		op.text=select_disk.options[j].text;
		op.selected="selected";
		selected_disk.add(op);
		select_disk.remove(j);
	}
}
function remove_disk(a,b){
	var select_disk=document.getElementById(a);
	var selected_disk=document.getElementById(b);
	
	for(var i=selected_disk.length-1; i>=0; i--){
		if(selected_disk.options[i].selected){
			var op=document.createElement('option');
			op.value=selected_disk.options[i].value;
			op.text=selected_disk.options[i].text;
			select_disk.add(op);
			selected_disk.remove(i);
		}
	}
}
function remove_all_disk(a,b){
	var select_disk=document.getElementById(a);
	var selected_disk=document.getElementById(b);
	
	for(var i=selected_disk.length-1; i>=0; i--){
		var j=(selected_disk.length-1)-i;
		var op=document.createElement('option');
		op.value=selected_disk.options[j].value;
		op.text=selected_disk.options[j].text;
		select_disk.add(op);
		selected_disk.remove(j);
	}
}
function select_all(b){
	var selected_disk=document.getElementById(b);
	for(var i=selected_disk.length-1; i>=0; i--){
		selected_disk.options[i].selected="selected";
	}
}

