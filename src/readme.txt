WebSphere Enhanced Monitor

--------------------------------------------
Description:
--------------------------------------------

This monitor has the following features:
- SSL-supported
- Can monitor specified JVM (i.e. recognizes multiple JVMs, need to specify which to monitor)
- Flexible design to allow for additional metrics to be added
- Monitor WebSphere by gathering the following metrics:
	CPU Usage	
	Heap Size	 
	Used Memory	
	Active Thread Count	
	Committed Transaction	
	Rolled Back Transaction	
	Servlet Session Live	
	Average Connection Used Time	
	Connection Pool Size	
	Free Pool Size	
	Connection Pool Used	
	Average Wait Time	
	Closed Connection	
	Waiting Threads	
	Response time	

--------------------------------------------
Requirements:
--------------------------------------------

1. cURL needs to be enabled in up.time.  To enable cURL, please do the following:
	1. Open the following file for edit:
		<uptime_dir>\apache\php\php.ini 
	2. Look for the following line in the above file:
		;extension=php_curl.dll
	3. Uncomment it by removing the ; as such:
		extension=php_curl.dll
	4. Restart the up.time webserver.  
		On *nix: /etc/init.d/uptime_httpd restart
		On Windows: look for "up.time Web Server" in Services and restart

2. PerfServlet needs to be deployed.  Please consult WebSphere documentation 
   on the procedure.

--------------------------------------------
Installation:
--------------------------------------------

Monitoring Station Installation:
1. Extract "WebSphereEnhancedPlugin-v0.3.zip" in a temporary directory

2. Place "MonitorWebSphere+Enhanced.xml" to <uptime_dir>

3. On the command line, execute the following:
	> cd <uptime_dir>
	> scripts/erdcloader -x MonitorWebSphere+Enhanced.xml

4. Copy the entire "EnhancedWebSphere" directory to the following location:
		<uptime_dir>\scripts\
	
	i.e. You should have <uptime_dir>\scripts\EnhancedWebSphere\
		 and inside the directory, you will find the following files:
			ews-ms.bat
			ews-ms.php
	
Now that the monitor is installed, you can go to the up.time UI and add a WebSphere Enhanced monitor.