---
title: 抛弃ObjectOutputStream用JSON处理Java对象串行化
author: Jie Chen
date: 2015-06-12
categories: [Java]
tags: []
---

前面提到了Java推荐的<a href="/2015-05-20-java-io-objectstreamioserialization" target="_blank" class="bodyA">串行化ObjectInputStream/ObjectOutputStream 缺点</a>比较明显（生成的数据大；人和其他语言无法阅读），所以已经不再适用了。相反的，JSON或者XML格式处理串行化已经是主流，优点自然显而易见：

* 数据小，快速传输
* 人能直接阅读，各语言都能解析
* 可以构造字符串，反串行化生成实例对象

这里举个Google的<a href="https://github.com/google/gson" target="_blank" class="bodyA">开源Gson</a>，用于将Java对象JSON化。

## JSON格式的串行与反串行
~~~
public class Employee implements Serializable {
    private static final long serialVersionUID = 9L;
    public int no;
    public String email;
    public float salary;
    public boolean active;
    public char sex;
    public String toString(){
        return String.format("no=%d, email=%s, salary=%f, active=%b, sex=%c", no, email, salary, active, sex);
    }
}
~~~

~~~
Gson gson = new Gson();
String employeeStr = gson.toJson(emp);
System.out.println(String.format("%s = %d", employeeStr, employeeStr.length()));
~~~

简单易读的JSON，字节量非常小。相比之下使用ObjectOutputStream生成的文件要137字节。
~~~
{"no":123,"email":"jie@xwiz.cn","salary":3000.45,"active":true,"sex":"m"} = 73
~~~

## 构造JSON生成类实例

另外，我可以模拟出一个JSON，通过网络传输给remote，让它生成Employee。
~~~
String newEmpoyeeStr = "{'no':456,'salary':4000.45,'sex':'f','active':false,'email':'jiechencn@qq.com'}";
Employee newEmp = gson.fromJson(newEmpoyeeStr, Employee.class);
System.out.println(newEmp.getClass() + ": " + newEmp.toString());
~~~
~~~
class cn.xwiz.jvm.serializable.Employee: no=456, email=jiechencn@qq.com, salary=4000.449951, active=false, sex=f
~~~