---
title: Poorer performance of IQuery in 9.3.0.3 than 9.3.0.1
author: Jie Chen
date: 2013-05-23
categories: [AgilePLM]
tags: [performance]
---

Recently we got several customers' report that the IQuery API in client SDK program has poorer performance in Agile 9.3.0.3 than 9.3.0.1. If to monitor SDK client program's memory heap usage we will see much more heap is used in 9.3.0.3 and Wireshark monitor shows more data packages are received from Agile Server. This article will bring us close to the essence of the problem and see the different behavior between 9.3.0.3 and 9.3.0.1.

First we have a very simple IQuery sample code to get all active users from Agile. After the IQuery finished, we call Thread.sleep(1000000) to pause the program, then take the heap dump.

    public static ArrayList getUsersData(IAgileSession agileSession) throws Exception {
        ArrayList result = null;
        try {
            String query = "SELECT [General Info.User ID], ";
            query += "       [General Info.First Name], ";
            query += "       [General Info.Last Name], ";
            query += "       [General Info.Business Phone], ";
            query += "       [General Info.Status], ";
            query += "       [General Info.Email], ";
            query += "       [General Info.LOB], ";
            query += "       [General Info.BU], ";
            query += "       [General Info.Division], ";
            query += "       [General Info.Department], ";
            query += "       [General Info.Title], ";
            query += "       [General Info.Address], ";
            query += "       [General Info.Mobile Phone], ";
            query += "       [General Info.Role], ";
            query += "       [General Info.Region], ";
            query += "       [General Info.Country], ";
            query += "       [Detail Information.User Group] , ";
            query += "       [Detail Information.RO User], ";
            query += "       [Detail Information.Brand], ";  
            query += "       [Detail Information.NS], ";
            query += "       [Detail Information.LOCATED RO], ";
            query += "       [Detail Information.LOCATED NS], ";
            query += "       [Detail Information.Business Group] ";
            query += "FROM [User] ";
            query += "WHERE [General Info.Status] == 'Active' ";
            IQuery iQuery = (IQuery) agileSession.createObject(IQuery.OBJECT_TYPE, query);
            ITable table = iQuery.execute();
            table.setPageSize(1000);
            ITwoWayIterator it = table.getTableIterator();
            result = new ArrayList();
            int i=0;
            while (it.hasNext()) {
                IRow row = (IRow) it.next();
                Map map = row.getValues();
                System.out.println(i++ +" : " +map);
                result.add(map);
                if(i == 1000 ) break;
            }
            printUsedMemory("KB");
            
        } catch (APIException e) {
            throw e;
        }

        return result;
    }

In above code, we fill result (ArrayList) with 1000 objects, it is for diagnosis only.

Now we have two Heap Dump files, one for 9.3.0.3 and the rest one for 9.3.0.1.

In 9.3.0.1, we have 1000 objects in ArrayList, they consumes 5,770,544 bytes, while 9.3.0.3 consumes 206,871,844 bytes.

	Address	0x17b92198
	Name	array of [Ljava/lang/Object;
	Number of children	1,000
	Number of parents	1
	Owner address	0x17ec3ee0
	Owner object	java/util/ArrayList
	Size	4,076
	Total size	5,770,544

.

	Leak suspect	Responsible for 206,871,844 bytes (93.252 %) of Java heap
	Address	0x22780c40
	Name	array of [Ljava/lang/Object;
	Number of children	1,000
	Number of parents	1
	Owner address	0x17f429c8
	Owner object	java/util/ArrayList
	Size	4,076
	Total size	206,871,844

Definitely, each object in 9.3.0.3 consumes more memory than 9.3.0.1. Let's capture one attribute [General Info.Department] to analyze. "Department" is a List type, of course we do not care it is common List or Cascade List. Compare this attribute between two version we will see the big difference and confirm there is a big object (com/agile/api/pc/CascadeList) in 9.3.0.3 which consumes 100, 199 bytes, 9.3.0.1 consumes 248 bytes only.

![](/assets/res/troubleshooting_agileplm-poorperfiquery9303-1.png)

Look more close to the big object (com/agile/api/pc/CascadeList) in 9.3.0.3, we should see com/agile/admin/client/value/AdminList, it takes 99, 947 bytes and contains 579 arrays of com/agile/admin/client/value/AdminListItem , actually AdminListItem represent one List entry object. From here we realize that in 9.3.0.3, if to return a List attribute value, Agile also will return the whole List's all entries value to the client. But 9.3.0.1 will not.

![](/assets/res/troubleshooting_agileplm-poorperfiquery9303-2.png)

So, that is the difference and you get it, right? Please contact Oracle Agile Support if you consider the performance impact to your business and system.




