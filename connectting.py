#!/usr/bin/env python   
#coding=utf-8
#���ű�����ʵʱ�ۿ���Ƶʱ������쳣�رգ�����ʱ�������δ�رյ����Ӻ͸������ݿ�״̬��������crontab��һ����ִ��һ��   
 
import MySQLdb,os,socket,time,re,struct

#���������ݿ�ϵͳ������
conn = MySQLdb.connect(host='localhost', user='root',passwd='asdf')   
  
#��ȡ�����α�    
cursor = conn.cursor()   

#ѡ�����ݿ�
cursor.execute("use zm")
cursor.execute("set names utf8")

timeout=10#��ʱ��Χ
interval=10#ѭ�����
mintime=int((60-interval)/interval)#ÿ����ѭ������

for i in range(mintime):
	record_num=cursor.execute("select Id,Function,Views,Connectting from Monitors where Function='Watch' or Function='SameTime'")
	
	if record_num>0:
		monitor_arrs=cursor.fetchall()
		
		for monitor_arr in monitor_arrs:		
			mid=monitor_arr[0]
			function=monitor_arr[1]
			views=monitor_arr[2]
			connectting=monitor_arr[3]
			
			now_unix=int(time.time())#��ǰʱ���			
			overtime=now_unix-int(connectting)#��ʱ		
			#print overtime
			
			#�ж��Ƿ�ʱ
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
	
	time.sleep(interval)#ѭ�����


#�ر����ӣ��ͷ���Դ     
cursor.close(); 
conn.close();	