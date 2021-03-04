<?php declare(strict_types=1);

namespace FreeSwitch\Connection;

use FreeSwitch\Event\EventHandleInterface;
use FreeSWITCH\Tool\Context;
use Swoole\Coroutine\{Client, System};

/**
 * Trait Api
 * @package App\FreeSwitch\src
 */
trait Api
{
    /**
     * @var Client
     */
    protected $connection;

    protected function send(string $data)
    {
        $data .= "\r\n\r\n";

        if (strlen($data) == $this->connection->send($data)) return true;

        return false;
    }

    /**
     * @param string $password
     * @return bool
     */
    public function auth(string $password = '')
    {
        if ($this->send(sprintf("auth %s", $password))) {
            return $this->getContentByContentLength();
        }
        return false;
    }

    /**
     * @param string $api
     * @param string $args
     * @return bool|mixed|string
     */
    public function api(string $api, string $args = '')
    {
        if ($this->send(sprintf("api %s %s", $api, $args))) {
            return $this->getContentByContentLength();
        }
        return false;
    }

    /**
     * @param string $api
     * @param string $args
     * @param string $uuid
     * @return bool
     */
    public function bgapi(string $api, string $args = '', string $uuid = '')
    {
        return $this->send(sprintf("bgapi %s %s %s", $api, $args, $uuid));
    }

    /**
     * 通话录音监听
     * @param string $uuid
     * @param string $file_name
     * @param int $duration
     * @return bool
     */
    public function uuidRecord(string $uuid, string $file_name, int $duration = 7200)
    {
        return $this->bgapi('uuid_record', sprintf("%s start %s %d", $uuid, $file_name, $duration));
    }

    /**
     * 通话变量设置
     * @param string $uuid
     * @param string $var
     * @param string $val
     * @return bool
     */
    public function uuidSetVar(string $uuid, string $var, string $val)
    {
        return $this->bgapi('uuid_setvar', sprintf("%s %s %s ", $uuid, $var, $val));
    }

    /**
     * @param string $uuid
     * @param string $app
     * @param string $args
     * @return bool
     */
    public function execute(string $uuid, string $app, string $args)
    {
        return $this->send(sprintf("sendmsg %s\ncall-command: execute\nexecute-app-name: %s \nexecute-app-arg: %s", $uuid, $app, $args));
    }

    /**
     * @param string $uuid
     * @param string $app
     * @param string $args
     * @return bool
     */
    public function executeAsync(string $uuid, string $app, string $args)
    {
        return $this->send(sprintf("sendmsg %s\ncall-command: executeAsync\nexecute-app-name: %s \nexecute-app-arg: %s", $uuid, $app, $args));
    }


    /**
     * @param string $uuid
     * @return bool
     */
    public function sendMsg(string $uuid)
    {
        return $this->send(sprintf("sendmsg %s", $uuid));
    }

    /**
     * 事件监听，依赖于事件机制
     * 此方法会阻塞，这里不会主动退出
     * @param string $sorts
     * @param string $args
     * @return bool
     */
    public function event(string $sorts = 'plain', string $args = '')
    {
        $events = Context::get('events') ?? [];

        $events[$args] = $sorts;

        Context::set('events', $events);

        if (!$this->send(sprintf("event %s %s", $sorts, $args))) return false;

        while (true) {

            $this->event_handle_object instanceof EventHandleInterface && $this->event_handle_object->process(recv_to_array($this->getContentByContentLength()));

            if (Context::get('end_listen_event')) break;

            System::sleep(1);
        }

        return false;
    }

    /**
     * @param string $uuid
     * @return bool
     */
    public function filterUuid(string $uuid)
    {
        $filter_unique_ids = Context::get('filter_unique_ids') ?? [];

        array_push($filter_unique_ids, $uuid);

        Context::set('filter_unique_ids', $filter_unique_ids);

        if ($this->send(sprintf("filter Unique-ID %s", $uuid))) {
            return $this->getContentByContentLength();
        }
        return false;
    }

    /**
     * 根据内容长度获取内容
     * 自己处理TCP粘包问题
     * @return string
     */
    public function getContentByContentLength()
    {
        $recv = '';

        $content_length = 0;

        while (true) {
            if ($recv == '' && Context::get('more_than')) {
                $recv = Context::get('more_than');
                Context::set('more_than', null);
            }

            $package = $this->getConnection()->connection->recv();

            if ($package === '') $this->connection->close();

            $recv .= str_replace(["Content-Type: text/event-xml\n", "Content-Type: text/event-plain\n", "Content-Type: text/event-json\n"], '', (string)$package);

            if (preg_match('/(Content-Length:)\s*(\d+)\n/Ui', $recv, $preg_match_arr)) {
                $content_length = (int)end($preg_match_arr);
            }

            if ($content_length > 0) {
                $content_length_self_len = strlen("Content-Length: $content_length\n") + 1; // 加1是因为截取得从前面一位开始

                $substr_len = $content_length + $content_length_self_len;

                $result = substr($recv, strpos($recv, "Content-Length: $content_length\n"), $substr_len);

                if (strlen($result) < $content_length) {
                    continue;
                } else {
                    $more_than = substr($recv, strpos($recv, $result) + strlen($result));
                    $more_than && Context::set('more_than', $more_than); // 存储未处理的数据
                    return $result;
                }
            } else {
                return $recv;
            }
        }
    }

    /**
     * 窥探
     * @param int $length
     * @return mixed
     */
    public function peek(int $length = 65535)
    {
        return $this->connection->peek($length);
    }

    /**
     * 验证网关配置是否有效，有效的情况返回xml包
     * @param string $gateway_name
     * @return bool|mixed|string
     */
    public function checkGateway(string $gateway_name)
    {
        $gateway_status = $this->api(sprintf("sofia xmlstatus gateway %s", $gateway_name));
        if (strpos($gateway_status, 'Invalid') !== false) return false;
        return $gateway_status;
    }
}