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

$instance = new Manager([
	'passwd' 	 => 'dvd',
	'worker_num' => 3,
	], function (Process $worker) {
		$time = microtime(true);
		ProcessException::debug([
			'msg' => [
				'microtime' => $time,
				'debug' 	=> 'this is business logic'
			]
		]);
		// mock business logic
		sleep(10);
	}
);
