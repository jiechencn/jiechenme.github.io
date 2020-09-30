---
title: Background execution of IQuery API in Agile SDK
author: Jie Chen
date: 2013-05-03
categories: [AgilePLM]
tags: [sdk,iquery]
---

I have been asked by customers and partners about one same puzzle that why IQuery.execute() runs extremely fast, while the first time execution of Iterator.hasNext() runs very slow。To answer and summarize the background technology of IQuery, let's analyze it detailedly. Below is a very typical usage of IQuery.

	String sql = "[1001] contains '247'";
	IQuery query = (IQuery) session.createObject(IQuery.OBJECT_TYPE,
			ItemConstants.CLASS_PARTS_CLASS);
	query.setCaseSensitive(false);
	query.setCriteria(sql);
	query.setResultAttributes(new Object[] { 1001, 1002, 1014, 1016, 1017, 1081, 1082 , 1084, 12089, 2000002781, 2000002859, 2000004143});
	ITable tabs = query.execute();		
	tabs.setPageSize(0);		
	Iterator iterator = tabs.iterator();
	while(iterator.hasNext()){
		IRow row = (IRow)iterator.next();
		t0 = System.currentTimeMillis();
	}

To better watch its background execution, we deploy this program as Process Extension, also enable SQL DEBUG option on Agile Application. We also add the -Ddisable.tasks=true parameter to JVM to filter out other SQL output. Now we print the timestamp in the code and check the system log.

Create IQuery Instance

	t0 = System.currentTimeMillis();
	IQuery query = (IQuery) session.createObject(IQuery.OBJECT_TYPE,
			ItemConstants.CLASS_PARTS_CLASS);
	System.out.println("createObject: " + (System.currentTimeMillis() - t0));

Log output of createObject

	<2013-01-23 04:07:06,287>execute (Elapsed Time = 16 ms): "INSERT INTO QUERY (ID, NAME, TYPE, OWNER, OBJVERSION, FLAGS, CASE_SENSITIVE) VALUES (32134262, '32134262', 10000, 704, 0, '00000000000000000000000000000000', 1)"
	13/01/23 04:07:06 createObject: 1110

Set Case Sensitive

	t0 = System.currentTimeMillis();
	query.setCaseSensitive(false);
	System.out.println("setCaseSensitive: " + (System.currentTimeMillis() - t0));

Log output of setCaseSensitive

	<2013-01-23 04:07:06,428>executeQuery (Elapsed Time = 16 ms): "SELECT A.ID, 5, 5, OBJVERSION, 0 , NAME FROM QUERY A  WHERE ID = 32134262"
	<2013-01-23 04:07:06,428>executeQuery (Elapsed Time = 0 ms): "SELECT OBJVERSION, ID FROM QUERY WHERE ID IN (  32134262 ) FOR UPDATE NOWAIT "
	<2013-01-23 04:07:06,428>executeQuery (Elapsed Time = 0 ms): "SELECT A.ID, 5, 5, OBJVERSION, 0 , NAME FROM QUERY A  WHERE ID = 32134262"
	<2013-01-23 04:07:06,428>executeQuery (Elapsed Time = 0 ms): "SELECT NAME, TYPE, OWNER, IS_PUBLIC, CASE_SENSITIVE, OBJVERSION, START_AT, RANGE, WHERE_USED_TYPE, FLAGS, SORT_COLUMNS, SORT_ORDER, GROUP_COLUMNS, REL_OBJ_CLASS, LOCKED_ATT FROM QUERY WHERE ID = 32134262"
	<2013-01-23 04:07:06,428>executeQuery (Elapsed Time = 0 ms): "SELECT att_id, width FROM SELECT_LIST WHERE query_id = 32134262 ORDER BY seq_id"
	<2013-01-23 04:07:06,428>executeUpdate (Elapsed Time = 0 ms): "UPDATE QUERY SET CASE_SENSITIVE = '0' WHERE ID = 32134262"
	<2013-01-23 04:07:06,443>executeUpdate (Elapsed Time = 0 ms): "UPDATE QUERY SET OBJVERSION = NVL(OBJVERSION,0)+ 1 WHERE ID = 32134262"
	<2013-01-23 04:07:06,443>executeQuery (Elapsed Time = 0 ms): "SELECT A.ID, 5, 5, OBJVERSION, 0 , NAME FROM QUERY A  WHERE ID = 32134262"
	13/01/23 04:07:06 setCaseSensitive: 47

Set Criteria for Query:

	t0 = System.currentTimeMillis();
	query.setCriteria(sql);
	System.out.println("setCriteria: " + (System.currentTimeMillis() - t0));

