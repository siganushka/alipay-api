# Alipay API

支付宝支付相关接口实现，基于 [siganushka/api-factory](https://github.com/siganushka/api-factory) 抽象层，可快速实现业务。

### 安装

```bash
$ composer require siganushka/alipay-api
```

### 使用

具体使用参考 `./example` 示例目录，运行示例前请复制 `_config.php.dist` 文件为 `_config.php` 并修改相关参数。

该目录包含以下示例：

| 文件                        | 功能                 |
| --------------------------- | -------------------- |
| example/query.php           | 支付宝订单查询       |
| example/refund.php          | 支付宝退款           |
| example/parameter_utils.php | 生成支付宝支付参数   |
| example/signature_uitls.php | 生成、验证支付签名   |
| example/page_pay_utils.php  | 生成网站扫码支付参数 |
| example/notify.php          | 支付异步通知         |

### 框架集成

`Symfony` 项目已集成至 [siganushka/api-factory-bundle](https://github.com/siganushka/api-factory-bundle)，将以服务的形式使用。

安装

```bash
$ composer require siganushka/alipay-api siganushka/api-factory-bundle
```

配置

```yaml
# config/packages/siganushka_api_factory.yaml

siganushka_api_factory:
    alipay:
        appid: your_appid                   # 应用 ID
        app_private_key: your_private_key   # 应用私钥（注意是应用的私钥，与 alipay_public_key 不是一对）
        alipay_public_key: your_public_key  # 支付宝公钥（注意是支付宝的公钥，与 app_private_key 不是一对）
```

使用

```php
// src/Controller/DefaultController.php

use Siganushka\ApiFactory\Alipay\ParameterUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function index(ParameterUtils $utils)
    {
        $options = [
            'subject' => 'APP 支付测试订单',
            'out_trade_no' => uniqid(),
            'total_amount' => '0.01',
        ];

        $parameter = $utils->app($options);
        var_dump($parameter);
    }
}
```

查看所有服务

```bash
$ php bin/console debug:container Siganushka\\ApiFactory\\Alipay
```
