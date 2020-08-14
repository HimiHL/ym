package proxy

import (
	"bytes"
	"crypto/tls"
	"fmt"
	"net/http"
	"net/url"
	"os"
	"os/exec"
	"strings"
	"sync"
	"time"

	"github.com/ouqiang/goproxy"
	"github.com/fatih/color"
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
	// fmt.Println("访问结束: ", ctx.Req.URL)
	// fmt.Println("Header: ", ctx.Req.Header)
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
var nw string

// Handle 入口
func Handle(network string) string {
	nw = network
	defer CloseNetworkProxy()
	openNetworkProxy()
	startProxy()
	return tk
}

// Cmd 执行Unix命令
func Cmd(command string) (string, error) {
	cmd := exec.Command("sh", "-c", command)
	var out bytes.Buffer
	cmd.Stdout = &out
	cmd.Stderr = os.Stderr
	err := cmd.Start()
	if err != nil {
		return "", err
	}
	err = cmd.Wait()
	return out.String(), err
}

func openNetworkProxy() {
	_, err := Cmd("sudo networksetup -setsecurewebproxy " + nw + " 127.0.0.1 9998")
	if err != nil {
		fmt.Println("开启代理出现异常: ", err)
	}
	fmt.Println("已开启代理")
}

// CloseNetworkProxy 关闭网络代理
func CloseNetworkProxy() {
	defer func() {
		if r := recover(); r != nil {
		}
	}()
	_, err := Cmd("sudo networksetup -setsecurewebproxystate " + nw + " off")
	if err != nil {
		// fmt.Println("关闭代理出现异常: ", err)
	}
	fmt.Println("已关闭代理")
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
	content := fmt.Sprintf("第一步：%s\n第二步：%s\n第三步：%s\n切记：%s",
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
