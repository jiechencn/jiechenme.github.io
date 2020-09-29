---
title: Java多线程 - 任务指派的临时线程
author: Jie Chen
date: 2009-09-13
categories: [Java]
tags: [multithread]
---

在我所提供技术支持的产品代码中，有一类模式非常普遍，就是主线程得到客户端的请求，在处理完必要的逻辑之后，再发送邮件通知或者JMS消息，而主线程在调用完这类消息通知发送后，完全不用等待消息通知的返回结果，马上返回。他们的实现方式大同小异，基本上都是将消息通知指派给一个临时新建的线程，而主线程即刻返回。使用这类任务指派给临时线程的模式，我总结了一下，有2个基本的条件：

1. 这些任务不需要返回结果给主线程
2. 必须是轻量的临时线程，没有复杂的线程调度，随即销毁，不占用全局线程池

基本上，使用简单的临时线程，通过匿名内部类就能实现。

	// 处理前台逻辑
	// 在方法体最后启用临时线程，委派后台任务
	new Thread(){
		public void run(){
			// 后台任务的调用
		}
	}.start();

			

通过演示一个小型HTTP服务器的例子，可以很好地展示。

下面的代码就是一个Web服务器建立监听的通用方法。

    public static void main(String args[]){
        MyWebServer ws = new MyWebServer(9989);
        try {
            ws.listen();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }
	

因为HTTP服务器都属于多线程响应，能响应多个客户的浏览请求。所以在ServerSocket接受客户连接请求后，可以把具体的Http数据输出的方法指派给临时的线程，这样主线程可以接受下一个客户请求，并启用又一个新线程处理数据输出。

	public class MyWebServer {
		private final int port;
		
		public MyWebServer(int port) {
			this.port = port;
		}
		public void listen() throws IOException {
			while(true){
				ServerSocket srvSocket = new ServerSocket(port);
				Socket clientSocket = srvSocket.accept();
				System.out.println(clientSocket + " connected to server");
				new Thread(){
					public void run(){
						try {
							HttpService.service(clientSocket);
						} catch (IOException e) {
						}
					}
				}.start();

				srvSocket.close();
			}
		}
	}

可以看到上面的listen()监听方法体的最后，new了一个新Thread，使用匿名内部类，线程run方法中用来处理具体的Http数据输出。

	public class HttpService {
		public HttpService() {
			super();
		}
		public static void service(Socket clientSocket) throws IOException { 
			try {
				DataOutputStream doStream = new DataOutputStream(clientSocket.getOutputStream());
				doStream.writeBytes("HTTP/1.0 200 OK\r\n");
				doStream.writeBytes("Content-type: text/html\r\n");
				doStream.writeBytes("\r\n");
				for (int i=0; i<10; i++){
					doStream.writeBytes(i+ ",");
					doStream.flush();
					System.out.println(clientSocket.getPort() + ": " + i );
					Thread.sleep(1000);;
				}
				doStream.close();
			} catch (Exception e) {
				e.printStackTrace();
			} finally{
				clientSocket.close();
				System.out.println(clientSocket.getPort() +  " finishes");
			}
		}
	}
	
这个小型的Http服务器，明显很不完美，但它已经具备了基本的web服务器的雏形，它能接收客户浏览器同时发出的多个连接请求，简单地输出数字。
	
	2517 connected to server
	2517: 0
	2517: 1
	2519 connected to server
	2519: 0
	2517: 2
	2519: 1
	2517: 3
	2519: 2
	2517: 4
	2519: 3
	2517: 5
	2519: 4
	2517: 6
	2519: 5
	2517: 7
	2519: 6
	2517: 8
	2519: 7
	2517: 9
	2519: 8
	2517 finishes
	2519: 9
	2519 finishes
	2524 connected to server
	2524: 0
	2525 connected to server
	2525: 0
	2524: 1
	2525: 1
	2524: 2
	2525: 2
	2524: 3
	2525: 3
	2524: 4
	2525: 4
	2524: 5
	2525: 5
	2524: 6
	2525: 6
	2524: 7
	2525: 7
	2528 connected to server
	2528: 0
	2528: 1
	2524: 8
	2525: 8
	2524: 9
	2525: 9
	2524 finishes
	2525 finishes
	2528: 2
	2528: 3
	2528: 4
	2528: 5
	2528: 6
	2528: 7
	2528: 8
	2528: 9
	2528 finishes
