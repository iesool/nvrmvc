#!/usr/bin/env python   
#coding=utf-8   
#���ű����ڿ�������ʱcheck license��������rc.local
 
import MySQLdb,os

#���������ݿ�ϵͳ������
conn = MySQLdb.connect(host='localhost', user='root',passwd='asdf')   
  
#��ȡ�����α�    
cursor = conn.cursor()   

#ѡ�����ݿ�
cursor.execute("use zm")
cursor.execute("set names utf8")

cursor.execute("select License from Diy")
license=cursor.fetchall()

#print license[0][0]
os.system("/sbin/check_lic "+license[0][0]);

#�ر����ӣ��ͷ���Դ     
cursor.close(); 
conn.close();  