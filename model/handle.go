package model

// TimeNow 服务器时间
var APITimeNow = "/seckill/seckill/now2.do"
// Regions 地区信息
var APIRegions = "/base/region/childRegions.do"
// SaveLinkMan 保存预约人信息
var APISaveLinkMan = "/seckill/linkman/saveOrUpdate.do"
// GetLinkMan 获取预约人信息列表
var APIGetLinkMan = "/seckill/linkman/findByUserId.do"
// VaccineList 门诊列表
var APIVaccineList = "/seckill/seckill/list.do"
// Vaccine 门诊信息
var APIVaccine = "/seckill/seckill/detail.do"
// Subscribe 秒杀
var APISubscribe = "/seckill/seckill/subscribe.do"

// Callback 回调函数
type Callback func(resp string, err error)

// TimeNowModel 服务器当前时间
type TimeNowModel struct {
	Code string `json:"code"`
	Data int64 `json:"data"`
	Ok bool `json:"ok"`
	Msg string `json:msg`
	NotOk bool `json:"notOk"`
}

// SubscribeModel 服务器当前时间
type ResponseModel struct {
	Code string `json:"code"`
	Data string `json:"data"`
	Msg string `json:msg`
	Ok bool `json:"ok"`
	NotOk bool `json:"notOk"`
}

// RegionsModel 地区信息接口
type RegionsModel struct {
	Code string `json:"code"`
	Msg string `json:msg`
	Data []struct {
		Name string `json:"name"`
		Value string `json:"value"`
	} `json:"data"`
	Ok bool `json:"ok"`
}

// LinkMansModel 预约人列表接口
type LinkMansModel struct {
	Code string `json:"code"`
	Msg string `json:msg`
	Data []struct {
		ID int `json:"id"`
		UserID int `json:"userId"`
		Name string `json:"name"`
		IDCardNo string `json:"idCardNo"`
		Birthday string `json:"birthday"`
		Sex int `json:"sex"`
		RegionCode string `json:"regionCode"`
		Address string `json:"address"`
		IsDefault int `json:"isDefault"`
		RelationType int `json:"relationType"`
		CreateTime string `json:"createTime"`
		ModifyTime string `json:"modifyTime"`
		Yn int `json:"yn"`
	} `json:"data"`
	Ok bool `json:"ok"`
	NotOk bool `json:"notOk"`
}

type VaccinesModel struct {
	Code string `json:"code"`
	Msg string `json:msg`
	Data []struct {
		ID int `json:"id"`
		Name string `json:"name"`
		ImgURL string `json:"imgUrl"`
		VaccineCode string `json:"vaccineCode"`
		VaccineName string `json:"vaccineName"`
		Address string `json:"address"`
		StartTime string `json:"startTime"`
		Stock int `json:"stock"`
	} `json:"data"`
	Ok bool `json:"ok"`
	NotOk bool `json:"notOk"`
}