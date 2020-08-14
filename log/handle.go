package log

import (
	"github.com/fatih/color"
	"time"
	"fmt"
	"miaomiao/util"
)
// Success 绿色日志
func Success(str string) {
	c := color.New(color.FgGreen)
	content := fmt.Sprintf("[%s]%s", time.Now().Format("2006-01-02 15:04:05.000000"), str)
	c.Println(content)
	util.Log(content)
}
// Info 蓝色日志
func Info(str string) {
	c := color.New(color.FgHiBlue)
	content := fmt.Sprintf("[%s]%s", time.Now().Format("2006-01-02 15:04:05.000000"), str)
	c.Println(content)
	util.Log(content)
}
// Danger 红色日志
func Danger(str string) {
	c := color.New(color.FgRed)
	content := fmt.Sprintf("[%s]%s", time.Now().Format("2006-01-02 15:04:05.000000"), str)
	c.Println(content)
	util.Log(content)
}