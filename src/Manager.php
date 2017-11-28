<?php
/****************************************************
 *                     naruto                       *
 *                                                  *
 * An object-oriented multi process manager for PHP *
 *                                                  *
 *                    TIERGB                        *
 *           <https://github.com/TIGERB>            *
 *                                                  *
 ****************************************************/

namespace Naruto;

use Naruto\Master;
use Naruto\Worker;
use Naruto\ProcessException;
use Closure;

/**
 * process manager
 */
class Manager
{
	/**
	 * master process object
	 *
	 * @var object Process
	 */
	private $master = '';

	/**
	 * worker process objects
	 *
	 * @var array [Process]
	 */
	public  $workers = [];

	/**
	 * the pool for the worker that will be handle by the signal
	 *
	 * signal: string reload/stop
	 * pool: array [Process]
	 * 
	 * @var array
	 */
	private $waitSignalProcessPool = [
		'signal' => '',
		'pool'	 => []
	];

	/**
	 * the closure object which will be inject in the worker object
	 *
	 * @var object Closure
	 */
	private $workBusinessClosure = '';

	/**
	 * minimum idle worker process number
	 *
	 * @var int
	 */
	// private $minNum = 1;

	/**
	 * maximum idle worker process number
	 *
	 * @var object Process
	 */
	// private $maxNum = 10;
	private $startNum = 5;

	/**
	 * linux user passwd
	 *
	 * @var string
	 */
	private $userPasswd = '';

	/**
	 * support linux signals
	 *
	 * @var array
	 */
	private $signalSupport = [
		'reload' => 10, // reload signal
		'stop'   => 12, // stop signal
		// 'int'	 => 2 // interrupt signal
	];

	/**
	 * hangup sleep time unit:second /s
	 *
	 * @var int
	 */
	const LOOP_SLEEP_TIME = 1;

	/**
	 * construct function
	 */
	public function __construct($config = [], Closure $closure)
	{
		// set user password
		$this->userPasswd = $config['passwd'];

		// set worker start number
		$this->startNum = $config['worker_num'];

		// init master instance
		$this->master = new Master();

		// register worker business logic
		$this->workBusinessClosure = $closure;

		// int signal num
		$this->signalSupport = [
			'reload' => SIGUSR1,
			'stop'	 => SIGUSR2,
			// 'int'	 => SIGINT
		];
		
		// exectue fork
		$this->execFork();

		// register signal handler
		$this->registerSigHandler();

		// hangup master
		$this->hangup();
	}

	/**
	 * define signal handler
	 *
	 * @param integer $signal
	 * @return void
	 */
	public function defineSigHandler($signal = 0)
	{
		switch ($signal) {
			// reload signal
			case $this->signalSupport['reload']:
				// throw worker process to waitSignalProcessPool
				$this->waitSignalProcessPool = [
					'signal' => 'reload',
					'pool'	 => $this->workers
				];
				// push reload signal to the worker processes from the master process
				foreach ($this->workers as $v) {
					$v->pipeWrite('reload');
				}
			break;

			// kill signal
			case $this->signalSupport['stop']:
				// throw worker process to waitSignalProcessPool
				$this->waitSignalProcessPool = [
					'signal' => 'stop',
					'pool'	 => $this->workers
				];
				// push reload signal to the worker processes from the master process
				foreach ($this->workers as $v) {
					$v->pipeWrite('stop');
				}
			break;

			default:

			break;
		}
	}

	/**
	 * register signal handler
	 *
	 * @return void
	 */
	private function registerSigHandler()
	{
		foreach ($this->signalSupport as $v) {
			pcntl_signal($v, ['Naruto\Manager', 'defineSigHandler']);
		}
	}

	/**
	 * fork a worker process
	 *
	 * @return void
	 */
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
				$worker->hangup($this->workBusinessClosure);

				// worker exit
				exit;
				break;
	
			default:
				$worker = new Worker("worker instance create", $pid, 'master');
				$this->workers[$pid] = $worker;
				break;
		}
	}

	/**
	 * execute fork worker operation
	 *
	 * @return void
	 */
	private function execFork()
	{
		foreach (range(1, $this->startNum) as $v) {
			$this->fork();
		}
	}

	/**
	 * hangup the master process
	 */
	private function hangup()
	{
		while (true) {
			// dispatch signal for the handlers
			pcntl_signal_dispatch();

			// prevent the child process become a zombie process
			// pcntl_wait($status);
			foreach ($this->workers as $k => $v) {
				$res = pcntl_waitpid($v->pid, $status, WNOHANG);
				// if ($res == -1 || $res = 0) {
				// 	// exception
				// 	continue;
				// }
				if ($res > 0) {
					unset($this->workers[$res]);
					
					if ($this->waitSignalProcessPool['signal'] === 'reload') {
						if (array_key_exists($res, $this->waitSignalProcessPool['pool'])) {
							unset($this->waitSignalProcessPool['pool'][$res]);
							// fork a new worker
							$this->fork();
						}
					}

					if ($this->waitSignalProcessPool['signal'] === 'stop') {
						if (array_key_exists($res, $this->waitSignalProcessPool['pool'])) {
							unset($this->waitSignalProcessPool['pool'][$res]);
						}
					}

				}
			}

			if ($this->waitSignalProcessPool['signal'] === 'stop') {
				// all worker stop then stop the master process
				if (empty($this->waitSignalProcessPool['pool'])) {
					$this->master->stop();
				}
			}

			// read signal from worker
			// $this->master->pipeRead();

			// precent cpu usage rate reach 100%
			sleep(self::LOOP_SLEEP_TIME);
		}
	}
}
