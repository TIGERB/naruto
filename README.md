```
                       _        
                      | |       
_ __   __ _ _ __ _   _| |_ ___  
| '_ \ / _` | '__| | | | __/ _ \ 
| | | | (_| | |  | |_| | || (_) |
|_| |_|\__,_|_|   \__,_|\__\___/ .TIGERB.cn
			
An object-oriented multi process manager for PHP

Version: 0.3.0

```

# How to use?

### Install

```
composer create-project tigerb/naruto naruto --prefer-dist
```

### Business code
```php
use Naruto\Manager;
use Naruto\Process;

$instance = new Manager([
		'passwd' 	 => 'tigerb',
		'worker_num' => 5,
		], function (Process $worker) {
      # your business logic here ...
      
		}
	);
```

### Run

> export PATH="$PATH:\<yourpath\>/naruto/bin"

```
naruto start/reload/quit/stop
```

### Manager process

- start \<worker-num\> \<passwd\>: start the naruto
- reload: gracefully quit&start the worker process
- quit: gracefully exit
- stop: forcefully exit

# Specification

- [中文](./docs/specification-zh.md)
- English

# TODO

- [x] Implement a shell script to control the process
- [x] Implement a daemon for worker by the master
- [ ] Add more information for the log
