```
                       _        
                      | |       
_ __   __ _ _ __ _   _| |_ ___  
| '_ \ / _` | '__| | | | __/ _ \ 
| | | | (_| | |  | |_| | || (_) |
|_| |_|\__,_|_|   \__,_|\__\___/ .TIGERB.cn
			
An object-oriented multi process manager for PHP

```

# 业务场景

在我们实际的业务场景中(PHP技术栈)，我们可能需要定时或者近乎实时的执行一些业务逻辑，简单的我们可以使用unix系统自带的crontab实现定时任务，但是对于一些实时性要求比较高的业务就不适用了，所以我们就需要一个常驻内存的任务管理工具，为了保证实时性，一方面我们让它一直执行任务(适当的睡眠，保证cpu不被100%占用)，另一方面我们实现多进程保证并发的执行任务。

# 目的

综上所述，我的目标就是：实现基于php-cli模式实现的master-worker多进程管理工具。其次，“我有这样一个目标，我是怎样一步步去分析、规划和实现的”，这是本文的宗旨。

> 备注：下文中，父进程统称为master,子进程统称为worker。

# 分析

我们把这一个**大目标拆成多个小目标**去逐个实现，如下：

- 多进程
  + 目的：一个master fork多个worker
  + 现象：所有worker的ppid父进程ID为当前master的pid
- master控制worker
  + 目的：master通知worker，worker接收来自master的消息
- master接收信号
  + 目的：master接收并自定义处理来自终端的信号

### 多进程

PHP fork进程的方法 `pcntl_fork`, 这个大家应该有所了解，如果不知道的简单google/bing一下应该很容易找到这个函数。接着FTM, 我们看看`pcntl_fork`这个函数的使用方式大致如下：

```php
$pid = pcntl_fork(); // pcntl_fork 的返回值是一个int值
                     // 如果$pid=-1 fork进程失败
                     // 如果$pid=0 当前的上下文环境为worker
                     // 如果$pid>0 当前的上下文环境为master，这个pid就是fork的worker的pid
```

接着看代码：

```php
$pid = pcntl_fork();	
switch ($pid) {
  case -1:
    // fatal error 致命错误 所有进程crash掉
    break;

  case 0:
    // worker context
    exit; // 这里exit掉，避免worker继续执行下面的代码而造成一些问题
    break;

  default:
    // master context
    pcntl_wait($status); // pcntl_wait会阻塞，例如直到一个子进程exit
    // 或者 pcntl_waitpid($pid, $status, WNOHANG); // WNOHANG:即使没有子进程exit，也会立即返回
    break;
}
```

我们看到master有调用`pcntl_wait`或者`pcntl_waitpid`函数，为什么呢？首先我们在这里得提到两个概念，如下：

- 孤儿进程：父进程挂了，子进程被pid=1的init进程接管(wait/waitpid)，直到子进程自身生命周期结束被系统回收资源和父进程采取相关的回收操作
- 僵尸进程：子进程exit退出,父进程没有通过wait/waitpid获取子进程状态，子进程占用的进程号等描述资源符还存在，产生危害：例如进程号是有限的，无法释放进程号导致未来可能无进程号可用

所以，`pcntl_wait`或者`pcntl_waitpid`的目的就是防止worker成为僵尸进程(zombie process)。

除此之外我们还需要把我们的master挂起和worker挂起，我使用的的是while循环，然后`usleep(200000)`防止CPU被100%占用。

最后我们通过下图(1-1)来简单的总结和描述这个多进程实现的过程：

<p align="center"><img src="http://odcgj0xrb.bkt.clouddn.com/multi-process.png" width="500px"></p>

### master控制worker

上面实现了多进程和多进程的常驻内存，那master如何去管理worker呢？答案：多进程通信。话不多说google/bing一下，以下我列举几种方式：

- 命名管道: 感兴趣
- 队列: 个人感觉和业务中使用redis做消息队列思路应该一致
- 共享内存: 违背“不要通过共享内存来通信，要通过通信来实现共享”原则
- 信号: 承载信息量少
- 套接字: 不熟悉

所以我选择了“命名管道”的方式。我设计的通信流程大致如下：

- step 1: 创建worker管道
- step 2: master写消息到worker管道
- step 3: worker读消息从worker管道

接着还是逐个击破，当然话不多说还是google/bing一下。`posix_mkfifo`创建命名管道、`fopen`打开文件(管道以文件形式存在)、`fread`读取管道、`fclose`关闭管道就呼啸而出，哈哈，这样我们就能很容易的实现我们上面的思路的了。接着说说我在这里遇到的问题：`fopen`阻塞了，导致业务代码无法循环执行，一想不对啊，平常`fopen`普通文件不存在阻塞行为,这时候二话不说FTM搜`fopen`,crtl+f页面搜“block”，重点来了：

> fopen() will block if the file to be opened is a fifo. This is true whether it's opened in "r" or "w" mode.  (See man 7 fifo: this is the correct, default behaviour; although Linux supports non-blocking fopen() of a fifo, PHP doesn't).

翻译下，大概意思就是“当使用fopen的r或者w模式打开一个fifo的文件，就会一直阻塞;尽管linux支持非阻塞的打开fifo，但是php不支持。”，得不到解决方案，不支持，感觉要放弃，一想这种场景应该不会不支持吧，再去看看`posix_mkfifo`,结果喜出望外：

```
<?php
  $fh=fopen($fifo, "r+"); // ensures at least one writer (us) so will be non-blocking
  stream_set_blocking($fh, false); // prevent fread / fwrite blocking
?>

The "r+" allows fopen to return immediately regardless of external  writer channel.
```

结论使用“r+”,同时我们又知道了使用`stream_set_blocking`防止紧接着的`fread`阻塞。接着我们用下图(1-2)来简单的总结和描述这个master-worker通信的方式。

<p align="center"><img src="http://cdn.tigerb.cn/pipe.png" width="500px"></p>

### master接收信号

最后我们需要解决的问题就是master怎么接受来自client的信号，google/bing结论：
```
master接收信号 -> pcntl_signal注册对应信号的handler方法 -> pcntl_signal_dispatch() 派发信号到handler
```

如下图(1-3)所示，

<p align="center"><img src="http://cdn.tigerb.cn/signal.png" width="500px"></p>

### 其他

接着我们只要实现不同信号下master&worker的策略，例如worker的重启等。这里需要注意的就是，当master接受到重启的信号后，worker不要立即exit，而是等到worker的业务逻辑执行完成了之后exit。具体的方式就是：

```
master接收reload信号 -> master把reload信号写worker管道 -> worker读取到reload信号 -> worker添加重启标志位 -> worker执行完业务逻辑后且检测到重启的标志位后exit
```

# 建模

上面梳理完我们的实现方式后，接着我们就开始码代码了。码代码之前进行简单的建模，如下：

进程管理类Manager

```
- attributes
  + master: master对象
  + workers: worker进程对象池
  + waitSignalProcessPool: 等待信号的worker池
  + startNum: 启动进程数量
  + userPasswd: linux用户密码
  + pipeDir: 管道存放路径
  + signalSupport: 支持的信号
  + hangupLoopMicrotime: 挂起间隔睡眠时间
- method
  + welcome: 欢迎于
  + configure: 初始化配置
  + fork: forkworker方法
  + execFork: 执行forkworker方法
  + defineSigHandler: 定义信号handler
  + registerSigHandler: 注册信号handler
  + hangup: 挂起主进程
```

进程抽象类Process

```
- attributes
  + type: 进程类型 master/worker
  + pid: 进程ID
  + pipeName: 管道名称 
  + pipeMode: 管道模式
  + pipeDir: 管道存放路径
  + pipeNamePrefix: 管道名称前缀
  + pipePath: 管道生成路径
  + readPipeType: 读取管道数据的字节数
  + workerExitFlag: 进程退出标志位
  + signal: 当前接受到的信号
  + hangupLoopMicrotime: 挂起间隔睡眠时间
- method
  + hangup: 挂起进程(抽象方法)
  + pipeMake: 创建管道
  + pipeWrite: 写管道
  + pipeRead: 读管道
  + clearPipe: 清理管道文件
  + stop: 进程exit
```

master实体类MasterProcess

```
- attributes
  + 
- method
  + hangup: 挂起进程
```

worker实体类MasterProcess

```
- attributes
  + 
- method
  + dispatchSig: 定义worker信号处理方式
```

最后我们需要做的就是优雅的填充我们的代码了。

# 最后

项目地址 <https://github.com/TIGERB/naruto>

个人知识还有很多不足，如果有写的不对的地方，希望大家及时指正。

THX~

<p align="center"><img src="http://cdn.tigerb.cn/naruto-zsh.png" width="500px"></p>
