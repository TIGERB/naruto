<?php
namespace Naruto;

use Naruto\ProcessException;

abstract class Process
{
	protected $type = '';
	public	  $pid = '';
	protected $pipeName = '';
	protected $pipeMode = 0777;
	protected $pipeNamePrefix = 'naruto.pipe';
	protected $pipeDir = '/tmp/';
	protected $pipePath = '';
	protected $readPipeType = 1024;

	const LOOP_SLEEP_TIME = 1;

	public function __construct()
	{
		if (empty($this->pid)) {
			$this->pid = posix_getpid();
		}
		$this->pipeName = $this->pipeNamePrefix . $this->pid;
		$this->pipePath = $this->pipeDir . $this->pipeName;
	}

	abstract protected function hangup();

	public function pipeMake()
	{
		if (! file_exists($this->pipePath)) {
			if (! posix_mkfifo($this->pipePath, $this->pipeMode)) {
				ProcessException::error("{$this->type} | {$this->pid} | pipe | make | {$this->pipePath}");
				exit;
			}
			chmod($this->pipePath, $this->pipeMode);
			ProcessException::info("{$this->type} | {$this->pid} | pipe | make | {$this->pipePath}");
		}
	}

	public function pipeWrite($signal = '')
	{
		$pipe = fopen($this->pipePath, 'w');
		if (! $pipe) {
			ProcessException::error("{$this->type} | {$this->pid} | pipe | open | {$this->pipePath}");
			return;
		}

		ProcessException::info("{$this->type} | {$this->pid} | pipe | open | {$this->pipePath}");
		
		$res = fwrite($pipe, $signal);
		if (! $res) {
			ProcessException::error("{$this->type} | {$this->pid} | pipe | write | {$this->pipePath} | {$signal}");
			return;
		}

		ProcessException::info("{$this->type} | {$this->pid} | pipe | write | {$this->pipePath} | {$signal}");

		if (! fclose($pipe)) {
			ProcessException::error("{$this->type} | {$this->pid} | pipe | close | {$this->pipePath}");
			return;
		}

		ProcessException::info("{$this->type} | {$this->pid} | pipe | close | {$this->pipePath}");
	}

	public function pipeRead()
	{
		// check pipe
		while (! file_exists($this->pipePath)) {
			sleep(self::LOOP_SLEEP_TIME);
		}

		// open pipe
		do {
			$workerPipe = fopen($this->pipePath, 'r');
			sleep(self::LOOP_SLEEP_TIME);
		} while (! $workerPipe);

		// read pipe
		if ($msg = fread($workerPipe, $this->readPipeType)) {
			ProcessException::info("{$this->type} | {$this->pid} | pipe | read | {$this->pipePath} | {$msg}");
		}
		return $msg;
	}

}
