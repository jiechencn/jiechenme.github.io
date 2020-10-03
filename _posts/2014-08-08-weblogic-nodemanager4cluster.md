---
title: 为Weblogic集群中的节点配置Node Manager
author: Jie Chen
date: 2014-08-08
categories: [Weblogic]
tags: []
---

为Weblogic集群中的所有服务器配置节点管理器，原来比较简单，但是过程有些复杂。下面通过一个具体的实例来演示如何配置。实验中我们使用Oracle Agile PLM产品的集群环境来做验证。实验方法可以运用到Weblogic中发布的其他企业系统中去，原理是一模一样的。

假设我们有3台Linux的机器，1台为Admin Server，另外两台分别为Managed Server。具体主机信息列下：

	# Machine slag9310w5c.mycompany.com
	Weblogic Server Location: /opt/bea/wlserver_10.3
	Admin Server Name: slag9310w5c-AgileServer
	Listen Address: slag9310w5c.mycompany.com:7001
	Agile Domain location: /opt/agile0/agileDomain

	# Machine slag9310w5c-1.mycompany.com
	Weblogic Server Location: /opt/bea/wlserver_10.3
	Managed Server 1 Name: slag9310w5c-ManagedServer1
	Listen Address: slag9310w5c-1.mycompany.com:7001
	Agile Domain location: /opt/agile1/agileDomain

	# Machine slag9310w5c-2.mycompany.com
	Weblogic Server Location: /opt/bea/wlserver_10.3
	Managed Server 2 Name: slag9310w5c-ManagedServer2
	Listen Address: slag9310w5c-2.mycompany.com:7001
	Agile Domain location: /opt/agile2/agileDomain

## 为节点添加Machine
	
接下来为这三台服务器配置三个Node Manager，也就是需要3个Machine。命名这三个Machine如下：分别对应一台weblogic的服务器。

	# Node Manager for Admin Server
	slag9310w5c --> slag9310w5c.mycompany.com:6666
	# Node Manager for Managed Server 1
	slag9310w5c-1 --> slag9310w5c-1.mycompany.com:6667
	# Node Manager for Managed Server 2
	slag9310w5c-2 --> slag9310w5c-2.mycompany.com:6668

计划设置好后，我们登录Weblogic控制台，添加三台Machine，标注3个端口。

