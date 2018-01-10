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

use Naruto\Process;
use Naruto\ProcessException;
use Closure;

/**
 * work class
 */
class Worker extends Process
{
	/**
	 * construct function
	 *
	 * @param string $msg
	 * @param integer $pid
	 * @param string $type
	 * @param array $config config [
	 * 	'pid' => 1212,
	 * 	'type'=> 'worker',
	 *  'pipe_dir' => '/tmp/'
	 * ]
	 */
	public function __construct($config = [])
	{
		$this->type    = isset($config['type'])? $config['type']: 'worker';
		$this->pid     = isset($config['pid'])? $config['pid']: $this->pid;
		$this->pipeDir = isset($config['pipe_dir']) && ! empty($config['pipe_dir'])
		? $config['pipe_dir']: $this->pipeDir;
		
		// log
		ProcessException::info([
			'msg' => [
				'from'  => $this->type,
				'extra' => 'worker instance create'
			]
		]);
		parent::__construct();
	}

	/**
	 * the work hungup function
	 *
	 * @param Closure $closure
	 * @return void
	 */
	public function hangup(Closure $closure)
	{
		while (true) {
			// business logic
			$closure($this);

			// check exit flag
			if ($this->workerExitFlag) {
				$this->workerExit();
			}

			// check max execute time
			if (self::$currentExecuteTimes >= self::$maxExecuteTimes) {
				$this->workerExit();
			}

			// handle pipe msg
			if ($this->signal = $this->pipeRead()) {
				$this->dispatchSig();
			}

			// increment 1
			++self::$currentExecuteTimes;

			// precent cpu usage rate reach 100%
			usleep(self::$hangupLoopMicrotime);
		}
	}

	/**
	 * dispatch signal for the worker process
	 *
	 * @return void
	 */
	private function dispatchSig()
	{
		switch ($this->signal) {
			// reload
			case 'reload':
			$this->workerExitFlag = true;
			break;
			
			// stop
			case 'stop':
			$this->workerExitFlag = true;
			break;

			default:

			break;
		}
	}

	/**
	 * exit worker
	 *
	 * @return void
	 */
	private function workerExit()
	{
		ProcessException::info([
			'msg' => [
				'from'   => $this->type,
				'signal' => $this->signal,
				'extra'  => 'worker process exit'
			]
		]);
		$this->clearPipe();
		exit;
	}
}
