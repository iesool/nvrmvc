// JavaScript Document

function table_load(index,order,url,by,uncookie){
	var backinfo=$.ajax({url:url+"&order="+order+"&by=desc&page=1&pagesize=10&get=num",async:false});
	var num=backinfo.responseText;
	if(!by){
		var by="desc";
	}
	
	if(!getCookie(index+'_page')||uncookie){
		var page=1;
	}else{
		var page=getCookie(index+'_page');
	}
	
	if(!getCookie(index+'_pagesize')){
		var pagesize=10;
	}else{
		var pagesize=parseInt(getCookie(index+'_pagesize'));
	}
	
	var page_banner='';
	
	page_banner+='<input type="hidden" id="'+index+'_url" value="'+url+'" size="100" />';
	page_banner+='<input type="hidden" id="'+index+'_order" value="'+order+'" size="2" />';
	page_banner+='<input type="hidden" id="'+index+'_by" value="'+by+'" size="2" />';
	page_banner+='<input type="hidden" id="'+index+'_num" value="'+num+'" size="2" />';
	page_banner+='<input type="hidden" id="'+index+'_page" value="'+page+'" size="2" />';
	
	if(word['word_online']=='登录为'){
		page_banner+='当前第<span id="'+index+'_pagenow"></span>页&nbsp;共'+num+'条记录&nbsp;每页&nbsp;';
		page_banner+='<select id="'+index+'_pagesize" onChange=page_change("'+index+'",1)>';
		switch(pagesize){
			case 10:
			page_banner+='<option value="10" selected>10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="200">200</option>';
			break;
			
			case 20:
			page_banner+='<option value="10">10</option><option value="20" selected>20</option><option value="50">50</option><option value="100">100</option><option value="200">200</option>';
			break;
			
			case 50:
			page_banner+='<option value="10">10</option><option value="20">20</option><option value="50" selected>50</option><option value="100">100</option><option value="200">200</option>';
			break;
			
			case 100:
			page_banner+='<option value="10">10</option><option value="20">20</option><option value="50">50</option><option value="100" selected>100</option><option value="200">200</option>';
			break;
			
			case 200:
			page_banner+='<option value="10">10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="200" selected>200</option>';
			break;
		}
		page_banner+='</select>';
		page_banner+='&nbsp;条记录&nbsp;共<span id="'+index+'_pageall"></span>页&nbsp;';
	}else{
		page_banner+='This is page <span id="'+index+'_pagenow"></span>, All of '+num+' record, ';
		page_banner+='<select id="'+index+'_pagesize" onChange=page_change("'+index+'",1)>';
		switch(pagesize){
			case 10:
			page_banner+='<option value="10" selected>10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="200">200</option>';
			break;
			
			case 20:
			page_banner+='<option value="10">10</option><option value="20" selected>20</option><option value="50">50</option><option value="100">100</option><option value="200">200</option>';
			break;
			
			case 50:
			page_banner+='<option value="10">10</option><option value="20">20</option><option value="50" selected>50</option><option value="100">100</option><option value="200">200</option>';
			break;
			
			case 100:
			page_banner+='<option value="10">10</option><option value="20">20</option><option value="50">50</option><option value="100" selected>100</option><option value="200">200</option>';
			break;
			
			case 200:
			page_banner+='<option value="10">10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="200" selected>200</option>';
			break;
		}
		page_banner+='</select>';
		page_banner+=' record per page, All of <span id="'+index+'_pageall"></span> page ';
	}
	
	
	page_banner+='<span id="'+index+'_show_page"></span>';
	
	$("#"+index+"_"+"page_banner").html(page_banner);
										
	list_sort(index,order);
	list_page(index);
}

function list_sort(index,order){
	if(order!=$("#"+index+"_"+"order").val()){
		$("#"+index+"_"+$("#"+index+"_"+"order").val()).empty();
		$("#"+index+"_"+"order").val(order);
	}
	if($("#"+index+"_"+"by").val()=="asc"){
		$("#"+index+"_"+"by").val("desc");
		$("#"+index+"_"+order).html("<img src='../images/desc.jpg' width=10 height=10 />");
	}else{
		$("#"+index+"_"+"by").val("asc");
		$("#"+index+"_"+order).html("<img src='../images/asc.jpg' width=10 height=10 />");
	}
	get_table_list(index,$("#"+index+"_"+"url").val(),order,$("#"+index+"_"+"by").val(),$("#"+index+"_"+"page").val(),$("#"+index+"_"+"pagesize").val());
}

