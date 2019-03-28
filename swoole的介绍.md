## swoole的介绍
> 林创彬 2017-11

### 前言
PHP是最好的语言(自黑一波)，Swoole重新定义了最好的语言，这当然是个梗了，不过php做为一个入门低、开发快、执行效率高的一门语言，而在以快速著称的pc互联网时代，无可争议的成为首选，这是php的优势，然后优势慢慢转化为思维定势，在很多工程师看来php开发就等同于web开发，然而如今已经是移动互联的时代，物联网，智能硬件也如火如涂，好像php不是那么受待见了（ps:一直如此），而swoole的出现，成功突破了这一思维定势，使phper可以从web开发跳出，进入了更大的服务器网络编程领域，但web开发和服务器网络编程在开发思维上还是有很大的不同，

#### 什么是swoole
swoole是PHP的异步、并行、高性能网络通信引擎，使用纯C语言编写，提供了PHP语言的异步多线程服务器，异步TCP/UDP网络客户端，异步MySQL，异步Redis，数据库连接池，AsyncTask，消息队列，毫秒定时器，异步文件读写，异步DNS查询。 Swoole内置了Http/WebSocket服务器端/客户端、Http2.0服务器端。

##### 为什么我们要使用swoole
用户打开了我们的网站。他要做的就是勾选需要发邮件的收件人列表，然后把结算邮件发出去。  

假如我们需要发1封邮件，我们写个函数执行即可。考虑到网络可能会稍微有点延迟，但是是可以接受的，用户会乖乖等你的网页发完邮件了再关闭网页。  

假如我们要发布10封邮件，用一个for循环，循环10遍执行发邮件操作。这时候，也许10倍的网络延迟会让用户稍微有点不耐烦，但勉强可以等吧。  
假如要发100封邮件，for循环100遍，超长的等待，甚至超时！

但实际上，我们很可能有超过1万的邮件。怎么处理这个延迟的问题？  

答案就是用异步。把“发邮件”这个操作封装，然后后台异步地执行1万遍。这样的话，用户提交网页后，他所等待的时间只是“把发邮件任务请求推送进队列里”的时间。而我们的后台服务将在用户看不见的地方跑。而swoole就为我们实现了异步队列处理及并发等问题。


#### php和swoole的关系
> swoole是php的一个扩展，纯c开发，主要是为了补充php在网络编程方面的不足。不是框架，不是框架，不是框架。

#### PHP与SWOOLE的运行模式

php做为swoole的宿主，所以了解php本身的运行模式是必不可少的，下图是以cli下执行一个php文件时的完整流程。  
![Alt text](/Users/linchuangbin/Desktop/技术分享会/swoole/ci图片.png "Optional title")

这上层有个SAPI的概念，SAPI是php给外部环境能够执行php内核提供的一个统一接口,我们常见的三种SAPI有cli, php-fpm, mod_php。
在这里，以fpm为例，把运行周期的关键5步拿出来：  
>
1.MINIT  
在这步（包括之前）php引擎会初始化一些公用配置，读取ini文件，加载zend引擎，执行所以模块的MINIT模块，然后就长驻在fpm进程中，然后就等待处理请求.  
2.RINIT  
在每个请求过来之后，会调用所有模块的RINIT进行一些请求内数据的初始化，比如一些超全局变量，一些模块数据初始化等  
3.执行php  
然后在这加载php文件，进行词法，语法分析，生成opcode代码，交由zend vm执行, 暂存执行结果.   
4. RSHUTDOWN  
在把结果返回给fpm之前，会调用所有模块的RSHUTDOWN模块进行一些数据的回收，zend vm也会关闭打开的数据流，进行内存释放等操作，然后把暂存的执行结果flush输出.   
5.  MSHUTDOWN  
这一阶段在重启fpm时发生，会调用所有模块的MSHUTDOWN,关闭zend引擎等操作
到这，可以得到一些结论：   


fpm每个请求都是在执行2~4步  
> opcode cache是把第3步的词法分析、语法分析、生成opcode代码这几个操作给缓存起来了，从而达到加速的作用  

我们分析出了php的基本流程，那swoole是在哪一步执行的呢？首先，swoole运行有个前提条件： 必需在cli模式下执行. 然后在第3步，swoole就接管了php，进入了swoole的生命周期了。

##### 如何使用
```php
//创建Server对象，监听 127.0.0.1:9501端口
$serv = new swoole_server("127.0.0.1", 9501); 

//监听连接进入事件
$serv->on('connect', function ($serv, $fd) {  
    echo "Client: Connect.\n";
});

//监听数据发送事件
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    $serv->send($fd, "Server: ".$data);
});

//监听连接关闭事件
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

//启动服务器
$serv->start(); 
```
由于服务端是异步、常驻内存的，因此必须通过命令行来启动。在命令行执行以上代码以启动服务。执行完毕后关闭命令行窗口即可。服务会在后台以守护进程运行。

