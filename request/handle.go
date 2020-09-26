package request

import (
	"crypto/tls"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"log"
	"miaomiao/model"
	"net/http"
	"regexp"
	"time"
)

// Domain 域名
var Domain = "https://miaomiao.scmttec.com"

// TimeNow 当前服务器时间
func TimeNow(token string) model.TimeNowModel {
	var response model.TimeNowModel
	requestData := model.RequestModel{
		Route:  model.APITimeNow,
		Token:  token,
		Debug:  false,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// Regions 获取地区信息
func Regions(token string, parentCode string) model.RegionsModel {
	var response model.RegionsModel
	requestData := model.RequestModel{
		Route:  fmt.Sprintf("%s?parentCode=%s", model.APIRegions, parentCode),
		Token:  token,
		Debug:  false,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// LinkMans 获取预约人联系列表
func LinkMans(token string) model.LinkMansModel {
	var response model.LinkMansModel
	requestData := model.RequestModel{
		Route:  model.APIGetLinkMan,
		Token:  token,
		Debug:  false,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// SubscribeDays 获取订单可选的日期列表
func SubscribeDays(token string, id string, orderID string) model.SubscribeDaysModel {
	var response model.SubscribeDaysModel
	requestData := model.RequestModel{
		Route:  fmt.Sprintf("%s?id=%s&sid=%s", model.APISubscribeDays, id, orderID),
		Token:  token,
		Debug:  true,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// SubscribeDayTimes 获取订单可选的时间列表
func SubscribeDayTimes(token string, id string, orderID string, day string) model.SubscribeDayTimesModel {
	var response model.SubscribeDayTimesModel
	requestData := model.RequestModel{
		Route:  fmt.Sprintf("%s?id=%s&sid=%s&day=%s", model.APISubscribeDayTimes, id, orderID, day),
		Token:  token,
		Debug:  true,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// SubmitDateTime 提交预约时间
func SubmitDateTime(token string, id string, orderID string, day string, wid string) model.ResponseModel {
	var response model.ResponseModel
	requestData := model.RequestModel{
		Route:  fmt.Sprintf("%s?id=%s&sid=%s&day=%s&wid=%s", model.APISubmitDateTime, id, orderID, day, wid),
		Token:  token,
		Debug:  true,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// SaveLinkMan 保存联系人
func SaveLinkMan(token string, name string, idCard string, regionCode string, birthday string, id string) model.ResponseModel {
	var response model.ResponseModel
	route := fmt.Sprintf("%s?sex=2&isDefault=2&relationType=7&name=%s&idCardNo=%s&birthday=%s&regionCode=%s", model.APISaveLinkMan, name, idCard, birthday, regionCode)
	if id != "" {
		route += "&id=" + string(id)
	}
	requestData := model.RequestModel{
		Route:  route,
		Token:  token,
		Debug:  false,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// DelLinkMan 删除联系人
func DelLinkMan(token string, id string) model.ResponseModel {
	var response model.ResponseModel
	route := fmt.Sprintf("%s?id=%s", model.APIDelLinkMan, id)
	requestData := model.RequestModel{
		Route:  route,
		Token:  token,
		Debug:  false,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// Vaccines 获取秒杀门诊列表
func Vaccines(token string, regionCode string) model.VaccinesModel {
	var response model.VaccinesModel
	route := fmt.Sprintf("%s?regionCode=%s&offset=0&limit=50", model.APIVaccineList, regionCode)
	requestData := model.RequestModel{
		Route:  route,
		Token:  token,
		Debug:  false,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// Subscribe 秒杀
func Subscribe(token string, id string, linkmanID string, idCard string, sign string, cookie string) model.ResponseModel {
	var response model.ResponseModel
	route := fmt.Sprintf("%s?seckillId=%s&vaccineIndex=1&linkmanId=%s&idCardNo=%s", model.APISubscribe, id, linkmanID, idCard)
	requestData := model.RequestModel{
		Route:  route,
		Token:  token,
		Debug:  true,
		Sign:   sign,
		Cookie: cookie,
	}
	jsonStr, _ := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)
	return response
}

// Stock 检查库存
func Stock(token string, id string) model.StockModel {
	var response model.StockModel
	route := fmt.Sprintf("%s?id=%s", model.APICheckStock, id)
	requestData := model.RequestModel{
		Route:  route,
		Token:  token,
		Debug:  true,
		Sign:   "",
		Cookie: "",
	}
	jsonStr, respHeader := Request(requestData)
	json.Unmarshal([]byte(jsonStr), &response)

	// 解析Header
	cookieArray := respHeader["Set-Cookie"]
	for _, val := range cookieArray {
		comp := regexp.MustCompile(`([a-fA-Z0-9]+){0,4}=([a-zA-Z0-9]+){0,18}`)
		match := comp.FindStringSubmatch(val)
		if len(match) == 3 {
			response.Cookie = match[0]
		}
	}

	return response
}

// Request 请求
func Request(data model.RequestModel) (string, http.Header) {
	tr := &http.Transport{
		TLSClientConfig: &tls.Config{InsecureSkipVerify: true},
	}
	// 超时时间：60秒
	client := &http.Client{Timeout: 60 * time.Second, Transport: tr}
	url := Domain + data.Route

	if data.Debug {
		log.Println("请求地址: " + url)
	}

	request, err := http.NewRequest("GET", url, nil)
	if err != nil {
		panic(err)
	}
	request.Header.Set("Host", "miaomiao.scmttec.com")
	request.Header.Set("Accept-Encoding", "gzip, deflate, br")
	request.Header.Set("Accept", "application/json, text/plain, */*")
	request.Header.Set("Referer", "https://servicewechat.com/wxff8cad2e9bf18719/12/page-frame.html")
	request.Header.Set("Accept-Language", "zh-cn")
	request.Header.Set("User-Agent", "Mozilla/5.0 (iPhone; CPU iPhone OS 13_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.15(0x17000f2d) NetType/4G Language/zh_CN")

	request.Header.Set("Cookie", data.Cookie)
	request.Header.Set("tk", data.Token)
	request.Header.Set("ecc-hs", data.Sign)

	if data.Debug {
		log.Println(fmt.Sprintf("请求Header: %v", request.Header))
	}

	result := []byte("")
	resp, err := client.Do(request)
	if err != nil {
		panic(err)
	}
	result, _ = ioutil.ReadAll(resp.Body)
	defer resp.Body.Close()

	resultText := string(result)
	if data.Debug {
		log.Println("响应结果: " + resultText)
	}

	return resultText, resp.Header
}
