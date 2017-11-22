<?php
namespace Naruto;

use Naruto\Manager;
use Naruto\Process;
use Naruto\ProcessException;

class Master extends Process
{
	public function __construct()
	{
		$this->type = 'master';
		parent::__construct();
		
		// log
		ProcessException::info("master | $this->pid | master instance create");

		// make pipe
		$this->pipeMake();
		
	}

	public function hangup()
	{
		# do nothing...
	}
}
