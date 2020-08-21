package main

import (
	"errors"
	"fmt"
	"io/ioutil"
	"miaomiao/log"
	"miaomiao/proxy"
	"miaomiao/request"
	"miaomiao/util"
	"net/url"
	"os"
	"strconv"
	"time"

	"github.com/AlecAivazis/survey/v2"
)

// Token 请求Token
var Token string

func main() {
	util.LogFileName = strconv.FormatInt(util.TimestampNow(), 10)
	log.Danger("日志文件名：" + util.LogFileName + ".log")

	// 获取本地与服务器的时间差
	timeNotice()
	log.Danger("操作方式：方向键上下键选择，回车键确认选中")
	// 获取Token
	Token = questionToken()
	// 打印Token
	log.Success(Token)
	// 选择联系人的操作方式
	for !questionMember() {

	}
	// Token = "wxtoken:08dd80b5572d3f4827dd33b692c4b439_929ca8ef575621b739e8844aeadfc284"
	// 获取预约人信息
	MemberID, MemberIDCard := selectMember()
	// 选择地区
	RegionCode := selectRegion()
	// 选择门诊
	VaccineID, StartTime := questionVaccine(RegionCode)
	// 设置提前时间
	Delay := questionDelay()
	// 设置并发次数
	Concurrent := questionConcurrent()
	// 开始秒杀
	Handle(MemberID, MemberIDCard, VaccineID, StartTime, Token, Delay, Concurrent)
}

func timeNotice() {
	result := request.TimeNow("")
	if result.Ok {
		nowTime := util.TimestampNow()
		timeDiff := nowTime - result.Data
		log.Info(fmt.Sprintf("\n本  地时间: %s\n服务器时间: %s\n本地时间比服务器快了%s毫秒", util.MillTimestampToDate(nowTime), util.MillTimestampToDate(result.Data), strconv.FormatInt(timeDiff, 10)))
	}
}

/** 第一步: 获取Token */
// 获取Token
func questionToken() string {
	methodStr := ""
	methodMap := map[string]bool{
		"电脑自动抓取TK":  false,
		"已通过手机抓到TK": true}
	provincePrompt := &survey.Select{
		Message: "请选择支持的方式",
		Options: []string{"电脑自动抓取TK", "已通过手机抓到TK"}}
	survey.AskOne(provincePrompt, &methodStr)
	if methodMap[methodStr] {
		return inputToken()
	} else {
		installCrt()
		return proxy.Handle()
	}
}

// 输入Token
func inputToken() string {
	Token := ""
	prompt := &survey.Input{
		Message: "输入Token", Help: "使用抓包工具获取`约苗`/`秒苗`请求Header中的TK字段"}
	survey.AskOne(prompt, &Token, survey.WithValidator(survey.Required), survey.WithValidator(func(val interface{}) error {
		if _, ok := val.(string); !ok {
			return errors.New("输入的文本不符合要求")
		}
		return nil
	}))
	return Token
}

