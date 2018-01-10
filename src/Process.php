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

use Naruto\ProcessException;
use Closure;

/**
 * process abstract class
 */
abstract class Process
{
	/**
	 * current process type such as master and worker
	 *
	 * @var string
	 */
	public $type = '';

	/**
	 * process id
	 *
	 * @var int
	 */
	public $pid = '';

	/**
	 * pipe name
	 *
	 * @var string
	 */
	protected $pipeName = '';

	/**
	 * pipe mode
	 *
	 * @var integer
	 */
	protected $pipeMode = 0777;

	/**
	 * pipe name prefix
	 *
	 * @var string
	 */
	protected $pipeNamePrefix = 'naruto.pipe';

	/**
	 * the folder for pipe file store
	 *
	 * @var string
	 */
	protected $pipeDir = '/tmp/';
	
	/**
	 * pipe file path
	 *
	 * @var string
	 */
	protected $pipePath = '';

	/**
	 * the byte size read from pipe
	 *
	 * @var integer
	 */
	protected $readPipeType = 1024;

	/**
	 * worker process exit flag
	 *
	 * @var boolean
	 */
	protected $workerExitFlag = false;

	/**
	 * signal
	 *
	 * @var string
	 */
	protected $signal = '';

	/**
	 * hangup sleep time unit:microsecond /μs
	 * 
	 * default 200000μs
	 *
	 * @var int
	 */
	protected static $hangupLoopMicrotime = 200000;

	/**
	 * max execute times
	 * 
	 * default 5*60*60*24
	 *
	 * @var int
	 */
	protected static $maxExecuteTimes = 5*60*60*24;

	/**
	 * current execute times
	 * 
	 * default 0
	 *
	 * @var int
	 */
	protected static $currentExecuteTimes = 0;

	/**
	 * construct function
	 * 
	 * @param array $config config
	 */
	public function __construct($config = [])
	{
		if (empty($this->pid)) {
			$this->pid = posix_getpid();
		}
		$this->pipeName = $this->pipeNamePrefix . $this->pid;
		$this->pipePath = $this->pipeDir . $this->pipeName;

		// set hangup sleep time
		self::$hangupLoopMicrotime = isset($config['hangup_loop_microtime'])? 
		$config['hangup_loop_microtime']: self::$hangupLoopMicrotime;
	}

	/**
	 * hungup abstract funtion
	 *
	 * @param Closure $closure
	 * @return void
	 */
	abstract protected function hangup(Closure $closure);

	/**
	 * create pipe
	 *
	 * @return void
	 */
	public function pipeMake()
	{
		if (! file_exists($this->pipePath)) {
			if (! posix_mkfifo($this->pipePath, $this->pipeMode)) {
				ProcessException::error([
					'msg' => [
						'from'  => $this->type,
						'extra' => "pipe make {$this->pipePath}",
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

	/**
	 * write msg to the pipe
	 *
	 * @return void
	 */
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
					'extra' => "pipe write {$this->pipePath}",
					'signal'=> $signal,
					'res'   => $res
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

	/**
	 * read msg from the pipe
	 *
	 * @return void
	 */
	public function pipeRead()
	{
		// check pipe
		while (! file_exists($this->pipePath)) {
			usleep(self::$hangupLoopMicrotime);
		}

		// open pipe
		do {
			// fopen() will block if the file to be opened is a fifo. This is true whether it's opened in "r" or "w" mode.  (See man 7 fifo: this is the correct, default behaviour; although Linux supports non-blocking fopen() of a fifo, PHP doesn't).
			$workerPipe = fopen($this->pipePath, 'r+'); // The "r+" allows fopen to return immediately regardless of external  writer channel. 
			usleep(self::$hangupLoopMicrotime);
		} while (! $workerPipe);

		// set pipe switch a non blocking stream
		stream_set_blocking($workerPipe, false);

		// read pipe
		if ($msg = fread($workerPipe, $this->readPipeType)) {
			ProcessException::info([
				'msg' => [
					'from'  => $this->type,
					'extra' => "pipe read {$this->pipePath}",
					'signal'=> $msg,
				]
			]);
		}
		return $msg;
	}

	/**
	 * clear pipe file
	 *
	 * @return void
	 */
	public function clearPipe()
	{
		$msg = [
			'msg' => [
				'from'  => $this->type,
				'extra' => "pipe clear {$this->pipePath}"
			]
		];
		ProcessException::info($msg);
		if (! unlink($this->pipePath)) {
			ProcessException::error($msg);
			return false;
		}
		shell_exec("rm -f {$this->pipePath}");
		return true;
	}

	/**
	 * stop this process
	 *
	 * @return void
	 */
	public function stop()
	{
		$msg = [
			'msg' => [
				'from'  => $this->type,
				'extra' => "{$this->pid} stop"
			]
		];
		ProcessException::info($msg);
		$this->clearPipe();
		if (! posix_kill($this->pid, SIGKILL)) {
			ProcessException::error($msg);
			return false;
		}
		return true;
	}

	/**
	 * set this process name
	 *
	 * @return void
	 */
	protected function setProcessName()
	{
		// cli_set_process_title('naruto master');
	}
}
