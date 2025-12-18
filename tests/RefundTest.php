<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\Alipay\Refund;
use Siganushka\ApiFactory\Alipay\SignatureUtils;
use Siganushka\ApiFactory\Exception\ParseResponseException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RefundTest extends TestCase
{
    protected Refund $request;
    protected SignatureUtils $signatureUtils;

    protected function setUp(): void
    {
        $this->signatureUtils = new SignatureUtils();
        $this->request = new Refund(null, $this->signatureUtils);
    }

    public function testResolve(): void
    {
        $options = [
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'trade_no' => 'test_trade_no',
            'refund_amount' => '314159.26',
        ];

        static::assertEquals([
            'appid' => $options['appid'],
            'alipay_public_key' => file_get_contents($options['alipay_public_key']),
            'app_private_key' => file_get_contents($options['app_private_key']),
            'sign_type' => 'RSA2',
            'app_auth_token' => null,
            'trade_no' => $options['trade_no'],
            'out_trade_no' => null,
            'refund_amount' => '314159.26',
            'refund_amount_as_cents' => null,
            'refund_reason' => null,
            'out_request_no' => null,
            'refund_royalty_parameters' => null,
            'query_options' => null,
        ], $this->request->resolve($options));

        unset($options['refund_amount']);
        static::assertEquals([
            'appid' => $options['appid'],
            'alipay_public_key' => file_get_contents($options['alipay_public_key']),
            'app_private_key' => file_get_contents($options['app_private_key']),
            'sign_type' => 'RSA',
            'app_auth_token' => 'test_app_auth_token',
            'trade_no' => $options['trade_no'],
            'out_trade_no' => 'test_out_trade_no',
            'refund_amount' => '314159.26',
            'refund_amount_as_cents' => 31415926,
            'refund_reason' => 'test_refund_reason',
            'out_request_no' => 'test_out_request_no',
            'refund_royalty_parameters' => ['test_refund_royalty_parameters'],
            'query_options' => ['test_query_options'],
        ], $this->request->resolve($options + [
            'sign_type' => 'RSA',
            'app_auth_token' => 'test_app_auth_token',
            'out_trade_no' => 'test_out_trade_no',
            'refund_amount_as_cents' => 31415926,
            'refund_reason' => 'test_refund_reason',
            'out_request_no' => 'test_out_request_no',
            'refund_royalty_parameters' => ['test_refund_royalty_parameters'],
            'query_options' => ['test_query_options'],
        ]));
    }

    public function testBuild(): void
    {
        $options = [
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'trade_no' => 'test_trade_no',
            'refund_amount' => '0.01',
        ];

        $requestOptions = $this->request->build($options);
        static::assertSame('GET', $requestOptions->getMethod());
        static::assertSame('https://openapi.alipay.com/gateway.do', $requestOptions->getUrl());

        $query = $requestOptions->toArray()['query'];
        static::assertSame('test_appid', $query['app_id']);
        static::assertSame('alipay.trade.refund', $query['method']);
        static::assertSame('UTF-8', $query['charset']);
        static::assertSame('RSA2', $query['sign_type']);
        static::assertSame('1.0', $query['version']);
        static::assertArrayHasKey('timestamp', $query);
        static::assertArrayHasKey('biz_content', $query);
        static::assertArrayHasKey('sign', $query);

        $signature = $query['sign'];
        unset($query['sign']);

        static::assertTrue($this->signatureUtils->verify($signature, $query, [
            'alipay_public_key' => $options['alipay_public_key'],
            'app_private_key' => $options['app_private_key'],
        ]));

        $bizContent = json_decode($query['biz_content'], true);
        static::assertEquals([
            'trade_no' => $options['trade_no'],
            'refund_amount' => '0.01',
        ], $bizContent);

        unset($options['refund_amount']);
        $requestOptions = $this->request->build($options + [
            'sign_type' => 'RSA',
            'out_trade_no' => 'test_out_trade_no',
            'refund_amount_as_cents' => 31415926,
        ]);

        $query = $requestOptions->toArray()['query'];
        static::assertSame('RSA', $query['sign_type']);

        $signature = $query['sign'];
        unset($query['sign']);

        static::assertTrue($this->signatureUtils->verify($signature, $query, [
            'alipay_public_key' => $options['alipay_public_key'],
            'app_private_key' => $options['app_private_key'],
            'sign_type' => 'RSA',
        ]));

        $bizContent = json_decode($query['biz_content'], true);
        static::assertEquals([
            'trade_no' => $options['trade_no'],
            'out_trade_no' => 'test_out_trade_no',
            'refund_amount' => '314159.26',
        ], $bizContent);
    }

    public function testSend(): void
    {
        $options = [
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'trade_no' => 'test_trade_no',
            'refund_amount' => '0.01',
        ];

        $data = [
            'code' => '10000',
            'msg' => 'success',
        ];

        $body = json_encode(['alipay_trade_refund_response' => $data], \JSON_THROW_ON_ERROR);

        $mockResponse = new MockResponse($body);
        $client = new MockHttpClient($mockResponse);

        $result = (new Refund($client))->send($options);
        static::assertSame($data, $result);
    }

    public function testSendWithParseResponseException(): void
    {
        $this->expectException(ParseResponseException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('error (40000)');

        $options = [
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'trade_no' => 'test_trade_no',
            'refund_amount' => '0.01',
        ];

        $data = [
            'code' => '40000',
            'msg' => 'error',
        ];

        $body = json_encode(['alipay_trade_refund_response' => $data], \JSON_THROW_ON_ERROR);

        $mockResponse = new MockResponse($body);
        $client = new MockHttpClient($mockResponse);

        (new Refund($client))->send($options);
    }

    public function testSendWithSubCodeParseResponseException(): void
    {
        $this->expectException(ParseResponseException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('test_sub_msg (test_sub_code)');

        $options = [
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'trade_no' => 'test_trade_no',
            'refund_amount' => '0.01',
        ];

        $data = [
            'code' => '40000',
            'msg' => 'error',
            'sub_code' => 'test_sub_code',
            'sub_msg' => 'test_sub_msg',
        ];

        $body = json_encode(['alipay_trade_refund_response' => $data], \JSON_THROW_ON_ERROR);

        $mockResponse = new MockResponse($body);
        $client = new MockHttpClient($mockResponse);

        (new Refund($client))->send($options);
    }

    public function testAppidMissingOptionsException(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "appid" is missing');

        $this->request->build([
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'trade_no' => 'test_trade_no',
            'refund_amount' => '0.01',
        ]);
    }

    public function testPublicKeyMissingOptionsException(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "alipay_public_key" is missing');

        $this->request->build([
            'appid' => 'test_appid',
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'trade_no' => 'test_trade_no',
            'refund_amount' => '0.01',
        ]);
    }

    public function testPrivateKeyMissingOptionsException(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "app_private_key" is missing');

        $this->request->build([
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'trade_no' => 'test_trade_no',
            'refund_amount' => '0.01',
        ]);
    }

    public function testTradeNoMissingOptionsException(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "trade_no" or "out_trade_no" is missing');

        $this->request->build([
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'refund_amount' => '0.01',
        ]);
    }

    public function testRefundAmountMissingOptionsException(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "refund_amount" is missing');

        $this->request->build([
            'appid' => 'test_appid',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'trade_no' => 'test_trade_no',
        ]);
    }
}