// installCrt 安装TLS证书
func installCrt() {
	log.Danger("开始安装TLS证书")
	content := []byte(`-----BEGIN CERTIFICATE-----
MIIFdDCCA1ygAwIBAgIBATANBgkqhkiG9w0BAQsFADBZMQ4wDAYDVQQGEwVDaGlu
YTEPMA0GA1UECBMGRnVKaWFuMQ8wDQYDVQQHEwZYaWFtZW4xDTALBgNVBAoTBE1h
cnMxFjAUBgNVBAMTDWdvLW1pdG0tcHJveHkwIBcNMTgwMzE4MDkwMDQ0WhgPMjA2
ODAzMTgwOTAwNDRaMFkxDjAMBgNVBAYTBUNoaW5hMQ8wDQYDVQQIEwZGdUppYW4x
DzANBgNVBAcTBlhpYW1lbjENMAsGA1UEChMETWFyczEWMBQGA1UEAxMNZ28tbWl0
bS1wcm94eTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBANiuppEbanTv
iCs47AFIAy+AVXDhaInal4fGmN+kG1txO4YPygKGrdjokCZtkL6ZK61izFg6BLX+
p65j8wnAPZPZr3Zu5vlcDM7baO9ddxtnXm/fACPEuMIvgmG7zxE9CeX3LY7tsq10
hg8uKMnYGTy5Ce0hkuYn8Od0yHseGFWCmaCAHIcshbvQFxPGn42X/zWrEHDEgWtG
fOlamBBTSbNza11H8udLkXlr+N+vv/P/eKjpeIf/xzPCdiUOxdD+NHCeeSgho3Sm
P0T6ia4L7MVW0XUg7CseVVh+9TddO6QefmM1+AsWU/ektD+cUMtlWoDXE8idlpoZ
cMVJfq/6Sa9nG280fCPjd4wFLqbR67BHQkoPjQ1vmRgs4xvD04m796dRPpTDepb/
xvTTMcwgAC5tur/E5SHpr8hx9X6xGPfUUMiKyBQlSgLH4V02SjAmScxqt5AWZcT/
syLHg7BhjxwBGoCwcE8zWHCJarQ0t28Z7ptyL3DXPaJ7Vd2CvLJrekvtnm9B28aU
9KOC9JL3DKzFaRrhTYb0VNLfoLV8kRJCzZI6HAwiKcAAEIXi8on6YwqLvEIxo5AL
0gTeIf/nJU2W4OY640fIdwEvcaH4Wj2bKMRaTWvQGM1TJe4hoCN/c3mVopotCb44
IGC5R0XmVImVxZmdyCXJAfY1jYrWHA2ZAgMBAAGjRTBDMA4GA1UdDwEB/wQEAwIB
BjASBgNVHRMBAf8ECDAGAQH/AgEAMB0GA1UdDgQWBBSfjEyzebvckLQu+eZjlmJF
W0/ZmjANBgkqhkiG9w0BAQsFAAOCAgEAXHGvSFwtcqX6lD9rmLTPeViTIan5hG5T
sEWsPp/kI6j579OavwCr7mk4nUjsKFaOEzN78C4k6s4gDWWePoJhlsXB4KefriW4
gWevzwgRTrIlaw3EDb+fCle0HcsCI/wwxDD8eafUxEyqBGrhLJFiUIxvOcD+yP2Q
mX3Z89Pd38Qvkb9zneJdXo2wHMq0MGKlTPdE04rg1OsuPNnSwRhtem9/E4eCtumF
JoQEQtp440wpvrbZljR18Ahd+xNh6dyaD0prnrUEGsUkC1hMb3nUWmw6dZEA5rCv
8aW5ZMm9Jr7pW7yzrm8J4II1bY5v6i7+qvOFDAf1nEnVshcSCiHu6xzgtwoGtsP8
mSOquiWwiceJL6q8xh6nOD3SYm2mZwA1n7Nl3mRJE/RgbwJNkveMrmZ6CKUm3N/x
eqd5yhTLsD7sf3+d4B7i6fAZ+csccWaDuquVI9cXi2OoMKgIFeeVwJ1FCeLY0Nah
nPlNUA0h7xKeDIHtlGsSOng6uiEVVVXGS+j9V6h+Z55AsuOAoHYOBDoXfr0Y4Bww
irCRNyFcDrKoyILOOUiPxoEcclrwUBTB78JxVA8xKTbAh0aZQRZOZOz49qF4gA1d
1riiUHJIG2sD+54UEdFoR5nhZ4/RLGqQ/Kmch5VnPp7De4OzSMd/KkQDWEjR+AA1
CDPlL4gNB6s=
-----END CERTIFICATE-----`)
	err := ioutil.WriteFile("proxy.crt", content, 0777)
	if err != nil {
		fmt.Println(err)
	}

	_, e := proxy.WinCmd("certutil -addstore -f 'ROOT' proxy.crt")
	if e != nil {
		log.Danger("安装证书时出现了错误: " + e.Error())
		exit()
	}
	// 删除文件
	proxy.WinCmd("del proxy.crt")
}

/** 第二步： 选择预约人 */

// 获取预约人ID和身份证号码
func questionMember() bool {
	methodStr := ""
	methodMap := map[string]int{
		"选择联系人": 1,
		"新增联系人": 2,
		"删除联系人": 3,
		"修改联系人": 4}
	provincePrompt := &survey.Select{
		Message: "联系人管理",
		Options: []string{"选择联系人", "新增联系人", "删除联系人", "修改联系人"}}
	survey.AskOne(provincePrompt, &methodStr)
	if methodMap[methodStr] == 1 {
		return true
	} else if methodMap[methodStr] == 2 {
		createMember("")
		return false
	} else if methodMap[methodStr] == 3 {
		memberID, _ := selectMember()
		// 删除联系人
		deleteMember(memberID)
		return false
	} else if methodMap[methodStr] == 3 {
		memberID, _ := selectMember()
		println(memberID)
		// 修改联系人
		createMember(memberID)
		return false
	}
	return true
}

