package util

import (
	"os"
	"strconv"
	"time"
)

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

// Log 写入日志
func Log(str string) {
	data := []byte(str + "\n")
	f, err := os.OpenFile("runtime.log", os.O_RDWR|os.O_CREATE, 0644)
	if err != nil {
		println("写入日志文件出错: ", err.Error())
	} else {
		n, _ := f.Seek(0, 2)
		_, err = f.WriteAt(data, n)
	}
	defer f.Close()
}
