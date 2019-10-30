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
}