// 删除联系人
func deleteMember(id string) {
	result := request.DelLinkMan(Token, id)
	if result.Ok {
		log.Success("删除成功")
	} else {
		log.Danger(result.Msg)
	}
}

// 创建预约人信息
func createMember(id string) (string, string) {
	var qs = []*survey.Question{
		{
			Name:      "name",
			Prompt:    &survey.Input{Message: "姓名"},
			Validate:  survey.Required,
			Transform: survey.Title,
		},
		{
			Name:   "idcard",
			Prompt: &survey.Input{Message: "身份证号"},
			Validate: func(val interface{}) error {
				if str, ok := val.(string); !ok || len(str) != 18 {
					return errors.New("身份证不合法")
				}
				return nil
			},
		}}

	answers := struct {
		Name   string `survey:"name"`   // survey will match the question and field names
		IDCard string `survey:"idcard"` // if the types don't match, survey will convert it
	}{}

	err := survey.Ask(qs, &answers)
	if err != nil {
		log.Danger(err.Error())
	}

	RegionCode := questionRegion()

	println("\r正在创建预约人信息: " + answers.Name)

	temp := string([]byte(answers.IDCard)[6:14])
	birthday := fmt.Sprintf("%s-%s-%s", string([]byte(temp)[:4]), string([]byte(temp)[4:6]), string([]byte(temp)[6:]))

	result := request.SaveLinkMan(Token, url.QueryEscape(answers.Name), answers.IDCard, RegionCode, birthday, id)
	if !result.Ok {
		log.Danger(result.Msg)
		exit()
	}
	log.Success("创建成功！")
	return result.Data, answers.IDCard
}

// 选择预约人
func selectMember() (string, string) {
	// 选择身份信息
	memberList := request.LinkMans(Token)
	if !memberList.Ok {
		log.Danger(memberList.Msg)
		exit()
	}
	sexMap := map[int]string{
		1: "男",
		2: "女",
	}
	memberNameList := make([]string, 0)
	memberMapList := make(map[string]int)
	for i := 0; i < len(memberList.Data); i++ {
		name := fmt.Sprintf("[%s]%s/%s", sexMap[memberList.Data[i].Sex], memberList.Data[i].Name, memberList.Data[i].IDCardNo)
		memberNameList = append(memberNameList, name)
		memberMapList[name] = i
	}
	memberName := ""
	memberPrompt := &survey.Select{
		Message: "请选择预约人:",
		Options: memberNameList,
	}
	survey.AskOne(memberPrompt, &memberName)
	index := memberMapList[memberName]
	// 检测该预约人是否为男性，强制修改为女性
	if memberList.Data[index].Sex == 1 {
		result := request.SaveLinkMan(
			Token,
			memberList.Data[index].Name,
			memberList.Data[index].IDCardNo,
			memberList.Data[index].RegionCode,
			memberList.Data[index].Birthday,
			strconv.Itoa(memberList.Data[index].ID))
		if result.Ok {
			log.Info("检测到该联系人性别为男性，已强制修改为女性")
		} else {
			log.Danger("修改性别错误：" + result.Msg)
		}
	}
	return strconv.Itoa(memberList.Data[index].ID), memberList.Data[index].IDCardNo
}

// 获取地区Code
func questionRegion() string {
	regionCode := "0"
	regionFunc := func(provinceCode string) string {
		// 获取省份列表
		provinceList := request.Regions(Token, provinceCode)
		if !provinceList.Ok {
			log.Danger(provinceList.Msg)
			exit()
		}
		provinceNameList := make([]string, 0)
		provinceMapList := make(map[string]string)
		for i := 0; i < len(provinceList.Data); i++ {
			provinceNameList = append(provinceNameList, provinceList.Data[i].Name)
			provinceMapList[provinceList.Data[i].Name] = provinceList.Data[i].Value
		}
		provinceName := ""
		provincePrompt := &survey.Select{
			Message: "请选择省份:",
			Options: provinceNameList,
		}
		survey.AskOne(provincePrompt, &provinceName)
		provinceCode, _ = provinceMapList[provinceName]
		return provinceCode
	}
	provinceCode := regionFunc(regionCode)
	cityCode := regionFunc(provinceCode)
	return cityCode
}

