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

$instance = new Manager([
	'passwd' 	 => 'dvd',
	'worker_num' => 10,
	], function () {
		echo 'this is business logic' . PHP_EOL;
		// mock business logic
		sleep(10);
	}
);
