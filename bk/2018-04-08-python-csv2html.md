---
title: Python将CSV转换成HTML表格
author: Jie Chen
date: 2018-04-08
categories: [Python]
tags: []
---

我一般用Python处理数据文件，比如分析日志，转换文件等。比如我开发的Oracle内部使用的SQLView工具就是基于Jython/Python，对产生的Weblogic JDBC分析并生成最直接可视的报表。套用现在最装B的说法，就是数据可视化。

SQLView的脚本比较复杂，它需要对不同行进行交叉引用分析。而下面的这个例子，是把一个CSV文件转换成HTML文件。将复杂的数据生成彩色的HTML，老板们最喜欢这种花里胡哨的报表了。

首先读取CSV文件，open()会产生迭代列表。第一行作为表头，用绿色背景填充，后续的行交替用白色和浅黄色作为背景来区别开。no变量是行数。对于写文件对象后，习惯性用close()关闭。实际上不关闭，Python也会自动判断当文件对象超出作用域后会自动关闭。作为一个负责任的程序员，严谨规范才有可能不会被裁员。

	def process(filename):
		maxwidth = 100
		htmlfile = filename + ".html"
		fh = open(htmlfile, "w", encoding="utf8")
		write_table_head(fh)
		no = 0
		for line in open(filename, encoding="utf8"):
			if no == 0:
				color = "green"
			elif no % 2:
				color = "white"
			else:
				color = "lightyellow"
			write_table_td(fh, line, color, maxwidth)
			no += 1

		write_table_tail(fh)
		fh.close()


输出table的标签，并处理每一行的TR。一行TR由多个TD组成。每个TD的值都来自于CSV文件以逗号分隔的字段。函数split()下面会具体怎么处理每一行。另外escape_html()是处理一些特殊字符，使得HTML页面中能正确显示。

	def write_table_head(fh):
		fh.write("<table board=1>")

	def write_table_tail(fh):
		fh.write("</table>")
		
	def escape_html(field):
		field = field.replace("&", "&amp;")
		field = field.replace("<", "&lt;")
		field = field.replace(">", "&gt;")
		return field
	
	def write_table_td(fh, line, color, maxwidth):
		tr = "<tr bgcolor='{0}'>".format(color)
		fields = split(line)
		for field in fields:
			if not field:
				tr = tr + "<td></td>"
			else:
				if len(field) <= maxwidth:
					field = escape_html(field)
				else:
					field = "{0} ...".format(escape_html(field[:maxwidth]))
				tr = tr + "<td>{0}</td>".format(field)
		tr = tr + "</tr>"
		fh.write(tr)



split()处理CSV中的每一行。每个字段以逗号分隔。需要特殊的一个情况是，一个字段内被单双引号括起来的字符串可能也含有逗号。

	def split(line):
		fields = []
		field = ""
		quote = None
		for s in line:
			if s in "\"'":
				if quote is None:
					quote = s
				elif quote == s:
					quote = None
				else:
					field += s

			if quote is None and s == ",":
				fields.append(field)
				field = ""
			else:
				field += s
		if field:
			fields.append(field)
		return fields


就是这么简单。最后是主程序，稍加判断就可以了：

	if len(sys.argv) < 2:
		print("input csv filename please")
		exit(0)
	process(sys.argv[1])
	
	
