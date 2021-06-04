<?php

declare(strict_types=1);

namespace FreeSwitch;

use Exception;
use FreeSwitch\Connection\FreeSwitchConnection;
use FreeSwitch\Event\EventHandleInterface;
use FreeSwitch\Event\EventNameConstants;
use FreeSwitch\Exception\InvalidFreeSwitchConnectionException;
use FreeSWITCH\Exception\InvalidGatewayException;

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
     * 拨号字符串
     * @var string
     */
    protected $dial_str = 'originate {origination_uuid=__ORIGINATION_UUID__,origination_caller_id_number=__ORIGINATION_CALLER_ID_NUMBER__,origination_caller_id_name=__ORIGINATION_CALLER_ID_NAME__,effective_caller_id_number=__EFFECTIVE_CALLER_ID_NUMBER__,effective_caller_id_name=__EFFECTIVE_CALLER_ID_NAME__,customer_caller=__CALLEE__}user/__CALLER__ __GATEWAY_NAME____PREFIX____CALLEE_____CALLEEUUID__ XML default';

    /**
     * @var string
     */
    protected $origination_uuid;

    /**
     * 通话唯一ID设置
     * @param string|null $origination_uuid
     * @return $this
     */
    public function setOriginationUuid(string $origination_uuid = '')
    {
        if (!$origination_uuid) {
            $origination_uuid = md5(sprintf('%d%s%s', floor(microtime(true) * 1000), $this->caller, $this->callee));
        }

        $this->origination_uuid = $origination_uuid;

        $this->dial_str = str_replace('__ORIGINATION_UUID__', $this->origination_uuid, $this->dial_str);

        return $this;
    }

    /**
     * @return string
     */
    public function getCallerUuid()
    {
        return $this->origination_uuid;
    }

    /**
     * @var string
     */
    protected $callee_uuid;

    /**
     * @param string $callee_uuid
     * @return $this
     */
    public function setCalleeUuid(string $callee_uuid = '')
    {
        if (!$callee_uuid) {
            $callee_uuid = md5(sprintf('%d%s%s', floor(microtime(true) * 1000), $this->callee, $this->caller));
        }

        $this->callee_uuid = $callee_uuid;

        $this->dial_str = str_replace('__CALLEEUUID__', $this->callee_uuid, $this->dial_str);

        return $this;
    }

    /**
     * @return string
     */
    public function getCalleeUuid()
    {
        return $this->callee_uuid;
    }

    /**
     * 主叫
     * @var string
     */
    protected $caller;

    /**
     * @param string $caller
     * @return $this
     */
    public function setCaller(string $caller): self
    {
        $this->caller = $caller;

        $this->dial_str = str_replace('__CALLER__', $this->caller, $this->dial_str);

        return $this;
    }

    /**
     * @var string
     */
    protected $origination_caller_id_number;

    /**
     * 主叫id_number设置
     * @param string $origination_caller_id_number
     * @return $this
     */
    public function setOriginationCallerIdNumber(string $origination_caller_id_number)
    {
        $this->origination_caller_id_number = $origination_caller_id_number;

        $this->dial_str = str_replace('__ORIGINATION_CALLER_ID_NUMBER__', $this->origination_caller_id_number, $this->dial_str);

        return $this;
    }

    /**
     * 主叫名称设置
     * @var string
     */
    protected $origination_caller_id_name;

    /**
     * 主叫名称设置
     * @param string $origination_caller_id_name
     * @return $this
     */
    public function setOriginationCallerIdName(string $origination_caller_id_name)
    {
        $this->origination_caller_id_name = $origination_caller_id_name;

        $this->dial_str = str_replace('__ORIGINATION_CALLER_ID_NAME__', $this->origination_caller_id_name, $this->dial_str);

        return $this;
    }

    /**
     * 外显号码
     * @var string
     */
    protected $effective_caller_id_number;

    /**
     * 外显号码设置
     * @param string $effective_caller_id_number
     * @return $this
     */
    public function setEffectiveCallerIdNumber(string $effective_caller_id_number)
    {
        $this->effective_caller_id_number = $effective_caller_id_number;

        $this->dial_str = str_replace('__EFFECTIVE_CALLER_ID_NUMBER__', $this->effective_caller_id_number, $this->dial_str);

        return $this;
    }

    /**
     * 外显名称
     * @var string
     */
    protected $effective_caller_id_name;

    /**
     * 外显名称设置
     * @param string $effective_caller_id_name
     * @return $this
     */
    public function setEffectiveCallerIdName(string $effective_caller_id_name)
    {
        $this->effective_caller_id_name = $effective_caller_id_name;

        $this->dial_str = str_replace('__EFFECTIVE_CALLER_ID_NAME__', $this->effective_caller_id_name, $this->dial_str);

        return $this;
    }

    /**
     * 被叫
     * @var string
     */
    protected $callee;

    /**
     * @param string $callee
     * @return $this
     */
    public function setCallee(string $callee): self
    {
        $this->callee = $callee;

        $this->dial_str = str_replace('__CALLEE__', $this->callee, $this->dial_str);

        return $this;
    }

    /**
     * 网关名称
     * @var string
     */
    protected $gateway_name;

    /**
     * @param string $gateway_name
     * @return $this
     */
    public function setGatewayName(string $gateway_name = ''): self
    {
        $this->gateway_name = $gateway_name;

        $this->dial_str = str_replace('__GATEWAY_NAME__', $this->gateway_name ? $this->gateway_name . '_' : '', $this->dial_str);

        return $this;
    }

    /**
     * 前缀
     * @var string
     */
    protected $prefix;

    /**
     * 设置前缀
     * @param string $prefix
     * @return $this
     */
    public function setPrefix(string $prefix = '')
    {
        $this->prefix = $prefix;

        $this->dial_str = str_replace('__PREFIX__', $this->prefix, $this->dial_str);

        return $this;
    }

    /**
     * @var bool
     */
    protected $enable_filter_caller_uuid = true;

    /**
     * 禁用过滤主叫UUID
     */
    public function disableFilterCallerUuid()
    {
        $this->enable_filter_caller_uuid = false;

        return $this;
    }

    /**
     * @var bool
     */
    protected $enable_filter_callee_uuid = true;

    /**
     * 禁用被叫过滤
     * @return $this
     */
    public function disableFilterCalleeUuid()
    {
        $this->enable_filter_caller_uuid = false;

        return $this;
    }

    /**
     * 开始呼叫
     * @param string|null $caller
     * @param string|null $callee
     * @return $this
     * @throws InvalidFreeSwitchConnectionException
     * @throws InvalidGatewayException
     * @throws Exception
     */
    public function startCall(string $caller = null, string $callee = null)
    {
        $caller && $this->setCaller($caller);

        $callee && $this->setCallee($callee);

        if (!$this->caller) throw new Exception('Invalid caller number');

        if (!$this->callee) throw new Exception('Invalid callee number');

        // 检测网关可用
        if (!is_null($this->gateway_name) && false === $this->getConnection()->checkGateway($this->gateway_name)) {
            throw new InvalidGatewayException('Invalid gateway!');
        } else {
            $this->setGatewayName();
        }

        $this->origination_uuid or $this->setOriginationUuid();

        $this->callee_uuid or $this->setCalleeUuid();

        $this->origination_caller_id_number or $this->setOriginationCallerIdNumber($this->caller);

        $this->origination_caller_id_name or $this->setOriginationCallerIdName($this->caller);

        $this->effective_caller_id_number or $this->setEffectiveCallerIdNumber($this->caller);

        $this->effective_caller_id_name or $this->setEffectiveCallerIdName($this->caller);

        $this->prefix or $this->setPrefix();

        $this->getConnection()->bgapi($this->dial_str);

        $this->enable_filter_caller_uuid && $this->getConnection()->filterUuid($this->origination_uuid);

        $this->enable_filter_callee_uuid && $this->getConnection()->filterUuid($this->callee_uuid);

        var_dump($this->dial_str);

        return $this;
    }

    /**
     * 默认监听事件
     * @var array
     */
    protected $events = [EventNameConstants::ANSWER, EventNameConstants::HEARTBEAT, EventNameConstants::HANGUP];

    /**
     * @param array $events
     * @return $this
     */
    public function setEventLists(array $events)
    {
        $this->events = $events;

        return $this;
    }

    /**
     * 启用事件监听
     * @var bool
     */
    protected $enable_event_listening = true;

    /**
     * 启用事件监听
     * @return $this
     */
    public function disableEventListening()
    {
        $this->enable_event_listening = false;

        return $this;
    }

    /**
     * @param EventHandleInterface $eventHandle
     */
    public function eventListening(EventHandleInterface $eventHandle)
    {
        $this->getConnection()->setEventHandleObject($eventHandle);

        $this->enable_event_listening && $this->getConnection()->event('plain', implode(' ', $this->events));
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