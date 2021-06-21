# freeSWITCH-client

## 简介
freeSWITCH-client是基于php swoole扩展协程通过tcp协议实现的freeSWITCH的客户端，已实现对基本接口的封装。例如验证网关有效性，普通双方通话外呼接口及事件监听等等接口。同时实现了简单的异常断开重连，重启事件监听。

## 说明
需要监听事件则需要需自行实现\FreeSwitch\Event\EventHandleInterface，并通过事件监听接口(\FreeSwitch\FreeSwitch->eventListening或\FreeSwitch\FreeSwitch->setEventHandleObject)将处理事件类注入。需要注意的是处理事件类以及连接freeSWITCH是阻塞的。想要退出阻塞的时间监听可以调用\FreeSwitch\FreeSwitch->close方法即可。

实例\FreeSwitch\FreeSwitch需要传入你的FreeSwitch服务配置信息，host、port、password以及timeout(连接超时时间)。

你可以通过\FreeSwitch\FreeSwitch提供的各种方法去设置呼叫所需的必须或者自定义参数，你甚至可以通过代理方法调用“底层”的公用接口方法。

## 简单示例

````
class example implements \FreeSwitch\Event\EventHandleInterface {

    /**
     * @param array $event_recv 监听事件返回的包
     */
    public function process(array $event_recv)
    {
        // todo 
    }
    
    /**
     * 外呼接口
     */
    function outbound()
    {
        $caller = '****'; // 被叫
        $callee = '****'; // 主叫
        
        $fs = new \FreeSwitch\FreeSwitch(['host'=>'127.0.0.1','port'=>'12345','password'=>'******']);
        
        $fs->startCall($caller, $callee); // 开始呼叫

        // 退出事件监听示例(或者其他方式调用，不在同一协程下不会被阻塞，但是得注意您的上下文关系，避免造成变量污染或内存泄漏等问题)
        Timer::after(1000,function ()use ($freeSwitch){
            $freeSwitch->close();
        });
        
        $fs->eventListening($this); // 事件监听，并注入事件处理类(注意:监听事件是阻塞的)
    }
}

````
``QQ:827871186``