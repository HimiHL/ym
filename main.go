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
	"os/signal"
	"strconv"
	"strings"
	"syscall"
	"time"

	"github.com/AlecAivazis/survey/v2"
)

var tk string

func main() {
	// 获取本地与服务器的时间差
	timeNotice()
	// 监听退出
	listenExit()

	log.Danger("操作方式：方向键上下键选择，回车键确认选中")
	// 获取Token
	tk = questionToken()
	// 获取预约人信息
	MemberID, MemberIDCard := questionMember()
	// 选择地区
	RegionCode := questionRegion()
	// 选择门诊
	VaccineID, StartTime := questionVaccine(RegionCode)
	// 设置提前时间
	Delay := questionDelay()
	// 设置并发次数
	Concurrent := questionConcurrent()
	// 开始秒杀
	Handle(MemberID, MemberIDCard, VaccineID, StartTime, tk, Delay, Concurrent)
}

func timeNotice() {
	result := request.TimeNow("")
	if result.Ok {
		nowTime := util.TimestampNow()
		timeDiff := nowTime - result.Data
		log.Info(fmt.Sprintf("\n本  地时间: %s\n服务器时间: %s\n本地时间比服务器快了%s毫秒", util.MillTimestampToDate(nowTime), util.MillTimestampToDate(result.Data), strconv.FormatInt(timeDiff, 10)))
	}
}

func listenExit() {
	//创建监听退出chan
	c := make(chan os.Signal)
	//监听指定信号 ctrl+c kill
	signal.Notify(c, syscall.SIGHUP, syscall.SIGINT, syscall.SIGTERM, syscall.SIGQUIT, syscall.SIGUSR1, syscall.SIGUSR2)
	go func() {
		for s := range c {
			switch s {
			case syscall.SIGHUP, syscall.SIGINT, syscall.SIGTERM, syscall.SIGQUIT:
				proxy.StopProxy()
				proxy.CloseNetworkProxy()
				os.Exit(0)
			case syscall.SIGUSR1:
				fmt.Println("usr1", s)
			case syscall.SIGUSR2:
				fmt.Println("usr2", s)
			default:
				fmt.Println("other", s)
			}
		}
	}()
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
		network := selectNetwork()
		return proxy.Handle(network)
	}
}

// 选择网卡
func selectNetwork() string {
	log.Info("请输入密码(如需要)以获取网络设备信息：")
	result, _ := proxy.Cmd("sudo networksetup -listallnetworkservices")
	arr := strings.Split(result, "\n")
	network := ""
	networkPrompt := &survey.Select{
		Message: "请选择正在使用网络的设备:",
		Options: arr[1:],
	}
	survey.AskOne(networkPrompt, &network)
	// 开始检测是否已有代理

	status, _ := proxy.Cmd("scutil --proxy | grep -c 'Enable\\s*:\\s*1'")
	line := strings.Split(status, "\n")
	if line[0] == "1" {
		log.Danger("当前网络已经开启了其他代理，无法启动")
		exit()
	}

	return strings.Trim(network, " ")
}

// 输入Token
func inputToken() string {
	tk := ""
	prompt := &survey.Input{
		Message: "输入Token", Help: "使用抓包工具获取`约苗`/`秒苗`请求Header中的TK字段"}
	survey.AskOne(prompt, &tk, survey.WithValidator(survey.Required), survey.WithValidator(func(val interface{}) error {
		if _, ok := val.(string); !ok {
			return errors.New("输入的文本不符合要求")
		}
		return nil
	}))
	return tk
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

	_, e := proxy.Cmd("sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain proxy.crt")
	if e != nil {
		log.Danger("安装证书时出现了错误: " + e.Error())
		exit()
	}
}

/** 第二步： 选择预约人 */