Log output setCriteria:

	<2013-01-23 04:07:06,474>executeQuery (Elapsed Time = 0 ms): "SELECT A.ID, 5, 5, OBJVERSION, 0 , NAME FROM QUERY A  WHERE ID = 32134262"
	<2013-01-23 04:07:06,474>executeQuery (Elapsed Time = 0 ms): "SELECT NAME, TYPE, OWNER, IS_PUBLIC, CASE_SENSITIVE, OBJVERSION, START_AT, RANGE, WHERE_USED_TYPE, FLAGS, SORT_COLUMNS, SORT_ORDER, GROUP_COLUMNS, REL_OBJ_CLASS, LOCKED_ATT FROM QUERY WHERE ID = 32134262"
	<2013-01-23 04:07:06,474>executeQuery (Elapsed Time = 0 ms): "SELECT att_id, width FROM SELECT_LIST WHERE query_id = 32134262 ORDER BY seq_id"
	<2013-01-23 04:07:06,506>executeQuery (Elapsed Time = 0 ms): "SELECT OBJVERSION, ID FROM QUERY WHERE ID IN (  32134262 ) FOR UPDATE NOWAIT "
	<2013-01-23 04:07:06,506>executeUpdate (Elapsed Time = 0 ms): "DELETE FROM CRITERIA WHERE QUERY_ID = 32134262"
	<2013-01-23 04:07:06,506>executeUpdate (Elapsed Time = 0 ms): "UPDATE QUERY SET OBJVERSION = NVL(OBJVERSION,0)+ 1 WHERE ID = 32134262"
	<2013-01-23 04:07:06,506>executeQuery (Elapsed Time = 0 ms): "SELECT A.ID, 5, 5, OBJVERSION, 0 , NAME FROM QUERY A  WHERE ID = 32134262"
	<2013-01-23 04:07:06,521>executeQuery (Elapsed Time = 15 ms): "SELECT NAME, TYPE, OWNER, IS_PUBLIC, CASE_SENSITIVE, OBJVERSION, START_AT, RANGE, WHERE_USED_TYPE, FLAGS, SORT_COLUMNS, SORT_ORDER, GROUP_COLUMNS, REL_OBJ_CLASS, LOCKED_ATT FROM QUERY WHERE ID = 32134262"
	<2013-01-23 04:07:06,521>executeQuery (Elapsed Time = 0 ms): "SELECT att_id, width FROM SELECT_LIST WHERE query_id = 32134262 ORDER BY seq_id"
	<2013-01-23 04:07:06,537>executeQuery (Elapsed Time = 0 ms): "SELECT OBJVERSION, ID FROM QUERY WHERE ID IN (  32134262 ) FOR UPDATE NOWAIT "
	<2013-01-23 04:07:06,537>executeQuery (Elapsed Time = 0 ms): "SELECT A.ID, 5, 5, OBJVERSION, 0 , NAME FROM QUERY A  WHERE ID = 32134262"
	<2013-01-23 04:07:06,537>executeQuery (Elapsed Time = 0 ms): "SELECT NAME, TYPE, OWNER, IS_PUBLIC, CASE_SENSITIVE, OBJVERSION, START_AT, RANGE, WHERE_USED_TYPE, FLAGS, SORT_COLUMNS, SORT_ORDER, GROUP_COLUMNS, REL_OBJ_CLASS, LOCKED_ATT FROM QUERY WHERE ID = 32134262"
	<2013-01-23 04:07:06,537>executeQuery (Elapsed Time = 0 ms): "SELECT att_id, width FROM SELECT_LIST WHERE query_id = 32134262 ORDER BY seq_id"
	<2013-01-23 04:07:06,537>executeBatch (Elapsed Time = 0 ms): "INSERT INTO CRITERIA (ID, ROW_ID, QUERY_ID, ATTR_ID, RELATIONAL_OP, VALUE, LOGICAL_OP, LEFT_PAREN, RIGHT_PAREN, SET_OPERATOR, RELEXPRESSION_OP, FLAGS) SELECT 32134263, NVL(MAX(ROW_ID) + 1, 0), 32134262, 1001, 1, '247', 0, 0, 0, 0, 0, '00000000000000000000000000000000' FROM CRITERIA WHERE QUERY_ID = 32134262"
	<2013-01-23 04:07:06,537>executeUpdate (Elapsed Time = 0 ms): "UPDATE QUERY SET OBJVERSION = NVL(OBJVERSION,0)+ 1 WHERE ID = 32134262"
	13/01/23 04:07:06 setCriteria: 94

Set the Result Layout：

	t0 = System.currentTimeMillis();
	query.setResultAttributes(new Object[] { 1001, 1002, 1014, 1016, 1017, 1081, 1082 , 1084, 12089, 2000002781, 2000002859, 2000004143});
	System.out.println("setResultAttributes: " + (System.currentTimeMillis() - t0));

