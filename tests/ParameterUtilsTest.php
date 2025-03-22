<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\Alipay\ParameterUtils;
use Siganushka\ApiFactory\Alipay\SignatureUtils;

class ParameterUtilsTest extends TestCase
{
    protected SignatureUtils $signatureUtils;
    protected ParameterUtils $parameterUtils;

    protected function setUp(): void
    {
        $this->signatureUtils = new SignatureUtils();
        $this->parameterUtils = new ParameterUtils($this->signatureUtils);
    }

    public function testResolve(): void
    {
        static::assertEquals([
            'appid' => 'test_appid',
            'alipay_public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'app_private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
            'sign_type' => 'RSA2',
            'notify_url' => null,
            'app_auth_token' => null,
            'subject' => 'test_subject',
            'out_trade_no' => 'test_out_trade_no',
            'total_amount' => 'test_total_amount',
            'total_amount_as_cents' => null,
            'product_code' => null,
            'body' => null,
            'goods_detail' => null,
            'time_expire' => null,
            'extend_params' => null,
            'passback_params' => null,
            'agreement_sign_params' => null,
            'enable_pay_channels' => null,
            'disable_pay_channels' => null,
            'specified_channel' => null,
            'merchant_order_no' => null,
            'ext_user_info' => null,
            'query_options' => null,
        ], $this->parameterUtils->resolve([
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'subject' => 'test_subject',
            'out_trade_no' => 'test_out_trade_no',
            'total_amount' => 'test_total_amount',
        ]));

        $dateTimeAsString = '2021-09-27 18:43:00';
        static::assertEquals([
            'appid' => 'test_appid',
            'alipay_public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'app_private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
            'sign_type' => 'RSA',
            'notify_url' => 'test_notify_url',
            'app_auth_token' => 'test_app_auth_token',
            'subject' => 'test_subject',
            'out_trade_no' => 'test_out_trade_no',
            'total_amount' => '0.12',
            'total_amount_as_cents' => 12,
            'product_code' => 'CYCLE_PAY_AUTH',
            'body' => 'test_body',
            'goods_detail' => ['test_goods_detail'],
            'time_expire' => $dateTimeAsString,
            'extend_params' => ['test_extend_params'],
            'passback_params' => 'test_passback_params',
            'agreement_sign_params' => ['test_agreement_sign_params'],
            'enable_pay_channels' => 'test_enable_pay_channels',
            'disable_pay_channels' => 'test_disable_pay_channels',
            'specified_channel' => 'test_specified_channel',
            'merchant_order_no' => 'test_merchant_order_no',
            'ext_user_info' => ['test_ext_user_info'],
            'query_options' => ['test_query_options'],
        ], $this->parameterUtils->resolve([
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'sign_type' => 'RSA',
            'notify_url' => 'test_notify_url',
            'app_auth_token' => 'test_app_auth_token',
            'subject' => 'test_subject',
            'out_trade_no' => 'test_out_trade_no',
            'total_amount_as_cents' => 12,
            'product_code' => 'CYCLE_PAY_AUTH',
            'body' => 'test_body',
            'goods_detail' => ['test_goods_detail'],
            'time_expire' => new \DateTime($dateTimeAsString),
            'extend_params' => ['test_extend_params'],
            'passback_params' => 'test_passback_params',
            'agreement_sign_params' => ['test_agreement_sign_params'],
            'enable_pay_channels' => 'test_enable_pay_channels',
            'disable_pay_channels' => 'test_disable_pay_channels',
            'specified_channel' => 'test_specified_channel',
            'merchant_order_no' => 'test_merchant_order_no',
            'ext_user_info' => ['test_ext_user_info'],
            'query_options' => ['test_query_options'],
        ]));
    }

    public function testApp(): void
    {
        $options = [
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'subject' => 'test_subject',
            'out_trade_no' => 'test_out_trade_no',
            'total_amount' => 'test_total_amount',
        ];

        $data = $this->parameterUtils->app($options);

        parse_str($data, $parsed);
        static::assertSame('test_appid', $parsed['app_id']);
        static::assertSame('alipay.trade.app.pay', $parsed['method']);
        static::assertSame('UTF-8', $parsed['charset']);
        static::assertSame('RSA2', $parsed['sign_type']);
        static::assertSame('1.0', $parsed['version']);
        static::assertArrayHasKey('timestamp', $parsed);
        static::assertArrayHasKey('biz_content', $parsed);
        static::assertArrayHasKey('sign', $parsed);

        /** @var string */
        $bizContent = $parsed['biz_content'];
        static::assertEquals([
            'subject' => 'test_subject',
            'out_trade_no' => 'test_out_trade_no',
            'total_amount' => 'test_total_amount',
        ], json_decode($bizContent, true));

        /** @var string */
        $signature = $parsed['sign'];
        unset($parsed['sign']);

        $options = [
            'alipay_public_key' => $options['alipay_public_key'],
            'app_private_key' => $options['app_private_key'],
        ];

        static::assertTrue($this->signatureUtils->verify($signature, $parsed, $options));
    }
}