func selectRegion() string {
	methodStr := ""
	methodMap := map[string]bool{
		"四川成都": false,
		"其他地区": true}
	provincePrompt := &survey.Select{
		Message: "请选择地区",
		Options: []string{"四川成都", "其他地区"}}
	survey.AskOne(provincePrompt, &methodStr)
	if methodMap[methodStr] {
		return questionRegion()
	} else {
		return "5101"
	}
}

// 获取秒杀的地区列表
func questionVaccine(regionCode string) (string, string) {
	// 选择疫苗
	vaccineList := request.Vaccines(Token, regionCode)
	if !vaccineList.Ok {
		log.Danger(vaccineList.Msg)
		exit()
	}
	if len(vaccineList.Data) == 0 {
		log.Danger("没有可以秒杀的门诊")
		exit()
	}
	vaccineMapList := make(map[string]int)
	vaccineNameList := make([]string, 0)
	for i := 0; i < len(vaccineList.Data); i++ {
		key := vaccineList.Data[i].Name + "[" + vaccineList.Data[i].StartTime + "]"
		vaccineNameList = append(vaccineNameList, key)
		vaccineMapList[key] = i
	}
	vaccineName := ""
	vaccinePrompt := &survey.Select{
		Message: "请选择门诊:",
		Options: vaccineNameList,
	}
	survey.AskOne(vaccinePrompt, &vaccineName)
	vaccineID := vaccineList.Data[vaccineMapList[vaccineName]].ID
	vaccineStartTime := vaccineList.Data[vaccineMapList[vaccineName]].StartTime
	return strconv.Itoa(vaccineID), vaccineStartTime
}

// 获取提前时间
func questionDelay() int {
	// 询问Token
	str := ""
	prompt := &survey.Input{
		Message: "提前秒杀(毫秒)", Help: "秒杀开始后提前指定时长后再执行秒杀"}
	survey.AskOne(prompt, &str, survey.WithValidator(survey.Required), survey.WithValidator(func(val interface{}) error {
		str, ok := val.(string)
		if !ok {
			return errors.New("不是有效的输入")
		}
		if _, ok := strconv.Atoi(str); ok != nil {
			return errors.New("请输入有效的数字")
		}
		return nil
	}))
	delay, _ := strconv.Atoi(str)
	return delay
}

// 获取并发次数
func questionConcurrent() int {
	str := ""
	prompt := &survey.Input{
		Message: "并发次数", Help: "并发次数"}
	survey.AskOne(prompt, &str, survey.WithValidator(survey.Required), survey.WithValidator(func(val interface{}) error {
		str, ok := val.(string)
		if !ok {
			return errors.New("不是有效的输入")
		}
		if _, ok := strconv.Atoi(str); ok != nil {
			return errors.New("请输入有效的数字")
		}
		return nil
	}))
	concurrent, _ := strconv.Atoi(str)
	return concurrent
}

// 确认订单
func confirmOrder(VaccineID string, OrderID string) {
	daysResult := request.SubscribeDays(Token, VaccineID, OrderID)
	if daysResult.Code == "0000" {
		// 选择日期
		DaysList := make([]string, 0)
		DayMap := make(map[string]int)
		for i := 0; i < len(daysResult.Data); i++ {
			name := fmt.Sprintf("%s，剩余: %d", daysResult.Data[i].Day, daysResult.Data[i].Total)
			DaysList = append(DaysList, name)
			DayMap[name] = i
		}

		ChooseDayText := ""
		vaccinePrompt := &survey.Select{
			Message: "请选择日期:",
			Options: DaysList,
		}
		survey.AskOne(vaccinePrompt, &ChooseDayText)
		ChooseDay := daysResult.Data[DayMap[ChooseDayText]].Day

		// 选择时间
		TimesResult := request.SubscribeDayTimes(Token, VaccineID, OrderID, ChooseDay)
		if TimesResult.Code == "0000" {
			// 选择日期
			TimesList := make([]string, 0)
			TimeMap := make(map[string]int)
			for i := 0; i < len(TimesResult.Data); i++ {
				name := fmt.Sprintf("[%d]%s - %s", TimesResult.Data[i].MaxSub, TimesResult.Data[i].StartTime, TimesResult.Data[i].EndTime)
				TimesList = append(TimesList, name)
				TimeMap[name] = i
			}

			ChooseTimeText := ""
			vaccinePrompt := &survey.Select{
				Message: "请选择时间段:",
				Options: TimesList,
			}
			survey.AskOne(vaccinePrompt, &ChooseTimeText)
			Wid := TimesResult.Data[TimeMap[ChooseTimeText]].Wid

			// 提交
			result := request.SubmitDateTime(Token, VaccineID, OrderID, ChooseDay, Wid)
			if result.Code == "0000" {
				log.Info("订单确认成功！")
			} else {
				log.Danger(result.Msg + "\r 请前往小程序确认订单")
			}
		} else {
			log.Danger(TimesResult.Msg + "\r 请前往小程序确认订单")
		}
	} else {
		log.Danger(daysResult.Msg + "\r 请前往小程序确认订单")
	}
}