```
php Server.php
```
这里就创建了一个TCP服务器，监听本机9501端口，你也可以改成其他的端口号，只要你的服务器可以支持这个端口。
 
##### swoole的进程模型
简单开启一个swoole服务，如上述例子。

```
php swoole.php
```
在启动服务之后，我们继续在shell中输入以下命令：

```
pstree |grep swoole.php
```

> pstree命令可以查看进程的树模型

从系统的输出中，我们可以很容看出server其实有n个进程（3个）
进程之间有父子关系

> 所以，其实我们虽然看起来只是启动了一个Server，其实最后产生的是n(6)个进程。

这些进程中，所有进程的根进程，就是所谓的master进程，而二级进程，则是manager进程，最后的进程，就是worker进程。

基于此，我们简单分析一下，当执行start之后发生了什么。  

1. 守护进程模式下，当前进程fork出Master进程，然后退出，Master进程触发OnMasterStart事件。  
2. Master进程启动成功之后，fork出Manager进程，并触发OnManagerStart事件。  
3. Manager进程启动成功时候，fork出Worker进程，并触发OnWorkerStart事件。

> 非守护进程模式下，则当前进程直接作为Master进程工作。

所以，一个最基础的Swoole Server，至少需要有3个进程，分别是Master进程、Manager进程和Worker进程。  
事实上，一个多进程模式下的Swoole Server中，有且只有一个Master进程；有且只有一个Manager进程；却可以有n个Worker进程。

> 那么这几个进程之间是怎么协同工作的呢？我们先暂时考虑只有一个Worker的情况。

1. Client主动Connect的时候，Client实际上是与Master进程中的某个Reactor线程发生了连接。
2. 当TCP的三次握手成功了以后，由这个Reactor线程将连接成功的消息告诉Manager进程，再由Manager进程转交给Worker进程。
3. 在这个Worker进程中触发了OnConnect的方法。
4. 当Client向Server发送了一个数据包的时候，首先收到数据包的是Reactor线程，同时Reactor线程会完成组包，再将组好的包交给Manager进程，由Manager进程转交给Worker。
5. 此时Worker进程触发OnReceive事件。
如果在Worker进程中做了什么处理，然后再用Send方法将数据发回给客户端时，数据则会沿着这个路径逆流而上。

![Alt text](/Users/linchuangbin/Desktop/技术分享会/swoole/worker.png "Optional title")

首先，Master进程是一个多线程进程，其中有一组非常重要的线程，叫做Reactor线程（组），每当一个客户端连接上服务器的时候，都会由Master进程从已有的Reactor线程中，根据一定规则挑选一个，专门负责向这个客户端提供维持链接、处理网络IO与收发数据等服务。

而Manager进程，某种意义上可以看做一个代理层，它本身并不直接处理业务，其主要工作是将Master进程中收到的数据转交给Worker进程，或者将Worker进程中希望发给客户端的数据转交给Master进程进行发送。

另外，Manager进程还负责监控Worker进程，如果Worker进程因为某些意外挂了，Manager进程会重新拉起新的Worker进程，有点像Supervisor的工作.

Master进程就像业务窗口的，Reactor就是前台接待员，Reactor负责与客户直接沟通，对客户的请求进行初步的整理（传输层级别的整理——组包）；然后，Manager进程就是类似项目经理的角色，要负责将业务分配给合适的Worker（例如空闲的Worker）；而Worker进程就是工人，负责实现具体的业务。

> 实际上，一对多投递这种模式总是在并发的程序设计非常常见：1个Master进程投递n个Reactor线程；1个Manager进程投递n个Worker进程。

来看看一个简单的多进程Swoole Server的几个基本配置：

```
$server->set([
    "daemonize"=>true,
    "reactor_num"=>2,
    "worker_num"=>4,
]);
$server -> start();
```

reactor_num：表示Master进程中，Reactor线程总共开多少个，注意，这个可不是越多越好，因为计算机的CPU是有限的，所以一般设置为与CPU核心数量相同，或者两倍即可。

worker_num：表示启动多少个Worker进程，同样，Worker进程数量不是越多越好，仍然设置为与CPU核心数量相同，或者两倍即可。


#### 例子
> 使用swoole开发定时执行功能。


没有swoole，如何开发定时执行功能？  
1.在Crontab中使用PHP执行脚本。在Crontab中调用普通的shell脚本一样（具体Crontab用法），使用PHP程序来调用PHP脚本，每一小时执行 myscript.php 如下：

```
00 * * * * /usr/local/bin/php /home/john/myscript.php
```

