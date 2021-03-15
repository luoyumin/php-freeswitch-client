<?php

declare(strict_types=1);

namespace FreeSwitch;

use FreeSwitch\Connection\FreeSwitchConnection;
use FreeSwitch\Exception\InvalidFreeSwitchConnectionException;

/**
 * Class FreeSwitch
 * @package App\FreeSwitch\src
 * @mixin FreeSwitchConnection
 */
class FreeSwitch
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var FreeSwitchConnection
     */
    protected $connection;

    public function __construct(array $config = [])
    {

        $this->config = $config;

    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws InvalidFreeSwitchConnectionException
     */
    public function __call($name, $arguments)
    {
        /**
         * @var FreeSwitchConnection
         */
        $connection = $this->getConnection();

        $connection = $connection->getConnection();

        // Execute the command with the arguments.
        return $connection->{$name}(...$arguments);
    }

    /**
     * @return FreeSwitchConnection
     * @throws InvalidFreeSwitchConnectionException
     */
    private function getConnection(): FreeSwitchConnection
    {
        if (!($this->connection instanceof FreeSwitchConnection)) {
            $this->connection = new FreeSwitchConnection($this->config);
        }
        if (!$this->connection instanceof FreeSwitchConnection) {
            throw new InvalidFreeSwitchConnectionException('The connection is not a valid FreeSwitchConnection.');
        }
        return $this->connection;
    }

    /**
     * 关闭链接
     */
    public function close()
    {
        if ($this->connection instanceof FreeSwitchConnection && $this->connection->check()) {
            $this->connection->close();
        }

        $this->connection = null;
    }
}