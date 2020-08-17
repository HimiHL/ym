package request

import (
	"encoding/json"
	"fmt"
	"miaomiao/model"
)

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
