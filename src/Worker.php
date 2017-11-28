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
	 */
	public function __construct($msg = '', $pid = 0, $type = 'worker')
	{
		$this->type = $type;
		$this->pid = $pid? : $this->pid;
		
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
			
			// handle pipe msg
			if ($this->signal = $this->pipeRead()) {
				$this->dispatchSig();
			}

			// precent cpu usage rate reach 100%
			sleep(self::LOOP_SLEEP_TIME);
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
