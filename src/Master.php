<?php
namespace Naruto;

use Naruto\Manager;
use Naruto\Process;
use Naruto\ProcessException;

class Master extends Process
{
	public function __construct()
	{
		parent::__construct();
		$this->type = 'master';

		// log
		ProcessException::info("master | $this->pid | master instance create");

		// make pipe
		$this->pipeMake();
	}

	public function hangup()
	{
		while (true) {
			// dispatch signal for the handlers
			pcntl_signal_dispatch();

			// prevent the child process become a zombie process
			pcntl_wait($status);

			// precent cpu usage rate reach 100%
			sleep(self::LOOP_SLEEP_TIME);
		}
	}
}
