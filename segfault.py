import os
now=int(float(os.popen('cat /proc/uptime').read().split(' ')[0]))
dmesg=os.popen('dmesg').readlines()
for i in dmesg:
	uptime=int(float(i.split(']')[0].replace('[','')))
	if now-uptime<=60 and i.find('perabytesNVR')>0 and i.find('segfault')>0:
		os.system('killall -9 perabytesNVR')
		os.system('python /var/www/zm/recover.py')
