<?php

declare(strict_types=1);

use Siganushka\ApiFactory\Alipay\SignatureUtils;

require __DIR__.'/_autoload.php';

$signatureUtils = new SignatureUtils();
$signatureUtils->extend($configurationExtension);

/**
 * 一、根据请求的原始数据生成签名（注意，生成签名使用的是“应用私钥”）.
 */
$requestData = [
    'foo' => 'bar',
];

$signature = $signatureUtils->generate(['data' => $requestData]);
dump('生成签名结果：', $signature);

/**
 * 二、根据支付宝返回的数据验证签名（注意：支付宝返回数据里的 sign 是支付宝的签名，所以需要使用“支付宝公钥”来验证）.
 */
$responseData = json_decode('{"gmt_create":"2022-09-23 00:40:26","charset":"UTF-8","gmt_payment":"2022-09-23 00:40:27","seller_email":"xiejianting@weilaixiansen.com","notify_time":"2022-09-24 01:09:21","subject":"2226502419903540-测试","gmt_refund":"2022-09-23 00:40:42.662","sign":"fo3aGduuqcWNRjlJW+KBwcTh0EJhAR8CSK6ZnTDz7nAJI+YXxVgqz+aEL2VWCjjLpvqAzy4xYiSW/UgMLYKGyYz3SKzyIpvE3Nq61mb+IKhOVqgOgcB9VIoRYK7EmKxQ3m8CKnZFx9MQGiqV5cJxaOtpQvaR+GWDuELay37uBKCdzckNPX878ThKFb4hMIMZSWlhaJrwLTBaLBUFuCXqQpA44+/YO3ZLcC/iZ+OxLtQIcQdYHpbPGym84kmcCAum+BwoXoI0CS8VN43BfTIHrWF2Pcey3mD1hKzUjfxVN+hBeEPoFnb+faYMdJ+0sRyPE60tpbhVJ0V7/QMQYnoicA==","buyer_id":"2088332960215614","out_biz_no":"2226502419903540","version":"1.0","notify_id":"2022092300222004042015611448041400","notify_type":"trade_status_sync","out_trade_no":"2226502419903540","total_amount":"0.01","trade_status":"TRADE_CLOSED","refund_fee":"0.01","trade_no":"2022092322001415611423953094","auth_app_id":"2021003146603265","gmt_close":"2022-09-23 00:40:42","buyer_logon_id":"186****0012","app_id":"2021003146603265","sign_type":"RSA2","seller_id":"2088141463553859"}', true);

$signature = $responseData['sign'];
unset($responseData['sign'], $responseData['sign_type']);

$isSignatureValid = $signatureUtils->verify($signature, ['data' => $responseData]);
dump('验证签名结果：', $isSignatureValid);
