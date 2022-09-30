<?php

declare(strict_types=1);

use Siganushka\ApiFactory\Alipay\ConfigurationExtension;
use Siganushka\ApiFactory\Alipay\Query;

require __DIR__.'/_autoload.php';

$options = [
    // 'trade_no' => '2022092322001415611423953094',
    'out_trade_no' => '2226502419903540',
];

$request = new Query();
$request->extend(new ConfigurationExtension($configuration));

$result = $request->send($options);
dump('订单查询结果：', $result);
