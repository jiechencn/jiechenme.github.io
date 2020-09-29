---
title: Java多线程 - 中途放弃
author: Jie Chen
date: 2009-09-10
categories: [Java]
tags: [multithread]
---

如果有多个线程需要处理同一个资源，当一个线程已经处理完了，另外的线程根据资源的状态可以判断要不要也去处理。如果状态显示资源无须再处理，那么这些线程可以中途放弃。等到下次执行机会到来时再判断。这可能是一种比较节省资源的方式。和前面的保护性暂挂方式有一定的区别。保护性暂挂一直没有放弃，一直处于等待状态，当notify它时马上激活。而这里的中途放弃，则是测底放弃本次执行，等下一次机会的到来。

模仿Word文档有个自动保存的功能。当编辑文档时，Word每分钟就会自动保存一次。当1分钟内没有任何新内容更新时，Word即便1分钟保存的线程到来，也没有必要做保存的动作，因为根本没有新内容，就无须做多余的动作。

假设Word的内容定义为Data

	public class Data {
		private final String filename;
		private String content;
		private boolean changed;
		public Data(String filename) {
			this.filename = filename;
			content = "";
		}
		public synchronized void write(String content){
			this.content += content;
			changed = true;
		}
		
		public synchronized void save(){
			if (!changed) return;
			
			try (Writer fo = new FileWriter(filename)) {
				fo.write(content);
				System.out.println("\nsaved");
			} catch (Exception e) {
			  e.printStackTrace();
			}
			changed = false;
		}
	}

content字段可以简单地作为内容字符串累加。资源的状态用changed来表示。当有新内容时，write()被执行，内容被修改，同时changed置为true。外部线程需要调用save()时，首先要判断changed状态。这里可以看到和前面的保护性暂挂方式的区别。这里没有while循环内的wait()，而是直接退出，等待下一次机会。

保存的线程非常简单，模拟时可以假设2秒钟做一次save。

	public class SaverThread extends Thread{
		private Data data;
		public SaverThread(String name, Data data) {
			super(name);
			this.data = data;
		}
		public void run(){
			try{
				while(true){
					data.save();
					Thread.sleep(2000);
				}
			}catch(Exception e){
				e.printStackTrace();
			}
		}
	}


目前这里只有一个线程。另外的线程模拟用户编辑Word。从控制台输入文字。

	public class Client {
		public static void main(String args[]){
			Data data = new Data("saved.txt");
			SaverThread st = new SaverThread("Saver", data);
			st.start();
			
			try {
				for (; ; ) {
				   System.out.print(">");
				   String content = new BufferedReader(new InputStreamReader(System.in)).readLine();
				   data.write(content);
				}
			} catch (Exception e) {
				e.printStackTrace();
			}
		}
	}


这里的逻辑非常简单，用户时不时地输入字符，类似编辑Word，后台有个SaverThread做每2秒钟的自动保存。如果用户自上次保存以来的2秒内没有再输入，直接根据条件退出save()。

	if (!changed) return;

这种中途放弃的模式，非常适合多个线程需要对同一个资源做同一件事情的情况。