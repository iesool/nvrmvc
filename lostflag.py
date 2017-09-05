#!/usr/bin/env python   
#coding=utf-8 
#本脚本用于处理异常的lostflag，运行于crontab  
 
import MySQLdb,os,socket,time,re,struct
  
#建立和数据库系统的连接
conn = MySQLdb.connect(host='localhost', user='root',passwd='asdf')   
  
#获取操作游标    
cursor = conn.cursor()   

#选择数据库
cursor.execute("use zm")
cursor.execute("set names utf8")

#
record_num=cursor.execute("select Id,Ip from Monitors where LostFlag='1'")

#
if record_num>0:
	monitor_arrs=cursor.fetchall()
	
	for monitor_arr in monitor_arrs:
		
		mid=monitor_arr[0]
		ip=monitor_arr[1]
		
		ping=os.system("ping -c 1"+ip)
		
		if ping==0:
			cursor.execute("update Monitors set Function='Record' where Id="+str(mid))
 
#关闭连接，释放资源     
cursor.close(); 
conn.close();