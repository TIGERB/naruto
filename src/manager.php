<?php
namespace Naruto;

use Exception;

class Manager
{
	private $masterPid = 0;
	private $masterPipeName = '';
	private $workerPids = [];
	private $workerPipeNames = [];
	private $maxNum = 5;
	private $minNum = 1;
	private $criticalPoint = 5;
	private $forkNum = 0;
	private $userPasswd = '';
	private $stopSign = false;
	private $workerSignal = '';

	// private $reloadSignal = false;
	private $waitReloadProcessPool = [];
	private $pipeMode = '0777';

	const WORK_PIPE_PATH = '/tmp/';
	const WORK_PIPE_NAME = 'naruto.pipe';
	const LOOP_SLEEP_TIME = 1;

	private $workerAcceptedSignal = '';

	public function __construct($forkNum = 1, $passwd = '')
	{
		$this->forkNum 	  = $forkNum;
		$this->userPasswd = $passwd;
		$this->masterPid  = getmypid();

		// make the pipe for the master process
		$this->makeMasterPipe();

		// register signal handler
		pcntl_signal(SIGUSR1, ['Naruto\Manager', 'defineSigHandler']);
		pcntl_signal(SIGINT, ['Naruto\Manager', 'defineSigHandler']);
		pcntl_signal(SIGQUIT, ['Naruto\Manager', 'defineSigHandler']);
	}

	public function setReloadProcessSig ()
	{
		// no reload signal and worker process
		if (empty($this->waitReloadProcessPool)) {
			return;
		}

		/* write the reload signal in worker process pipe(fifo) */
		$this->workerSignal = 'signal|reload';
		$this->writeWorkerPipe();
	}

	public function setKillProcessSig ()
	{
		/* kill all process and delete all pipe */
		$pipeFiles = self::WORK_PIPE_PATH . '/' . self::WORK_PIPE_NAME . '*';
		exec("sudo rm -rf {$pipeFiles}");

		exec("sudo kill -SIGKILL {$this->masterPid} | echo {$this->userPasswd}");

		foreach ($this->workerPids as $v) {
			exec("sudo kill -SIGKILL {$this->v} | echo {$this->userPasswd}");
		}

	}

	public function makeWorkerPipe ()
	{
		// create worker pipe
		foreach ($this->workerPids as $v) {
			$pipeName = self::WORK_PIPE_PATH . self::WORK_PIPE_NAME . $v;
			if (! file_exists($pipeName)) {
				if (! posix_mkfifo($pipeName, $this->pipeMode)) {
					// throw new Exception('500', 'create pipe fail');
					echo "create pipe: {$pipeName} fail" . PHP_EOL;
					exit;
				}
				$this->workerPipeNames[] = $pipeName;
				// $old = umask(0);
				chmod($pipeName, 0666);
				// umask($old);
				// if ($old != umask()) {
				// 	// exception
				// }
			}
		}
	}

	public function makeMasterPipe ()
	{
		// create master pipe
		$pipeName = self::WORK_PIPE_PATH . self::WORK_PIPE_NAME . $this->masterPid;
		if (! file_exists($pipeName)) {
			if (! posix_mkfifo($pipeName, $this->pipeMode)) {
				// throw new Exception('500', 'create pipe fail');
				echo "create pipe: {$pipeName} fail" . PHP_EOL;
				exit;
			}
			$this->masterPipeName = $pipeName;
			// $old = umask(0);
			chmod($pipeName, 0777);
			// umask($old);
			// if ($old != umask()) {
			// 	// exception
			// }
		}
	}

	public function writeWorkerPipe ()
	{
		// var_dump($this->workerPipeNames);
		if (empty($this->workerPipeNames)) {
			// exception
			return;
		}

		foreach ($this->workerPipeNames as $v) {
			$pipe = fopen($v, 'w');
			if (! $pipe) {
				// exception
				return;
			}
			
			$res = fwrite($pipe, $this->workerSignal);
			if (! $res) {
				// exception
				return;
			}

			if (! fclose($pipe)) {
				// exception
				return;
			}

			echo "{$v} | write signal for worker pipe success" . PHP_EOL;
		}
	}