Log output of setResultAttributes:

	<2013-01-23 04:07:06,553>executeQuery (Elapsed Time = 0 ms): "SELECT A.ID, 5, 5, OBJVERSION, 0 , NAME FROM QUERY A  WHERE ID = 32134262"
	<2013-01-23 04:07:06,553>executeQuery (Elapsed Time = 0 ms): "SELECT NAME, TYPE, OWNER, IS_PUBLIC, CASE_SENSITIVE, OBJVERSION, START_AT, RANGE, WHERE_USED_TYPE, FLAGS, SORT_COLUMNS, SORT_ORDER, GROUP_COLUMNS, REL_OBJ_CLASS, LOCKED_ATT FROM QUERY WHERE ID = 32134262"
	<2013-01-23 04:07:06,553>executeQuery (Elapsed Time = 0 ms): "SELECT att_id, width FROM SELECT_LIST WHERE query_id = 32134262 ORDER BY seq_id"
	<2013-01-23 04:07:06,943>executeQuery (Elapsed Time = 0 ms): "SELECT OBJVERSION, ID FROM QUERY WHERE ID IN (  32134262 ) FOR UPDATE NOWAIT "
	<2013-01-23 04:07:06,943>executeQuery (Elapsed Time = 0 ms): "SELECT A.ID, 5, 5, OBJVERSION, 0 , NAME FROM QUERY A  WHERE ID = 32134262"
	<2013-01-23 04:07:06,959>executeQuery (Elapsed Time = 0 ms): "SELECT NAME, TYPE, OWNER, IS_PUBLIC, CASE_SENSITIVE, OBJVERSION, START_AT, RANGE, WHERE_USED_TYPE, FLAGS, SORT_COLUMNS, SORT_ORDER, GROUP_COLUMNS, REL_OBJ_CLASS, LOCKED_ATT FROM QUERY WHERE ID = 32134262"
	<2013-01-23 04:07:06,959>executeQuery (Elapsed Time = 0 ms): "SELECT att_id, width FROM SELECT_LIST WHERE query_id = 32134262 ORDER BY seq_id"
	<2013-01-23 04:07:06,959>executeUpdate (Elapsed Time = 0 ms): "DELETE FROM SELECT_LIST WHERE QUERY_ID = 32134262"
	<2013-01-23 04:07:06,959>executeBatch (Elapsed Time = 0 ms): "INSERT INTO select_list(query_id, seq_id, att_id, width) VALUES (32134262, 11, 2000004143, 0)"
	<2013-01-23 04:07:06,959>executeUpdate (Elapsed Time = 0 ms): "UPDATE QUERY SET OBJVERSION = NVL(OBJVERSION,0)+ 1 WHERE ID = 32134262"
	13/01/23 04:07:06 setResultAttributes: 422

Execute the Query, set Pagesize and read the collection:

	t0 = System.currentTimeMillis();
	ITable tabs = query.execute();		
	System.out.println("execute: " + (System.currentTimeMillis() - t0));

	t0 = System.currentTimeMillis();
	tabs.setPageSize(0);		
	System.out.println("setPageSize: " + (System.currentTimeMillis() - t0));

	t0 = System.currentTimeMillis();
	Iterator iterator = tabs.iterator();
	System.out.println("iterator: " + (System.currentTimeMillis() - t0));

Log output shows above executions do not call SQL. The real SQL query is invoked by hasNext() function.

	13/01/23 04:07:06 execute: 0
	13/01/23 04:07:06 setPageSize: 0
	13/01/23 04:07:06 iterator: 0

Read the query result in while loop.

	t0 = System.currentTimeMillis();
	while(iterator.hasNext()){
		System.out.println("hasNext: " + (System.currentTimeMillis() - t0));
		
		t0 = System.currentTimeMillis();
		IRow row = (IRow)iterator.next();
		System.out.println("next: " + (System.currentTimeMillis() - t0));
		
		t0 = System.currentTimeMillis();
	}
		
