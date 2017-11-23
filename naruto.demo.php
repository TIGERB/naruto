<?php

// 注册自加载
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
	'worker_num' => 3,
	], function () {
		var_dump('this is business logic');
		sleep(10);
	}
);
