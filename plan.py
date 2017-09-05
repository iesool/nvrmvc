#!/usr/bin/env python   
#coding=utf-8
#���ű����ڼƻ�¼���ִ�У�������crontab��һ����ִ��һ��   
 
import MySQLdb,os,socket,time,re,struct

#���������ݿ�ϵͳ������
conn = MySQLdb.connect(host='localhost', user='root',passwd='asdf')   
  
#��ȡ�����α�    
cursor = conn.cursor()   

#ѡ�����ݿ�
cursor.execute("use zm")
cursor.execute("set names utf8")
	
def main():
	#��ȡ���üƻ���ǰ���豸
	record_num=cursor.execute("select Monitors.Id,Monitors.Name,Monitors.Function,Monitors.Path,Monitors.Width,Monitors.Height,Monitors.WarmupCount,Monitors.SectionLength,Monitors.Plan_id,Plans.Type,Plans.Is_record from Monitors,Plans,Groups where Plan_id!=0 and Monitors.Plan_id=Plans.Id and Monitors.Mgroup=Groups.Id and Groups.Is_mount=1 order by Monitors.Id asc")

	#���δ��¼�����ҿ����Զ�¼���ǰ���豸������0��ִ���Զ����Ϳ�ʼ¼���socket����
	if record_num>0:
		monitor_arrs=cursor.fetchall()
		
		for monitor_arr in monitor_arrs:					
			mid=monitor_arr[0]
			mname=monitor_arr[1]
			function=monitor_arr[2]			
			path=monitor_arr[3]
			width=monitor_arr[4]
			height=monitor_arr[5]
			warmupcount=monitor_arr[6]
			sectionlength=monitor_arr[7]
			plan_id=monitor_arr[8]
			type=monitor_arr[9]
			is_record=monitor_arr[10]
			
			key=0
			timepart_num=cursor.execute("select Timepart from Timeparts where Pid="+str(plan_id)+" order by Id asc");
			
			if timepart_num>0:
				timepart_arrs=cursor.fetchall()
				for timepart_arr in timepart_arrs:
					timepart=timepart_arr[0].split(" ")
					
					start_time_arr=timepart[1].split(":")
					end_time_arr=timepart[3].split(":")
					
					start_hour=int(start_time_arr[0])
					end_hour=int(end_time_arr[0])
					
					start_min=int(start_time_arr[1])
					end_min=int(end_time_arr[1])
									
					#�ж�����ѭ������ʱ���
					if type==1:
						start_week=int(timepart[0])
						end_week=int(timepart[2])
						
						start_num=start_hour*60+start_min
						end_num=end_hour*60+end_min
						
						local_num=time.localtime().tm_hour*60+time.localtime().tm_min
						
						#�ж��ڲ���ʱ���
						if time.localtime().tm_wday>=start_week and time.localtime().tm_wday<=end_week:						
							if start_week==end_week:
								#�����ͬһ��
								if local_num>=start_num and local_num<end_num:
									key+=1			
								else:
									pass
							else:
								#�������ͬһ��
								if time.localtime().tm_wday==start_week:
									if local_num>=start_num:
										key+=1
									else:
										pass
								elif time.localtime().tm_wday==end_week:
									if local_num<end_num:
										key+=1
									else:
										pass							
								else:
									key+=1							
						else:
							pass
					else:
						start_day=timepart[0]
						end_day=timepart[2]
						
						start_unix=int(time.mktime(time.strptime(start_day+" "+str(start_hour)+":"+str(start_min)+":0",'%Y-%m-%d %H:%M:%S')))
						end_unix=int(time.mktime(time.strptime(end_day+" "+str(end_hour)+":"+str(end_min)+":0",'%Y-%m-%d %H:%M:%S')))
						
						now_unix=int(time.time())
						
						#�ж��ڲ���ʱ���
						if now_unix>=start_unix and now_unix<end_unix:
							key+=1
						else:
							pass
			else:
				key-=1
			
			
			#�ж��ڲ���ʱ���
			if key>0:
				#�ж��ǲ���Ҫ¼��
				if 	is_record==1:
					op="start"
				else:	
					op="stop"
				
			elif key==0:
				#�ж��ǲ���Ҫ¼��
				if 	is_record==1:
					op="stop"
				else:
					op="start"
			elif key<0:
				op="pass"				
			else:
				op="pass"
			
			#print op
			
			#�����жϽ����IPCִ����صĲ�������ʼ¼��ֹͣ¼������κβ�����
			if op=="start":
				start_record(mid,mname,path,width,height,warmupcount,sectionlength,function)
			elif op=="stop":
				stop_record(mid,function)
			else :
				pass
			
			

#��ʼ¼����			
def start_record(mid,mname,path,width,height,warmupcount,sectionlength,function):		
	
	if function=="Nodect":
		
		c=os.popen("/usr/local/bin/NVRCommandLine -C start -i 1000 -m "+str(mid)+" -u '"+path+"' ").readline()
		time.sleep(2)
		r=os.system("/usr/local/bin/NVRCommandLine -R start -i 1000 -m "+str(mid)+" -d "+str(sectionlength)+" -h "+str(height)+" -l "+str(width)+" -v "+str(warmupcount))
		 
		if(int(c)==0&r==0):
			cursor.execute("update Monitors set Function='Record' where Id="+str(mid))
		
	elif function=="Watch":		
	
		r=os.system("/usr/local/bin/NVRCommandLine -R start -i 1000 -m "+str(mid)+" -d "+str(sectionlength)+" -h "+str(height)+" -l "+str(width)+" -v "+str(warmupcount))	
		
		if(r==0):
			cursor.execute("update Monitors set Function='SameTime' where Id="+str(mid))
		
	else:
		pass
	
	
	
	
#ֹͣ¼����	
def stop_record(mid,function):

	if function=="SameTime":
	
		r=os.system("/usr/local/bin/NVRCommandLine -R stop -i 1000 -m "+str(mid))
		
		if(r==0):
			cursor.execute("update Monitors set Function='Watch' where Id="+str(mid))
		
	elif function=="Record":
		
		r=os.system("/usr/local/bin/NVRCommandLine -R stop -i 1000 -m "+str(mid))
		c=os.popen("/usr/local/bin/NVRCommandLine -C stop -i 1000 -m "+str(mid)).readline()
		time.sleep(2)
		
		if(int(c)==0&r==0):
			cursor.execute("update Monitors set Function='Nodect' where Id="+str(mid))
		
	else:
		pass
	
		
	

if __name__ == '__main__':
	main()
	
#�ر����ӣ��ͷ���Դ     
cursor.close(); 
conn.close();