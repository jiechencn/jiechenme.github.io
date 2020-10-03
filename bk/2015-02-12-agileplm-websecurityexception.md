---
title: com.agile.ui.web.security.WebSecurityException
author: Jie Chen
date: 2015-02-12
categories: [AgilePLM]
tags: []
---

ESAPI is an open source used by Oracle Agile Web Client to protect Web application's security, avoid kinds of web attack like XSS, Injection, CSRF and so on. This API component must be present and your HTTP request must legal, or Agile WebClient throws com.agile.ui.web.security.WebSecurityException error and refuse to service client's request. I will discuss briefly how to handle two kinds of issue of WebSecurityException.

## Case of WebClient Unaccessible

This only happens if below three ESAPI configuration files are lost, not readable or not of Agile Customization.

* antisamy-esapi.xml
* ESAPI.properties
* validation.properties

We will get error message in browser with following.

	Error 500--Internal Server Error
	From RFC 2068 Hypertext Transfer Protocol -- HTTP/1.1:
	10.5.1 500 Internal Server Error
	The server encountered an unexpected condition which prevented it from fulfilling the request.

And error in server log as following.

	Attempting to load ESAPI.properties via file I/O.
	Attempting to load ESAPI.properties as resource file via file I/O.
	Not found in 'org.owasp.esapi.resources' directory or file not readable: /u01/agile/agile933/agileDomain/ESAPI.properties
	Not found in SystemResource Directory/resourceDirectory: .esapi/ESAPI.properties
	Not found in 'user.home' (/home/oracle) directory: /home/oracle/esapi/ESAPI.properties
	Loading ESAPI.properties via file I/O failed. Exception was: java.io.FileNotFoundException
	Attempting to load ESAPI.properties via the classpath.
	ESAPI.properties could not be loaded by any means. Fail. Exception was: java.lang.IllegalArgumentException: Failed to load ESAPI.properties as a classloader resource.
		<[ServletContext@611231314[app:AgilePLM module:/Agile path:null spec-version:3.0]] Servlet failed with an Exception
	java.lang.NoClassDefFoundError: Could not initialize class com.agile.ui.web.security.WebSecurityAPI
			at com.agile.ui.pcm.common.filter.WebSecurityFilter.doFilter(WebSecurityFilter.java:110)
			at weblogic.servlet.internal.FilterChainImpl.doFilter(FilterChainImpl.java:74)
			at weblogic.servlet.internal.WebAppServletContext$ServletInvocationAction.wrapRun(WebAppServletContext.java:3288)
			at weblogic.servlet.internal.WebAppServletContext$ServletInvocationAction.run(WebAppServletContext.java:3254)
			at weblogic.security.acl.internal.AuthenticatedSubject.doAs(AuthenticatedSubject.java:321)
			Truncated. see log file for complete stacktrace

Or just one line of error in browser like below.

	An exception occurred while processing one of the input values, please contact the system admin for more details. 

And error in server log:

	Attempting to load ESAPI.properties via file I/O.
	Attempting to load ESAPI.properties as resource file via file I/O.
	Not found in 'org.owasp.esapi.resources' directory or file not readable: /u01/agile/agile933/agileDomain/ESAPI.properties
	Not found in SystemResource Directory/resourceDirectory: .esapi/ESAPI.properties
	Found in 'user.home' directory: /home/weblogic/esapi/ESAPI.properties
	Loaded 'ESAPI.properties' properties file
	Attempting to load validation.properties via file I/O.
	Attempting to load validation.properties as resource file via file I/O.
	Not found in 'org.owasp.esapi.resources' directory or file not readable: /u01/agile/agile933/agileDomain/validation.properties
	Not found in SystemResource Directory/resourceDirectory: .esapi/validation.properties
	Found in 'user.home' directory: /home/weblogic/esapi/validation.properties
	Loaded 'validation.properties' properties file
	SecurityConfiguration for Validator.AgileHeaderValue not found in ESAPI.properties. Using default: 
		<[ServletContext@1287452409[app:Agile module:/Agile path:null spec-version:3.0]] Servlet failed with an Exception
	com.agile.ui.web.security.WebSecurityException
		at com.agile.ui.web.security.WebSecurityAPI.getValidInput(WebSecurityAPI.java:582)
		at com.agile.ui.pcm.AgileServletResponseWrapper.validateHeaderValue(AgileServletResponseWrapper.java:55)
		at com.agile.ui.pcm.AgileServletResponseWrapper.setHeader(AgileServletResponseWrapper.java:48)
		at com.agile.ui.pcm.common.filter.WebSecurityFilter.clickjackPrevention(WebSecurityFilter.java:238)
		at com.agile.ui.pcm.common.filter.WebSecurityFilter.doFilter(WebSecurityFilter.java:164)
		Truncated. see log file for complete stacktrace

