package util

import (
	"crypto/md5"
	"encoding/hex"
	"os"
	"strconv"
	"time"
)

// LogFileName 日志名称
var LogFileName string

// TimestampNow 获取当前Unix时间戳
func TimestampNow() int64 {
	return time.Now().UnixNano() / int64(time.Millisecond)
}

// TimestampFormat 格式化指定时间字符串的Unix时间戳
func TimestampFormat(date string) int64 {
	timestamp, _ := time.ParseInLocation("2006-01-02 15:04:05", date, time.Local)
	return timestamp.UnixNano() / int64(time.Millisecond)
}

// MillTimestampToDate 将13位时间戳转换为日期格式
func MillTimestampToDate(timestamp int64) string {
	millsec := timestamp % 1000
	return time.Unix(timestamp/1000, 0).Format("2006-01-02 15:04:05") + "." + strconv.FormatInt(millsec, 10)
}

// Md5 加密
func Md5(str string) string {
	h := md5.New()
	h.Write([]byte(str))
	return hex.EncodeToString(h.Sum(nil))
}

// MapKeys 获取字典所有KEY
func MapKeys(m map[string]int) []string {
	// 数组默认长度为map长度,后面append时,不需要重新申请内存和拷贝,效率较高
	j := 0
	keys := make([]string, len(m))
	for k := range m {
		keys[j] = k
		j++
	}
	return keys
}

// Log 写入日志
func Log(str string) {
	data := str + "\n"
	f, err := os.OpenFile(LogFileName+".log", os.O_RDWR|os.O_CREATE|os.O_APPEND, 0644)
	if err != nil {
		println("打开日志文件出错: ", err.Error())
	}
	_, err = f.WriteString(data)
	defer f.Close()
}
