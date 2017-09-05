#!/usr/bin/env python   
#coding=utf-8   
#本脚本用于开机启动时check license，运行于rc.local
 
import MySQLdb,os

#建立和数据库系统的连接
conn = MySQLdb.connect(host='localhost', user='root',passwd='asdf')   
  
#获取操作游标    
cursor = conn.cursor()   

#选择数据库
cursor.execute("use zm")
cursor.execute("set names utf8")

cursor.execute("select License from Diy")
license=cursor.fetchall()

#print license[0][0]
os.system("/sbin/check_lic "+license[0][0]);

#关闭连接，释放资源     
cursor.close(); 
conn.close();  