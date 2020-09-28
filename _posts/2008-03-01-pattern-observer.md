---
title: 状态管理-observer-模式实现事件驱动
author: Jie Chen
date: 2008-03-01
categories: [Design]
tags: [Java,Pattern]
---

用观察者模式处理Java里的图形编程事件响应，非常好理解。多个观察者通过注册事件监听器，监听被观察者，一旦被观察者有状态改变，会发送通知给所有这些观察者去响应。举个最简单的例子，多个病人去看病，首先挂号，然后等叫号。病人就是观察者，医生就是被观察者。医生一旦发出叫号信息，所有病人会检查该叫号判断是不是自己要去看病。

我现在要设计一个用户响应的程序，需要处理鼠标的输入事件。鼠标有单击、双击、右键单击。

我先定义一个事件对象，用来表示是什么事件，这个事件用来在观察者和被观察者种传输事件对象的状态。

~~~
import java.util.EventObject;

public class EventEntity extends EventObject {

    private final String state;

    public EventEntity(Object obj, String state) {
        super(obj);
        this.state = state;
    }
    public String getState(){
        return state;
    }
}
~~~

定义观察对象，换个名词就是被观察者，也就是事件源（鼠标父类），接收观察者（监听器）的注册，并提供叫醒方式。

~~~
import java.util.Iterator;
import java.util.Vector;

public abstract class EventSource {
    private Vector<EventListener> listeners = new Vector<>();

    public void addListener(EventListener lsn){
        listeners.add(lsn);
    }

    /*
    public void removeListener(EventListener lsn){
        listeners.remove(lsn);
    }
    */

    private void notifyListener(EventEntity me){
        Iterator lit = listeners.iterator();
        while(lit.hasNext()){
            EventListener lsn = (EventListener) lit.next();
            lsn.fireEvent(me);
        }
    }

    public void action(String event){
        EventEntity me = new EventEntity(this, event);
        notifyListener(me);
    }
}
~~~

定义观察对象的子类，Mouse，由于EventSource已经有了Mouse所需要的成员，没有额外的信息需要补充，所以只是简单的继承。

~~~
public class Mouse extends EventSource{

}
~~~

接下来定义观察者本身。它提供了自己的响应方式，由于我不确定有多少个观察者（鼠标事件监听器），所以我定义一个通用的标准接口。

~~~
public interface EventListener {

    public void fireEvent(EventEntity m);

}
~~~


设计鼠标事件监听器（提供了单击和双击两种方式）

~~~
public class MouseClickEventListener implements EventListener {
    final static String EVENT_CLICK = "mouse-single-click";
    final static String EVENT_DOUBLECLICK = "mouse-double-click";
    @Override
    public void fireEvent(EventEntity m) {
        if (m.getSource().getClass() == Mouse.class)
            if (EVENT_CLICK.equalsIgnoreCase(m.getState()))
                System.out.println("EventEntity: " + m.getState());
            else if (EVENT_DOUBLECLICK.equalsIgnoreCase(m.getState()))
                System.out.println("EventEntity: " + m.getState());
    }
}
~~~

由于EventEntity继承自EventObject，getSource()包含了事件源（是来自鼠标还是其他），所以在事件处理中可以轻松地判断当前事件是不是需要我来处理。

最后，观察者模式的调用，是先定义事件源，接收不同的监听器的注册，最后发出事件动作。

用模式来说，先定义被观察者，把不同的观察者注册到这个观察者里去，然后观察者发生状态改变。后台里，观察者模式通过循环唤醒所有被注册的观察者，让他们提供处理。观察者处理前，会判断是不是的确让自己来处理。

~~~
Mouse mouse = new Mouse();
mouse.addListener(new MouseClickEventListener());

mouse.action(MouseClickEventListener.EVENT_DOUBLECLICK);
mouse.action(MouseRightClickListener.EVENT);
~~~

## 设计的拓展

增加鼠标右键事件，如果能修改MouseClickEventListener的源代码，直接修改。如果不能修改，可以继承EventListener定义一个新监听器类。

~~~
public class MouseRightClickListener implements EventListener {
    final static String EVENT = "mouse-right-click";
    @Override
    public void fireEvent(EventEntity m) {
        if (m.getSource().getClass() == Mouse.class)
            if (EVENT.equalsIgnoreCase(m.getState())){
                System.out.println("EventEntity: " + m.getState());
        }
    }
}
~~~

如果需要增加其他输入设备，比如键盘的事件监听。可以很容易扩展。

增加键盘事件源

~~~
public class Keyboard extends EventSource {
    @Override
    public void action(String event) {
        super.action(event);
        System.out.println("do other jobs for keyboard source");
    }
}
~~~

添加观察者，也就是键盘事件监听器。

~~~
public class KeyboardPressListener implements EventListener {
    final static String EVENT = "keyboard-single-click";
    @Override
    public void fireEvent(EventEntity m) {
        if (m.getSource().getClass() == Keyboard.class)
            if (EVENT.equalsIgnoreCase(m.getState()))
                System.out.println("EventEntity: " + m.getState());
    }
}
~~~

调用程序只要简单地创建一个被观察者和给被观察者添加监听。

~~~
Mouse mouse = new Mouse();
mouse.addListener(new MouseClickEventListener());

mouse.action(MouseClickEventListener.EVENT_DOUBLECLICK);
mouse.action(MouseRightClickListener.EVENT);

Keyboard kb = new Keyboard();
kb.addListener(new KeyboardPressListener());
kb.action(KeyboardPressListener.EVENT);
~~~
