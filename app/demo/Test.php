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

namespace App\Demo;

use Naruto\ProcessException;

class Test
{
    public function businessLogic()
    {
        $time = microtime(true);
        ProcessException::debug([
            'msg' => [
                'microtime' => $time,
                'debug' 	=> 'this is the business logic'
            ]
        ]);
        // mock business logic
        usleep(1000000);
    }
}
