<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\Alipay\NotifyHandler;
use Siganushka\ApiFactory\Alipay\SignatureUtils;
use Symfony\Component\HttpFoundation\Request;

class NotifyHandlerTest extends TestCase
{
    protected NotifyHandler $notifyHandler;
    protected SignatureUtils $signatureUtils;

    protected function setUp(): void
    {
        $this->notifyHandler = new NotifyHandler();
        $this->signatureUtils = new SignatureUtils();
    }

    public function testHandle(): void
    {
        $notifyData = [
            'foo' => 'bar',
        ];

        $options = [
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
        ];

        // Generate signature
        $notifyData['sign'] = $this->signatureUtils->generate($notifyData, $options);

        $request = Request::create('/', 'POST', $notifyData);

        $data = $this->notifyHandler->handle($request, $options);
        static::assertSame($notifyData, $data);
    }

    public function testSuccessResponse(): void
    {
        /** @var string */
        $content = $this->notifyHandler->success()->getContent();
        static::assertSame('success', $content);
    }

    public function testFailResponse(): void
    {
        /** @var string */
        $content = $this->notifyHandler->fail()->getContent();
        static::assertSame('fail', $content);
    }

    public function testHandleWithInvalidSignatureException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid signature');

        $notifyData = [
            'foo' => 'bar',
            'sign' => 'invalid_sign',
        ];

        $content = json_encode($notifyData, \JSON_THROW_ON_ERROR);
        $request = Request::create('/', 'POST', [], [], [], [], $content);

        $data = $this->notifyHandler->handle($request, [
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
        ]);

        static::assertSame($notifyData, $data);
    }
}
