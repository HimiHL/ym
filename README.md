## 使用前须知

务必替换 `app/Service/Handle.php`文件中的`__construct()`修改Cookie|tk|st三个参数，身份凭证有效期大概为6个小时左右

可使用`Stream`抓包获取

## 使用Phar执行命令

如果不关心代码结构，可以使用Phar执行，可省略第一步，将`php artisan ***` 替换成`php YueMiao.phar ***`即可


## 文件结构

- `.env` 配置文件
- `config.json` Phar包|源码的配置文件，优先级高于`.env`
- `box.json` 打包Phar的配置文件


## 配置信息

```json
{
    "cookie": "", // 抓包的header中的cookie
    "tk": "", // 抓包的header中的tk
    "st": "", // 抓包的header中的st
    "CJY_POWER": false, // 是否自动打码，超级鹰
    "CJY_USER": "", // 超级鹰账号
    "CJY_PASS": "", // 超级鹰密码
    "CJY_ID": "" // 超级鹰唯一ID
}
```

## 功能列表

##### 1. 通过Composer安装拓展包

``` shell
composer install
```

##### 2. 获取成都区可秒杀的医院列表(含预约ID)

``` shell
php artisan vlist
```

##### 3. 获取当前账号绑定的身份列表(含身份ID)

```shell
php artisan mlist
```

##### 4. 执行秒杀

> MemberId 是指通过3步骤获取的身份ID
> VaccineId 是指通过2步骤获取的预约ID

```shell
php artisan ym <MemberId> <VaccineId>
```

##### 5. 执行秒杀【全套流程，耗时较长】

```shell
php artisan nbym
```


