<?php
namespace Naruto;

use Naruto\ProcessException;
use Closure;

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

	abstract protected function hangup(Closure $closure);

	public function pipeMake()
	{
		if (! file_exists($this->pipePath)) {
			if (! posix_mkfifo($this->pipePath, $this->pipeMode)) {
				ProcessException::error([
					'msg' => [
						'from'  => $this->type,
						'extra' => "pipe make {$this->pipePath}"
					]
				]);
				exit;
			}
			chmod($this->pipePath, $this->pipeMode);
			ProcessException::info([
				'msg' => [
					'from'  => $this->type,
					'extra' => "pipe make {$this->pipePath}"
				]
			]);
		}
	}

	public function pipeWrite($signal = '')
	{
		$pipe = fopen($this->pipePath, 'w');
		if (! $pipe) {
			ProcessException::error([
				'msg' => [
					'from'  => $this->type,
					'extra' => "pipe open {$this->pipePath}"
				]
			]);
			return;
		}

		ProcessException::info([
			'msg' => [
					'from'  => $this->type,
					'extra' => "pipe open {$this->pipePath}"
				]
		]);
		
		$res = fwrite($pipe, $signal);
		if (! $res) {
			ProcessException::error([
				'msg' => [
					'from'  => $this->type,
					'extra' => "pipe write {$this->pipePath}",
					'signal'=> $signal,
					'res'   => $res
				]
			]);
			return;
		}

		ProcessException::info([
			'msg' => [
					'from'  => $this->type,
					'extra' => "pipe write {$this->pipePath}"
				]
		]);

		if (! fclose($pipe)) {
			ProcessException::error([
				'msg' => [
					'from'  => $this->type,
					'extra' => "pipe close {$this->pipePath}"
				]
			]);
			return;
		}

		ProcessException::info([
			'msg' => [
					'from'  => $this->type,
					'extra' => "pipe close {$this->pipePath}"
				]
		]);
	}

	public function pipeRead()
	{
		// check pipe
		while (! file_exists($this->pipePath)) {
			sleep(self::LOOP_SLEEP_TIME);
		}

		// open pipe
		do {
			$workerPipe = fopen($this->pipePath, 'r+'); // The "r+" allows fopen to return immediately regardless of external  writer channel. 
			sleep(self::LOOP_SLEEP_TIME);
		} while (! $workerPipe);

		// set pipe switch a non blocking stream
		stream_set_blocking($workerPipe, false);

		// read pipe
		if ($msg = fread($workerPipe, $this->readPipeType)) {
			ProcessException::info([
				'msg' => [
					'from'  => $this->type,
					'extra' => "pipe read {$this->pipePath}"
				]
			]);
		}
		return $msg;
	}

	protected function processExit()
	{
		unlink($this->pipePath);
	}
}
