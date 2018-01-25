```
                       _        
                      | |       
_ __   __ _ _ __ _   _| |_ ___  
| '_ \ / _` | '__| | | | __/ _ \ 
| | | | (_| | |  | |_| | || (_) |
|_| |_|\__,_|_|   \__,_|\__\___/ .TIGERB.cn
			
An object-oriented multi process manager for PHP

Version: 0.3.5

```

<p align="center">
<a href="http://naruto.tigerb.cn/"><img src="https://img.shields.io/badge/os-Linux%26Darwin-blue.svg" alt="OS"></a>
</p>


<p align="center"><img width="30%" src="http://cdn.tigerb.cn/wechat-blog-qrcode.jpg"><p>

# How to use?

### Install

```
composer create-project tigerb/naruto naruto --prefer-dist
```

### Business code

```php
use Naruto\Manager;
use Naruto\Process;
use Naruto\ProcessException;
use Exception as Ex;
use App\Demo\Test;

/**
 * example
 * 
 * $config = [
 * 		'passwd' => '123456', // unix user passwd
 * 		'worker_num' => 5, // worker start number,
 * 		'os' => 'Linux' // os type
 * ]
 * new Manager($config, $closure)
 */
try {
	$instance = new Manager([
		'os'         => $input['os']?? 'Linux',
		'passwd' 	 => $input['passwd']?? '',
		'worker_num' => $input['worker-num']?? 5,
		], function (Process $worker) {
			// mock business logic
			$instance = new Test();
			$instance->businessLogic();
			$instance->dbTest();
		}
	);
} catch (Ex $e) {
	ProcessException::error([
		'msg' => [
			'msg'  => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
		]
	]);
}
```

### Run

> export PATH="$PATH:\<yourpath\>/naruto/bin"

> export NARUTO_PATH="\<yourpath\>/naruto"

> composer install

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
- [x] Optimize log
- [x] Use a lightweight Orm [Metoo](https://github.com/catfan/Medoo)
- [x] Implement max execute times for the worker process
- [x] Add config file
- [ ] Implement email send when the worker in a exception&error
- [ ] Add config reload strategy
