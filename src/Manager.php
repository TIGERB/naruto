<?php
namespace Naruto;

use Naruto\Master;
use Naruto\Worker;
use Naruto\ProcessException;

class Manager
{
	private $master = '';
	public  $workers = [];
	private $waitSignalProcessPool = [];
	private $minNum = 1;
	private $maxNum = 10;
	private $startNum = 5;
	private $userPasswd = '';
	private $signalSupport = [
		// reload signal
		'SIGUSR1' => 10,
		// 
		// 'SIGINT'  => 2,
		// // 
		// 'SIGQUIT'  => 3
	];

	const LOOP_SLEEP_TIME = 1;

	public function __construct($config = [])
	{
		// set user password
		$this->userPasswd = $config['passwd'];

		// set worker start number
		$this->startNum = $config['worker_num'];

		// init master instance
		$this->master = new Master();

		// exectue fork
		$this->execFork();

		// register signal handler
		$this->registerSigHandler();

		// hangup master
		$this->master->hangup();
	}

	public function defineSigHandler($signo = 0)
	{
		switch ($signo) {
			// reload signal
			case SIGUSR1:
				$this->master->pipeWrite('reload');
			break;

			default:

			break;
		}
	}

	private function registerSigHandler()
	{
		if (empty($this->signalSupport)) {
			// exception

		}
		foreach ($this->signalSupport as $v) {
			pcntl_signal($v, ['Naruto\Manager', 'defineSigHandler']);
		}
	}

	private function fork()
	{
		$pid = pcntl_fork();
		
		switch ($pid) {
			case -1:
				// exception
				exit;
				break;
	
			case 0:
				// init worker instance
				$worker = new Worker();
				$worker->pipeMake();
				$worker->hangup();

				sleep(10);
				exit;
				break;
	
			default:
				$worker = new Worker("master | $pid | worker instance create", $pid);
				$this->workers[] = $worker;
				break;
		}
	}

	private function execFork()
	{
		foreach (range(1, $this->startNum) as $v) {
			$this->fork();
		}
	}
}
