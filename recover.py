#!/usr/bin/env python   
#coding=utf-8 
#本脚本用于机器启动时处理异常和自动启动NVR服务，运行于rc.local  
 
import MySQLdb,os,socket,time,re,struct
  
#建立和数据库系统的连接
conn = MySQLdb.connect(host='localhost', user='root',passwd='asdf')   
  
#获取操作游标    
cursor = conn.cursor()   

#选择数据库
cursor.execute("use zm")
cursor.execute("set names utf8")

#自动挂载
cursor.execute("select * from Mounts")
mount_arrs=cursor.fetchall()
for mount_arr in mount_arrs:
    os.system("mount -t xfs -o noatime,logbufs=8,quota,usrquota,grpquota "+mount_arr[2]+" "+mount_arr[3])

#自动启动NVR
cursor.execute("select Is_auto from Diy")
is_auto=cursor.fetchall()
is_auto=is_auto[0][0]
if is_auto==1:
	os.system("bash /usr/local/bin/zm.sh start")

	
#获取断电时还在录像的前端设备数
record_num=cursor.execute("select Id,Function,Path,Width,Height,WarmupCount,SectionLength,Name from Monitors where Function='Record' or Function='SameTime'")

#如果断电时还在录像的前端设备数大于0则执行1、自动启动nvr，2、自动发送开始录像的socket命令
if record_num>0:
	monitor_arrs=cursor.fetchall()
		
	#1
	if is_auto==0:
		os.system("bash /usr/local/bin/zm.sh start")
	
	#2	
	for monitor_arr in monitor_arrs:
		
		mid=monitor_arr[0]
		function=monitor_arr[1]
		path=monitor_arr[2]
		width=monitor_arr[3]
		height=monitor_arr[4]
		warmupcount=monitor_arr[5]
		sectionlength=monitor_arr[6]
		mname=monitor_arr[7]
	
		start_time=int(time.time())
		end_time=start_time+sectionlength
					
		if function=="SameTime":
			cursor.execute("update Monitors set Function='Record' where Id="+str(mid));
		
		
		c=os.popen("/usr/local/bin/NVRCommandLine -C start -i 1000 -m "+str(mid)+" -u "+path).readline()
		time.sleep(2)
		r=os.system("/usr/local/bin/NVRCommandLine -R start -i 1000 -m "+str(mid)+" -d "+str(sectionlength)+" -h "+str(height)+" -l "+str(width)+" -v "+str(warmupcount))
		
		if(int(c)==0&r==0):
			back_time=time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
			cursor.execute("insert into Logs (Time,Level,Class,Type,User,Ip,Event) values ('"+back_time+"','normal','monitor','run','系统','本机','前端设备“"+mname+"”恢复录像')")
		else:
		    cursor.execute("update Monitors set Function='Nodect' where Id="+str(mid));
		
		
 
#关闭连接，释放资源     
cursor.close(); 
conn.close();