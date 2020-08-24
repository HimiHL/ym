package model

// APITimeNow 服务器时间
var APITimeNow = "/seckill/seckill/now2.do"

// APIRegions 地区信息
var APIRegions = "/base/region/childRegions.do"

// APISaveLinkMan 保存预约人信息
var APISaveLinkMan = "/seckill/linkman/saveOrUpdate.do"

// APIDelLinkMan 删除预约人信息
var APIDelLinkMan = "/seckill/linkman/delete.do"

// APIGetLinkMan 获取预约人信息列表
var APIGetLinkMan = "/seckill/linkman/findByUserId.do"

// APIVaccineList 门诊列表
var APIVaccineList = "/seckill/seckill/list.do"

// APIVaccine 门诊信息
var APIVaccine = "/seckill/seckill/detail.do"

// APISubscribe 秒杀
var APISubscribe = "/seckill/seckill/subscribe.do"

// APICheckStock 检查库存
var APICheckStock = "/seckill/seckill/checkstock2.do"

// APISubscribeDays 秒杀选择日期
var APISubscribeDays = "/seckill/seckill/subscribeDays.do"

// APISubscribeDayTimes 秒杀选择时间
var APISubscribeDayTimes = "/seckill/seckill/dayTimes.do"

// APISubmitDateTime 提交时间确认秒杀
var APISubmitDateTime = "/seckill/seckill/submitDateTime.do"

// Callback 回调函数
type Callback func(resp string, err error)

// TimeNowModel 服务器当前时间
type TimeNowModel struct {
	Code  string `json:"code"`
	Data  int64  `json:"data"`
	Ok    bool   `json:"ok"`
	Msg   string `json:"msg"`
	NotOk bool   `json:"notOk"`
}

// StockModel 检查库存接口
type StockModel struct {
	Code string `json:"code"`
	Msg  string `json:"msg"`
	Ok   bool   `json:"ok"`
	Data struct {
		St    int `json:"st"`
		Stock int `json:"stock"`
	} `json:"data"`
	NotOk bool `json:"notOk"`
}

// ResponseModel 基础响应
type ResponseModel struct {
	Code  string `json:"code"`
	Data  string `json:"data"`
	Msg   string `json:"msg"`
	Ok    bool   `json:"ok"`
	NotOk bool   `json:"notOk"`
}

// RegionsModel 地区信息接口
type RegionsModel struct {
	Code string `json:"code"`
	Msg  string `json:"msg"`
	Data []struct {
		Name  string `json:"name"`
		Value string `json:"value"`
	} `json:"data"`
	Ok bool `json:"ok"`
}

// LinkMansModel 预约人列表接口
type LinkMansModel struct {
	Code string `json:"code"`
	Msg  string `json:"msg"`
	Data []struct {
		ID           int    `json:"id"`
		UserID       int    `json:"userId"`
		Name         string `json:"name"`
		IDCardNo     string `json:"idCardNo"`
		Birthday     string `json:"birthday"`
		Sex          int    `json:"sex"`
		RegionCode   string `json:"regionCode"`
		Address      string `json:"address"`
		IsDefault    int    `json:"isDefault"`
		RelationType int    `json:"relationType"`
		CreateTime   string `json:"createTime"`
		ModifyTime   string `json:"modifyTime"`
		Yn           int    `json:"yn"`
	} `json:"data"`
	Ok    bool `json:"ok"`
	NotOk bool `json:"notOk"`
}

// SubscribeDaysModel 秒杀选择日期接口
type SubscribeDaysModel struct {
	Code string `json:"code"`
	Msg  string `json:"msg"`
	Data []struct {
		Day   string `json:"day"`
		Total int    `json:"total"`
	} `json:"data"`
	Ok    bool `json:"ok"`
	NotOk bool `json:"notOk"`
}

// SubscribeDayTimesModel 秒杀选择时间接口
type SubscribeDayTimesModel struct {
	Code string `json:"code"`
	Msg  string `json:"msg"`
	Data []struct {
		Wid       string `json:"wid"`
		StartTime string `json:"startTime"`
		EndTime   string `json:"endTime"`
		MaxSub    int    `json:"maxSub"`
	} `json:"data"`
	Ok    bool `json:"ok"`
	NotOk bool `json:"notOk"`
}

// VaccinesModel 秒杀详情接口
type VaccinesModel struct {
	Code string `json:"code"`
	Msg  string `json:"msg"`
	Data []struct {
		ID          int    `json:"id"`
		Name        string `json:"name"`
		ImgURL      string `json:"imgUrl"`
		VaccineCode string `json:"vaccineCode"`
		VaccineName string `json:"vaccineName"`
		Address     string `json:"address"`
		StartTime   string `json:"startTime"`
		Stock       int    `json:"stock"`
	} `json:"data"`
	Ok    bool `json:"ok"`
	NotOk bool `json:"notOk"`
}