// 获取预约人ID和身份证号码
func questionMember() (string, string) {
	methodStr := ""
	methodMap := map[string]bool{
		"选择预约人": false,
		"新增预约人": true}
	provincePrompt := &survey.Select{
		Message: "是否新增预约人",
		Options: []string{"新增预约人", "选择预约人"}}
	survey.AskOne(provincePrompt, &methodStr)
	if methodMap[methodStr] {
		createMember()
	}
	memberCode, memberIDCard := selectMember()
	return memberCode, memberIDCard
}

// 创建预约人信息
func createMember() (string, string) {
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

	result := request.SaveLinkMan(tk, url.QueryEscape(answers.Name), answers.IDCard, RegionCode, birthday)
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
	memberList := request.LinkMans(tk)
	if !memberList.Ok {
		log.Danger(memberList.Msg)
		exit()
	}
	memberNameList := make([]string, 0)
	memberMapList := make(map[string]string)
	memberIDCardList := make(map[string]string)
	for i := 0; i < len(memberList.Data); i++ {
		name := fmt.Sprintf("%s[%s]", memberList.Data[i].Name, memberList.Data[i].IDCardNo)
		memberNameList = append(memberNameList, name)
		memberIDCardList[name] = memberList.Data[i].IDCardNo
		memberMapList[name] = memberList.Data[i].Name
	}
	memberName := ""
	memberPrompt := &survey.Select{
		Message: "请选择预约人:",
		Options: memberNameList,
	}
	survey.AskOne(memberPrompt, &memberName)
	memberCode, _ := memberMapList[memberName]
	memberIDCard, _ := memberIDCardList[memberName]
	return memberCode, memberIDCard
}

// 获取地区Code
func questionRegion() string {
	regionCode := "0"
	regionFunc := func(provinceCode string) string {
		// 获取省份列表
		provinceList := request.Regions(tk, provinceCode)
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

// 获取秒杀的地区列表
func questionVaccine(regionCode string) (string, string) {
	// 选择疫苗
	vaccineList := request.Vaccines(tk, regionCode)
	if !vaccineList.Ok {
		log.Danger(vaccineList.Msg)
		exit()
	}
	if len(vaccineList.Data) == 0 {
		log.Danger("没有可以秒杀的门诊")
		exit()
	}
	vaccineMapList := make(map[string]string)
	vaccineStartTimeMapList := make(map[string]string)
	vaccineNameList := make([]string, 0)
	for i := 0; i < len(vaccineList.Data); i++ {
		key := vaccineList.Data[i].Name + "[" + vaccineList.Data[i].StartTime + "]"
		vaccineNameList = append(vaccineNameList, key)
		vaccineMapList[key] = string(vaccineList.Data[i].ID)
		vaccineStartTimeMapList[key] = vaccineList.Data[i].StartTime
	}
	vaccineName := ""
	vaccinePrompt := &survey.Select{
		Message: "请选择门诊:",
		Options: vaccineNameList,
	}
	survey.AskOne(vaccinePrompt, &vaccineName)
	vaccineID, _ := vaccineMapList[vaccineName]
	vaccineStartTime, _ := vaccineStartTimeMapList[vaccineName]
	return vaccineID, vaccineStartTime
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
	for range time.Tick(10 * time.Millisecond) {
		// 如果当前时间+提前时间已经超过了秒杀时间，跳出循环
		if util.TimestampNow()+int64(Delay) >= startTimeMillSecond {
			break
		} else {
			fmt.Printf("\r%s", time.Now().Format("2006-01-02 15:04:05.000000"))
		}
	}

	results := request.MultiSubscribe(tk, VaccineID, MemberID, MemberIDCard, ConcurrentTimes)
	for i := range results {
		if results[i].Ok {
			log.Success("秒杀成功")
		} else {
			log.Danger(results[i].Msg)
		}
	}

	// // 开始执行秒杀
	// var wg sync.WaitGroup
	// for i := 0; i < ConcurrentTimes; i++ {
	// 	wg.Add(1)
	// 	go func() {
	// 		defer wg.Done()
	// 		subscribeResult := request.Subscribe(tk, VaccineID, MemberID, MemberIDCard)
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