// Handle 执行任务
func Handle(MemberID string, MemberIDCard string, VaccineID string, startTime string, TK string, Delay int, ConcurrentTimes int) {
	// 任务如果出现了异常，就做一下取消任务的回调
	defer func() {
		if r := recover(); r != nil {
			log.Danger("任务异常退出")
		}
	}()

	startTimeMillSecond := util.TimestampFormat(startTime)
	log.Danger(fmt.Sprintf("提前(%s)毫秒执行秒杀：", strconv.Itoa(Delay)))
	log.Danger(fmt.Sprintf("并发秒杀(%s)次", strconv.Itoa(ConcurrentTimes)))
	log.Danger(fmt.Sprintf("开始时间：%s", startTime))
	log.Danger(fmt.Sprintf("开始毫秒时间戳：%s", strconv.FormatInt(startTimeMillSecond, 10)))

	// 开始倒计时
	for range time.Tick(5 * time.Millisecond) {
		// 如果当前时间+提前时间已经超过了秒杀时间，跳出循环
		if util.TimestampNow()+int64(Delay) >= startTimeMillSecond {
			break
		} else {
			fmt.Printf("\r%s", time.Now().Format("2006-01-02 15:04:05.000000"))
		}
	}

	// 获取库存
	/**
		Stock这个接口是否可以提前调用是一个问题

		既然采用了md5不可逆的加密，肯定在stock接口里，服务器端做了缓存，记录了最后一次请求时间

		// 问题是服务器端会不会去校验这个请求时间是否超过秒杀时间(比如12点开始秒杀，11点59分59秒之前的st都无效)
		// 等抢到一个了再试试

	**/
	log.Info("开始秒杀")
	stockResult := request.Stock(Token, VaccineID)
	if stockResult.Ok {
		// 开始签名
		salt := "ux$ad70*b"
		/**
		签名算法可能有问题
		因为参与签名的三个参数在理论上来说都是int，所以有可能是三者之和
		但是！因为VaccineID是从页面路由上拿到的，所以VaccineID是string，在JS弱类型语言中，第一个是string，所以会变成连接字符串
		暂时不确定是连接字符串还是求和
		*/
		sign := util.Md5(util.Md5(VaccineID+MemberID+stockResult.Data.St) + salt)
		log.Info(fmt.Sprintf("签名字符串: %s + %s + %s + %s = %s", VaccineID, MemberID, stockResult.Data.St, salt, sign))
		results := request.MultiSubscribe(Token, VaccineID, MemberID, MemberIDCard, ConcurrentTimes, sign)
		for i := range results {
			if results[i].Ok {
				log.Success("秒杀成功，即将开始确认订单")
				// 开始选择日期
				orderID := results[i].Data
				confirmOrder(VaccineID, orderID)
				break
			} else {
				log.Danger(results[i].Msg)
			}
		}
		log.Info(fmt.Sprintf("当前库存: %s, 当前时间: %s", stockResult.Data.Stock, stockResult.Data.St))
	} else {
		log.Danger(stockResult.Msg)
	}

	// // 开始执行秒杀
	// var wg sync.WaitGroup
	// for i := 0; i < ConcurrentTimes; i++ {
	// 	wg.Add(1)
	// 	go func() {
	// 		defer wg.Done()
	// 		subscribeResult := request.Subscribe(Token, VaccineID, MemberID, MemberIDCard)
	// 		if subscribeResult.Ok {
	// 			log.Info("秒杀成功")
	// 		} else {
	// 			log.Danger("秒杀失败")
	// 		}
	// 	}()
	// }
	// wg.Wait()
}

func exit() {
	os.Exit(1)
}
