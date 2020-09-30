---
title: Conflict between Change Control and ASL Mapping
author: Jie Chen
date: 2013-11-07
categories: [AgilePLM]
tags: []
---

Yesterday I got one strange report that on Agile 9.3.1.2, adding a Supplier into Item's Supplier tab will always remove all the data from Item.PageTwo.MultiList01 field which is assigned to a User Group list.

The detailed problem description is like below.

In JavaClient, MultiList01 attribute on Parts class's PageTwo tab is enabled and assigned with User Group list. On WebClient, user created a new Part and assign MultiList01 with two UserGroups: "Global User Group Test1" and "Personal Group_Test1".

![](/assets/res/troubleshooting-agileplm-conflictccasl-1.png)

Then go to Suppliers tab to add three Suppliers.

![](/assets/res/troubleshooting-agileplm-conflictccasl-2.png)

Switch back to Part's TitleBlock, will see MultiList01 loses the User Group data.

![](/assets/res/troubleshooting-agileplm-conflictccasl-3.png)

To confirm if MultiList01 really loses the data or it saves with other wrong data, I need to check the database and find strange data that MultiList01 saves wrong data ",7976911,7976907,7976959,", which are exactly the ID of these three Suppliers.

![](/assets/res/troubleshooting-agileplm-conflictccasl-4.png)

Then I can suspect the Supplier attribute on Suppliers tab must be mapped to MultiList01. However when I check Supplier in JavaClient, the "ASL mapped to" is blank.

![](/assets/res/troubleshooting-agileplm-conflictccasl-5.png)

More interesting thing is the database clearly shows Supplier attribute (Base ID =2000004219) is mapped to 2090, which is PageTwo.MultiList01 Base ID.

![](/assets/res/troubleshooting-agileplm-conflictccasl-6.png)

Till now, we can get a conclusion that Supplier data is really mapped to MultiList01, though we assign MultiList01 to User Group list and Supplier does not set "ASL mapped to". It must be another function which overrides "ASL mapped to" visibility in JavaClient with high priority.

That is the "Change Controlled" function.

![](/assets/res/troubleshooting-agileplm-conflictccasl-7.png)

We immediately see "ASL mapped to" with value "MultiList01" when we disable Change Controlled for Multilist01

![](/assets/res/troubleshooting-agileplm-conflictccasl-8.png)

If one attribute is Change Controlled, Supplier data cannot be mapped to this attribute theoretically because Supplier could be dynamically modified by users, not by Changes. In real situation of Agile 9.3.1.2, it could be a Code Defect.

We can imagine the scenario customer met. He setup Parts.PageTwo.Multilist01 assigned with Supplier list, then in Parts.Suppliers.Supplier attribute, he set "ASL mapped to" to "Multilist01". Later company business is changed, so he set Multilist01 with Change Controlled and re-assign with User Group list. He forgot to remove "ASL mapped to" before he did modifications to Multilist01.

Finally we know the solution, it depends on real business.

* If still need to mapping Supplier to Parts.PageTwo attribute, should modify "ASL mapped to" to other one attribute which already has assigned with Suppliers list.
* If do not need "ASL mapped to" function, should delete the data from database level. We cannot do it from JavaClient UI.

		delete propertytable where id in (select p.id from propertytable p, nodetable n where p.parentid =n.id and n.inherit=2000004219 and propertyid=794);
		commit;


