---
title: ORA-06502 from AGILE_SERVER_BOM_EXPANSION
author: Jie Chen
date: 2014-01-20
categories: [AgilePLM]
tags: []
---

Agile has a smart rule named "BOM Multi-Level Recursion". And when we route a Change Order to next status, Agile will check current Change Affected Items' Recursion status in Audit function. Suppose we have Part PART-123456789-01, which contains a BOM component PART-123456789-02, while PART-123456789-01 has its own BOM component PART-123456789-03. Now we create one Change Order against PART-123456789-03 and redline PART-123456789-03 to add PART-123456789-01, click Next Status, Audit function will detect below error.

	PART-123456789-03 has recursive BOM: PART-123456789-01 --> PART-123456789-02 --> PART-123456789-03 --> PART-123456789-01

That is the expected correct behavior.

![](/assets/res/troubleshooting-agileplm-ora06502bomexpansion-1.png)

Last week I have one customer reporting that he gets an unfriendly error message when do Audit Release function in Agile 9.3.0.2.

	14/01/20 03:00:08 java.sql.SQLException: ORA-06502: PL/SQL: numeric or value error: character string buffer too small
	ORA-06512: at "AGILE.AGILE_SERVER_BOM_EXPANSION", line 711
	ORA-06512: at line 1


## Scenario

Let's discuss why the error occurs un such matter. Here I directly give out the scenario of problem. My Item numbers are very longer than 32 characters like below with BOM component structure.

	PART-123456789-ABCDEFGHIJKLMNOPQRSTUVWXYZ-01
	 |_ PART-123456789-ABCDEFGHIJKLMNOPQRSTUVWXYZ-02
		 |_ PART-123456789-ABCDEFGHIJKLMNOPQRSTUVWXYZ-03

Then I redline PART-123456789-ABCDEFGHIJKLMNOPQRSTUVWXYZ-03 to add PART-123456789-ABCDEFGHIJKLMNOPQRSTUVWXYZ-01, click Audit Release or route to next status, I see exactly same problem.

![](/assets/res/troubleshooting-agileplm-ora06502bomexpansion-2.png)

## Analysis

Below is the server log, but it has not much help to us. We can only look at the package AGILE.AGILE_SERVER_BOM_EXPANSION at line 711 and around.

	14/01/20 03:00:08 java.sql.SQLException: ORA-06502: PL/SQL: numeric or value error: character string buffer too small
	ORA-06512: at "AGILE.AGILE_SERVER_BOM_EXPANSION", line 711
	ORA-06512: at line 1
	14/01/20 03:00:08 	at oracle.jdbc.driver.DatabaseError.throwSqlException(DatabaseError.java:138)
	14/01/20 03:00:08 	at oracle.jdbc.driver.T4CTTIoer.processError(T4CTTIoer.java:316)
	14/01/20 03:00:08 	at oracle.jdbc.driver.T4CTTIoer.processError(T4CTTIoer.java:282)
	14/01/20 03:00:08 	at oracle.jdbc.driver.T4C8Oall.receive(T4C8Oall.java:639)
	14/01/20 03:00:08 	at oracle.jdbc.driver.T4CCallableStatement.doOall8(T4CCallableStatement.java:184)
	14/01/20 03:00:08 	at oracle.jdbc.driver.T4CCallableStatement.execute_for_rows(T4CCallableStatement.java:873)
	14/01/20 03:00:08 	at oracle.jdbc.driver.OracleStatement.doExecuteWithTimeout(OracleStatement.java:1161)
	14/01/20 03:00:08 	at oracle.jdbc.driver.OraclePreparedStatement.executeInternal(OraclePreparedStatement.java:3001)
	14/01/20 03:00:08 	at oracle.jdbc.driver.OraclePreparedStatement.execute(OraclePreparedStatement.java:3093)
	14/01/20 03:00:08 	at oracle.jdbc.driver.OracleCallableStatement.execute(OracleCallableStatement.java:4286)
	14/01/20 03:00:08 	at com.agile.pc.cmserver.item.ItemBOMExpansion.getRowBomCycleDetails(ItemBOMExpansion.java:265)
	14/01/20 03:00:08 	at com.agile.pc.cmserver.item.ItemBOMExpansion.getAllBomCycleRows(ItemBOMExpansion.java:178)
	14/01/20 03:00:08 	at com.agile.pc.cmserver.change.ChangeService.bomRecursionCheck(ChangeService.java:4287)
	14/01/20 03:00:08 	at com.agile.pc.cmserver.base.BaseServiceRoute.checkReq(BaseServiceRoute.java:3356)
	14/01/20 03:00:08 	at com.agile.pc.cmserver.base.BaseServiceRoute.moveStatusForward(BaseServiceRoute.java:3063)
	14/01/20 03:00:08 	at com.agile.pc.cmserver.base.BaseServiceRoute.doAuditStatus(BaseServiceRoute.java:1938)
	14/01/20 03:00:08 	at com.agile.pc.cmserver.base.BaseServiceRoute.doAuditCurrentStatus(BaseServiceRoute.java:1496)
	14/01/20 03:00:08 	at com.agile.pc.cmserver.base.BaseServiceRoute.auditCurrentStatus(BaseServiceRoute.java:1467)
	14/01/20 03:00:08 	at com.agile.pc.cmserver.base.CMRouteSessionBean.preAuditStatusBatch(CMRouteSessionBean.java:1549)

Package find_bom_recursion:

	PROCEDURE find_bom_recursion(
	  ...
	  o_itemNumbers OUT t_Varchar2s,
		
	  select item_number, class, subclass into itm_number, itm_class, itm_subclass
	  from item where id = itm_id;
	  o_itemNumbers(compCursor) := itm_number;   --  <--- line 711
		

From above, we understand o_itemNumbers is Agile customized type t_Varchar2s, and at line 711 one array of it is assigned with itm_number column data which comes from item table.

And we can find the definition of t_Varchar2s from in ORA_HOME/admin/AGILEINSTANCE/create/AGILESCHEMA/useragile.sql t_Varchar2s is defined in sys schema with sysdba role, and it is referenced as synonyms in AGILE schema.

	declare
	  type_exists number;
	  sql_command varchar2(200);
	begin
	  select count(1) into type_exists from user_objects where object_name = 'T_VARCHAR2S' and object_type='TYPE';
	  if (type_exists = 0) then
		sql_command := 'create type t_varchar2s as table of varchar2(32)';
		execute immediate sql_command;
	  end if;
	end;

So definitely, t_varchar2s is a collection type of varchar2 and each element only alows 32 characters at most.


## The last

As a conclusion of this BUG, you can use Note 1471579.1 to detect and remove all recursive BOMs, or wait for Oracle to fix this BUG officially.

I had no chance to verify other version of Agile like 9.3.2 or 9.3.3, I believe it happens same there. Maybe I am wrong, but please go ahead to verify them if you are interested in this.