function list_page(index){
	var pages=Math.ceil($("#"+index+"_"+"num").val()/$("#"+index+"_"+"pagesize").val());
	var page=parseInt($("#"+index+"_"+"page").val());
	
	//设定总页数至少1页
	if(pages<1){
		pages=1;
	}
	
	//如果传递过来的页数比总页数还大，就让它等于总页数，如果传递过来的页数小于1，就让他等于1
	if(page>pages){
		var page=pages;
	}else if(page<1){
		var page=1;
	}
	
	var page_str="";
	
	if(page==1){
		page_str+=word['word_first_page']+"&nbsp;"+word['word_front_page']+"&nbsp;";
	}else{
		var front_page=page-1;
		page_str+="<a href='javascript:;' onclick=page_change('"+index+"',1)>"+word['word_first_page']+"</a>&nbsp;<a href='javascript:;' onclick=page_change('"+index+"',"+front_page+")>"+word['word_front_page']+"</a>&nbsp;";
	}
	
	var page_arr=new Array();
	
	var first_page=page-3;
	var last_page=page+3;
	if(first_page<1){
		first_page=1;
	}
	if(last_page>pages){
		last_page=pages;
	}
	for(var i=first_page;i<=last_page;i++){
		if(i==page){
			page_arr.push("<span style=color:#00B6EF>["+i+"]</span>");
		}else{
			page_arr.push("<a href='javascript:;' onclick=page_change('"+index+"',"+i+")>["+i+"]</a>");
		}
	}
	page_str+=page_arr.join(" ");
	
	if(page==pages){
		page_str+="&nbsp;"+word['word_next_page']+"&nbsp;"+word['word_last_page'];
	}else{
		var next_page=page+1;
		page_str+="&nbsp;<a href='javascript:;' onclick=page_change('"+index+"',"+next_page+")>"+word['word_next_page']+"</a>&nbsp;<a href='javascript:;' onclick=page_change('"+index+"',"+pages+")>"+word['word_last_page']+"</a>";
	}
	
	$("#"+index+"_pagenow").html(page);
	$("#"+index+"_pageall").html(pages);
	$("#"+index+"_show_page").html(page_str);
}

function page_change(index,page){
	$("#"+index+"_"+"page").val(page);
	list_page(index);
	setCookie(index+"_page",$("#"+index+"_"+"page").val(),30);
	setCookie(index+"_pagesize",$("#"+index+"_"+"pagesize").val(),30);
	get_table_list(index,$("#"+index+"_"+"url").val(),$("#"+index+"_"+"order").val(),$("#"+index+"_"+"by").val(),$("#"+index+"_"+"page").val(),$("#"+index+"_"+"pagesize").val());
}

function get_table_list(index,url,order,by,page,pagesize){
	$.getJSON(url+"&order="+order+"&by="+by+"&page="+page+"&pagesize="+pagesize,function(list){
		$("#"+index+"_table").empty();
		var table_str='';
		if(list.length){
			for(i in list){
				table_str+="<tr onmousemove='move_color(this)' onmouseout='out_color(this)'>";
				for(j in list[i]){
					table_str+="<td>"+list[i][j]+"</td>";
				}
				table_str+="</tr>";
			}
		}else{
			table_str+="<tr><td colspan=100>"+word['word_none']+"</td></tr>";
		}
		$("#"+index+"_table").html(table_str);
	});
}

function get_table(index,url,nothing){
	$.getJSON(url,function(list){
		$("#"+index+"_table").empty();
		var table_str='';
		if(list.length){
			for(i in list){
				table_str+="<tr onmousemove='move_color(this)' onmouseout='out_color(this)'>";
				for(j in list[i]){
					table_str+="<td>"+list[i][j]+"</td>";
				}
				table_str+="</tr>";
			}
		}else{
			table_str+="<tr><td colspan=100>"+nothing+"</td></tr>";
		}
		$("#"+index+"_table").html(table_str);
	});
}

function move_color(tr){
	tr.style.backgroundColor="#eee";
}

function out_color(tr){
	tr.style.backgroundColor="";
}

function filter(index,key,val,by){
	$("."+index+"_"+key).css("background","");
	$("#"+index+"_"+key+"_"+val).css("background","#CCC");
	
	var val_now=false;
	
	var r=$("#"+index+"_"+"url").val().split("?");
	if(r[1]){
		var r2=r[1].split("&");
		for(i in r2){
			var r3=r2[i].split("=");
			if(r3[0]==key){
				var val_now=r3[1];
			}
		}
	}
	
	if(val_now){
		var url=$("#"+index+"_"+"url").val().replace(key+"="+val_now,key+"="+val);
	}else{
		var url=$("#"+index+"_"+"url").val()+"&"+key+"="+val;
	}
										  
	table_load(index,$("#"+index+"_"+"order").val(),url,by);
}

