---
title: 在MFC中给程序注册热键
author: Jie Chen
date: 2009-04-18
categories: [Others]
tags: [mfc]
---

给程序注册一个热键相当简单，只需要调用Windows的API RegisterHotKey以及UnregisterHotKey即可，然后再创建一个函数来处理基于WM_HOTKEY的消息映像。

## API原型

MSDN查看RegisterHotKey：

	BOOL RegisterHotKey(          
		HWND hWnd,          
		int id,             
		UINT fsModifiers,
		UINT vk
	);

- hWnd 设定哪个窗口来接受键盘WM_HOTKEY消息，一般默认本程序可设置为this->m_hWnd。如果设置为NULL，即交给系统注册该热键，接受到的键盘消息进入系统级的消息循环，而非程序级。
- id 热键的标识符。如果相同的hWnd中给相同的id多次注册热键，最后注册的热键才会被启用
- fsModifiers 由Alt, Ctrl, Shift, Win四个键自由组合而成，如同时按下Ctrl+Alt，则设置为 MOD_CONTROL | MOD_ALT
- vk 由键盘上的其他键组成，不可组合
- 返回值 由bool值决定。不能注册和系统级相同的热键（如Win+D显示桌面），能注册和其他程序级相同的热键。比如注册Ctrl+A，消息进入消息队列分配给注册热键的程序，同时会屏蔽其他程序的Ctrl+A功能。

再看UnregisterHotKey

	BOOL UnregisterHotKey(          
		HWND hWnd,
		int id
	);

- hWnd 设定哪个窗口释放热键
- id 热键的标识符

UnregisterHotKey是安全的，即使释放一个不存在的热键也不会引起错误。

## 代码示例

在窗口的创建过程中注册热键Ctrl+Alt+A，热键标识为2009：

	int CHotkeyWindow::OnCreate(LPCREATESTRUCT lpCreateStruct)
	{
		if (!RegisterHotKey(this->m_hWnd, 2009, MOD_CONTROL | MOD_ALT, 'A'))
		{
			AfxMessageBox("Hotkeys already registered by other program");
			return false;
		}
		return 0;
	};
	
在窗口类的消息映像声明中加入一个OnHotkey的函数来处理WM_HOTKEY热键消息，同时在窗口类中声明该函数。

	BEGIN_MESSAGE_MAP(CHotkeyWindow, CFrameWnd)  
	ON_MESSAGE(WM_HOTKEY,OnHotkey)   
	END_MESSAGE_MAP() 

	afx_msg LONG OnHotkey(WPARAM wParam,LPARAM lParam);
	
定义如下：

	LONG CHotkeyWindow::OnHotkey(WPARAM wParam,LPARAM lParam)
	{  
		switch(wParam)
		{
			case 2009:
				AfxMessageBox("CTRL + ALT + A");
				break;
			default:
				AfxMessageBox("UNKNOWN KEYS"); 
				break;
		}
		return 0;           
	};
第二种方式：

	LONG CHotkeyWindow::OnHotkey(WPARAM wParam,LPARAM lParam)
	{
		UINT funcKeys = (UINT) LOWORD(lParam);  // ctrl? alt? shift?    
		UINT virtKey = (UINT) HIWORD(lParam);     // virtual-key code  
		if (funcKeys==(MOD_CONTROL|MOD_ALT))
		{
			AfxMessageBox("CTRL+ALT");
		}
		if (virtKey=='A')
		{
			AfxMessageBox("A");
		}else if (virtKey=='B')
		{
			AfxMessageBox("B");
		}
		return 0;           
	};
	
最后在窗口的销毁函数中释放热键：

	UnregisterHotKey(this->m_hWnd, 2009);

运行程序，如图所示：

![](/assets/res/mfc_hotkey_screen.jpg)


## 备注

热键和快捷键、键盘hook属于不同的概念。