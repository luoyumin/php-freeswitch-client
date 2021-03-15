<?php

declare(strict_types=1);

namespace FreeSwitch\Connection;

use FreeSwitch\Exception\InvalidFreeSwitchConnectionException;
use FreeSwitch\Tool\SwContext;
use Swoole\Coroutine\Socket;

/**
 * Class FreeSwitchConnection
 * @package App\FreeSwitch\src
 */
class FreeSwitchConnection
{
    use Api;

    /**
     * @var Socket
     */
    protected $socket;

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
        if (isset($this->socket) && $this->socket instanceof Socket) {
            return $this->socket->checkLiveness();
        }
        return false;
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

        if ($connection instanceof Socket && isset($password) && $password !== '') {
            $auth_str = sprintf("auth %s\r\n\r\n", $password);
            if (strlen($auth_str) != $connection->send($auth_str)) return false;
            while ($packet = $connection->recvPacket()) {
                if (strpos((string)$packet, '+OK') !== false) break;
            }
        }

        $this->socket = $connection;

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
        return $this->socket->errCode;
    }

    /**
     * @return string
     */
    public function getErrMsg()
    {
        return $this->socket->errMsg;
    }

    /**
     * 创建FreeSWITCH Tcp连接
     * @param $host
     * @param $port
     * @param $timeout
     * @return Socket
     * @throws InvalidFreeSwitchConnectionException
     */
    protected function createFreeSwitch($host, $port, $timeout)
    {
        $socket = new Socket(AF_INET, SOCK_STREAM, SOL_TCP);

        $socket->setProtocol([
            'open_eof_check' => true, // 验证分包
            'open_eof_split' => true, // 开启分包
            'package_eof' => "\n\n", // 包分隔符
            'package_max_length' => 1024 * 1024 * 2,
        ]);

        if (!$socket->connect($host, $port, $timeout)) {

            $socket->close();

            throw new InvalidFreeSwitchConnectionException('FreeSWITCH connection failed.', $this->getErrCode());
        }

        return $socket;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {

        $this->socket instanceof Socket && $this->socket->close();

        unset($this->socket);

        return true;
    }
}