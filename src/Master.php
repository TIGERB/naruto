<?php
namespace Naruto;

use Naruto\Manager;
use Naruto\Process;
use Naruto\ProcessException;
use Closure;

class Master extends Process
{
	public function __construct()
	{
		$this->type = 'master';
		parent::__construct();
		
		// log
		ProcessException::info([
			'msg' => [
				'from'  => 'master',
				'extra' => 'master instance create'
			]
		]);

		// make pipe
		$this->pipeMake();
		
	}

	public function hangup(Closure $closure)
	{
		# do nothing...
	}
}
