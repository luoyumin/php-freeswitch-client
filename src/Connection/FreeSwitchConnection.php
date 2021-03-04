<?php

declare(strict_types=1);

namespace FreeSwitch\Connection;

use FreeSwitch\Event\EventHandleInterface;
use FreeSWITCH\Exception\InvalidFreeSwitchConnectionException;
use FreeSWITCH\Tool\SwContext;
use Swoole\Coroutine\Client;

/**
 * Class FreeSwitchConnection
 * @package App\FreeSwitch\src
 */
class FreeSwitchConnection
{
    /**
     * @var EventHandleInterface
     */
    protected $event_handle_object;

    use Api;

    /**
     * @var Client
     */
    protected $connection;

    /**
     * 配置
     * @var array
     */
    protected $config = [
        'host' => 'localhost',
        'port' => '8022',
        'password' => 'dgg@1234.',
        'timeout' => 0.5
    ];

    /**
     * FreeSwitchConnection constructor.
     * @param array $config
     * @throws InvalidFreeSwitchConnectionException
     */
    public function __construct(array $config = [])
    {

        $this->config = array_replace_recursive($this->config, $config);

        $this->reconnect();
    }

    /**
     * @return $this
     * @throws InvalidFreeSwitchConnectionException
     */
    public function getConnection()
    {
        return $this->getActiveConnection();
    }

    /**
     * @return bool
     */
    public function check(): bool
    {
        return $this->connection->isConnected();
    }

    /**
     * 发布
     */
    public function release(): void
    {
        // TODO: Implement release() method.
    }

    /**
     * @return $this
     * @throws InvalidFreeSwitchConnectionException
     */
    public function getActiveConnection()
    {
        if ($this->check()) return $this;

        if (!$this->reconnect()) throw new InvalidFreeSwitchConnectionException('FreeSWITCH connection failed.', $this->getErrCode());

        return $this;
    }

    /**
     * 连接fs
     * @return bool
     * @throws InvalidFreeSwitchConnectionException
     */
    public function reconnect(): bool
    {
        $host = $this->config['host'];
        $port = (int)$this->config['port'];
        $timeout = $this->config['timeout'];
        $password = $this->config['password'];

        $connection = $this->createFreeSwitch($host, $port, $timeout);

        if ($connection instanceof Client && isset($password) && $password !== '') {
            $auth_str = sprintf("auth %s\r\n\r\n", $password);
            if (strlen($auth_str) != $connection->send($auth_str)) return false;
            while ($recv = $connection->recv()) {
                if (strpos((string)$recv, 'disconnect') !== false) return false;
            }
        }

        $this->connection = $connection;

        $this->reEvent(); // 重新监听事件

        $this->reFilterUuid(); // 重新过滤UUID

        return true;
    }

    /**
     * 重新监听事件
     */
    public function reEvent()
    {
        if (SwContext::has('events')) {
            foreach (SwContext::get('events') as $event => $sorts) {
                $this->event($sorts, $event);
            }
        }
    }

    /**
     * 重新过滤UUID
     */
    public function reFilterUuid()
    {
        if (SwContext::has('filter_unique_ids')) {
            foreach (SwContext::get('filter_unique_ids') as $filter_unique_id) {
                $this->filterUuid($filter_unique_id);
            }
        }
    }

    /**
     * @return int
     */
    public function getErrCode()
    {
        return $this->connection->errCode;
    }

    /**
     * @return string
     */
    public function getErrMsg()
    {
        return $this->connection->errMsg;
    }

    /**
     * 创建FreeSWITCH Tcp连接
     * @param $host
     * @param $port
     * @param $timeout
     * @return Client
     * @throws InvalidFreeSwitchConnectionException
     */
    protected function createFreeSwitch($host, $port, $timeout)
    {
        $connection = new Client(SWOOLE_SOCK_TCP);

        if (!$connection->connect($host, $port, $timeout)) {

            $connection->close();

            throw new InvalidFreeSwitchConnectionException('FreeSWITCH connection failed.', $this->getErrCode());
        }

        return $connection;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        unset($this->connection);

        return true;
    }

    /**
     * @param EventHandleInterface $eventHandle
     */
    public function setEventHandleObject(EventHandleInterface $eventHandle)
    {
        $this->event_handle_object = $eventHandle;
    }
}