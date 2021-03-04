<?php

declare(strict_types=1);

namespace FreeSwitch;

use FreeSwitch\Connection\FreeSwitchConnection;
use FreeSWITCH\Exception\InvalidFreeSwitchConnectionException;
use FreeSWITCH\Tool\SwContext;

/**
 * Class FreeSwitch
 * @package App\FreeSwitch\src
 * @mixin FreeSwitchConnection
 */
class FreeSwitch
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $config;

    public function __construct(string $name = 'default', array $config = [])
    {

        $this->name = $name;

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
        $hasContextConnection = SwContext::has($this->getContextKey());
        /**
         * @var FreeSwitchConnection
         */
        $connection = $this->getConnection($hasContextConnection);

        $connection = $connection->getConnection();

        // Execute the command with the arguments.
        return $connection->{$name}(...$arguments);
    }

    /**
     * @param $hasContextConnection
     * @return FreeSwitchConnection
     * @throws InvalidFreeSwitchConnectionException
     */
    private function getConnection($hasContextConnection): FreeSwitchConnection
    {
        $connection = null;
        if ($hasContextConnection) {
            $connection = SwContext::get($this->getContextKey());
        }
        if (!($connection instanceof FreeSwitchConnection)) {
            $connection = SwContext::set($this->getContextKey(), new FreeSwitchConnection($this->config));
        }
        if (!$connection instanceof FreeSwitchConnection) {
            throw new InvalidFreeSwitchConnectionException('The connection is not a valid FreeSwitchConnection.');
        }
        return $connection;
    }

    /**
     * The key to identify the connection object in coroutine context.
     */
    private function getContextKey(): string
    {
        return 'fs.connection';
    }
}