These three files are Agile customized distributed in Installation package. You should never use the default ones in EDAPI package to replace Agile's. When Agile WebClient is accessed by very first time, Agile will try to load these files from classpathes dominated by below two ClassLoader

1. sun.misc.Launcher$AppClassLoader
2. weblogic.utils.classloaders.GenericClassLoader

With additional customization, Agile will look for them following below orders and locations.

1. Java parameter: -Dorg.owasp.esapi.resources=/your_directoy_path
2. AGILE_HOME/agileDomain/esapi/
3. AGILE_HOME/agileDomain/config/esapi/
4. AGILE_HOME/agileDomain/
5. AGILE_HOME/agileDomain/config/
6. AGILE_HOME/agileDomain/servers/xxx-AgileServer/tmp/_WL_user/AgilePLM/xxxx/APP-INF/classes/esapi/ -- Default location
7. AGILE_HOME/agileDomain/servers/xxx-AgileServer/tmp/_WL_user/AgilePLM/xxxx/APP-INF/classes/resources/

The solution is to ask Oracle Support to send the out-of-box configuration files to one of the above locations.

## Case of WebClient Refuse Some Specific Request

You may see this error message in server log and find one specific function does not work randomly.

	<[ServletContext@423624099[app:Agile module:/Agile path:null spec-version:3.0]] Servlet failed with an Exception
	com.agile.ui.web.security.WebSecurityException
		   at com.agile.ui.web.security.WebSecurityAPI.verifyCsrfToken(WebSecurityAPI.java:827)
		   at com.agile.ui.pcm.common.filter.WebSecurityFilter.verifyCSRF(WebSecurityFilter.java:184)
		   at com.agile.ui.pcm.common.filter.WebSecurityFilter.doFilter(WebSecurityFilter.java:161)
		   at weblogic.servlet.internal.FilterChainImpl.doFilter(FilterChainImpl.java:74)
		   at weblogic.servlet.internal.WebAppServletContext$ServletInvocationAction.wrapRun(WebAppServletContext.java:3288)
		   at weblogic.servlet.internal.WebAppServletContext$ServletInvocationAction.run(WebAppServletContext.java:3254)
		   at weblogic.security.acl.internal.AuthenticatedSubject.doAs(AuthenticatedSubject.java:321)
		   at weblogic.security.service.SecurityManager.runAs(SecurityManager.java:120)
		   at weblogic.servlet.provider.WlsSubjectHandle.run(WlsSubjectHandle.java:57)
		   at weblogic.servlet.internal.WebAppServletContext.doSecuredExecute(WebAppServletContext.java:2163)
		   at weblogic.servlet.internal.WebAppServletContext.securedExecute(WebAppServletContext.java:2089)
		   at weblogic.servlet.internal.WebAppServletContext.execute(WebAppServletContext.java:2074)
		   at weblogic.servlet.internal.ServletRequestImpl.run(ServletRequestImpl.java:1513)
		   at weblogic.servlet.provider.ContainerSupportProviderImpl$WlsRequestExecutor.run(ContainerSupportProviderImpl.java:254)
		   at weblogic.work.ExecuteThread.execute(ExecuteThread.java:256)

It is difficult to diagnose as the HTTP request parameters varies quite differently. However it is still some basic troubleshooting regulations to abide by. Since this is the issue of Web, below steps are used to collect diagnostic data and send to Oracle Support.

1. Stop Application Server
2. Enable WebSecurity DEBUG

		## agile.properties
		WebSecurity.InvalidInputDiagnostic =true

		## log.xml set priority to DEBUG
		com.agile.ui.pcm.AgileServletResponseWrapper
		com.agile.ui.pcm.util.WebSecurityUtils
		com.agile.ui.pcm.common.filter.WebSecurityFilter

3. Restart Application Server
4. Replication the issue with Fiddler or tcpdump running simultaneously.

Related data is logged in agileWebSecurity.log and server output. 