---
title: Transaction Timeout in Agile + Weblogic
author: Jie Chen
date: 2013-08-07
categories: [AgilePLM]
tags: [weblogic,stdout]
---

To rotate the stdout.log of Weblogic on Windows Service is quite easy to achieve. It is different from other server log and platforms for Weblogic. I have no idea why Weblogic design as that but it is really tricky. We will see how it setups and how it works for Agile PLM from below presentation.

## How to setup

When we install Agile PLM service against Weblogic for Windows, we use "beasvc -install -svcname:AgilePLM" command in installService.cmd. So we need to modify the CMD file.

First, we need to remove "-Dweblogic.Stdout=%STDOUT% -Dweblogic.Stderr=%STDERR%"

Then we add -log:%STDOUT% to the end of command line "beasvc -install -svcname:AgilePLM".

If to put it together we have:

	set STDOUT="C:\Agile\Agile931/agileDomain/servers/SLAG9311W8-1-AgileServer/logs/stdout.log"

	set CMDLINE="-server -XX:MaxPermSize=256M -ms1280M -mx1280M -XX:NewSize=256M -XX:MaxNewSize=256M -classpath \"%CLASSPATH%\" %JMX_SET% -Dweblogic.Name=SLAG9311W8-1-AgileServer \"-Dbea.home=C:\bea\" -Dweblogic.management.username=%WLS_USERNAME% -Dweblogic.management.password=%WLS_PW% -Dweblogic.ProductionModeEnabled=%STARTMODE% \"-Djava.security.policy==C:\bea\wlserver_10.3/server/lib/weblogic.policy\" -Dagile.log.dir=C:\Agile\Agile931/agileDomain/servers/SLAG9311W8-1-AgileServer/logs -Dlog4j.configuration=file:.\config\log.xml weblogic.Server"

	rem *** Install the service
	"%WLS_HOME%\server\bin\beasvc" -install -svcname:"AgilePLM" -javahome:"%JAVA_HOME%" -execdir:"%INSTALL_DIR%" -extrapath:"%WLS_HOME%\server\bin;%JAVA_HOME%\bin" -cmdline:%CMDLINE% -password:%WLS_PW% -log:%STDOUT%

Since we expect the stdout to file "C:\Agile\Agile931/agileDomain/servers/SLAG9311W8-1-AgileServer/logs/stdout.log", so we need to edit it (if it does not exist, must create it manually), add below lines in the head of stdout.log file.

Attention, we add 5 lines in the stdout.log, and must press Enter/Carriage Return after the last line "#"

	#
	# ROTATION_TYPE = SIZE
	# SIZE_KB = 100
	# SIZE_TRIGGER_INTERVAL_MINS = 3
	#

Then we re-install the Agile PLM service, you will see stdout.log will be rotated each 3 minutes if the filesize is larger than 100K.

## How it works

When AgilePLM service runs, Weblogic check if the filesize is larger than defined size (100K in this sample). If it is, it will immediately rotate to new stdout log file with timestamp as the filename. During runtime, weblogic will check every 3 minutes as interval, if it is again larger than 100K, it will rotate to a second stdout log file, and so on.

Unlike other log rotation, the stdout.log rotation for Windows Service has different filesize for each stdout log file. From below screenshot, we see each stdout.log-xxxxxxxxx has different size.

![](/assets/res/troubleshooting-weblogic-rotatewlsstdoutlog-1.jpg)

That is to say, suppose we have current stdout.log filesize 420K, it does NOT rotate to 4 log files (420/100=4). Actually it only rotate to a single file like stdout.log-2013_08_06-05_44_05, then create a blank stdout.log replicated with only below content. That is to say, each stdout.log-xxxxxx has exactly same file header like below.

	#
	# ROTATION_TYPE = SIZE
	# SIZE_KB = 100
	# SIZE_TRIGGER_INTERVAL_MINS = 3
	#






