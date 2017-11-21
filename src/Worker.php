<?php
namespace Naruto;

use Naruto\Process;
use Naruto\ProcessException;

class Worker extends Process
{
	public function __construct($msg = '', $pid = 0)
	{
		parent::__construct();
		$this->type = 'worker';
		$this->pid = $pid? : $this->pid;
		
		// log
		$msg = $msg? : "worker | $this->pid | worker instance create ";
		ProcessException::info($msg);
	}

	public function hangup()
	{
		while (true) {
			// handle pipe msg
			if ($msg = $this->pipeRead()) {

			}


			// precent cpu usage rate reach 100%
			sleep(self::LOOP_SLEEP_TIME);
		}
	}
}
