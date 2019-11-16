<?php
namespace App;

class Util
{
    /**
     * 格式化含有时间前缀的字符串
     * 
     */
    public static function buildTimePrefix($str)
    {
        return sprintf("[%s]%s", (new \DateTime())->format('H:i:s:u'), $str);;
    }

    /**
     * 获取指定日期是周几
     * 
     */
    public static function getWeek($date)
    {
        if (is_string($date)) {
            $date = strtotime($date);
        }
        return ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][date('w', $date)];
    }
    
    /**
     * 获取当前时间[微秒]
     * 
     */
    public static function microtimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
    
    /**
     * 获取当前时间[微秒]
     * 
     */
    public static function microtimeInt()
    {
        list($usec, $sec) = explode(" ", microtime());
        return (int)(((float)$usec + (float)$sec) * 1000);
    }

    /**
     * 建立报错数据
     *
     */
    public static function buildException($e)
    {
        $trace = $e->getTraceAsString();
        $trace = explode("\n", $trace);
        return [
            'type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getcode(),
            'message' => $e->getMessage(),
            'trace' => $trace,
            'payload' => $e->payload ?? []
        ];
    }
}