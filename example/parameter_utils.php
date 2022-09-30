<?php

declare(strict_types=1);

use Siganushka\ApiFactory\Alipay\ConfigurationExtension;
use Siganushka\ApiFactory\Alipay\ParameterUtils;

require __DIR__.'/_autoload.php';

$parameterUtils = new ParameterUtils();
$parameterUtils->extend(new ConfigurationExtension($configuration));

$options = [
    'subject' => '测试订单',
    'out_trade_no' => uniqid(),
    'total_amount' => '0.01',
];

$appParameter = $parameterUtils->app($options);
dump('APP 支付参数：', $appParameter);