![](/assets//res/weblogic_nodemanager_1.jpg)

![](/assets//res/weblogic_nodemanager_2.jpg)

然后，分别将3台Machine分配给Admin Server和2个Managed Server。

![](/assets//res/weblogic_nodemanager_3.jpg)

在config.xml文件中，我们可以看到相应的设置自动产生了。

![](/assets//res/weblogic_nodemanager_4.jpg)

![](/assets//res/weblogic_nodemanager_5.jpg)


## 配置Node Manager

在三台服务器上，我们分别创建他们各自的Node Manager。首先创建必须的目录结构。

	# Admin Server‘s Node Manager location: /opt/agile0/nodemanager/slag9310w5c
	[oracle@slag9310w5c agile0]$ pwd
	/opt/agile0
	[oracle@slag9310w5c agile0]$ mkdir -p nodemanager/slag9310w5c

	# Managed Server 1‘s Node Manager location: /opt/agile1/nodemanager/slag9310w5c-1
	[oracle@slag9310w5c-1 agile1]$ pwd
	/opt/agile1
	[oracle@slag9310w5c-1 agile1]$ mkdir -p nodemanager/slag9310w5c-1

	# Managed Server 2‘s Node Manager location: /opt/agile2/nodemanager/slag9310w5c-2
	[oracle@slag9310w5c-2 agile2]$ pwd
	/opt/agile2
	[oracle@slag9310w5c-2 agile2]$ mkdir -p nodemanager/slag9310w5c-2

从Weblogic服务器上拷贝 startNodeManager.sh到Node Manager上去。

	# On Admin Server‘s machine
	[oracle@slag9310w5c slag9310w5c]$ pwd
	/opt/agile0/nodemanager/slag9310w5c
	[oracle@slag9310w5c slag9310w5c]$ cp /opt/bea/wlserver_10.3/server/bin/startNodeManager.sh startNodeManager.sh

修改NODEMGR_HOME，指向真正的Node Manager目录，同时JAVA_OPTIONS参数中设置 weblogic.nodemanager.ServiceEnabled=true，目地是为了解决Weblogic的一个bug，就似乎nmStart命令无法返回给终端的问题。

	# On Admin Server‘s Node Manager dirctory, edit /opt/agile0/nodemanager/slag9310w5c/startNodeManager.sh
	NODEMGR_HOME="/opt/agile0/nodemanager/slag9310w5c"
	JAVA_OPTIONS="${JAVA_OPTIONS} -Dweblogic.nodemanager.ServiceEnabled=true"

同样，修改另外2个Managed Server上的startNodeManager.sh。

	# On Admin Server, /opt/agile0/nodemanager/slag9310w5c/startNodeManager.sh
	NODEMGR_HOME="/opt/agile0/nodemanager/slag9310w5c"
	JAVA_OPTIONS="${JAVA_OPTIONS} -Dweblogic.nodemanager.ServiceEnabled=true"

	# On Managed Server 1, /opt/agile1/nodemanager/slag9310w5c-1/startNodeManager.sh
	NODEMGR_HOME="/opt/agile1/nodemanager/slag9310w5c-1"
	JAVA_OPTIONS="${JAVA_OPTIONS} -Dweblogic.nodemanager.ServiceEnabled=true"

	# On Managed Server 2, /opt/agile2/nodemanager/slag9310w5c-2/startNodeManager.sh
	NODEMGR_HOME="/opt/agile2/nodemanager/slag9310w5c-2"
	JAVA_OPTIONS="${JAVA_OPTIONS} -Dweblogic.nodemanager.ServiceEnabled=true"

在每一个NodeManager目录下新建 nodemanager.properties 文件。

	# On Admin Server, /opt/agile0/nodemanager/slag9310w5c/nodemanager.properties
	ListenAddress=slag9310w5c.mycompany.com
	ListenPort=6666
	SecureListener=false
	StartScriptEnabled=true
	StartScriptName=startServerAdminFromNM.sh
	NativeVersionEnabled=true
	CrashRecoveryEnabled=true

	# On Managed Server 1, /opt/agile1/nodemanager/slag9310w5c-1/nodemanager.properties
	ListenAddress=slag9310w5c-1.mycompany.com
	ListenPort=6667
	SecureListener=false
	StartScriptEnabled=true
	StartScriptName=startServerManaged1FromNM.sh
	NativeVersionEnabled=true
	CrashRecoveryEnabled=true

	# On Managed Server 2, /opt/agile2/nodemanager/slag9310w5c-2/nodemanager.properties
	ListenAddress=slag9310w5c-2.mycompany.com
	ListenPort=6668
	SecureListener=false
	StartScriptEnabled=true
	StartScriptName=startServerManaged2FromNM.sh
	NativeVersionEnabled=true
	CrashRecoveryEnabled=true

默认情况下，Node Manager启动时会启动startWeblogic.sh文件。但很多情况下我们的产品服务器的启动脚本不是这个文件名，需要使用我们自己的文件名。因此设置参数StartScriptName，同时StartScriptEnabled必须为true。NativeVersionEnabled参数目地是能够使用emKill命令远程地停止服务器。CrashRecoveryEnabled能够使得服务器在异常当机的情况下能自动启动。

从上面的设置中我们直到NodeManager会调用startServerAdminFromNM.sh文件来启动Admin Server，调用startServerManaged1FromNM.sh启动其中第一个Managed Server，startServerManaged2FromNM.sh启动另外一个Managed Server。因为这三个文件不存在，所以需要我们自己创建，可以从服务器的启动脚本复制过来。

	# Admin Server /opt/agile0/agileDomain/bin/ directory
	[oracle@slag9310w5c bin]$ cp startServerAgileAdmin.sh startServerAdminFromNM.sh

	# Managed Server 1 /opt/agile1/agileDomain/bin/ directory
	[oracle@slag9310w5c-1 bin]$ cp startServerAgileManaged1.sh startServerManaged1FromNM.sh

	# Managed Server 2 /opt/agile2/agileDomain/bin/ directory
	[oracle@slag9310w5c-2 bin]$ cp startServerAgileManaged2.sh startServerManaged2FromNM.sh

理论上我们可以直接使用服务器里的启动脚本，但之所以这么做，是因为Node Manager启动这些脚本的时候是从自己的进程中调用触发，而这些脚本和Node Manager存在环境参数特别是目录绝对路径的差异。所以我们需要对这三个文件做一些小小的修改。

	# Admin Server /opt/agile0/agileDomain/bin/startServerAdminFromNM.sh
	#. ./setEnv.sh   -- comment it
	#cd ..           -- comment it
	. /opt/agile0/agileDomain/bin/setEnv.sh

	# Managed Server 1 /opt/agile1/agileDomain/bin/startServerManaged1FromNM.sh
	#. ./setEnv.sh   -- comment it
	#cd ..           -- comment it
	. /opt/agile1/agileDomain/bin/setEnv.sh

	# Managed Server 2 /opt/agile2/agileDomain/bin/startServerManaged2FromNM.sh
	#. ./setEnv.sh   -- comment it
	#cd ..           -- comment it
	. /opt/agile2/agileDomain/bin/setEnv.sh

同时，需要给Java增加一个参数：weblogic.nodemanager.ServiceEnabled=true，比如：

	"$JAVA_HOME/bin/java"  ... ... -Dweblogic.nodemanager.ServiceEnabled=true weblogic.Server

## Node Manager加入Domain

将Node Manager加入Domain，需要在三台服务器上分别操作。在加入之前，确保Admin Server已经启动。

	[oracle@slag9310w5c bin]$ pwd
	/opt/agile0/agileDomain/bin

	[oracle@slag9310w5c bin]$ source setEnv.sh
	Your environment has been set.

执行 weblogic.WLST

	[oracle@slag9310w5c bin]$ java weblogic.WLST

	Initializing WebLogic Scripting Tool (WLST) ...
	Jython scans all the jar files it can find at first startup. Depending on the system, this process may take a few minutes to complete, and WLST may not return a prompt right away.
	Welcome to WebLogic Server Administration Scripting Shell
	Type help() for help on available commands

connect命令连接至Admin Server

	wls:/offline> connect(‘superadmin‘, ‘agile‘, ‘t3://slag9310w5c.mycompany.com:7001‘)
	Connecting to t3://slag9310w5c.mycompany.com:7001 with userid superadmin ...
	Successfully connected to Admin Server ‘slag9310w5c.mycompany.com-AgileServer‘ that belongs to domain ‘agileDomain‘.
	Warning: An insecure protocol was used to connect to the server. To ensure on-the-wire security, the SSL port or Admin port should be used instead.

将Admin Server的 Node Manager 加入Domain。

	wls:/agileDomain/serverConfig> nmEnroll(‘/opt/agile0/agileDomain‘, ‘/opt/agile0/nodemanager/slag9310w5c‘)
	Enrolling this machine with the domain directory at /opt/agile0/agileDomain ...
	Successfully enrolled this machine with the domain directory at /opt/agile0/agileDomain.

	wls:/agileDomain/serverConfig> exit()
	Exiting WebLogic Scripting Tool.

到此，我们已经将Admin Server的Node Manager加入到Domain中了。接下来，对其他Managed Server的Node Manager同样方式加入到Domain中去。总共用到如下几个命令。

	source setEnv.sh
	java weblogic.WLST
	connect(‘superadmin‘, ‘agile‘, ‘t3://slag9310w5c.mycompany.com:7001‘)
	nmEnroll(‘/opt/agile1/agileDomain‘, ‘/opt/agile1/nodemanager/slag9310w5c-1‘)  # on Managed Server 1 host
	nmEnroll(‘/opt/agile2/agileDomain‘, ‘/opt/agile2/nodemanager/slag9310w5c-2‘)  # on Managed Server 2 host

## 启动每个Node Manager

登录Admin Server的服务器，运行它的Node Manager。

	[oracle@slag9310w5c slag9310w5c]$ pwd
	/opt/agile0/nodemanager/slag9310w5c
	[oracle@slag9310w5c slag9310w5c]$ ./startNodeManager.sh
	...
	...
	  
	Jul 24, 2014 11:06:39 PM weblogic.nodemanager.server.Listener run
	INFO: Plain socket listener started on port 6,666, host slag9310w5c.mycompany.com

登录另外Managed Server机器，运行他们各自的Node Manager

## 远程启动Weblogic服务器

Admin Server的NodeManager启动后，可以从远程启动Admin Server。

	nmConnect: connect to remote Node Manager
	nmStart: start remote Weblogic Server
	nmServerStatus: check remote server status
	nmKill: stop remote Weblogic 

比如，我要从我自己本地电脑的Weblogic中远程启动生产环境里的Admin Server，可以执行类似如下的命令：

	C:\Oracle\Middleware\wlserver_12.1\server\bin>setWLSEnv.cmd
	Your environment has been set.

	C:\Oracle\Middleware\wlserver_12.1\server\bin>java weblogic.WLST

	Initializing WebLogic Scripting Tool (WLST) ...
	Welcome to WebLogic Server Administration Scripting Shell
	Type help() for help on available commands

	wls:/offline> nmConnect(‘superadmin‘, ‘agile‘, ‘slag9310w5c.mycompany.com‘, ‘6666‘, ‘agileDomain‘,‘/opt/agile0/agileDomain‘,‘plain‘)
	Connecting to Node Manager ...
	Successfully Connected to Node Manager.
	wls:/nm/agileDomain> nmStart(‘slag9310w5c.mycompany.com-AgileServer‘)
	Starting server slag9310w5c.mycompany.com-AgileServer ...
	Successfully started server slag9310w5c.mycompany.com-AgileServer ...

	wls:/nm/agileDomain> nmServerStatus(‘slag9310w5c.mycompany.com-AgileServer‘)
	RUNNING

	wls:/nm/agileDomain> nmKill(‘slag9310w5c.mycompany.com-AgileServer‘)
	Killing server slag9310w5c.mycompany.com-AgileServer ...
	Successfully killed server slag9310w5c.mycompany.com-AgileServer ...

注意nmConnect中的密码，这个是Node Manager的密码，不是管理控制台的登录密码。该密码在控制台中需要另外设置。

![](/assets//res/weblogic_nodemanager_6.jpg)

查看NodeManager的日志文件
 
	INFO: Starting WebLogic server with command line: /opt/agile0/agileDomain/bin/startServerAdminFromNM.sh
		
	Jul 24, 2014 11:07:15 PM weblogic.nodemanager.server.ServerManager log
	INFO: Server output log file is ‘/opt/agile0/agileDomain/servers/slag9310w5c.mycompany.com-AgileServer/logs/slag9310w5c.mycompany.com-AgileServer.out

Admin Server上的启动脚本startServerAdminFromNM.sh被Node Manager调用，服务器日志写到slag9310w5c.mycompany.com-AgileServer.out中。

这是远程启动AdminServer的方法，启动Managed Server也是一样的，比如如下：

	setWLSEnv.cmd
	java weblogic.WLST
	nmConnect(‘superadmin‘, ‘agile‘, ‘slag9310w5c-1.mycompany.com‘,‘6667‘, ‘agileDomain‘,‘/opt/agile1/agileDomain‘,‘plain‘)
	nmStart(‘slag9310w5c-1.mycompany.com‘)
	nmServerStatus(‘slag9310w5c-1.mycompany.com‘)
	nmKill(‘slag9310w5c-1.mycompany.com‘)

远程启动Managed Server还有另外一个方法，就是通过Weblogic的管理控制台：

![](/assets//res/weblogic_nodemanager_7.jpg)

 