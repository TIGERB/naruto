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

$instance = new Manager([
	'passwd' 	 => 'dvd',
	'worker_num' => 3,
	], function (Process $worker) {
		$time = microtime(true);
		echo "[worker:{$worker->pid} {$time}] this is business logic" . PHP_EOL;
		// mock business logic
		sleep(10);
	}
);
