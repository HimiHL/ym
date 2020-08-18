package request

import (
	"crypto/tls"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"miaomiao/model"
	"net/http"
	"sync"
	"time"
)

// Domain 域名
var Domain = "https://miaomiao.scmttec.com"

// TimeNow 当前服务器时间
func TimeNow(token string) model.TimeNowModel {
	var response model.TimeNowModel
	jsonStr := GET(model.APITimeNow, token)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// TimeNowTest 当前服务器时间
func TimeNowTest(token string, times int) []model.TimeNowModel {
	var response model.TimeNowModel
	var resultChan = make([]model.TimeNowModel, 0, times)
	jsonStrList := MultiGet(model.APITimeNow, token, times)
	for i := range jsonStrList {
		json.Unmarshal([]byte(jsonStrList[i]), &response)
		resultChan = append(resultChan, response)
	}
	return resultChan
}

// Regions 获取地区信息
func Regions(token string, parentCode string) model.RegionsModel {
	var response model.RegionsModel
	route := fmt.Sprintf("%s?parentCode=%s", model.APIRegions, parentCode)
	jsonStr := GET(route, token)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// LinkMans 获取预约人联系列表
func LinkMans(token string) model.LinkMansModel {
	var response model.LinkMansModel
	jsonStr := GET(model.APIGetLinkMan, token)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// SaveLinkMan 秒杀
func SaveLinkMan(token string, name string, idCard string, regionCode string, birthday string) model.ResponseModel {
	var response model.ResponseModel
	route := fmt.Sprintf("%s?sex=2&isDefault=2&relationType=7&name=%s&idCardNo=%s&birthday=%s&regionCode=%s", model.APISaveLinkMan, name, idCard, birthday, regionCode)
	jsonStr := GET(route, token)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// Vaccines 获取秒杀门诊列表
func Vaccines(token string, regionCode string) model.VaccinesModel {
	var response model.VaccinesModel
	route := fmt.Sprintf("%s?regionCode=%s&offset=0&limit=50", model.APIVaccineList, regionCode)
	jsonStr := GET(route, token)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// Subscribe 秒杀
func Subscribe(token string, id string, linkmanID string, idCard string) model.ResponseModel {
	var response model.ResponseModel
	route := fmt.Sprintf("%s?seckillId=%s&vaccineIndex=1&linkmanId=%s&idCardNo=%s", model.APISubscribe, id, linkmanID, idCard)
	jsonStr := GET(route, token)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// MultiSubscribe 秒杀/批量
func MultiSubscribe(token string, id string, linkmanID string, idCard string, times int) []model.ResponseModel {
	var response model.ResponseModel
	var resultChan = make([]model.ResponseModel, 0, times)
	route := fmt.Sprintf("%s?seckillId=%s&vaccineIndex=1&linkmanId=%s&idCardNo=%s", model.APISubscribe, id, linkmanID, idCard)
	jsonStrList := MultiGet(route, token, times)
	for i := range jsonStrList {
		json.Unmarshal([]byte(jsonStrList[i]), &response)
		resultChan = append(resultChan, response)
	}
	return resultChan
}

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
