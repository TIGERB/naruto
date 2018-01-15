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

use Exception;

/**
 * process exception class
 */
class ProcessException extends Exception
{
	/**
	 * log method support
	 *
	 * @var array
	 */
	private static $methodSupport = ['info', 'error', 'debug'];

	/**
	 * the log path
	 *
	 * @var string
	 */
	private static $logPath = '/tmp/naruto';
	
	/**
	 * the magic __callStatics function
	 *
	 * @param string $method
	 * @param array $data
	 * @return void
	 */
	public static function __callStatic($method = '', $data = [])
	{
		$data = $data[0];
		if (! in_array($method, self::$methodSupport)) {
			throw new Exception('log method not support', 500);
		}
		self::$logPath = (isset($data['path'])? $data['path']: '')? : self::$logPath;
        $msg = self::decorate($method, $data['msg']);
		error_log($msg, 3, self::$logPath . '.' . date('Y-m-d', time()) . '.log');
		if ($method === 'error') {
			exit;
		}
	}

	/**
	 * decorate log msg
	 *
	 * @param string $rank
	 * @param array $msg
	 * @return void
	 */
	private static function decorate($rank = 'info', $msg = [])
	{
		$time        = date('Y-m-d H: i: s', time());
		$pid         = posix_getpid();
		$memoryUsage = round(memory_get_usage()/1024, 2) . ' kb';
		switch ($rank) {
			case 'info':
				$rank = "\033[36m{$rank} \033[0m";
			break;
			case 'error':
				$rank = "\033[31m{$rank}\033[0m";
			break;
			case 'debug':
				$rank = "\033[32m{$rank}\033[0m";
			break;

			default:
			
			break;
		}
		$default = [
			$time,
			$rank,
			$pid,
			$memoryUsage
		];

		if (! isset($msg['from']) || empty($msg['from'])) {
			$default[] = 'worker';
			unset($msg['from']);
		}

		$msg  = array_merge($default, $msg);
		$tmp  = '';
		foreach ($msg as $k => $v) {
			if ($k === 0) {
				$tmp = "{$v}";
				continue;
			}
			$tmp .= " | {$v}";
		}
		$tmp .= PHP_EOL;
		echo $tmp;
        return $tmp;
	}
}
