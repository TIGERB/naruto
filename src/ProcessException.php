<?php
namespace Naruto;

use Exception;

class ProcessException extends Exception
{
	private static $logPath = '/tmp/naruto.process.manager.log';

	public static function info($msg = '', $path = '')
	{
        self::$logPath = $path? : self::$logPath;
        $msg = self::decorate('info', $msg);
        error_log($msg, 3, self::$logPath);
    }
    
    public static function error($msg = '', $path = '')
    {
    	self::$logPath = $path? : self::$logPath;
        $msg = self::decorate('error', $msg);
        error_log($msg, 3, self::$logPath);
    }

    private static function decorate($rank = 'info', $msg = '')
	{
        $time = date('Y-m-d H:i:s', time());
		$msg = "{$time} | {$rank} | {$msg}" . PHP_EOL;
		echo $msg;
        return $msg;
	}
}
