<?php
namespace Naruto;

use Naruto\Process;
use Naruto\ProcessException;
use Closure;

class Worker extends Process
{
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

	public function hangup(Closure $closure)
	{
		while (true) {
			// business logic
			$closure($this);
			
			// handle pipe msg
			if ($msg = $this->pipeRead()) {
				$this->dispatchSig($msg);
			}

			// precent cpu usage rate reach 100%
			sleep(self::LOOP_SLEEP_TIME);
		}
	}

	private function dispatchSig($signal = '')
	{
		switch ($signal) {
			case 'reload':
			ProcessException::info([
				'msg' => [
					'from'   => $this->type,
					'signal' => $signal,
					'extra'  => 'worker process exit'
				]
			]);
			$this->processExit();
			exit;
			break;

			default:

			break;
		}
	}
}
