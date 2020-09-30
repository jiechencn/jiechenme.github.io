---
title: At most how many customized P3 attributes could be added into Agile?
author: Jie Chen
date: 2013-06-24
categories: [AgilePLM]
tags: [customization]
---

I have one customer/Oracle Partner Consultant asking me such question: how many customized attributes can be allowed to add to Agile's subclass Page Three? I never did research against this because Agile User Guide never says this and theoretically Agile supports unlimited amount of customized attributes, unless the browser itself cannot handle them in allocated memory. However my customers says when to add almost 1000 attributes, the browser (Web Client) will not show any Page Three attributes, including all the out-of-box attributes. Let's see why.

## Analysis

It is horrible to add 1000 attributes manually. Let's do it by a batch SQL like below to add them to Item's subclass Page Three tab. Do not execute below SQL because it will not take effect due to your different node id.

	CREATE OR REPLACE PROCEDURE createP3Text(v_name IN VARCHAR2) IS
		v_nid NUMBER;
		v_pid NUMBER;
	BEGIN
		select SEQNODETABLE.nextval into v_nid from dual;
		Insert Into nodeTable ( id,parentID,description,objType,inherit,helpID,version,name ) values ( v_nid,2473003, v_name ,1,0,0,0, v_name);
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,2,1,0,1,925, null);
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,0,0,0,0,1,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,0,0,0,0,2,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,2,2,0,1,3,'50');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,2,1,0,1,5, null);
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,2,2,0,1,6,'50');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,2,2,0,0,7,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,4,1,451,1,8,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,4,1,451,1,9,'1');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,2,1,0,1,10,v_name);
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,0,0,0,0,11,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,4,1,11743,1,14,'2');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,2,1,0,1,30, null);
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,2,1,0,1,38, null);
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,4,1,451,0,59,'1');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,4,1,451,0,60,'1');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,4,1,724,0,61, null);
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,2,1,0,0,232,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,4,1,451,0,233,'1');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,4,1,12239,1,415,'13307');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,2,1,0,0,605,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,4,1,451,1,610,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,1,4,1,451,0,716,'1');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,4,1,451,1,795,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,4,1,2000008821,1,864,'2');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,4,1,451,1,923,'0');
		Insert Into propertyTable ( ID,parentID,readOnly,attType,dataType,selection,visible,propertyID,value ) values ( SEQPROPERTYTABLE.nextval,v_nid,0,4,1,451,0,719,'0');
		Insert Into tableInfo ( tabID,tableID,classID,att,ordering ) values ( 2473005,1501,2473002,v_nid,9999);
		
		commit;
	END createP3Text;
	/

	BEGIN
	FOR i in 1..1000 
	  LOOP
	  createP3Text('MyText' || i);
	END LOOP;
	END;
	/

	DROP PROCEDURE createP3Text;
	COMMIT;

Now restart Agile Server and check the Server's log, we noticed below:

	***** Node Created : 85625
	***** Property Created : 184579
	+++++++++++++++++++++++++++++++++++++
	+   Agile PLM Server Starting Up... +
	+++++++++++++++++++++++++++++++++++++

However the previously log before batch SQL is

	***** Node Created : 84625
	***** Property Created : 157579
	+++++++++++++++++++++++++++++++++++++
	+   Agile PLM Server Starting Up... +
	+++++++++++++++++++++++++++++++++++++

Obviously we successfully imported 1000 (85625-84625) attributes. Now go to JavaClient and confirm if we have them or not.

![](/assets/res/troubleshooting-agileplm-p3flexattr-1.png)
 
Theoretically we are able to open such item object and see all these 1000 attributes and their values, but we get below error.

![](/assets/res/troubleshooting-agileplm-p3flexattr-2.png)

We have no error tips in server log. But never mind we have the Java Console for JavaClient. If to open the same item in JavaClient we get a clear error and detailed trace in Java Console.

![](/assets/res/troubleshooting-agileplm-p3flexattr-3.png)

	ORA-01795: maximum number of expressions in a list is 1000
	java.sql.SQLException: ORA-01795: maximum number of expressions in a list is 1000
		at oracle.jdbc.driver.DatabaseError.throwSqlException(DatabaseError.java:125)
		... ...
		at weblogic.jdbc.wrapper.PreparedStatement.executeQuery(PreparedStatement.java:128)
		at com.agile.pc.cmserver.base.AgileFlexUtil.setFlexValuesForOneRowTable(AgileFlexUtil.java:1104)
		at com.agile.pc.cmserver.base.BaseFlexTableDAO.loadExtraFlexAttValues(BaseFlexTableDAO.java:111)
		at com.agile.pc.cmserver.base.BasePageThreeDAO.loadTable(BasePageThreeDAO.java:108)

If you are interested in the background of the problem, you may de-compile the class com.agile.pc.cmserver.base.AgileFlexUtil.setFlexValuesForOneRowTable and find the root cause that Agile happens to hit Oracle Database's limitation that more than 1000 values in the "IN" clause. Check here http://ora-01795.ora-code.com

![](/assets/res/troubleshooting-agileplm-p3flexattr-4.png)

If you need Oracle Agile's final solution, please contact Oracle Agile Support.

## Performance

Below two screenshot are jvm heap usage from before-SQL and after-SQL. We can see there is no big memory gap between two cases. So definitely there is no performance impact to Agile Application Server unless you have more than 1000 attributes for EACH of your dozens of  subclasses.

![](/assets/res/troubleshooting-agileplm-p3flexattr-5.png)

![](/assets/res/troubleshooting-agileplm-p3flexattr-6.png)

And for client, 1000 attributes should not impact the browser's performance because in HTML we only use dt and dd for each attribute's pair: label and value. It is quite lightweight.

![](/assets/res/troubleshooting-agileplm-p3flexattr-7.png)

