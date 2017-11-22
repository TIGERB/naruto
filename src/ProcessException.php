<?php
namespace Naruto;

use Exception;

class ProcessException extends Exception
{
	private static $methodSupport = ['info', 'error', 'debug'];
	private static $logPath = '/tmp/naruto.process.manager.log';
	
	public static function __callStatic($method = '', $data = [])
	{
		$data = $data[0];
		if (! in_array($method, self::$methodSupport)) {
			throw new Exception('log method not support', 500);
		}
		self::$logPath = ($data['path']?? '')? : self::$logPath;
        $msg = self::decorate($method, $data['msg']);
        error_log($msg, 3, self::$logPath);
	}

    private static function decorate($rank = 'info', $msg = [])
	{
		$time = date('Y-m-d H:i:s', time());
		$pid  = posix_getpid(); 
		$default = [
			$time,
			$rank,
			$pid,
		];

		if (! isset($msg['from']) || empty($msg['from'])) {
			$default[] = 'master';
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