	public function writeMasterPipe ()
	{
		if (empty($this->masterPipeName)) {
			// exception
			return;
		}

		$pipe = fopen($masterPipeName, 'w');
		if (! $pipe) {
			// exception
			return;
		}
		
		$res = fwrite($pipe, $this->workerSignal);
		if (! $res) {
			// exception
			return;
		}

		if (! fclose($pipe)) {
			// exception
			return;
		}

		echo "{$masterPipeName} | write signal for master pipe success" . PHP_EOL;
	}

	public function defineSigHandler($signo = 0)
	{
		switch ($signo) {
			// reload signal
			case SIGUSR1:
				echo "[master:$this->masterPid] set reload signal" . PHP_EOL;
				$this->waitReloadProcessPool = $this->workerPids;
				// set reload signal
				$this->setReloadProcessSig();
			break;

			case SIGINT:
				echo "[master:$this->masterPid] set kill signal" . PHP_EOL;
				// set reload signal
				$this->setKillProcessSig();
			break;

			case SIGQUIT:
				echo "[master:$this->masterPid] set kill signal" . PHP_EOL;
				// set reload signal
				$this->setKillProcessSig();
			break;

			default:
				exit('crash');
			break;
		}
	}

	/**
	 * fork multi process
	 *
	 * @return void
	 */
	private function fork()
	{
		$pid = pcntl_fork();
		
		switch ($pid) {
			case -1:
				exit('Fork a worker process fail' . PHP_EOL);
				break;
	
			case 0:
				// worker process
				$cpid = posix_getpid();
				echo("Create worker process {$cpid} success" . PHP_EOL);

				// check worker pipe file
				while (! file_exists("/tmp/naruto.pipe{$cpid}")) {
					sleep(self::LOOP_SLEEP_TIME);
				}

				// open worker pipe
				do {
					$workerPipe = fopen("/tmp/naruto.pipe{$cpid}", 'r');
					sleep(self::LOOP_SLEEP_TIME);
				} while (! $workerPipe);

				while (true) {

					/* accept signal from master process */
					$signal = fread($workerPipe, 1024);
					if (! empty($signal)) {
						error_log($signal . PHP_EOL, 3, '/tmp/debug.log');
						$signal = explode('|', $signal);
						if (count($signal) !== 2) {
							// exception
							continue;
						}
						switch ($signal[0]) {
							// signal flag
							case 'signal':
							$this->workerAcceptedSignal = 'reload';
							break;

							default:

							break;
						}
					}

					// execute business logic
					sleep(10);

					// signal handle event
					if ($this->signalHandle()) {
						break;
					}

					// precent cpu usage rate reach 100%
					sleep(self::LOOP_SLEEP_TIME);
				}
				
				// fcloise($workerPipe);
				exit;
				break;
	
			default:
				$this->workerPids[] = $pid;
				// master process
				$ppid = posix_getpid();
				echo("This is the master process {$ppid}" . PHP_EOL);
				// fork
				// pcntl_wait($status);
				break;
		}
	}

	public function signalHandle()
	{
		switch ($this->workerAcceptedSignal) {
			case 'reload':
				
				// write a reload success signal for master pipe
				

				// exit current worker
				return true;
				break;
			
			default:
				// exception

				break;
		}
	}
	
	public function execFork()
	{
		foreach (range(1, $this->forkNum) as $v) {
			$this->fork();
		}

		// make worker pipe
		$this->makeWorkerPipe();

		// hangup
		$this->hangup();
	}

	public function hangup()
	{
		while (true) {
			// dispatch signal handler
			pcntl_signal_dispatch();

			// wait worker process
			foreach ($this->workerPids as $k => $v) {
				$res = pcntl_waitpid($v, $status, WNOHANG);
				if ($res == -1 || $res > 0) {
					unset($this->workerPids[$k]);
				}
			}

			sleep(self::LOOP_SLEEP_TIME);
		}
	}
}