Log output

	<2013-01-23 04:07:06,990>executeQuery (Elapsed Time = 0 ms): "SELECT A.ID, 5, 5, OBJVERSION, 0 , NAME FROM QUERY A  WHERE ID = 32134262"
	<2013-01-23 04:07:06,990>executeQuery (Elapsed Time = 0 ms): "SELECT NAME, TYPE, OWNER, IS_PUBLIC, CASE_SENSITIVE, OBJVERSION, START_AT, RANGE, WHERE_USED_TYPE, FLAGS, SORT_COLUMNS, SORT_ORDER, GROUP_COLUMNS, REL_OBJ_CLASS, LOCKED_ATT FROM QUERY WHERE ID = 32134262"
	<2013-01-23 04:07:06,990>executeQuery (Elapsed Time = 0 ms): "SELECT att_id, width FROM SELECT_LIST WHERE query_id = 32134262 ORDER BY seq_id"
	<2013-01-23 04:07:07,006>executeQuery (Elapsed Time = 16 ms): "SELECT ID, ROW_ID, ATTR_ID, RELATIONAL_OP, VALUE, LOGICAL_OP, LEFT_PAREN, RIGHT_PAREN, FLAGS, SET_OPERATOR, RELEXPRESSION_OP, PROMPT, PARAM_INDEX FROM CRITERIA WHERE QUERY_ID = 32134262 ORDER BY ROW_ID"
	<2013-01-23 04:07:07,084>executeQuery (Elapsed Time = 31 ms): "select  /*+ ALL_ROWS */ ITEM_P2P3_QUERY.ID,ITEM_P2P3_QUERY.CLASS,ITEM_P2P3_QUERY.SUBCLASS,ITEM_P2P3_QUERY.FLAGS,ITEM_P2P3_QUERY.REV_FLAGS,NULL,NULL,ITEM_P2P3_QUERY.ITEM_NUMBER,ITEM_P2P3_QUERY.ITEM_NUMBER,ITEM_P2P3_QUERY.DESCRIPTION,ITEM_P2P3_QUERY.DESC_REV,ITEM_P2P3_QUERY.REV_NUMBER,ITEM_P2P3_QUERY.LATEST_RELEASED_ECO, TO_Char(ITEM_P2P3_QUERY.RELEASE_DATE, 'YYYY-MM-DD HH24:MI:SS'), TO_Char(ITEM_P2P3_QUERY.INCORP_DATE, 'YYYY-MM-DD HH24:MI:SS'),ITEM_P2P3_QUERY.SUBCLASS,ITEM_P2P3_QUERY.CATEGORY,ITEM_P2P3_QUERY.RELEASE_TYPE, TO_Char(ITEM_P2P3_QUERY.EFFECTIVE_DATE, 'YYYY-MM-DD HH24:MI:SS'),ITEM_P2P3_QUERY.IS_TLA,ITEM_P2P3_QUERY.EXCLUDE_FROM_ROLLUP, TO_Char(ITEM_P2P3_QUERY.COMPLIANCY_CALC_DATE, 'YYYY-MM-DD HH24:MI:SS'),ITEM_P2P3_QUERY.CREATE_USER,ITEM_P2P3_QUERY.MULTILIST06,ITEM_P2P3_QUERY.CREATE_USER,NULL,NULL,ITEM_P2P3_QUERY.ITEM_NUMBER from ITEM_P2P3_QUERY where (ITEM_P2P3_QUERY.ITEM_NUMBER LIKE '%247%' ESCAPE '\') AND ITEM_P2P3_QUERY.CLASS = 10000 AND (NVL(ITEM_P2P3_QUERY.DELETE_FLAG,0) = 0) ORDER BY 28"
	13/01/23 04:07:07 hasNext: 875
	13/01/23 04:07:07 next: 0
	13/01/23 04:07:07 hasNext: 0
	13/01/23 04:07:07 next: 0
	13/01/23 04:07:07 hasNext: 0
	...
	...
	...
	
We get the point that the real SQL query is called by the first time of Iterator.hasNext(), which loads all the result into local JVM heap. Subsequent hasNext() and next() read data from local heap and almost do not consume CPU time.


## Iterator.hasNext()

You may be interested why Iterator.hasNext() can trigger SQL call. If we debug the program, we will see the returned type of ITable tabs = query.execute(); is acturally TableQueryResults. And Iterator iterator = tabs.iterator(); returns TableIterator.

![](/assets/res/troubleshooting-agileplm-iqueryapi-1.png)

_Below analysis is only for research._

If to De-compile com.agile.api.pc.query.Query from SDK.jar, you will see an inner class ExecuteAction, which defines the returned type of IQuery.execute().

![](/assets/res/troubleshooting-agileplm-iqueryapi-2.png)

ITable.iterator() calls TableQueryResults.iterator(). TableQueryResults' super class (com.agile.api.pc.Table) implemented the concrete iterator().

![](/assets/res/troubleshooting-agileplm-iqueryapi-3.png)

To call Iterator.hasNext(), it is acturally to call TableIterator.hasNext(). And the SQL query is invocked by checkIterator().

![](/assets/res/troubleshooting-agileplm-iqueryapi-4.png)

Likewise, next() function is defined in TableIterator and invocked by TableIterator instance, not Iterator.

![](/assets/res/troubleshooting-agileplm-iqueryapi-5.png)