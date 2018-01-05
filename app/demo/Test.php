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
use Medoo\Medoo;

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

    public function dbTest()
    {
        $db = new Medoo([
            'database_type' => 'mysql',
            'database_name' => 'naruto',
            'server'        => 'localhost',
            'username'      => 'naruto',
            'password'      => 'naruto'
        ]);

        $db->insert('account', [
            'username'  => 'test',
            'email'     => 'test@test.com',
            'password'  => 'test'
        ]);
    }
}
