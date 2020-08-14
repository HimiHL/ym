package proxy

import (
	"encoding/hex"
	"fmt"
	"io/ioutil"
	"strconv"

	"golang.org/x/sys/windows/registry"
)

func openNetworkProxyWin() {
	backup()
	SetProxy("127.0.0.1:9998")
}

func CloseNetworkProxyWin() {
	text, _ := ioutil.ReadFile("backup.txt")
	setProxy("DefaultConnectionSettings", string(text))
	setProxy("SavedLegacySettings", string(text))
	WinCmd("del backup.txt")
}

func backup() {
	// 先获取注册表原本的信息
	key, err := registry.OpenKey(registry.CURRENT_USER, `Software\Microsoft\Windows\CurrentVersion\Internet Settings\Connections`, registry.ALL_ACCESS)
	defer key.Close()
	if err != nil {
		fmt.Println("没有获取到注册表信息")
	}

	s, _, _ := key.GetBinaryValue(`DefaultConnectionSettings`)
	d := ""
	for _, x := range s {
		d = d + fmt.Sprintf("%02x", x)
	}
	// fmt.Println("[debug]原注册表信息:" + d)
	data := []byte(d)
	err = ioutil.WriteFile("backup.txt", data, 0666)
	if err != nil {
		fmt.Println("备份注册表失败：" + err.Error())
	}
}

// SetProxy 设置代理
func SetProxy(ip string) {
	key, err := registry.OpenKey(registry.CURRENT_USER, `Software\Microsoft\Windows\CurrentVersion\Internet Settings\Connections`, registry.ALL_ACCESS)
	defer key.Close()
	if err != nil {
		fmt.Println("无法打开注册表信息")
	}

	s, _, _ := key.GetBinaryValue(`DefaultConnectionSettings`)
	d := ""
	for _, x := range s {
		d = d + fmt.Sprintf("%02x", x)
	}
	// TODO 为了防止无法回滚，存入文件
	p1 := d[:16]                           //460000003A160000
	switchProxy := "03"                    // d[16:18]
	leng := fmt.Sprintf("%02x", (len(ip))) //如果ip长度小于16，前面补0
	iphex := hex.EncodeToString([]byte(ip))
	data := p1 + switchProxy + "000000" + leng + "000000" + iphex + "070000003c6c6f63616c3e2b000000"
	setProxy("DefaultConnectionSettings", data)
	setProxy("SavedLegacySettings", data)
	fmt.Println("代理设置成功")
}

//传入要修改keyname和16进制字符串
func setProxy(keyname string, data string) {
	key, err := registry.OpenKey(registry.CURRENT_USER, `Software\Microsoft\Windows\CurrentVersion\Internet Settings\Connections`, registry.ALL_ACCESS)
	defer key.Close()
	if err != nil {
		panic(err)
	}

	//把16进制字符串转为byte切片
	bytedata := []byte{}
	for i := 0; i < len(data)-2; i = i + 2 {
		t := data[i : i+2]
		n, err := strconv.ParseUint(t, 16, 32)
		if err != nil {
			panic(err)
		}
		n2 := byte(n)
		bytedata = append(bytedata, n2)
	}

	err = key.SetBinaryValue(keyname, bytedata)
	if err != nil {
		panic(err)
	}
}

func clear() {
	data := "460000003016000009000000010000003a050000006c6f63616c000000000100000000000000000000000000000000000000000000000000000000000000"
	setProxy("DefaultConnectionSettings", data)
	setProxy("SavedLegacySettings", data)
}
