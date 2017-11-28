```
                       _        
                      | |       
_ __   __ _ _ __ _   _| |_ ___  
| '_ \ / _` | '__| | | | __/ _ \ 
| | | | (_| | |  | |_| | || (_) |
|_| |_|\__,_|_|   \__,_|\__\___/ .TIGERB.cn
			
An object-oriented multi process manager for PHP

Version: 0.1.0

```

一个主进程fork多个子进程

pcntl_fork

$pid = pcntl_fork();

$pid=-1 fork失败
$pid=0 子进程
$pid>0 父进程

概念：
- 孤儿进程：父进程挂了，子进程被pid=1的init进程接管(wait/wait)，直到子进程自身生命周期结束被系统回收资源和父进程获取状态
- 僵尸进程：子进程exit退出,父进程没有通过wait/waitpid获取子进程状态，子进程占用的进程号等描述资源符还存在，产生危害：例如进程号是有限的，无法释放进程号导致未来可能无进程号可用

父进程获取状态 -> 防止子进程成为僵尸进程ZOMBIE -> pcntl_waitpid

pcntl\_waitpid 和 pcntl\_wait 区别

主进程挂起 子进程挂起 

sleep(1) 防止占用CPU


主进程管理子进程

主进程接收信号 -> pcntl\_signal注册对应信号的handler方法 -> pcntl\_signal\_dispatch() 派发信号到handler

主进程接收信号通知子进程 -> 主进程和子进程通信的方式管道 -> posix_mkfifo()

子进程接收到主进程下发的信号信息执行对应的操作 -> 通知主进程执行结果(主进程管道) -> 主进程读取信号执行对应逻辑

其他

命令脚本

建模

进程管理类Manager

```
- attributes
  + master: 主进程对象
  + workers: 子进程进程对象池
  + waitSignalProcessPool: 等待信号的子进程池
  + minNum: 最少闲置进程数量
  + maxNum: 最大闲置进程数量
  + startNum: 启动进程数量
  + userPasswd: linux用户密码
  + signalSupport: 支持的信号
  + LOOP_SLEEP_TIME: 挂起间隔睡眠时间
- method
  + fork: fork子进程方法
  + execFork: 执行fork子进程方法
  + defineSigHandler: 定义信号handler
  + registerSigHandler: 注册信号handler
```

进程抽象类Process

```
- attributes
  + type: 进程类型 master/worker
  + pid: 进程ID
  + pipeName: 管道名称 
  + pipeMode: 管道模式
  + pipeNamePrefix: 管道名称前缀
  + pipePath: 管道生成路径
- method
  + hangup: 挂起进程(抽象方法)
  + pipeMake: 创建管道
  + pipeWrite: 写管道
  + pipeRead: 读管道
```

主进程实体类MasterProcess

```
- attributes
  + 
- method
  + 
```

子进程实体类MasterProcess

```
- attributes
  + 
- method
  + 
```

问题

fopen 

阻塞的

> although Linux supports non-blocking fopen() of a fifo, PHP doesn't

posix_mkfifo -> use r+
> The "r+" allows fopen to return immediately regardless of external  writer channel.
