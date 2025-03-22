<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\Alipay\SignatureUtils;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SignatureUtilsTest extends TestCase
{
    protected SignatureUtils $signatureUtils;

    protected function setUp(): void
    {
        $this->signatureUtils = new SignatureUtils();
    }

    public function testResolve(): void
    {
        $data = ['foo' => 'hello'];
        static::assertEquals([
            'sign_type' => 'RSA2',
            'alipay_public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'app_private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
        ], $this->signatureUtils->resolve([
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
        ]));

        static::assertEquals([
            'sign_type' => 'RSA',
            'alipay_public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'app_private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
        ], $this->signatureUtils->resolve([
            'sign_type' => 'RSA',
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
        ]));
    }

    /**
     * @dataProvider getSignatureProvider
     */
    public function testGenerateAndVerify(array $data, string $signType): void
    {
        $options = [
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'sign_type' => $signType,
        ];

        $signature = $this->signatureUtils->generate($data, $options);
        static::assertTrue($this->signatureUtils->verify($signature, $data, $options));
    }

    public function testPublicKeyMissingOptionsException(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "alipay_public_key" is missing');

        $data = ['foo' => 'hello'];
        $this->signatureUtils->generate($data, [
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
        ]);
    }

    public function testPrivateKeyMissingOptionsException(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "app_private_key" is missing');

        $data = ['foo' => 'hello'];
        $this->signatureUtils->generate($data, [
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
        ]);
    }

    public function testSignTypeInvalidOptionsException(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "sign_type" with value "foo" is invalid. Accepted values are: "RSA", "RSA2"');

        $data = ['foo' => 'hello'];
        $this->signatureUtils->generate($data, [
            'alipay_public_key' => ConfigurationTest::PUBLIC_KEY,
            'app_private_key' => ConfigurationTest::PRIVATE_KEY,
            'sign_type' => 'foo',
        ]);
    }

    public function getSignatureProvider(): array
    {
        return [
            [
                ['foo' => 'hello'],
                'RSA',
            ],
            [
                ['bar' => 'world'],
                'RSA2',
            ],
        ];
    }
}
