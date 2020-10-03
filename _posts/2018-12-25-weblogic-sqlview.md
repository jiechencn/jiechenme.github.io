---
title: SQLView-用Python将Weblogic JDBC数据可视化
author: Jie Chen
date: 2018-12-25
categories: [Python]
tags: [jdbc,weblogic]
---

## When to run SQLView?

If you have any issue of SQL data related, or you have to analyze SQL execution time, then use SQLView. The case could be like:

* No expected data from Search
* Duplicated rows returned from Search
* Potential long running SQL

How to run SQLView?

1. Copy SQLView.py to Weblogic DOMAIN_HOME\bin\ folder
2. Run environment setting script of Weblogic

	> Windows: setEnv.cmd
	
	> Linux     : source ./setEnv.sh
		
3. Run command:

	> java weblogic.WLST SQLView.py
		
4. Follow the instruction to proceed on


## Sample of SQLView Execution

### Online mode

This mode enables you to collect SQL data dynamically on a running Agile Application Server while reproducing the problem. Please note:

* In case of Weblogic Cluster, you should run SQLView in one of the Weblogic Managed Servers and logon Agile Web Client of this Managed Server only to reproduce the problem
* When SQLView prompts for "server URL", you must input Weblogic Admin Server and its port, like t3://weblogic-admin.mycompany.com:9001

