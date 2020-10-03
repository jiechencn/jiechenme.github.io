---
title: 使用纯CSS编写带箭头的气泡提示
author: Jie Chen
date: 2014-01-08
categories: [Design]
tags: [css]
---

现在很多网站的链接或者按钮提示习惯使用各种丰富多彩的提示方式，气泡式弹出提示框是非常流行的一种方法。比如下图的气泡式提示，一个框上带着一个箭头。本文将一点点地讲述如何使用CSS一步步地产生气泡弹出框的演变过程。

![](/assets/res/web_css3_arrowbubble_tip_06.png)

首先我们通过学习border-color, border-style和border-width的属性来了解边框风格。为div定义一个box1的样式，上右下左的边框分别为红色、黄色、蓝色和绿色，宽度为20px的实心线条。而div层本身高宽为20px。

	.box1{
		border-color: red yellow blue green;
		border-style: solid;
		border-width: 20px;
		width: 20px;
		height: 20px;
	}
为div套用上述样式，就会产生如下的效果。

![](/assets/res/web_css3_arrowbubble_tip_01.png)

假使div块的高宽缩小为0，就会发生了奇特的变化。

	.box2{
		border-color: red yellow blue green;
		border-style: solid;
		border-width: 20px;
		width: 0;
		height: 0;
	}

![](/assets/res/web_css3_arrowbubble_tip_02.png)

可以看到内部的空白部分被全部填充了。联想到最上面的箭头，我们很容易想到，如果使用某一个箭头，只要将其他三角形部分的颜色设为透明就可以了。比如下面的样式，我们只保留了最左的绿色，而将上、下和右的三角形置为透明，就形成了一个绿色三角形。

	.box3{
		border-color: transparent transparent transparent green;
		border-style: solid;
		border-width: 20px;
		width: 0;
		height: 0;
	}

![](/assets/res/web_css3_arrowbubble_tip_03.png)

接下来我们可以为这个箭头添加上面的大的框，先架设一个css的雏形，建立一个背景色为红色的框，position设为relative，因为我们需要为将来的定位做考虑。

	.box4{
		position: relative;
		background-color: red;
		padding: 15px;
		text-align: center;
		width: 150px;
		height: 20px;
	}
为了给这个框添加一个三角形，我们使用:before伪元素，在box4生成之前，先附着在box4身边产生一个三角形，使用absolute定位到左边16像素处。为了明显体现出css的变化，我们给这个三角形定义为黄色，方向向下。

	.box4:before{
		content: '';
		position: absolute;
		top: 100%;
		left: 16px;
		border-color: orange transparent transparent transparent;
		border-style: solid;
		border-width: 16px;
		width: 0;
		height: 0;
	}

![](/assets/res/web_css3_arrowbubble_tip_04.png)

由于三角形本身是实心的，不具备额外的边框，所以接下来我们要给box4修改，为三角形的外延部分添加边框。我们添加一个:after伪元素，生成一个小的绿色三角形，覆盖在黄色三角形的上方，这样看起来我们就拥有了三角形的外框。同时给长方形的矩形添加一个黑色的实线边框。

	.box5:before{
		content: ' ';
		position: absolute;
		top: 100%;
		left: 16px;
		border-color: orange transparent transparent transparent;
		border-style: solid;
		border-width: 16px;
		width: 0;
		height: 0;
	}

	.box5{
		position: relative;
		border: 3px solid black;
		background-color: red;
		padding: 15px;
		text-align: center;
		width: 150px;
		height: 20px;
	}
	
	.box5:after{
		content: ' ';
		position: absolute;
		top: 100%;
		left: 20px;
		border-color: green transparent transparent transparent;
		border-style: solid;
		border-width: 12px;
		width: 0;
		height: 0;
	}

![](/assets/res/web_css3_arrowbubble_tip_05.png)

到这里，很容易就能想到，只要将红色背景改为透明，:before三角形的黄色改成黑色，:after三角形为白色（不能使用透明，否则:before三角形的背景色会透出来），就能产生最终的效果了。

	.box6:before{
		content: ' ';
		position: absolute;
		top: 100%;
		left: 16px;
		border-color: black transparent transparent transparent;
		border-style: solid;
		border-width: 16px;
		width: 0;
		height: 0;
	}

	.box6{
		position: relative;
		border: 3px solid black;
		background-color: transparent;
		padding: 15px;
		text-align: center;
		width: 150px;
		height: 20px;
		border-radius: 15px;
	}

	.box6:after{
		content: ' ';
		position: absolute;
		top: 100%;
		left: 20px;
		border-color: white transparent transparent transparent;
		border-style: solid;
		border-width: 12px;
		width: 0;
		height: 0;
	}

上面的样式中我们添加了border-radius: 15px; 使得外框看起来更加圆润。如果再调整外框的高宽以及border-radius，就能作出球形的箭头气泡。我们还可以重新调整样式，设计出箭头指向其他方向的气泡。

![](/assets/res/web_css3_arrowbubble_tip_06.png)