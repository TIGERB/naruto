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

use Naruto\Manager;
use Naruto\Process;
use Naruto\ProcessException;
use Closure;

/**
 * daemon process class
 */
class Daemon extends Process
{
	/**
	 * construct function
	 */
	public function __construct()
	{
		$this->type = 'daemon';
		
		ProcessException::info([
			'msg' => [
				'from'  => 'master',
				'extra' => 'daemon instance create'
			]
		]);
    }

	/**
	 * check function
	 *
     * check worker process num
     * 
	 * @param Manager $manager
	 * @return void
	 */
	public function check(Manager $manager)
	{
		if (! empty($manager->waitSignalProcessPool['signal'])) {
            return;
        }

        // get num now
		$num = shell_exec("pstree -p {$manager->master->pid} | grep php | wc -l") - 3;
		
        // check num
        $diff = $manager->startNum - $num;
        if ($diff > 0) {
            // start worker
            $manager->execFork($diff);   
        }
	}

	/**
	 * hangup function
	 *
	 * @param Closure $closure
	 * @return void
	 */
	public function hangup(Closure $closure)
	{
		# do nothing...
	}
}