.


	E:\app\oracle\Agile\Agile936\agileDomain\bin>setEnv.cmd

	CLASSPATH="C:\PROGRA~1\Java\JDK18~1.0_1\lib\tools.jar;E:\app\oracle\Middleware\Oracle_Home\wlserver\modules\features\wlst.wls.classpath.jar;"

	PATH=";E:\app\oracle\Middleware\Oracle_Home\wlserver\server\native\win\x64;E:\app\oracle\Middleware\Oracle_Home\wlserver\server\bin;E:\app\oracle\Middleware\Oracle_Home\oracle_common\modules\thirdpart
	y\org.apache.ant\1.9.8.0.0\apache-ant-1.9.8\bin;C:\PROGRA~1\Java\JDK18~1.0_1\jre\bin;C:\PROGRA~1\Java\JDK18~1.0_1\bin;E:\app\oracle\Middleware\Oracle_Home\wlserver\server\native\win\x64\oci920_8;C:\PR
	OGRA~1\Java\JDK18~1.0_1\bin;E:\app\oracle\product\12.2.0\dbhome_1\bin;C:\PROGRA~2\COMMON~1\Oracle\Java\javapath;C:\Windows\System32;C:\Windows;C:\Windows\System32\wbem;C:\Windows\System32\WINDOW~1\v1.
	0\;C:\Users\oracle\AppData\Local\Programs\Fiddler;E:\app\oracle\Middleware\Oracle_Home\oracle_common\modules\org.apache.maven_3.2.5\bin"

	Your environment has been set.

	E:\app\oracle\Agile\Agile936\agileDomain\bin>java weblogic.WLST SQLView.py

	Initializing WebLogic Scripting Tool (WLST) ...

	Welcome to WebLogic Server Administration Scripting Shell

	Type help() for help on available commands

							   >> Welcome to SQLView 1.0
							   >> Jython Version: 2.2.1
							   >> Type the number to select mode
							   >>
							   >>     0:  Online mode
							   >>     1:  Offline mode (for Oracle Support only)
							   >>
							   >> Your choice: 0
							   >> Weblogic Version: WebLogic Server 12.2.1.3.0
							   >> Note: You must run this script on the Weblogic Managed Server which you connect.
							   >>
	Please enter your username :superadmin
	Please enter your password :superadmin's password
	Please enter your server URL [t3://localhost:7001] :t3://weblogic-admin.mycompany.com:9001
	Connecting to t3://weblogic-admin.mycompany.com:9001 with userid superadmin ...
	Successfully connected to Admin Server "9366sy-ws12-u1c-Admin" that belongs to domain "agileDomain".

	Warning: An insecure protocol was used to connect to the server.
	To ensure on-the-wire security, the SSL port or Admin port should be used instead.

	Location changed to edit tree.
	This is a writable tree with DomainMBean as the root.
	To make changes you will need to start an edit session via startEdit().
	For more help, use help('edit').

	Starting an edit session ...
	Started edit session, be sure to save and activate your changes once you are done.
							   >> Find following Weblogic instance(s): 3
							   >>
							   >>     0: 9366sy-ws12-u1c-Admin
							   >>     1: 9366sy-ws12-u1c-Managed1
							   >>     2: 9366sy-ws12-u1c-Managed2
							   >>
							   >> Type the number to select the correct Weblogic instance to connect, or type x to exit.
							   >> Your choice: 1
							   >> Get log file: logs/9366sy-ws12-u1c-Managed1.log
							   >> Get log size: 5000
							   >> Get log date format: MMM d, yyyy h:mm:ss,SSS a z
							   >> Set log size: 50000
							   >> Set log date format: MMM d, yyyy h:mm:ss,SSS a z
							   >> Set DebugJDBCSQL: true
	Saving all your changes ...
	Saved all your changes successfully.
	Activating all your changes, this may take a while ...
	The edit lock associated with this edit session is released once the activation is completed.
	Activation completed
							   >> It is collecting SQL data. Press Enter after collected.
							   >> [Press Enter after you reproduce problem]
							   >> SQLView is waiting for Weblogic to flush log, please hold on...
							   >> ...........................
							   >> Copy logs/9366sy-ws12-u1c-Managed1.log to E:\app\oracle\Agile\Agile936\agileDomain\bin/../servers/9366sy-ws12-u1c-Managed1/logs/9366sy-ws12-u1c-Managed1.log.SQLView
	Already in requested Edit Tree

	Starting an edit session ...
	Started edit session, be sure to save and activate your changes once you are done.
							   >> Get server: 9366sy-ws12-u1c-Managed1
							   >> Reset log size: 5000
							   >> Reset log date format: MMM d, yyyy h:mm:ss,SSS a z
							   >> Reset DebugJDBCSQL: false
	Saving all your changes ...
	Saved all your changes successfully.
	Activating all your changes, this may take a while ...
	The edit lock associated with this edit session is released once the activation is completed.
	Activation completed
	Disconnected from weblogic server: 9366sy-ws12-u1c-Admin
							   >> Generating HTML Report...
							   >>
							   >> HTML Report file: E:\app\oracle\Agile\Agile936\agileDomain\bin/SQLView-9366sy-ws12-u1c-Managed1.html
							   >> JDBC Log file   : E:\app\oracle\Agile\Agile936\agileDomain\bin/../servers/9366sy-ws12-u1c-Managed1/logs/9366sy-ws12-u1c-Managed1.log.SQLView
							   >>
							   >> Script quits. Bye


	Exiting WebLogic Scripting Tool.

### Offline mode

If you have the JDBC DEBUG log file on hand, which is collected by following the second option of this Note (1061572.1 WLS JDBC Datasources and SQL Statements Debugging), you can copy the JDBC DEBUG log file to DOMAIN_HOME\bin\ directory, then run SQLView in offline mode to generate the HTML report directly without problem replication again.


	E:\app\oracle\Agile\Agile936\agileDomain\bin>setEnv.cmd

	CLASSPATH="C:\PROGRA~1\Java\JDK18~1.0_1\lib\tools.jar;E:\app\oracle\Middleware\Oracle_Home\wlserver\modules\features\wlst.wls.classpath.jar;"

	PATH=";E:\app\oracle\Middleware\Oracle_Home\wlserver\server\native\win\x64;E:\app\oracle\Middleware\Oracle_Home\wlserver\server\bin;E:\app\oracle\Middleware\Oracle_Home\oracle_common\modules\thirdpart
	y\org.apache.ant\1.9.8.0.0\apache-ant-1.9.8\bin;C:\PROGRA~1\Java\JDK18~1.0_1\jre\bin;C:\PROGRA~1\Java\JDK18~1.0_1\bin;E:\app\oracle\Middleware\Oracle_Home\wlserver\server\native\win\x64\oci920_8;C:\PR
	OGRA~1\Java\JDK18~1.0_1\bin;E:\app\oracle\product\12.2.0\dbhome_1\bin;C:\PROGRA~2\COMMON~1\Oracle\Java\javapath;C:\Windows\System32;C:\Windows;C:\Windows\System32\wbem;C:\Windows\System32\WINDOW~1\v1.
	0\;C:\Users\oracle\AppData\Local\Programs\Fiddler;E:\app\oracle\Middleware\Oracle_Home\oracle_common\modules\org.apache.maven_3.2.5\bin"

	Your environment has been set.

	E:\app\oracle\Agile\Agile936\agileDomain\bin>java weblogic.WLST SQLView.py

	Initializing WebLogic Scripting Tool (WLST) ...

	Jython scans all the jar files it can find at first startup. Depending on the system, this process may take a few minutes to complete, and WLST may not return a prompt right away.

	Welcome to WebLogic Server Administration Scripting Shell

	Type help() for help on available commands

							   >> Welcome to SQLView 1.0
							   >> Jython Version: 2.2.1
							   >> Type the number to select mode
							   >>
							   >>     0:  Online mode
							   >>     1:  Offline mode (for Oracle Support only)
							   >>
							   >> Your choice: 1
							   >> Please copy the JDBC log file to directory:  E:\app\oracle\Agile\Agile936\agileDomain\bin
							   >>
							   >> JDBC log file name: agilePCWeblogic.log
							   >> Generating HTML Report...
							   >>
							   >> HTML Report file: E:\app\oracle\Agile\Agile936\agileDomain\bin/SQLView-Offline.html
							   >> JDBC Log file   : E:\app\oracle\Agile\Agile936\agileDomain\bin/agilePCWeblogic.log
							   >>
							   >> Script quits. Bye


	Exiting WebLogic Scripting Tool.
	
### Check Report

 After SQLView executed, below reports are generated.

    HTML Report file
    JDBC Log file

## Download SQLView
	
<a href="/lab/SQLView.py" target="_blank">Version: SQLView 1.0</a>