2.
在Crontab中使用URL执行脚本.如果你的PHP脚本可以通过URL触发，你可以使用 lynx 或 curl 或 wget 来配置你的Crontab。

```
00 * * * * lynx -dump http://www.honraytech.com/myscript.php
```

3.使用swoole的定时器

```
//swoole_timer_tick函数就相当于setInterval，是持续触发的
//每隔2000ms触发一次
swoole_timer_tick(2000, function ($timer_id) {
    echo "tick-2000ms\n";
});

//swoole_timer_after函数相当于setTimeout，仅在约定的时间触发一次
//3000ms后执行此函数
swoole_timer_after(3000, function () {
    echo "after 3000ms.\n";
});
```

#### 进程模型与数据共享
我们最常接触到的回调方法如下：

1. OnConnect
2. OnReceive
3. OnClose

这三个回调其实都是在Worker进程中发生的，而了解了进程模型以后，我们可以认识一下更多的回调方法了：

```
// 以下回调发生在Master进程
$server->on("start", function (\swoole_server $server){
    echo "On master start.";
});
$server->on('shutdown', function (\swoole_server $server){
    echo "On master shutdown.";
});

// 以下回调发生在Manager进程
$server->on('ManagerStart', function (\swoole_server $server){
    echo "On manager start.";
});
$server->on('ManagerStop', function (\swoole_server $server){
    echo "On manager stop.";
});

// 以下回调也发生在Worker进程
$server->on('WorkerStart', function (\swoole_server $server, $worker_id){
    echo "Worker start";
});
$server->on('WorkerStop', function(\swoole_server $server, $worker_id){
    echo "Worker stop";
});
$server->on('WorkerError', function(\swoole_server $server, $worker_id, $worker_pid, $exit_code){
    echo "Worker error";
});

```

现在我们更新一下我们的测试代码，以展示不同进程之间，数据共享的特点和关系：
看 ```data.php```

从Manager start和Worker start中的输出，我们发现BaseProcess、MasterToManager、ManagerToWorker并没有分别在Master、Manager中被修改，并在子进程中打印出被修改后的结果，这是为什么呢？

打开会话二，先执行

```
pstree | grep data.php
```

找到刚刚启动的Server的worker进程的PID，然后向该进程发送-10信号，然后再次实行pstree命令看看

然后向该进程发送-10信号，然后再次实行pstree命令看看

```
kill -10 xxx
```

-10信号的作用是，要求Swoole重启Worker服务，我们会发现原来的Worker被干掉了，而产生了一个新的Worker，此时如果我们切换回会话一，会发现增加了终端的输出。

首先是Swoole自己打印的日志信息，Server正在被reloading，
然后Worker被终止，执行了WorkerStop的方法，此时WorkerStop输出的值我们可以看出，在WorkerStart中的赋值都是生效了的；然后，新的Worker被启动了，重新触发WorkerStart方法，这时我们发现，BaseProcess、MasterToManager和ManagerToWorker都分别被打印了出来？这是什么原因呢？

在方法被执行的顺序上，我们前文中的进程起调顺序并没有问题，但有些地方我们要做一点小小的细化：

1. Master进程被启动。
2. Manager进程Master进程fork出来。
3. Worker进程被Manager进程fork出来。
4. MasterStart被回调。
5. ManangerStart被回调。
6. WorkerStart被回调。

三种进程的OnStart方法被回调的时候都有一定的延迟，底层事实上已经完工了fork的行为，才回调的，因此，默认启动的时候，我们在OnMasterStart、OnManagerStart中写入的数据并不能按预期被fork到Manager进程或者Worker进程。

然后，我们执行了kill -10重新拉起Worker进程的时候，此时Worker进程仍然是由Mananger进程fork出来的，但此时ManangerStart已经被执行过了，所以我们会发现在OnWorkerStart的时候，输出变成了ManagerStart中修改过的内容。

> 现在我们回到Shell会话二，向Master进程发送kill -15命令

发现终端的信息。
kill -15命令是通知Swoole正常终止服务，首先停止Worker进程，触发OnWorkerStop回调，此时我们输出的内容都是我们在WorkerStart中修改过的版本。

然后停止Manager进程，这时候要留意，我们在Worker中做的所有操作并没有反应在Manager进程上，OnManagerStop的输出仍然是在OnManagerStart中赋值的内容。

最后停止Master进程，也会有相同的事情发生。

通过以上实验，展示了多进程Server的两个重要特性：

```
1. 父进程fork出子进程的时候，子进程会拷贝一份父进程的所有数据。
2. 各个进程之间的数据一般情况下是不共享内存的。
```

如果没有弄清楚当前的代码是在哪个进程执行的，很有可能就会引起数据的错误，而多个进程之间进行协作的话，不能像以往的PHP开发一样，通过共享变量实现。
