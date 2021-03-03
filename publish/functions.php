<?php

if (!function_exists('value')) {
    /**
     * @param $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}

if (!function_exists('recv_to_array')) {
    /**
     * @param string $recv
     * @return array
     */
    function recv_to_array(string $recv)
    {
        $recv_list = explode("\n", $recv);
        $recv_arr = [];
        foreach ($recv_list as $k => $v) {
            if (strpos($v, ':') === false) {
                continue;
            }
            list($key, $value) = explode(":", $v, 2);
            $recv_arr[$key] = trim($value);
        }
        return $recv_arr;
    }
}
