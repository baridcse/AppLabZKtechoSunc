

<?php

namespace App\Services;

use rats\Zkteco\Lib\ZKTeco;

class ZktecoService
{
    protected $ip;
    protected $port;

    public function __construct($ip = '192.168.1.253', $port = 4370)
    {
        $this->ip   = $ip;
        $this->port = $port;
    }

    protected function connect()
    {
        $zk = new ZKTeco($this->ip, $this->port);
        if (! $zk->connect()) {
            throw new \Exception("Unable to connect to device {$this->ip}:{$this->port}");
        }
        return $zk;
    }

    /**
     * Get all attendance logs
     */
    public function getAttendance()
    {
        $zk = $this->connect();

        $attendance = $zk->getAttendance();   // returns array
        $zk->disconnect();

        return $attendance;
    }

    /**
     * Get users from device
     */
    public function getUsers()
    {
        $zk = $this->connect();

        $users = $zk->getUser();   // or getUserInfo() in some versions
        $zk->disconnect();

        return $users;
    }

    /**
     * Clear attendance logs
     */
    public function clearAttendance()
    {
        $zk = $this->connect();

        $result = $zk->clearAttendance();
        $zk->disconnect();

        return $result;
    }

}
