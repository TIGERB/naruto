<?php

// 注册自加载
spl_autoload_register('autoload');
function autoload($class)
{
	$path = str_replace('\\', '/', strtolower($class));
	$path = str_replace('naruto', 'src', $path);
  	require __DIR__ . '/' . $path . '.php';
}

/* -----------------------demo------------------- */

use Naruto\Manager;

$instance = new Manager(3, 'dvd');
$instance->execFork();
