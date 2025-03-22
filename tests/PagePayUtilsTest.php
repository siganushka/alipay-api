<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\Alipay\PagePayUtils;
use Siganushka\ApiFactory\Alipay\SignatureUtils;

class PagePayUtilsTest extends TestCase
{
    protected SignatureUtils $signatureUtils;
    protected PagePayUtils $pagePayUtils;

    protected function setUp(): void
    {
        $this->signatureUtils = new SignatureUtils();
        $this->pagePayUtils = new PagePayUtils($this->signatureUtils);
    }

    public function testResolve(): void
    {
        static::assertEquals([
            'appid' => 'test_appid',
            'alipay_public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'app_private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
            'sign_type' => 'RSA2',
            'out_trade_no' => 'test_out_trade_no',
            'subject' => 'test_subject',
            'total_amount' => 'test_total_amount',
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
            'qr_pay_mode' => null,
            'qrcode_width' => null,
            'goods_detail' => null,
            'time_expire' => null,
            'sub_merchant' => null,
            'extend_params' => null,
            'business_params' => null,
            'promo_params' => null,
            'integration_type' => null,
            'request_from_url' => null,
            'store_id' => null,
            'merchant_order_no' => null,
            'ext_user_info' => null,
            'invoice_info' => null,
            'notify_url' => null,
            'return_url' => null,
            'app_auth_token' => null,
        ], $this->pagePayUtils->resolve([
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'out_trade_no' => 'test_out_trade_no',
            'subject' => 'test_subject',
            'total_amount' => 'test_total_amount',
        ]));

        $dateTimeAsString = '2021-09-27 18:43:00';
        static::assertEquals([
            'appid' => 'test_appid',
            'alipay_public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'app_private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
            'sign_type' => 'RSA',
            'out_trade_no' => 'test_out_trade_no',
            'subject' => 'test_subject',
            'total_amount' => 'test_total_amount',
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
            'qr_pay_mode' => 4,
            'qrcode_width' => 15,
            'goods_detail' => ['test_goods_detail'],
            'time_expire' => '2021-09-27 18:43:00',
            'sub_merchant' => ['test_sub_merchant'],
            'extend_params' => ['test_extend_params'],
            'business_params' => ['test_business_params'],
            'promo_params' => ['test_promo_params'],
            'integration_type' => 'ALIAPP',
            'request_from_url' => 'test_request_from_url',
            'store_id' => 'test_store_id',
            'merchant_order_no' => 'test_merchant_order_no',
            'ext_user_info' => ['test_ext_user_info'],
            'invoice_info' => ['test_invoice_info'],
            'notify_url' => 'test_notify_url',
            'return_url' => 'test_return_url',
            'app_auth_token' => 'test_app_auth_token',
        ], $this->pagePayUtils->resolve([
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'sign_type' => 'RSA',
            'out_trade_no' => 'test_out_trade_no',
            'subject' => 'test_subject',
            'total_amount' => 'test_total_amount',
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
            'qr_pay_mode' => 4,
            'qrcode_width' => 15,
            'goods_detail' => ['test_goods_detail'],
            'time_expire' => new \DateTime($dateTimeAsString),
            'sub_merchant' => ['test_sub_merchant'],
            'extend_params' => ['test_extend_params'],
            'business_params' => ['test_business_params'],
            'promo_params' => ['test_promo_params'],
            'integration_type' => 'ALIAPP',
            'request_from_url' => 'test_request_from_url',
            'store_id' => 'test_store_id',
            'merchant_order_no' => 'test_merchant_order_no',
            'ext_user_info' => ['test_ext_user_info'],
            'invoice_info' => ['test_invoice_info'],
            'notify_url' => 'test_notify_url',
            'return_url' => 'test_return_url',
            'app_auth_token' => 'test_app_auth_token',
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

        $data = $this->pagePayUtils->params($options);
        static::assertSame('test_appid', $data['app_id']);
        static::assertSame('alipay.trade.page.pay', $data['method']);
        static::assertSame('UTF-8', $data['charset']);
        static::assertSame('RSA2', $data['sign_type']);
        static::assertSame('1.0', $data['version']);
        static::assertArrayHasKey('timestamp', $data);
        static::assertArrayHasKey('biz_content', $data);
        static::assertArrayHasKey('sign', $data);

        /** @var string */
        $bizContent = $data['biz_content'];
        static::assertEquals([
            'out_trade_no' => 'test_out_trade_no',
            'subject' => 'test_subject',
            'total_amount' => 'test_total_amount',
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
        ], json_decode($bizContent, true));

        /** @var string */
        $signature = $data['sign'];
        unset($data['sign']);

        $options = [
            'alipay_public_key' => $options['alipay_public_key'],
            'app_private_key' => $options['app_private_key'],
        ];

        static::assertTrue($this->signatureUtils->verify($signature, $data, $options));
    }
}
