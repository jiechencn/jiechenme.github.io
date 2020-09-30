---
title: 使用CSS3的animation产生下雪效果
author: Jie Chen
date: 2013-12-26
categories: [Design]
tags: [css]
---

在以往的网页设计中,为页面添加类似冬天下雪的动画场景虽然看起来很眩,但工作量很大,需要编写大量的Javascript代码。而使用CSS3，经JQuery简单触发，就能产生同样的效果。

## Demo演示

只要点击图片上的<a href="/lab/blogweb_css3_animation_snow/index.html" target="_blank">圣诞帽子</a>，页面就出下雪。


## 支持版本
- Chrome
- Firefox
- Opera
- Safari
- IE 10

## 代码部分

在下雪的场景中，我们为BODY定义专门的CSS：snowBG。使用三张透明的不同大小的雪花图作为BODY的背景图，同时我们使用CSS3中的animation方法触发自定义的snowAction下雪动作。该动作使用平滑linear效果，每次动画持续20s，无止尽地执行。

	.snowBG{
		background-color: #C4D3E3;
		background-image: url(../image/snow1.png), 
		url(../image/snow2.png), 
		url(../image/snow3.png);	
		-webkit-animation: snowAction 20s linear infinite;
		-ms-animation: snowAction 20s linear infinite;
		animation: snowAction 20s linear infinite;
	}

Firefox和Opera本身就支持animation动画效果，为支持其他主流浏览器，我们为Chrome和Safari添加-webkit-animation，为IE添加-ms-animation。

在自定义的snowAction动作中，简单使用from to平移背景图片。由于有三张图片，因此需要3组x/y轴坐标标识起始点和终点。这样，一个下雪场景的动画效果就很快产生了。

	@keyframes snowAction {
		0% {background-position: 0px 0px, 0px 0px, 0px 0px;}
		100% {background-position: 500px 1000px, 400px 400px, 300px 300px;}
	}

	@-webkit-keyframes snowAction {
		0% {background-position: 0px 0px, 0px 0px, 0px 0px;}
		100% {background-position: 500px 1000px, 400px 400px, 300px 300px; }
	}

	@-ms-keyframes snowAction {
		0% {background-position: 0px 0px, 0px 0px, 0px 0px;}
		100% {background-position: 500px 1000px, 400px 400px, 300px 300px;}
	}

现在回到圣诞帽子的触发动作上来，我们只要一行代码调用JQuery的Selector.toggleClass简单地改变了BODY的背景样式。

	$(function(){
		$(".myHat").click(function(){
			$("body").toggleClass("snowBG");	
		});
	});
