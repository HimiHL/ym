package request

import (
	"crypto/tls"
	"io/ioutil"
	"net/http"
	"sync"
	"time"
)

// Domain 域名
var Domain = "https://miaomiao.scmttec.com"

// GET 请求
func GET(url string, tk string) string {
	tr := &http.Transport{
		TLSClientConfig: &tls.Config{InsecureSkipVerify: true},
	}
	// 超时时间：60秒
	client := &http.Client{Timeout: 60 * time.Second, Transport: tr}
	url = Domain + url

	request, err := http.NewRequest("GET", url, nil)
	if err != nil {
		panic(err)
	}
	request.Header.Set("Host", "miaomiao.scmttec.com")
	request.Header.Set("Cookie", "")
	request.Header.Set("tk", tk)
	request.Header.Set("Accept-Encoding", "gzip, deflate, br")
	request.Header.Set("Accept", "application/json, text/plain, */*")
	request.Header.Set("Referer", "https://servicewechat.com/wxff8cad2e9bf18719/4/page-frame.html")
	request.Header.Set("Accept-Language", "zh-cn")
	request.Header.Set("User-Agent", "Mozilla/5.0 (iPhone; CPU iPhone OS 13_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/4G Language/zh_CN")

	result := []byte("")
	resp, err := client.Do(request)
	if err != nil {
		panic(err)
	}
	result, _ = ioutil.ReadAll(resp.Body)
	defer resp.Body.Close()
	return string(result)
}

// MultiGet 批量请求
func MultiGet(url string, tk string, times int) []string {
	tr := &http.Transport{
		TLSClientConfig: &tls.Config{InsecureSkipVerify: true},
	}
	// 超时时间：60秒
	client := &http.Client{Timeout: 60 * time.Second, Transport: tr}
	url = Domain + url

	request, err := http.NewRequest("GET", url, nil)
	if err != nil {
		panic(err)
	}
	request.Header.Set("Host", "miaomiao.scmttec.com")
	request.Header.Set("Cookie", "")
	request.Header.Set("tk", tk)
	request.Header.Set("Accept-Encoding", "gzip, deflate, br")
	request.Header.Set("Accept", "application/json, text/plain, */*")
	request.Header.Set("Referer", "https://servicewechat.com/wxff8cad2e9bf18719/4/page-frame.html")
	request.Header.Set("Accept-Language", "zh-cn")
	request.Header.Set("User-Agent", "Mozilla/5.0 (iPhone; CPU iPhone OS 13_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/4G Language/zh_CN")

	responses := make(chan string)
	var wg sync.WaitGroup
	var results []string

	for i := 0; i < times; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			resp, err := client.Do(request)
			if err != nil {
				panic(err)
			}
			defer resp.Body.Close()
			result, _ := ioutil.ReadAll(resp.Body)
			responses <- string(result)
		}()
	}
	go func() {
		wg.Wait()
		close(responses)
	}()
	for result := range responses {
		// println(result)
		results = append(results, result)
	}
	return results
}
