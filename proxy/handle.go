package proxy

import (
	"bytes"
	"crypto/tls"
	"errors"
	"fmt"
	"net/http"
	"net/url"
	"os/exec"
	"strings"
	"sync"
	"time"

	"github.com/axgle/mahonia"
	"github.com/fatih/color"
	"github.com/ouqiang/goproxy"
)

// Cache 实现证书缓存接口
type Cache struct {
	m sync.Map
}

// Set 设置证书缓存
func (c *Cache) Set(host string, cert *tls.Certificate) {
	c.m.Store(host, cert)
}

// Get 获取证书缓存
func (c *Cache) Get(host string) *tls.Certificate {
	v, ok := c.m.Load(host)
	if !ok {
		return nil
	}

	return v.(*tls.Certificate)
}

// EventHandler 事件处理
type EventHandler struct{}

// Connect 连接回调
func (e *EventHandler) Connect(ctx *goproxy.Context, rw http.ResponseWriter) {

}

// Auth 授权回调
func (e *EventHandler) Auth(ctx *goproxy.Context, rw http.ResponseWriter) {
	// 身份验证
}

// BeforeRequest 请求前回调
func (e *EventHandler) BeforeRequest(ctx *goproxy.Context) {

}

// BeforeResponse 响应前回调
func (e *EventHandler) BeforeResponse(ctx *goproxy.Context, resp *http.Response, err error) {
	if err != nil {
		return
	}
}

// ParentProxy 设置上级代理
func (e *EventHandler) ParentProxy(req *http.Request) (*url.URL, error) {
	return nil, nil
}

// Finish 进程完成
func (e *EventHandler) Finish(ctx *goproxy.Context) {
	// log.Println(err)
	// fmt.Println("[debug]访问结束: ", ctx.Req.URL)
	// fmt.Println("[debug]Header: ", ctx.Req.Header)
	if strings.Contains(ctx.Req.URL.Host, "healthych.com") || strings.Contains(ctx.Req.URL.Host, "scmttec.com") {
		if ctx.Req.Header.Get(`Tk`) != "" {
			tk = ctx.Req.Header.Get(`Tk`)
			StopProxy()
		}
	}
}

// ErrorLog 记录错误日志
func (e *EventHandler) ErrorLog(err error) {
	// log.Println(err)
}

var server *http.Server
var tk string

// Handle 入口
func Handle() string {
	defer CloseNetworkProxyWin()
	openNetworkProxyWin()
	startProxy()
	return tk
}

// WinCmd 执行Windows命令
func WinCmd(command string) (string, error) {
	cmd := exec.Command("cmd")
	// cmd := exec.Command("powershell")
	in := bytes.NewBuffer(nil)
	cmd.Stdin = in //绑定输入
	var out bytes.Buffer
	cmd.Stdout = &out //绑定输出
	go func() {
		// start stop restart
		in.WriteString(command + "\n") //写入你的命令，可以有多行，"\n"表示回车
	}()
	err := cmd.Start()
	if err != nil {
		return "", err
	}
	err = cmd.Wait()
	if err != nil {
		return "", err
	}
	rt := mahonia.NewDecoder("gbk").ConvertString(out.String()) //

	if strings.ContainsAny(rt, "成功") {
		return rt, nil
	}
	return "", errors.New("执行失败")
}

// StopProxy 关闭监听
func StopProxy() {
	defer func() {
		if r := recover(); r != nil {
		}
	}()
	err := server.Shutdown(nil)
	if err != nil {

	}
	fmt.Println("已关闭监听")
}

func startProxy() {
	c := color.New(color.FgHiBlue)
	content := fmt.Sprintf("第一步：%s\n第二步：%s\n切记：%s",
		"登录电脑微信并打开`约苗`公众号网页或`秒苗`小程序",
		"进入后返回当前窗口，如提示选择预约人则关闭微信打开的窗口",
		"关闭窗口后千万不可再次进入约苗/秒苗了，否则会导致Token失效！！！")
	c.Println(content)
	proxy := goproxy.New(goproxy.WithDecryptHTTPS(&Cache{}), goproxy.WithDelegate(&EventHandler{}))
	server = &http.Server{
		Addr:         ":9998",
		Handler:      proxy,
		ReadTimeout:  1 * time.Minute,
		WriteTimeout: 1 * time.Minute,
	}
	err := server.ListenAndServe()
	if err != nil {

	}
}
