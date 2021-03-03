<?php
declare(strict_types=1);

namespace FreeSwitch\Event;

/**
 * 事件处理类
 * Interface EventHandleInterface
 * @package FreeSwitch\Event
 */
interface EventHandleInterface
{
    public function process(array $event_recv);
}