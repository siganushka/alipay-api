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
$responseData = [
    'app_id' => 'xxx',
    'out_trade_no' => 'xxx',
    'out_biz_no' => 'xxx',
    'subject' => 'xxx',
    'buyer_id' => 'xxx',
    'total_amount' => 'xxx',
    'trade_status' => 'xxx',
    'sign' => 'xxx',
    'sign_type' => 'xxx',
];

$signature = $responseData['sign'];
unset($responseData['sign'], $responseData['sign_type']);

$isValid = $signatureUtils->verify($signature, ['data' => $responseData]);
// 示例中这里会返回 false，因为示例数据无法计算直实的 sign 签名，需要用支付宝传过来的真实数据。
dump('验证签名结果：', $isValid);
