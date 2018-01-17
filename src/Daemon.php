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
		$num = intval(shell_exec("pstree -p {$manager->master->pid} | grep php | wc -l"));
		if ($manager->os === 'Darwin') {
			$num -= 3;
			$num = $num < 0? 0: $num;
		}

		// check num
		$diff = $manager->startNum - $num;
		$diff = $diff < 0? 0: $diff;
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
