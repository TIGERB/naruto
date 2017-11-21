<?php

namespace Naruto;

class Worker
{
	private $pid  = '';
	private $pipe = '';

	const WORK_PIPE_PATH = '/tmp/';
	const WORK_PIPE_NAME = 'naruto.pipe';
	const LOOP_SLEEP_TIME = 1;

	public function __constuct()
	{
		$this->pid = posix_getpid();
	}

	public function openPipe()
	{
		// check worker pipe file
		while (! file_exists(self::WORK_PIPE_PATH . self::WORK_PIPE_NAME . $this->pid)) {
			sleep(self::LOOP_SLEEP_TIME);
		}

		// open worker pipe
		do {
			$this->pipe = fopen(self::WORK_PIPE_PATH . self::WORK_PIPE_NAME . $this->pid, 'r');
			sleep(self::LOOP_SLEEP_TIME);
		} while (! $this->pipe);
	}

	public function hangup()
	{
		while (true) {

		}
	}

	public function readPipe()
	{
		$msg = fread($this->pipe, 1024);
		if (empty($msg)) {
			return '';
		}
	}

	
}
