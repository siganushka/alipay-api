<?php

declare(strict_types=1);

use Siganushka\ApiFactory\Alipay\ConfigurationExtension;
use Siganushka\ApiFactory\Alipay\PagePayUtils;

require __DIR__.'/_autoload.php';

$options = [
    'out_trade_no' => '67ca8a3341574',
    'total_amount' => '0.01',
    'subject' => '测试订单',
    // 'qr_pay_mode' => 2,
    // 'notify_url' => 'http://localhost/xxx'
];

$pagePayUtils = new PagePayUtils();
$pagePayUtils->extend(new ConfigurationExtension($configuration));

// 生成网站扫码支付参数
// $result = $pagePayUtils->params($options);
// dd($result);

// 生成网站扫码支付参数并返回 URL（前端直接跳转即可支付）
$result = $pagePayUtils->url($options);
header(sprintf('Location: %s', $result));
