---
title: Login/Logout impacted by USER_USAGE_HISTORY
author: Jie Chen
date: 2014-03-21
categories: [AgilePLM]
tags: []
---


You may see this type of error "The Session Has Been Terminated. Please Login Again" from Agile WebClient when you try to login. There are many possible root causes. This blog article discusses one of them, which happens on Weblogic, and presents the troubleshooting steps.

## READ THROUGH LOG

From stdout.log, we get "Session terminated" error which shows one user is trying to login, but immediately he is forced to logout. Just from this error, we cannot determine why his login is terminated.
  
	[PCMHelperSessionBean_9xz6y2_Impl:ERROR] Session terminated... 
	java.lang.NullPointerException
		at com.agile.util.sql.LocalConnectionFactory.getJDBCConnection(LocalConnectionFactory.java:82)
		...
		at com.agile.ipa.pc.CMHelper.logout(CMHelper.java:110)
		...
		at com.agile.ui.pcm.login.LoginHandler.forwardToForLoginError(LoginHandler.java:427)
		at com.agile.ui.pcm.login.LoginHandler.login(LoginHandler.java:269)

Now read another stderr.log, we can get a clearer clue that during password authentication with database, JDBC driver fails to get one free connection from pool. Perhaps the connection pool is exhausted and cannot allocate more in promp manner.

	java.lang.NullPointerException
			at com.agile.util.sql.LocalConnectionFactory.getJDBCConnection(LocalConnectionFactory.java:82)
			at com.agile.util.sql.ConnectionFactory.getConnection(ConnectionFactory.java:37)
			at com.agile.admin.security.userregistry.DBUserAdapter.getConnection(DBUserAdapter.java:203)
			at com.agile.admin.security.userregistry.DBUserAdapter.checkPassword(DBUserAdapter.java:56)
			at com.agile.admin.security.weblogic.WLSLoginModule.validate(WLSLoginModule.java:375)
			at com.agile.admin.security.weblogic.WLSLoginModule.login(WLSLoginModule.java:166)

Read more log we find a "read timeout" error thrown from JDBC driver when a user tries to logout manually. Here 1200 seconds is defined by a Session EJB PCMHelperSessionBean. From this error trace, we know that when user logs out, Agile will save his logout information into User Usage Report related table for audit, but now this user appears fail to logout.
  
	Cookie: 
	JSESSIONID=GNvkTNMJGvP10swTHYFxQSphymcR1rWHtyTnrJqr2J8J1rlbKHr4!284310265!1024205145; 
	j_password=xxx; 
	j_username=4839C73859281CD3; 

	]", which is more than the configured time (StuckThreadMaxTime) of "1,200" seconds. Stack trace:
		java.net.SocketInputStream.socketRead0(Native Method)
		...
		oracle.jdbc.driver.OraclePreparedStatementWrapper.executeUpdate(OraclePreparedStatementWrapper.java:1062)
		com.agile.report.server.usage.UserUsageHandler.logout(UserUsageHandler.java:160)
		...
		com.agile.pc.cmserver.pcmhelper.PCMHelperSessionBean.logoutUser(PCMHelperSessionBean.java:336)
		...
		com.agile.ui.pcm.login.LoginHandler.logout(LoginHandler.java:1713)

We get the user id from j_username, and get confirmation this user cannot logout and his browser shows an hourglass forever. Then we get to know during logout, the data cannot immediately write into user's usage report table.

As of now, we need to focus on the database performance problem, we may ask for a RDA report for analysis. But we have another way to research more.

## ANALYZE DATABASE

We know all the login and logout data are saved in user's usage report table which is user_usage_history.

First we check if any index is lost.
  
	select index_name, index_type, table_owner, uniqueness, status from user_indexes where table_name='USER_USAGE_HISTORY';
	INDEX_NAME             INDEX_TYPE                  TABLE_OWNER                    UNIQUENESS STATUS 
	------------------------------ --------------------------- ------------------------------ ---------- --------
	LOGOUT_TIME_IDX    FUNCTION-BASED NORMAL       AGILE                          NONUNIQUE  VALID    
	USER_UHIS_IDX1                 NORMAL                      AGILE                          NONUNIQUE  VALID 

Now we see the unique index USER_USAGE_HIS_PK disappears.

Second, we check how many data in this table.

  
	select count(*) from user_usage_history; 
	72654337

Missed Unique index and huge data in table, that is the real problem.

## SOLUTION

1. Re-build the unique index.

		CREATE UNIQUE INDEX USER_USAGE_HIS_PK on USER_USAGE_HISTORY (SID);

2. Purge too old data from user_usage_history table. Due to the table is huge, we cannot use Delete DML to perform, so we have a workaround.

		-- preserve useful data 
		create table user_usage_history_bk as select * from user_usage_history where login_time>to_date('2014-01-01', 'YYYY-MM-DD');

		-- truncate
		truncate table user_usage_history;

		-- disable trigger
		alter trigger user_usage_history_t disable;

		-- retrieve useful data
		insert into user_usage_history select * from user_usage_history_bk;
		commit;

		-- enable trigger
		alter trigger user_usage_history_t enable;

		-- analyze statistics
		exec dbms_stats.gather_table_stats('agile','USER_USAGE_HISTORY');

