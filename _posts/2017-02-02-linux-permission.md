---
title: 文件/文件夹权限的override
author: Jie Chen
date: 2017-02-02
categories: [Linux]
tags: [shell]
---


## setUID / setGID

setUID会让当前用户能够临时地以执行文件的拥有者的身份，相当于用高权限override了自己的低级权限。比如某个文件不允许被普通用户写操作（比如vi）、甚至不允许最基本的读操作（比如cat）。但是当root授予vi或者cat予setUID或者setGID后，当前用户就拥有了vi/cat拥有者的权限，具备了对文件的读写。

假设root用户创建了一个文本文件，并且只赋给 rwx------ 权限。

~~~
[root@localhost shell]# p2d.sh rwx------
permission = rwx------
binary = 111000000
decimal(str) = 700

[root@localhost shell]# chmod 700 test.txt

[root@localhost shell]# ll test.txt
-rwx------. 1 root root 7 Apr 29 05:41 test.txt
~~~

切换为普通用户，Permission denied

~~~
[oracle@localhost shell]$ touch test.txt
touch: cannot touch ‘test.txt’: Permission denied

[oracle@localhost shell]$ cat test.txt
cat: test.txt: Permission denied

[oracle@localhost shell]$ vi test.txt
[Permission Denied]  
~~~

root用setUID命令，修改这些可执行文件的权限标记s

~~~
[root@localhost shell]# which touch cat vim
/bin/touch
/bin/cat
/bin/vim

[root@localhost shell]# ll /bin/touch /bin/cat /bin/vim
-rwxr-xr-x. 1 root root   54080 Sep  7  2016 /bin/cat
-rwxr-xr-x. 1 root root   62488 Sep  7  2016 /bin/touch
-rwxr-xr-x. 1 root root 2254480 May  5  2014 /bin/vim

[root@localhost shell]#  chmod u+s /bin/touch /bin/cat /bin/vim
[root@localhost shell]# ll /bin/touch /bin/cat /bin/vim
-rwsr-xr-x. 1 root root   54080 Sep  7  2016 /bin/cat
-rwsr-xr-x. 1 root root   62488 Sep  7  2016 /bin/touch
-rwsr-xr-x. 1 root root 2254480 May  5  2014 /bin/vim
~~~

可执行文件的权限位x被升级为s

再切换为普通用户，虽然文件本身权限没变，但普通可以通过可执行文件来操作了。

~~~
[oracle@localhost shell]# ll test.txt
-rwx------. 1 root root 7 Apr 29 05:41 test.txt

[oracle@localhost shell]$ cat test.txt
hello
~~~

一个有意思的地方是，虽然vi可以修改这个文件了，但是会提示"Read Only"，用w!即可保存修改。

注意：
> setUID和setGID是针对可执行文件的

### setGID

setGID和setUID类似，只不过普通用户被override为执行文件的组身份。

## Sticky Bit

sticky位作用于文件夹，一般性的是一些共享文件夹。这个文件夹内的文件，除了目录所有者、文件所有者或者超级用户才允许删除或重命名。标志位为t

以root身份修改目录和下面的文本文件777，所有人理论上都可以修改或删除这个文件。
~~~
[root@localhost test]# mkdir sample
[root@localhost test]# chmod 777 sample
[root@localhost test]# ls -lrt
total 0
drwxrwxrwx. 2 root root 6 Apr 28 05:19 sample

[root@localhost sample]# touch hello.txt
[root@localhost sample]# chmod 777 hello.txt
[root@localhost sample]# ls -lrt
total 0
-rwxrwxrwx. 1 root root 0 Apr 28 05:23 hello.txt
~~~

设置文件夹的sticky bit，里面的文件的权限保持原样。

~~~
[root@localhost test]# chmod o+t sample
[root@localhost test]# ls -lrt
total 0
drwxrwxrwt. 2 root root 6 Apr 28 05:19 sample
~~~

切换为普通用户（非目录所有者、文件所有者或者超级用户）

~~~
[oracle@localhost sample]$ rm hello.txt
rm: cannot remove ‘hello.txt’: Operation not permitted

[oracle@localhost sample]$ mv hello.txt hello2.txt
mv: cannot move ‘hello.txt’ to ‘hello2.txt’: Operation not permitted
~~~

注意：
> sticky bit只适用于文件夹，对文件设置sticky bit无效


