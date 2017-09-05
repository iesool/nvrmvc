#!/usr/bin/env python   
#coding=utf-8
#本脚本用于实时观看视频时浏览器异常关闭，根据时间戳清理未关闭的连接和更改数据库状态，运行于crontab，一分钟执行一次   
 
import MySQLdb,os,socket,time,re,struct

#建立和数据库系统的连接
conn = MySQLdb.connect(host='localhost', user='root',passwd='asdf')   
  
#获取操作游标    
cursor = conn.cursor()   

#选择数据库
cursor.execute("use zm")
cursor.execute("set names utf8")

timeout=10#超时范围
interval=10#循环间隔
mintime=int((60-interval)/interval)#每分钟循环次数

for i in range(mintime):
	record_num=cursor.execute("select Id,Function,Views,Connectting from Monitors where Function='Watch' or Function='SameTime'")
	
	if record_num>0:
		monitor_arrs=cursor.fetchall()
		
		for monitor_arr in monitor_arrs:		
			mid=monitor_arr[0]
			function=monitor_arr[1]
			views=monitor_arr[2]
			connectting=monitor_arr[3]
			
			now_unix=int(time.time())#当前时间戳			
			overtime=now_unix-int(connectting)#超时		
			#print overtime
			
			#判断是否超时
			if overtime>=timeout:				
				if function=="Watch":
					if views<=1:
						os.system("/usr/local/bin/NVRCommandLine -C stop -i 1000 -m "+str(mid))
						cursor.execute("update Monitors set Function='Nodect',Views=0 where Id="+str(mid));
					else:
						views-=1
						cursor.execute("update Monitors set Views="+str(views)+" where Id="+str(mid));									
				elif function=="SameTime":
					if views<=1:
						cursor.execute("update Monitors set Function='Record',Views=0 where Id="+str(mid));
					else:
						views-=1
						cursor.execute("update Monitors set Views="+str(views)+" where Id="+str(mid));							
				else:
					pass
	
	time.sleep(interval)#循环间隔


#关闭连接，释放资源     
cursor.close(); 
conn.close();	