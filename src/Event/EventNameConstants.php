<?php

declare(strict_types=1);


namespace FreeSwitch\Event;


class EventNameConstants
{

    /**
     * 心跳
     */
    const HEARTBEAT = 'SESSION_HEARTBEAT';

    /**
     * 应答
     */
    const ANSWER = 'CHANNEL_ANSWER';

    /**
     * 挂机
     */
    const HANGUP = 'CHANNEL_HANGUP_COMPLETE';

    /**
     * 说话开始
     */
    const RECORD_START = 'RECORD_START';

    /**
     * 说话结束
     */
    const RECORD_STOP = 'RECORD_STOP';
}