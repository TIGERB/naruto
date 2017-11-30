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

// register autoload
spl_autoload_register('autoload');
function autoload($class)
{
	$path = str_replace('\\', '/', $class);
	$path = str_replace('Naruto', 'src', $path);
  	require __DIR__ . '/' . $path . '.php';
}

/* -----------------------demo------------------- */

use Naruto\Manager;
use Naruto\Process;
use Naruto\ProcessException;
use Exception as Ex;

/**
 * example
 * 
 * $config = [
 * 		'passwd' => '123456', // unix user passwd
 * 		'worker_num' => 5, // worker start number
 * 		'hangup_loop_microtime' => 200000, // master&worker hangup loop microtime unit/μs
 * 		'pipe_dir' => '/tmp/', // the directory name of the process's pipe will be storaged
 * ]
 * new Manager($config, $closure)
 */
try {
	$instance = new Manager([
		'passwd' 	 => 'dvd',
		'worker_num' => 5,
		// 'pipe_dir'   => '/tmp/naruto/'
		// 'hangup_loop_microtime' => 200000
		], function (Process $worker) {
			$time = microtime(true);
			ProcessException::debug([
				'msg' => [
					'microtime' => $time,
					'debug' 	=> 'this is the business logic'
				]
			]);
			// mock business logic
			usleep(10000000);
		}
	);
} catch (Ex $e) {
	ProcessException::error([
		'msg' => [
			'msg'  => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
		]
	]);
}
